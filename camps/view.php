<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
date_default_timezone_set('Asia/Tbilisi');

$pdo = db();

if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
function has_col(PDO $pdo, string $table, string $col): bool {
  $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $table);
  $col   = preg_replace('/[^a-zA-Z0-9_]+/', '', $col);
  if ($table === '' || $col === '') return false;
  try{
    $q = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$q->fetch(PDO::FETCH_ASSOC);
  }catch(Throwable $e){
    return false;
  }
}

/* ------------------ HELPERS ------------------ */
function fmtDate(?string $d): string {
  $d = trim((string)$d);
  if ($d === '') return '';
  $ts = strtotime($d);
  if ($ts === false) return $d;
  return date('Y-m-d', $ts);
}

function fmtDateTime(?string $d): string {
  $d = trim((string)$d);
  if ($d === '') return '';
  $ts = strtotime($d);
  if ($ts === false) return $d;
  return date('Y-m-d H:i', $ts);
}

function dateToTs(?string $raw, bool $endOfDay = false): ?int {
  $raw = trim((string)$raw);
  if ($raw === '') return null;

  $ts = strtotime($raw);
  if ($ts === false) return null;

  // If only date, optionally move to end of day
  if ($endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
    $ts += 86399;
  }
  return $ts;
}

function sanitize_pid(string $s): string {
  $s = preg_replace('/\s+/', '', $s) ?? $s;
  return preg_replace('/\D+/', '', $s) ?? $s;
}

function sanitize_phone(string $s): string {
  $s = trim($s);
  // keep + and digits
  $s = preg_replace('/(?!^\+)[^\d]+/', '', $s) ?? $s;
  return $s;
}

function field_autofill_key(array $f): string {
  $fk = trim((string)($f['field_key'] ?? ''));
  if ($fk !== '') return $fk;

  $opts = (string)($f['options_json'] ?? '');
  if ($opts !== '') {
    $j = json_decode($opts, true);
    if (is_array($j) && !empty($j['autofill'])) return trim((string)$j['autofill']);
  }
  return '';
}

/* safer uploads */
function upload_public_file(string $fieldName, string $subdir, array $allowedExt = ['jpg','jpeg','png','webp','gif','pdf','doc','docx'], int $maxBytes = 8_000_000): string {
  if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return '';

  $size = (int)($_FILES[$fieldName]['size'] ?? 0);
  if ($size <= 0 || $size > $maxBytes) return '';

  $tmp  = (string)($_FILES[$fieldName]['tmp_name'] ?? '');
  $orig = basename((string)($_FILES[$fieldName]['name'] ?? ''));
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

  if ($tmp === '' || !is_uploaded_file($tmp)) return '';
  if ($ext === '' || !in_array($ext, $allowedExt, true)) return '';

  // Basic MIME check for images
  $mime = '';
  if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) { $mime = (string)finfo_file($fi, $tmp); finfo_close($fi); }
  }
  if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
    if ($mime !== '' && strpos($mime, 'image/') !== 0) return '';
  }

  $dir = __DIR__ . '/../uploads/' . $subdir;
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) return '';
  }

  $base = "up_" . time() . "_" . bin2hex(random_bytes(8));
  $fname = $base . "." . $ext;
  $dest  = $dir . "/" . $fname;

  $isImage = in_array($ext, ['jpg','jpeg','png','webp','gif'], true);
  if ($isImage) {
    $fname = $base . ".webp";
    $dest = $dir . "/" . $fname;
    if (!convert_image_to_webp($tmp, $dest, 90)) {
      $fname = $base . "." . $ext;
      $dest = $dir . "/" . $fname;
      if (!move_uploaded_file($tmp, $dest)) return '';
    }
  } else {
    if (!move_uploaded_file($tmp, $dest)) return '';
  }

  return "/uploads/$subdir/" . $fname;
}

function normalize_public_path(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) return $path;

  $path = str_replace('\\', '/', $path);
  $path = preg_replace('~/+~', '/', $path) ?? $path;
  if (!str_starts_with($path, '/')) $path = '/' . ltrim($path, '/');

  if (str_starts_with($path, '/youthagency/')) {
    $path = '/' . ltrim(substr($path, strlen('/youthagency/')), '/');
  }
  return $path;
}

/* ------------------ PRG: POST -> REDIRECT -> GET ------------------ */
function redirect_same(array $params = []): void {
  // Keep id=... always
  $base = strtok($_SERVER['REQUEST_URI'], '?');
  $cur = $_GET ?? [];
  $cur = is_array($cur) ? $cur : [];
  $merged = array_merge($cur, $params);

  // remove old msg params if set
  unset($merged['ok'], $merged['err']);

  // add new ones
  foreach ($params as $k => $v) {
    $merged[$k] = $v;
  }

  $qs = $merged ? ('?' . http_build_query($merged)) : '';
  header("Location: {$base}{$qs}");
  exit;
}

/* ------------------ LOAD CAMP ------------------ */
$campId = (int)($_GET['id'] ?? 0);
if ($campId <= 0) { http_response_code(404); echo "Not found"; exit; }

$stmt = $pdo->prepare("SELECT * FROM camps WHERE id=? LIMIT 1");
$stmt->execute([$campId]);
$camp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$camp) { http_response_code(404); echo "Not found"; exit; }

/* ------------------ LOAD FIELDS ------------------ */
$fieldsSt = $pdo->prepare("
  SELECT id,label," . (has_col($pdo, 'camps_fields', 'label_en') ? "label_en" : "'' AS label_en") . ",type,required,options_json,field_key
  FROM camps_fields
  WHERE camp_id=?
  ORDER BY sort_order ASC, id ASC
");
$fieldsSt->execute([$campId]);
$fields = $fieldsSt->fetchAll(PDO::FETCH_ASSOC);

/* ------------------ LOAD POSTS ------------------ */
$postsSt = $pdo->prepare("
  SELECT id,title," . (has_col($pdo, 'camps_posts', 'title_en') ? "title_en" : "'' AS title_en") . ",
         cover,body," . (has_col($pdo, 'camps_posts', 'body_en') ? "body_en" : "'' AS body_en") . ",created_at
  FROM camps_posts
  WHERE camp_id=?
  ORDER BY id DESC
");
$postsSt->execute([$campId]);
$posts = $postsSt->fetchAll(PDO::FETCH_ASSOC);

/* Gallery media */
$postIds = array_map(fn($p)=>(int)$p['id'], $posts);
$mediaByPost = [];
if ($postIds) {
  $inQ = implode(',', array_fill(0, count($postIds), '?'));
  $m = $pdo->prepare("
    SELECT id,post_id,path,sort_order
    FROM camps_post_media
    WHERE post_id IN ($inQ)
    ORDER BY sort_order ASC, id ASC
  ");
  $m->execute($postIds);
  while ($row = $m->fetch(PDO::FETCH_ASSOC)) {
    $pid = (int)$row['post_id'];
    $mediaByPost[$pid][] = $row;
  }
}

/* ------------------ REGISTRATION WINDOW (IMPROVED) ------------------ */
$closedManual = ((int)($camp['closed'] ?? 0) === 1);

$startTs = dateToTs($camp['start_date'] ?? null, false);
$endTs   = dateToTs($camp['end_date'] ?? null, true);
$now     = time();

// If no start/end provided -> treat as open window unless admin closed
$timeNotStarted = ($startTs !== null && $now < $startTs);
$timeEnded      = ($endTs !== null && $now > $endTs);

$regOpen = (!$closedManual && !$timeNotStarted && !$timeEnded);

// Show reason in UI
$regReason = '';
if ($closedManual) $regReason = 'ადმინისტრატორმა დახურა';
elseif ($timeNotStarted) $regReason = 'მალე დაიწყება';
elseif ($timeEnded) $regReason = 'ვადა გავიდა';
$regReasonKey = '';
if ($closedManual) $regReasonKey = 'campsView.reasonManual';
elseif ($timeNotStarted) $regReasonKey = 'campsView.reasonSoon';
elseif ($timeEnded) $regReasonKey = 'campsView.reasonEnded';

/* ------------------ SUBMIT (POST) ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$regOpen) {
    redirect_same(['err' => 'closed']);
  }

  $vals = [];
  $uniqueKey = '';

  // Find PID / Email / Phone for unique key (priority)
  $pidValue = '';
  $emailValue = '';
  $phoneValue = '';

  foreach ($fields as $f) {
    $type = (string)($f['type'] ?? '');
    $fid  = (int)($f['id'] ?? 0);
    $fk   = field_autofill_key($f);
    $name = "f_" . $fid;

    if ($type === 'file') continue;

    $v = trim((string)($_POST[$name] ?? ''));

    if ($type === 'pid' || $fk === 'pid') {
      $pidValue = sanitize_pid($v);
    } elseif ($type === 'email' || $fk === 'email') {
      $emailValue = trim($v);
    } elseif ($type === 'phone' || $fk === 'phone') {
      $phoneValue = sanitize_phone($v);
    }
  }

  // blocklist check by PID
  if ($pidValue !== '') {
    $bst = $pdo->prepare("
      SELECT id
      FROM camps_pid_blocklist
      WHERE pid=? AND (camp_id IS NULL OR camp_id=?)
      LIMIT 1
    ");
    $bst->execute([$pidValue, $campId]);
    if ($bst->fetch(PDO::FETCH_ASSOC)) {
      // Keep response indistinguishable from successful submit, but do not save.
      redirect_same(['ok' => '1']);
    }
  }

  // collect + validate required fields, and build vals json
  foreach ($fields as $f) {
    $type     = (string)($f['type'] ?? '');
    $required = ((int)($f['required'] ?? 0) === 1);
    $fieldId  = (int)($f['id'] ?? 0);
    $name     = "f_" . $fieldId;

    if ($type === 'file') {
      $path = upload_public_file($name, 'camps/regs');
      if ($required && $path === '') redirect_same(['err' => 'required']);
      $vals[(string)$fieldId] = $path;
      continue;
    }

    $v = trim((string)($_POST[$name] ?? ''));

    if ($required && $v === '') redirect_same(['err' => 'required']);

    // Normalize values by type
    $fk = field_autofill_key($f);
    if ($type === 'pid' || $fk === 'pid') $v = sanitize_pid($v);
    if ($type === 'phone' || $fk === 'phone') $v = sanitize_phone($v);

    $vals[(string)$fieldId] = $v;
  }

  // choose uniqueKey: PID > Email > Phone
  if ($pidValue !== '') $uniqueKey = $pidValue;
  elseif ($emailValue !== '') $uniqueKey = $emailValue;
  elseif ($phoneValue !== '') $uniqueKey = $phoneValue;

  if ($uniqueKey === '') {
    redirect_same(['err' => 'unikey']);
  }

  // already registered?
  $ck = $pdo->prepare("SELECT id FROM camps_registrations WHERE camp_id=? AND unique_key=? LIMIT 1");
  $ck->execute([$campId, $uniqueKey]);
  if ($ck->fetch(PDO::FETCH_ASSOC)) {
    redirect_same(['err' => 'already']);
  }

  // save registration
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $ins = $pdo->prepare("
    INSERT INTO camps_registrations(camp_id,unique_key,ip,status,created_at,values_json)
    VALUES(?,?,?,?,NOW(),?)
  ");
  $ins->execute([
    $campId,
    $uniqueKey,
    $ip,
    'pending',
    json_encode($vals, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
  ]);

  redirect_same(['ok' => '1']);
}

/* ------------------ MESSAGES (GET only) ------------------ */
$msg = "";
$ok = false;

if (isset($_GET['ok']) && $_GET['ok'] === '1') {
  $ok = true;
  $msg = "გაიგზავნა ✅";
} elseif (isset($_GET['err'])) {
  $e = (string)$_GET['err'];
  if ($e === 'closed')   $msg = "რეგისტრაცია დახურულია.";
  if ($e === 'required') $msg = "გთხოვ შეავსო სავალდებულო ველები (*)";
  if ($e === 'already')  $msg = "ამ მონაცემებით უკვე დარეგისტრირებული ხარ.";
  if ($e === 'unikey')   $msg = "ფორმაში აუცილებელია PID ან Email ან Phone ველი.";
}

$campName  = (string)($camp['name'] ?? '');
$campNameEn  = (string)($camp['name_en'] ?? '');
$campText  = (string)($camp['card_text'] ?? '');
$campTextEn  = (string)($camp['card_text_en'] ?? '');
$campCover = normalize_public_path((string)($camp['cover'] ?? ''));
$campStart = fmtDate((string)($camp['start_date'] ?? ''));
$campEnd   = fmtDate((string)($camp['end_date'] ?? ''));
?>
<!doctype html>
<html lang="ka">
<head>
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h($campName)?></title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets.css?v=2">

  <style>
    :root{
      --bg:#0b1220;
      --panel: rgba(17,28,51,.62);
      --panel2: rgba(11,18,32,.40);
      --line:#1e2a45;
      --txt:#e5e7eb;
      --muted:#94a3b8;
      --accent:#60a5fa;
      --good:#22c55e;
      --bad:#ef4444;
      --warn:#f59e0b;
      --shadow: 0 14px 40px rgba(0,0,0,.32);
      --shadow2: 0 10px 28px rgba(0,0,0,.25);
    }

    body{
      margin:0;
      background:
        radial-gradient(900px 380px at 10% -10%, rgba(96,165,250,.18), transparent 58%),
        radial-gradient(900px 380px at 90% 0%, rgba(34,197,94,.10), transparent 60%),
        var(--bg);
      color:var(--txt);
      font-family:'Noto Sans Georgian',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    }

    .wrap{max-width:1100px;margin:0 auto;padding:18px}

    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin:6px 0 12px 0;
    }

    .back{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid var(--line);
      background: rgba(11,18,32,.38);
      color:#fff;
      text-decoration:none;
      font-weight:950;
      user-select:none;
      transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .back:hover{transform:translateY(-1px);border-color:rgba(96,165,250,.95);box-shadow:var(--shadow2)}

    .status{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid var(--line);
      background: rgba(11,18,32,.34);
      font-weight:950;
      white-space:nowrap;
    }
    .status.open{border-color: rgba(34,197,94,.70); background: rgba(34,197,94,.10); color:#d1fae5;}
    .status.closed{border-color: rgba(239,68,68,.70); background: rgba(239,68,68,.10); color:#ffe4e6;}
    .status.upcoming{border-color: rgba(245,158,11,.70); background: rgba(245,158,11,.12); color:#fff7ed;}

    .card{
      background: linear-gradient(180deg, rgba(17,28,51,.70), rgba(17,28,51,.52));
      border:1px solid var(--line);
      border-radius:18px;
      padding:14px;
      margin-top:12px;
      box-shadow: var(--shadow);
    }

    .muted{color:var(--muted);font-weight:750}
    .title{
      margin:0;
      color:#fff;
      font-weight:950;
      letter-spacing:.2px;
      font-size:1.35rem;
      line-height:1.15;
    }

    .camphead{
      display:grid;
      grid-template-columns: 240px 1fr;
      gap:14px;
      align-items:start;
    }
    @media(max-width:820px){ .camphead{grid-template-columns:1fr} }

    .cover{
      width:100%;
      height:160px;
      object-fit:cover;
      border-radius:14px;
      border:1px solid var(--line);
      display:block;
      background: rgba(0,0,0,.18);
    }

    .meta{
      margin-top:10px;
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      align-items:center;
      color:rgba(156,163,175,.98);
      font-weight:800;
    }

    .sectionTitle{
      margin:0;
      font-size:1.08rem;
      font-weight:950;
      color:#fff;
      display:flex;
      align-items:center;
      gap:10px;
    }

    .post{
      margin-top:12px;
      padding:12px;
      border-radius:16px;
      border:1px solid var(--line);
      background: rgba(11,18,32,.30);
    }

    .postTop{
      display:flex;
      gap:12px;
      align-items:center;
      flex-wrap:wrap;
    }

    .postCover{
      width:128px;
      height:82px;
      object-fit:cover;
      border-radius:12px;
      border:1px solid var(--line);
      background: rgba(0,0,0,.18);
    }

    .postTitle{font-weight:950;color:#fff;margin:0;line-height:1.2}
    .postDate{margin-top:4px;font-size:.92rem}

    .postBody{
      margin-top:10px;
      white-space:pre-wrap;
      color:rgba(229,231,235,.90);
      font-weight:650;
      line-height:1.45;
    }

    .gallery{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:10px;
    }

    .gallery img{
      width:160px;
      height:110px;
      object-fit:cover;
      border-radius:12px;
      border:1px solid var(--line);
      transition: transform .15s ease, border-color .15s ease;
    }
    .gallery img:hover{transform:translateY(-2px);border-color:rgba(96,165,250,.95)}

    .note{
      padding:10px 12px;
      border-radius:14px;
      border:1px solid var(--line);
      margin-top:10px;
      font-weight:850;
    }
    .ok{border-color:rgba(22,163,74,.55);background:rgba(22,163,74,.12)}
    .bad{border-color:rgba(239,68,68,.55);background:rgba(239,68,68,.12)}

    .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
      margin-top:10px;
    }
    @media(max-width:820px){ .grid{grid-template-columns:1fr} }

    label{display:block;font-weight:900;color:rgba(229,231,235,.95);margin-bottom:6px}
    .req{color:rgba(239,68,68,.95); font-weight:950}

    input,select,textarea{
      width:100%;
      padding:11px 12px;
      border-radius:14px;
      border:1px solid var(--line);
      background: rgba(11,18,32,.38);
      color:#e5e7eb;
      font-weight:750;
      outline:none;
    }
    input:focus,select:focus,textarea:focus{
      border-color: rgba(96,165,250,.95);
      box-shadow: 0 0 0 4px rgba(96,165,250,.12);
    }

    input:-webkit-autofill,textarea:-webkit-autofill,select:-webkit-autofill{
      -webkit-text-fill-color:#000 !important;
      box-shadow: 0 0 0px 1000px #fff inset !important;
      border-color: var(--line) !important;
      transition: background-color 9999s ease-in-out 0s;
    }
    select option{background:#fff;color:#000}

    .btn{
      padding:11px 14px;
      border-radius:14px;
      border:1px solid var(--line);
      background: rgba(96,165,250,.16);
      color:#fff;
      font-weight:950;
      cursor:pointer;
      transition: transform .15s ease, border-color .15s ease;
      display:inline-flex;
      align-items:center;
      gap:10px;
    }
    .btn:hover{transform:translateY(-1px);border-color:rgba(96,165,250,.95)}
    .btn[disabled]{opacity:.55;cursor:not-allowed}

    .formFooter{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
      margin-top:12px;
    }
    .small{font-size:.92rem}

    .fieldError{
      border-color: rgba(239,68,68,.95) !important;
      box-shadow: 0 0 0 4px rgba(239,68,68,.12) !important;
    }
  </style>
</head>

<body>

  <?php require_once __DIR__ . '/../header.php'; ?>

  <main class="wrap">

    <div class="topbar">
      <a class="back" href="/camps/">
        <i class="fa-solid fa-arrow-left"></i> <span data-i18n="campsView.back">ბანაკებზე დაბრუნება</span>
      </a>

      <?php
        $badgeClass = $regOpen ? 'open' : ($regReason === 'მალე დაიწყება' ? 'upcoming' : 'closed');
        $badgeText  = $regOpen ? 'რეგისტრაცია ღიაა' : ('რეგისტრაცია დახურულია' . ($regReason !== '' ? ' • ' . $regReason : ''));
        $badgeIcon  = $regOpen ? 'fa-circle-check' : ($badgeClass==='upcoming' ? 'fa-clock' : 'fa-circle-xmark');
      ?>
      <span class="status <?=$badgeClass?>">
        <i class="fa-solid <?=$badgeIcon?>"></i>
        <span data-i18n="<?= $regOpen ? 'campsView.registrationOpen' : 'campsView.registrationClosed' ?>"><?=h($regOpen ? 'რეგისტრაცია ღიაა' : 'რეგისტრაცია დახურულია')?></span>
        <?php if (!$regOpen && $regReasonKey): ?>
          <span> • </span>
          <span data-i18n="<?=h($regReasonKey)?>"><?=h($regReason)?></span>
        <?php endif; ?>
      </span>
    </div>

    <section class="card">
      <div class="camphead">
        <div>
          <?php if ($campCover !== ''): ?>
            <img class="cover" src="<?=h($campCover)?>" alt="">
          <?php else: ?>
            <div class="cover" style="display:grid;place-items:center;color:rgba(156,163,175,.9);font-weight:900" data-i18n="campsView.noCover">No cover</div>
          <?php endif; ?>
        </div>

        <div>
          <h1 class="title" data-i18n-text data-text-ka="<?=h($campName)?>" data-text-en="<?=h($campNameEn)?>"><?=h($campName)?></h1>
          <?php if ($campText !== '' || $campTextEn !== ''): ?>
            <div class="muted" style="margin-top:6px" data-i18n-text data-text-ka="<?=h($campText)?>" data-text-en="<?=h($campTextEn)?>"><?=h($campText !== '' ? $campText : $campTextEn)?></div>
          <?php endif; ?>

          <div class="meta">
            <span><i class="fa-regular fa-calendar"></i> <?=h($campStart)?> → <?=h($campEnd)?></span>
            <span>•</span>
            <span><i class="fa-solid fa-hashtag"></i> <span data-i18n="campsView.idLabel">ID:</span> <?=h((string)$campId)?></span>
            <?php if ($closedManual): ?>
              <span>•</span>
              <span class="muted"><i class="fa-solid fa-lock"></i> <span data-i18n="campsView.manualClosed">დახურულია (manual)</span></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="card">
      <h2 class="sectionTitle"><i class="fa-regular fa-newspaper"></i> <span data-i18n="campsView.posts">პოსტები</span></h2>

      <?php if (!$posts): ?>
        <div class="muted" style="margin-top:10px" data-i18n="campsView.postsEmpty">პოსტები ჯერ არ დამატებულა.</div>
      <?php else: ?>
        <?php foreach ($posts as $p): $pid=(int)$p['id']; ?>
          <?php
            $postTitle = (string)($p['title'] ?? '');
            $postTitleEn = (string)($p['title_en'] ?? '');
            $postBody = (string)($p['body'] ?? '');
            $postBodyEn = (string)($p['body_en'] ?? '');
          ?>
          <article class="post">
            <div class="postTop">
              <?php if (!empty($p['cover'])): ?>
                <img class="postCover" src="<?=h(normalize_public_path((string)$p['cover']))?>" alt="">
              <?php endif; ?>

              <div>
                <div class="postTitle" data-i18n-text data-text-ka="<?=h($postTitle)?>" data-text-en="<?=h($postTitleEn)?>"><?=h($postTitle)?></div>
                <div class="muted postDate"><?=h(fmtDateTime((string)$p['created_at']))?></div>
              </div>
            </div>

            <?php if ($postBody !== '' || $postBodyEn !== ''): ?>
              <div class="postBody" data-i18n-text data-text-ka="<?=h($postBody)?>" data-text-en="<?=h($postBodyEn)?>"><?=h($postBody !== '' ? $postBody : $postBodyEn)?></div>
            <?php endif; ?>

            <?php $g = $mediaByPost[$pid] ?? []; if ($g): ?>
              <div class="gallery">
                <?php foreach ($g as $img): ?>
                  <a href="<?=h((string)$img['path'])?>" target="_blank" rel="noopener">
                    <img src="<?=h(normalize_public_path((string)$img['path']))?>" alt="">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2 class="sectionTitle"><i class="fa-regular fa-clipboard"></i> <span data-i18n="campsView.registration">რეგისტრაცია</span></h2>

      <?php if ($msg !== ''): ?>
        <div class="note <?= $ok ? 'ok' : 'bad' ?>" id="serverMsg"><?=h($msg)?></div>
      <?php endif; ?>

      <div class="note bad" id="formMsg" style="display:none"></div>

      <?php if (!$regOpen): ?>
        <div class="muted" style="margin-top:10px" data-i18n="campsView.registrationClosed">რეგისტრაცია დახურულია.</div>
      <?php else: ?>

        <form method="post" enctype="multipart/form-data" id="regForm" novalidate>
          <div class="grid">
            <?php foreach ($fields as $f):
              $id    = (int)$f['id'];
              $label = (string)$f['label'];
              $labelEn = (string)($f['label_en'] ?? '');
              $type  = (string)$f['type'];
              $req   = (int)$f['required'] === 1;
              $autofill = field_autofill_key($f);
              $inputId = "f_".$id;
              $requiredAttr = $req ? 'required' : '';
            ?>
              <div>
                <label for="<?=h($inputId)?>">
                  <span data-i18n-text data-text-ka="<?=h($label)?>" data-text-en="<?=h($labelEn)?>"><?=h($label)?></span> <?= $req ? '<span class="req">*</span>' : '' ?>
                </label>

                <?php if ($type === 'select'): ?>
                  <?php
                    $opts = [];
                    $oj = (string)($f['options_json'] ?? '');
                    if ($oj !== '') {
                      $j = json_decode($oj, true);
                      if (is_array($j) && !empty($j['choices']) && is_array($j['choices'])) $opts = $j['choices'];
                    }
                    if (!$opts) $opts = array_filter(array_map('trim', explode(',', (string)($f['options_json'] ?? ''))));
                  ?>
                  <select name="<?=h($inputId)?>" id="<?=h($inputId)?>" data-autofill="<?=h($autofill)?>" <?=$requiredAttr?>>
                    <option value="" data-i18n="campsView.selectPlaceholder">-- აირჩიე --</option>
                    <?php foreach ($opts as $o): ?>
                      <option value="<?=h((string)$o)?>"><?=h((string)$o)?></option>
                    <?php endforeach; ?>
                  </select>

                <?php elseif ($type === 'date'): ?>
                  <input type="date" name="<?=h($inputId)?>" id="<?=h($inputId)?>" data-autofill="<?=h($autofill)?>" <?=$requiredAttr?>>

                <?php elseif ($type === 'email'): ?>
                  <input type="email" name="<?=h($inputId)?>" id="<?=h($inputId)?>" data-autofill="<?=h($autofill)?>" <?=$requiredAttr?>>

                <?php elseif ($type === 'phone'): ?>
                  <input type="text" name="<?=h($inputId)?>" id="<?=h($inputId)?>" placeholder="+995..." data-autofill="<?=h($autofill)?>" <?=$requiredAttr?>>

                <?php elseif ($type === 'pid'): ?>
                  <input type="text" name="<?=h($inputId)?>" id="<?=h($inputId)?>" placeholder="პირადი ნომერი" inputmode="numeric" data-autofill="pid" data-i18n-placeholder="campsView.pidPlaceholder" <?=$requiredAttr?>>

                <?php elseif ($type === 'file'): ?>
                  <input type="file" name="<?=h($inputId)?>" id="<?=h($inputId)?>" <?=$requiredAttr?>>

                <?php else: ?>
                  <input type="text" name="<?=h($inputId)?>" id="<?=h($inputId)?>" data-autofill="<?=h($autofill)?>" <?=$requiredAttr?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="formFooter">
            <div class="muted small">
              <i class="fa-solid fa-circle-info"></i>
              <span data-i18n="campsView.requiredNote">ვარსკვლავით (*) მონიშნული ველები სავალდებულოა.</span>
            </div>

            <button class="btn" type="submit" id="submitBtn" <?= $ok ? 'disabled' : '' ?>>
              <span data-i18n="<?= $ok ? 'campsView.submitted' : 'campsView.submit' ?>"><?= $ok ? 'გაიგზავნა ✅' : 'გაგზავნა' ?></span>
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </div>
        </form>

        <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
          <span data-i18n="campsView.errorRequired">გთხოვ შეავსო სავალდებულო ველები (*)</span>
          <span data-i18n="campsView.submitting">იგზავნება...</span>
        </div>

        <script>
          const form = document.getElementById('regForm');
          const msgBox = document.getElementById('formMsg');
          const submitBtn = document.getElementById('submitBtn');
          const errorRequired = document.querySelector('[data-i18n="campsView.errorRequired"]');
          const submittingLabel = document.querySelector('[data-i18n="campsView.submitting"]');

          function showError(text){
            msgBox.textContent = text;
            msgBox.style.display = '';
          }
          function hideError(){
            msgBox.textContent = '';
            msgBox.style.display = 'none';
          }

          function validateRequired(){
            hideError();
            let ok = true;

            form.querySelectorAll('.fieldError').forEach(el => el.classList.remove('fieldError'));

            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(el => {
              const type = (el.getAttribute('type') || '').toLowerCase();
              let empty = false;

              if (type === 'file') empty = !el.files || el.files.length === 0;
              else empty = (String(el.value || '').trim() === '');

              if (empty) {
                ok = false;
                el.classList.add('fieldError');
              }
            });

            if (!ok) {
              showError(errorRequired ? errorRequired.textContent : 'გთხოვ შეავსო სავალდებულო ველები (*)');
              const first = form.querySelector('.fieldError');
              if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
            }

            return ok;
          }

          form.addEventListener('submit', (e) => {
            if (!validateRequired()) {
              e.preventDefault();
              return;
            }
            // prevent double submit
            submitBtn.disabled = true;
            const label = submittingLabel ? submittingLabel.textContent : 'იგზავნება...';
            submitBtn.innerHTML = `${label} <i class="fa-solid fa-spinner" style="margin-left:8px"></i>`;
          });

          form.querySelectorAll('[required]').forEach(el => {
            el.addEventListener('input', ()=> el.classList.remove('fieldError'));
            el.addEventListener('change', ()=> el.classList.remove('fieldError'));
          });

          // remove ok/err from URL after showing message
          (function(){
            const url = new URL(window.location.href);
            if (url.searchParams.has('ok') || url.searchParams.has('err')) {
              url.searchParams.delete('ok');
              url.searchParams.delete('err');
              window.history.replaceState({}, '', url.toString());
            }
          })();

          // PID autofill
          const pidInput = document.querySelector('[data-autofill="pid"]');
          const mapKeys = ["first_name","last_name","birth_date","age","email","phone","address","university","faculty","course"];

          async function lookup(pid){
            try{
              const res = await fetch("/api/member_lookup.php?pid=" + encodeURIComponent(pid));
              const j = await res.json().catch(()=>null);
              if(!j || !j.ok || !j.found) return null;
              return j.member || null;
            }catch(e){
              return null;
            }
          }

          function fillFields(member){
            mapKeys.forEach(k=>{
              const el = document.querySelector('[data-autofill="'+k+'"]');
              if(!el) return;
              const v = (member && member[k] != null) ? String(member[k]) : "";
              if(v !== "") el.value = v;
            });
          }

          let t=null;
          if(pidInput){
            pidInput.addEventListener("input", ()=>{
              clearTimeout(t);
              const pid = (pidInput.value || '').replace(/\D+/g,'').trim();
              if(pid.length < 8) return;
              t=setTimeout(async ()=>{
                const m = await lookup(pid);
                if(m) fillFields(m);
              }, 260);
            });
          }
        </script>

      <?php endif; ?>
    </section>

  </main>

  <?php require_once __DIR__ . '/../footer.php'; ?>
  <script src="/app.js?v=2" defer></script>
  <script>window.addEventListener("DOMContentLoaded",()=>{if(typeof window.initHeader==="function") window.initHeader(); if(typeof window.initFooterAccordion==="function") window.initFooterAccordion();},{once:true});</script>

</body>
</html>
