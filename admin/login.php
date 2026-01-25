<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

/* =========================
   Security headers
========================= */
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

/* =========================
   CSRF token
========================= */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* =========================
   Brute-force (per IP) in file
========================= */
function client_ip(): string {
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function rl_path(): string { return __DIR__ . '/.ratelimit.json'; }
function rl_read(): array {
  $p = rl_path();
  if (!file_exists($p)) return [];
  $raw = @file_get_contents($p);
  $j = json_decode($raw ?: '[]', true);
  return is_array($j) ? $j : [];
}
function rl_write(array $data): void {
  $p = rl_path();
  $tmp = $p . '.tmp';
  @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
  @rename($tmp, $p);
}
function register_failed_attempt(array &$rl, string $ip, array &$entry, int $now, int $MAX_ATTEMPTS, int $LOCK_SEC): void {
  $entry['count'] = (int)($entry['count'] ?? 0) + 1;
  $entry['last']  = $now;

  if ($entry['count'] >= $MAX_ATTEMPTS) {
    $entry['lock_until'] = $now + $LOCK_SEC;
  }

  $rl[$ip] = $entry;
  rl_write($rl);
}

function new_login_captcha(): array {
  $a = random_int(2, 9);
  $b = random_int(1, 9);
  $_SESSION['login_captcha'] = [
    'a' => $a,
    'b' => $b,
    'answer' => $a + $b,
    'ts' => time(),
  ];
  return $_SESSION['login_captcha'];
}

$ip  = client_ip();
$now = time();

$MAX_ATTEMPTS = 7;
$WINDOW_SEC   = 10 * 60;
$LOCK_SEC     = 15 * 60;

$rl = rl_read();
$entry = $rl[$ip] ?? ['count'=>0,'first'=>$now,'last'=>$now,'lock_until'=>0];
$isLocked = ((int)($entry['lock_until'] ?? 0) > $now);

$error = '';
$genericError = 'მომხმარებელი ან პაროლი არასწორია.';
$captcha = $_SESSION['login_captcha'] ?? new_login_captcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if ($isLocked) {
    $wait = (int)$entry['lock_until'] - $now;
    $error = "ცდები ბევრჯერ. სცადე მოგვიანებით ({$wait} წამში).";
  } else {

    $csrf = (string)($_POST['csrf'] ?? '');
    if (!$csrf || !hash_equals($_SESSION['csrf'], $csrf)) {
      $error = 'უსაფრთხოების შემოწმება ვერ გაიარა. გვერდი განაახლე და თავიდან სცადე.';
    } else {

      // reset window
      if (($now - (int)$entry['first']) > $WINDOW_SEC) {
        $entry['count'] = 0;
        $entry['first'] = $now;
      }

      $u = trim((string)($_POST['user'] ?? ''));
      $p = (string)($_POST['pass'] ?? '');
      $cap = (string)($_POST['captcha'] ?? '');
      $captchaAnswer = (int)($captcha['answer'] ?? -1);
      $captchaOk = ($cap !== '' && (int)$cap === $captchaAnswer);

      // DB lookup (prepared statement)
      $ok = false;
      if (!$captchaOk) {
        $error = 'კოდს ვერ ვადასტურებთ. სცადე თავიდან.';
        register_failed_attempt($rl, $ip, $entry, $now, $MAX_ATTEMPTS, $LOCK_SEC);
      } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, is_active FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$u]);
        $user = $stmt->fetch();

        if ($user && (int)$user['is_active'] === 1) {
          $ok = password_verify($p, (string)$user['password_hash']);
        } else {
          // timing noise to avoid user enumeration timing
          password_verify($p, password_hash('dummy', PASSWORD_DEFAULT));
        }
      }

      if ($ok) {

        // success -> reset limiter
        unset($rl[$ip]);
        rl_write($rl);

        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = 1;
        $_SESSION['admin_id'] = (int)$user['id'];
        $_SESSION['admin_user'] = (string)$user['username'];
        $_SESSION['admin_role'] = (string)$user['role'];
        $_SESSION['admin_login_at'] = $now;
// ✅ LOG: successful login
if (function_exists('log_admin')) {
  log_admin('login', 'admin_users', (int)($_SESSION['admin_id'] ?? 0), [
    'username' => $_SESSION['admin_user'] ?? null
  ]);
}

        // rotate CSRF
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        // update last_login
        $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?")->execute([$_SESSION['admin_id']]);

        header('Location: index.php');
        exit;
      }

      if (!$ok && $error === '') {
        register_failed_attempt($rl, $ip, $entry, $now, $MAX_ATTEMPTS, $LOCK_SEC);
        $error = $genericError;
      }
    }
  }
}

$captcha = new_login_captcha();
$isLocked = ((int)($entry['lock_until'] ?? 0) > $now);
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>ადმინ პანელი — შესვლა</title>

  <style>
    :root{
      --bg:#0b1220; --panel:#0f172a; --card:#111c33; --line:#1e2a45;
      --txt:#e5e7eb; --muted:#94a3b8; --ac:#2563eb; --ok:#16a34a; --bad:#ef4444; --warn:#f59e0b;
      --radius:16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:18px;
      background:linear-gradient(180deg,#0b1220,#0a1020);
      color:var(--txt);
      font:700 14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial;
    }
    .card{
      width:min(520px,100%);
      background:rgba(17,28,51,.55);
      border:1px solid var(--line);
      border-radius:var(--radius);
      padding:14px;
      box-shadow:0 18px 40px rgba(0,0,0,.35);
      backdrop-filter: blur(6px);
    }
    .row{display:flex;gap:10px;align-items:center}
    .row.sp{justify-content:space-between}
    .muted{color:var(--muted);font-weight:900}
    .pill{
      display:inline-flex;align-items:center;gap:8px;
      padding:6px 10px;border-radius:999px;border:1px solid var(--line);
      font-weight:900;
      border-color:rgba(245,158,11,.35);
      background:rgba(245,158,11,.10);
    }
    .two{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
    label{display:block;margin:0 0 6px}
    input{
      width:100%;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid var(--line);
      background:rgba(17,28,51,.55);
      color:var(--txt);
      outline:none;
      font-weight:800;
    }
    input:focus{border-color:rgba(37,99,235,.55); box-shadow:0 0 0 3px rgba(37,99,235,.18)}
    .right{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
    .btn{
      padding:10px 12px;border-radius:12px;border:1px solid var(--line);
      background:rgba(17,28,51,.55);color:var(--txt);
      cursor:pointer;font-weight:1000;
    }
    .btn.ac{background:rgba(37,99,235,.18);border-color:rgba(37,99,235,.4)}
    .btn.ac:hover{background:rgba(37,99,235,.26)}
    .hint{font-size:12px;color:var(--muted);font-weight:900;margin-top:8px}
    .err{margin-top:8px;font-size:12px;font-weight:900;color:var(--bad)}
    @media (max-width: 520px){ .two{grid-template-columns:1fr} }
  </style>
</head>
<body>

  <form class="card" method="post" autocomplete="off">
    <div class="row sp">
      <div>
        <h2 style="margin:0">ადმინ პანელი</h2>
        <div class="muted">ავტორიზაცია აუცილებელია</div>
      </div>
      <span class="pill">ADMIN</span>
    </div>

    <?php if ($error): ?>
      <div class="err"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
      <div class="hint">დაცვა ჩართულია: ბევრი მცდელობის გამო დროებით დაბლოკილია.</div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

    <div class="two">
      <div>
        <label class="muted" for="user">მომხმარებელი</label>
        <input id="user" name="user" placeholder="Username" required>
      </div>
      <div>
        <label class="muted" for="pass">პაროლი</label>
        <input id="pass" name="pass" type="password" placeholder="Password" required>
      </div>
    </div>

    <div style="margin-top:12px">
      <label class="muted" for="captcha">უსაფრთხოების კოდი: <?= h((string)$captcha['a']) ?> + <?= h((string)$captcha['b']) ?> = ?</label>
      <input id="captcha" name="captcha" inputmode="numeric" pattern="[0-9]*" placeholder="შეიყვანე პასუხი" required>
    </div>

    <div class="right">
      <button class="btn ac" type="submit">შესვლა</button>
    </div>

    <div class="hint">მომხმარებლები ინახება ბაზაში (admin_users) — პაროლები hash-ით.</div>
  </form>

</body>
</html>
