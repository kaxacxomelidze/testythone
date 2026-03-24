<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
$pdo = db();

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function has_col(PDO $pdo, string $table, string $col): bool {
  $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $table);
  $col   = preg_replace('/[^a-zA-Z0-9_]+/', '', $col);
  if ($table === '' || $col === '') return false;

  try {
    $q = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$q->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    return false;
  }
}

function fmt_date(?string $raw): string {
  $raw = trim((string)$raw);
  if ($raw === '') return '—';
  $t = strtotime($raw);
  return $t ? date('Y-m-d', $t) : $raw;
}

function fmt_money($value, string $currency = '₾'): string {
  $raw = trim((string)$value);
  if ($raw === '') return '—';

  $normalized = str_replace([' ', ','], ['', ''], $raw);
  if (is_numeric($normalized)) {
    $num = (float)$normalized;
    $formatted = fmod($num, 1.0) === 0.0
      ? number_format($num, 0, '.', ' ')
      : number_format($num, 2, '.', ' ');
    return $formatted . ' ' . $currency;
  }

  return str_contains($raw, $currency) ? $raw : ($raw . ' ' . $currency);
}

function build_public_image_url(string $rawPath): string {
  $rawPath = trim($rawPath);
  if ($rawPath === '') return '';

  if (
    preg_match('~^(https?:)?//~i', $rawPath) ||
    stripos($rawPath, 'data:image/') === 0
  ) {
    return $rawPath;
  }

  $path = str_replace('\\', '/', $rawPath);
  $path = preg_replace('~/+~', '/', $path);

  if (str_starts_with($path, '/')) {
    return $path;
  }

  $segments = [];
  foreach (explode('/', $path) as $seg) {
    $seg = trim($seg);
    if ($seg === '' || $seg === '.') continue;
    if ($seg === '..') {
      array_pop($segments);
      continue;
    }
    $segments[] = $seg;
  }

  $clean = implode('/', $segments);
  if ($clean === '') return '';

  if (stripos($clean, 'youthagency/') === 0) {
    return '/' . $clean;
  }

  if (stripos($clean, 'admin/uploads/') === 0) {
    return '/' . $clean;
  }

  if (stripos($clean, 'uploads/') === 0) {
    return '/admin/' . $clean;
  }

  return '/' . ltrim($clean, '/');
}

function build_apply_url(int $grantId, string $dbUrl): string {
  $url = trim($dbUrl);

  if ($url === '') {
    $url = '/grants/grants_apply.php';
  }

  if ($url !== '' && $url[0] !== '/' && !preg_match('~^https?://~i', $url)) {
    $url = '/' . ltrim($url, '/');
  }

  if (!preg_match('/(?:^|[?&])id=\d+(?:&|$)/', $url)) {
    $sep = str_contains($url, '?') ? '&' : '?';
    $url .= $sep . 'id=' . $grantId;
  }

  return $url;
}

function is_deadline_passed(?string $deadline): bool {
  $deadlineRaw = trim((string)$deadline);
  if ($deadlineRaw === '') return false;

  $ts = strtotime($deadlineRaw);
  if (!$ts) return false;

  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadlineRaw)) {
    $ts += 86399;
  }

  return time() > $ts;
}

function parse_json_array($json): array {
  if (!is_string($json) || trim($json) === '') return [];
  try {
    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return is_array($decoded) ? $decoded : [];
  } catch (Throwable $e) {
    return [];
  }
}

function normalize_applicant_label(string $value): string {
  $v = trim(mb_strtolower($value));
  if ($v === '') return '';

  if (in_array($v, ['person', 'physical person', 'individual', 'ფიზიკური პირი'], true)) {
    return 'ფიზიკური პირი';
  }

  if (in_array($v, ['org', 'organization', 'organisation', 'company', 'ngo', 'იურიდიული პირი', 'ორგანიზაცია'], true)) {
    return 'ორგანიზაცია / იურიდიული პირი';
  }

  return $value;
}

function detect_applicant_types(array $fields): array {
  foreach ($fields as $f) {
    $label = mb_strtolower(trim((string)($f['label'] ?? '')));
    $type  = mb_strtolower(trim((string)($f['type'] ?? '')));

    $looksLikeApplicantType =
      str_contains($label, 'განმცხადებლის ტიპ') ||
      str_contains($label, 'applicant type') ||
      (str_contains($label, 'ტიპ') && in_array($type, ['select', 'radio', 'checkbox'], true));

    if (!$looksLikeApplicantType) {
      continue;
    }

    $options = parse_json_array($f['options_json'] ?? '');
    $result = [];

    foreach ($options as $opt) {
      if (is_array($opt)) {
        $opt = (string)($opt['label'] ?? $opt['value'] ?? '');
      } else {
        $opt = (string)$opt;
      }
      $opt = trim($opt);
      if ($opt !== '') {
        $result[] = normalize_applicant_label($opt);
      }
    }

    $result = array_values(array_unique(array_filter($result)));
    if ($result) return $result;
  }

  return [];
}

/* ---------- input ---------- */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  exit('Not found');
}

/* ---------- load grant ---------- */
$hasTitleEn    = has_col($pdo, 'grants', 'title_en');
$hasDescEn     = has_col($pdo, 'grants', 'description_en');
$hasBodyEn     = has_col($pdo, 'grants', 'body_en');
$hasMaxBudget  = has_col($pdo, 'grants', 'max_budget');
$hasCurrency   = has_col($pdo, 'grants', 'currency');

$sql = "SELECT
          id,
          title,
          " . ($hasTitleEn ? "title_en" : "'' AS title_en") . ",
          slug,
          description,
          " . ($hasDescEn ? "description_en" : "'' AS description_en") . ",
          body,
          " . ($hasBodyEn ? "body_en" : "'' AS body_en") . ",
          deadline,
          status,
          apply_url,
          is_active,
          image_path" .
          ($hasMaxBudget ? ", max_budget" : ", 0 AS max_budget") .
          ($hasCurrency ? ", currency" : ", '₾' AS currency") . "
        FROM grants
        WHERE id = ? AND is_active = 1
        LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([$id]);
$g = $st->fetch(PDO::FETCH_ASSOC);

if (!$g) {
  http_response_code(404);
  exit('Not found');
}

$isClosed = (($g['status'] ?? 'current') === 'closed') || is_deadline_passed((string)($g['deadline'] ?? ''));
$statusLabel = $isClosed ? 'დახურული' : 'მიმდინარე';
$applyUrl = build_apply_url($id, (string)($g['apply_url'] ?? ''));

/* ---------- image ---------- */
$img = build_public_image_url((string)($g['image_path'] ?? ''));
if ($img === '') {
  $img = "data:image/svg+xml;charset=UTF-8," . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="800">
      <rect width="100%" height="100%" fill="#1C2340"/>
      <rect x="60" y="60" width="1280" height="680" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="4"/>
      <text x="50%" y="50%" text-anchor="middle" fill="rgba(243,245,250,.55)" font-size="44" font-family="Arial">Grant Image</text>
    </svg>'
  );
}

/* ---------- load important info from apply-related tables ---------- */
$steps = [];
$fields = [];
$requiredFiles = [];
$optionalFiles = [];

try {
  $q = $pdo->prepare("
    SELECT id, grant_id, step_key, name, sort_order
    FROM grant_steps
    WHERE grant_id = ? AND is_enabled = 1
    ORDER BY sort_order ASC, id ASC
  ");
  $q->execute([$id]);
  $steps = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $steps = [];
}

try {
  $q = $pdo->prepare("
    SELECT id, grant_id, step_id, label, type, options_json, is_required, show_for, sort_order
    FROM grant_fields
    WHERE grant_id = ? AND is_enabled = 1
    ORDER BY step_id ASC, sort_order ASC, id ASC
  ");
  $q->execute([$id]);
  $fields = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $fields = [];
}

try {
  $q = $pdo->prepare("
    SELECT id, grant_id, name, is_required
    FROM grant_file_requirements
    WHERE grant_id = ? AND is_enabled = 1
    ORDER BY id ASC
  ");
  $q->execute([$id]);
  $reqs = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];

  foreach ($reqs as $r) {
    if ((int)($r['is_required'] ?? 0) === 1) {
      $requiredFiles[] = $r;
    } else {
      $optionalFiles[] = $r;
    }
  }
} catch (Throwable $e) {
  $requiredFiles = [];
  $optionalFiles = [];
}

$applicantTypes = detect_applicant_types($fields);

$title    = (string)($g['title'] ?? 'საგრანტო პროგრამა');
$titleEn  = (string)($g['title_en'] ?? '');
$desc     = trim((string)($g['description'] ?? ''));
$descEn   = trim((string)($g['description_en'] ?? ''));
$body     = (string)($g['body'] ?? '');
$bodyEn   = (string)($g['body_en'] ?? '');
$deadline = (string)($g['deadline'] ?? '');
$currency = trim((string)($g['currency'] ?? '₾')) ?: '₾';
$maxBudget = trim((string)($g['max_budget'] ?? ''));

$stepNames = [];
foreach ($steps as $s) {
  $name = trim((string)($s['name'] ?? ''));
  if ($name !== '') $stepNames[] = $name;
}
$stepNames = array_values(array_unique($stepNames));
?>
<!doctype html>
<html lang="ka">
<head>
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($title) ?></title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets.css?v=1">

  <style>
    :root{
      --bg:#151A2C;
      --panel:#1B2238;
      --card:#1C2340;
      --card2:#1A2036;
      --line: rgba(255,255,255,.10);
      --line2: rgba(255,255,255,.16);

      --text:#F3F5FA;
      --muted: rgba(243,245,250,.70);
      --muted2: rgba(243,245,250,.58);

      --btn:#243055;
      --btnHover:#2A3863;

      --radius:18px;
      --shadow: 0 14px 38px rgba(0,0,0,.28);
      --shadow2: 0 10px 26px rgba(0,0,0,.22);
      --focus: 0 0 0 4px rgba(255,255,255,.10);

      --okBg: rgba(46, 204, 113, .18);
      --okBd: rgba(46, 204, 113, .45);
      --okTx: #BFF3D1;

      --badBg: rgba(231, 76, 60, .18);
      --badBd: rgba(231, 76, 60, .45);
      --badTx: #FFD0CB;

      --blueBg: rgba(59, 130, 246, .18);
      --blueBd: rgba(59, 130, 246, .45);
      --blueTx: #CFE3FF;
    }

    html, body { background: var(--bg) !important; }

    body{
      margin:0;
      color: var(--text);
      font-family:"Noto Sans Georgian", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .wrap{max-width:1180px;margin:0 auto;padding:26px 18px 70px;}

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      margin:10px 0 16px;
    }

    .btn{
      padding:12px 16px;
      border-radius:14px;
      border:1px solid var(--line);
      color:var(--text);
      text-decoration:none;
      font-weight:900;
      background: var(--btn);
      display:inline-flex;
      gap:10px;
      align-items:center;
      transition: background .12s ease, border-color .12s ease, transform .12s ease;
      user-select:none;
    }
    .btn:hover{ background: var(--btnHover); border-color: var(--line2); }
    .btn:active{ transform: translateY(1px); }
    .btn:focus{ outline:none; box-shadow: var(--focus); }

    .btn.secondary{
      background: rgba(255,255,255,.06);
      border-color: var(--line);
    }
    .btn.secondary:hover{
      background: rgba(255,255,255,.10);
      border-color: var(--line2);
    }

    .hero{
      border:1px solid var(--line);
      border-radius: var(--radius);
      background: var(--panel);
      box-shadow: var(--shadow);
      overflow:hidden;
    }

    .heroHead{
      padding:18px 18px 0;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:14px;
      flex-wrap:wrap;
    }

    .heroTitle{
      margin:0;
      font-size:24px;
      font-weight:950;
      line-height:1.25;
      letter-spacing:-.2px;
    }

    .heroDesc{
      margin:10px 0 0;
      color: var(--muted);
      font-size:14px;
      line-height:1.7;
      max-width:860px;
      padding:0 18px 16px;
    }

    .layout{
      display:grid;
      grid-template-columns: 1.2fr .9fr;
      gap:14px;
      padding:18px;
    }

    @media(max-width:980px){
      .layout{ grid-template-columns: 1fr; }
    }

    .photo{
      border:1px solid var(--line);
      border-radius: var(--radius);
      overflow:hidden;
      background: var(--card2);
      min-height:260px;
    }

    .photo img{
      width:100%;
      height:100%;
      display:block;
      object-fit:cover;
    }

    .side{
      border:1px solid var(--line);
      border-radius: var(--radius);
      background: var(--card);
      padding:16px;
    }

    .metaRow{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:12px;
    }

    .pill{
      font-size:12px;
      font-weight:950;
      padding:6px 10px;
      border-radius:999px;
      display:inline-flex;
      gap:8px;
      align-items:center;
      white-space:nowrap;
      border:1px solid transparent;
    }
    .pill.current{ background: var(--okBg); border-color: var(--okBd); color: var(--okTx); }
    .pill.closed{ background: var(--badBg); border-color: var(--badBd); color: var(--badTx); }
    .pill.neutral{
      background: rgba(255,255,255,.06);
      border: 1px solid var(--line2);
      color: rgba(243,245,250,.86);
    }
    .pill.info{
      background: var(--blueBg);
      border-color: var(--blueBd);
      color: var(--blueTx);
    }

    .infoGrid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;
      margin-top:16px;
    }

    @media(max-width:620px){
      .infoGrid{ grid-template-columns:1fr; }
    }

    .infoCard{
      border:1px solid var(--line);
      border-radius:14px;
      background: rgba(255,255,255,.03);
      padding:13px;
    }

    .infoLabel{
      color: var(--muted2);
      font-size:12px;
      font-weight:900;
      margin-bottom:6px;
    }

    .infoValue{
      color: var(--text);
      font-size:15px;
      font-weight:950;
      line-height:1.45;
      word-break:break-word;
    }

    .moneyValue{
      font-size:24px;
      line-height:1.1;
    }

    .actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:16px;
    }

    .block{
      margin-top:14px;
      border:1px solid var(--line);
      border-radius: var(--radius);
      background: var(--card);
      padding:18px;
      box-shadow: var(--shadow2);
    }

    .block h2{
      margin:0 0 10px;
      font-size:16px;
      font-weight:950;
      letter-spacing:.1px;
    }

    .content{
      color: rgba(243,245,250,.74);
      font-size:14px;
      line-height:1.8;
      white-space:pre-wrap;
    }

    .plainList{
      margin:0;
      padding-left:18px;
      color: rgba(243,245,250,.84);
      line-height:1.9;
      font-size:14px;
      font-weight:700;
    }

    .plainList li + li{ margin-top:4px; }

    .muted{
      color: var(--muted);
      font-size:14px;
      line-height:1.7;
      font-weight:700;
    }

    .note{
      border:1px solid var(--blueBd);
      background: var(--blueBg);
      color: var(--blueTx);
      border-radius:14px;
      padding:12px 14px;
      font-weight:800;
      line-height:1.6;
      margin-top:12px;
    }

    a{ -webkit-tap-highlight-color: transparent; }
  </style>
</head>

<body>
  <div id="siteHeaderMount"></div>

  <main class="wrap">
    <div class="topbar">
      <a class="btn secondary" href="/grants/" onclick="if(history.length>1){ history.back(); return false; }">
        <i class="fa-solid fa-arrow-left"></i>
        <span>უკან</span>
      </a>
    </div>

    <section class="hero">
      <div class="heroHead">
        <h1 class="heroTitle"
            data-i18n-text
            data-text-ka="<?= h($title) ?>"
            data-text-en="<?= h($titleEn) ?>">
          <?= h($title) ?>
        </h1>

        <span class="pill <?= $isClosed ? 'closed' : 'current' ?>">
          <i class="fa-solid fa-circle" style="font-size:8px;opacity:.85"></i>
          <?= h($statusLabel) ?>
        </span>
      </div>

      <?php if ($desc !== '' || $descEn !== ''): ?>
        <div class="heroDesc"
             data-i18n-text
             data-text-ka="<?= h($desc) ?>"
             data-text-en="<?= h($descEn) ?>">
          <?= h($desc !== '' ? $desc : $descEn) ?>
        </div>
      <?php endif; ?>

      <div class="layout">
        <div class="photo">
          <img src="<?= h($img) ?>" alt="<?= h($title) ?>">
        </div>

        <aside class="side">
          <div class="metaRow">
            <span class="pill neutral">
              <i class="fa-regular fa-calendar"></i>
              ვადა: <?= h(fmt_date($deadline)) ?>
            </span>

            <span class="pill info">
              <i class="fa-solid fa-layer-group"></i>
              ნაბიჯები: <?= count($stepNames) ?>
            </span>

            <span class="pill info">
              <i class="fa-regular fa-folder-open"></i>
              დოკუმენტები: <?= count($requiredFiles) + count($optionalFiles) ?>
            </span>
          </div>

          <div class="infoGrid">
            <?php if ($maxBudget !== '' && (float)$maxBudget > 0): ?>
              <div class="infoCard">
                <div class="infoLabel">მაქსიმალური ბიუჯეტი</div>
                <div class="infoValue moneyValue"><?= h(fmt_money($maxBudget, $currency)) ?></div>
              </div>
            <?php endif; ?>

            <div class="infoCard">
              <div class="infoLabel">სტატუსი</div>
              <div class="infoValue"><?= h($statusLabel) ?></div>
            </div>

            <?php if ($applicantTypes): ?>
              <div class="infoCard">
                <div class="infoLabel">ვის შეუძლია განაცხადი</div>
                <div class="infoValue"><?= h(implode(', ', $applicantTypes)) ?></div>
              </div>
            <?php endif; ?>

            <?php if ($requiredFiles): ?>
              <div class="infoCard">
                <div class="infoLabel">სავალდებულო დოკუმენტები</div>
                <div class="infoValue"><?= count($requiredFiles) ?> დოკუმენტი</div>
              </div>
            <?php endif; ?>

            <?php if ($optionalFiles): ?>
              <div class="infoCard">
                <div class="infoLabel">დამატებითი დოკუმენტები</div>
                <div class="infoValue"><?= count($optionalFiles) ?> დოკუმენტი</div>
              </div>
            <?php endif; ?>

            <?php if ($stepNames): ?>
              <div class="infoCard">
                <div class="infoLabel">განაცხადის ფორმატი</div>
                <div class="infoValue">მრავალნაბიჯიანი ონლაინ განაცხადი</div>
              </div>
            <?php endif; ?>
          </div>

          <?php if (!$isClosed): ?>
            <div class="actions">
              <a class="btn" href="<?= h($applyUrl) ?>">
                <i class="fa-solid fa-file-pen"></i>
                განაცხადის შევსება
              </a>
            </div>
          <?php endif; ?>

          <?php if ($maxBudget !== '' && (float)$maxBudget > 0): ?>
            <div class="note">
              ბიუჯეტის ცხრილში შეტანილი თანხების ჯამი არ უნდა აღემატებოდეს
              <b><?= h(fmt_money($maxBudget, $currency)) ?></b>-ს.
            </div>
          <?php endif; ?>
        </aside>
      </div>
    </section>

    <?php if ($stepNames): ?>
      <section class="block">
        <h2>განაცხადის ნაბიჯები</h2>
        <ol class="plainList">
          <?php foreach ($stepNames as $stepName): ?>
            <li><?= h($stepName) ?></li>
          <?php endforeach; ?>
        </ol>
      </section>
    <?php endif; ?>

    <?php if ($requiredFiles || $optionalFiles): ?>
      <section class="block">
        <h2>საჭირო დოკუმენტები</h2>

        <?php if ($requiredFiles): ?>
          <div class="muted" style="margin-bottom:8px;">სავალდებულო დოკუმენტები:</div>
          <ul class="plainList">
            <?php foreach ($requiredFiles as $file): ?>
              <li><?= h((string)($file['name'] ?? '')) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($optionalFiles): ?>
          <div class="muted" style="margin:14px 0 8px;">დამატებითი დოკუმენტები:</div>
          <ul class="plainList">
            <?php foreach ($optionalFiles as $file): ?>
              <li><?= h((string)($file['name'] ?? '')) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="block">
      <h2>დეტალური აღწერა</h2>
      <div class="content"
           data-i18n-text
           data-text-ka="<?= h($body) ?>"
           data-text-en="<?= h($bodyEn) ?>">
        <?= h($body !== '' ? $body : $bodyEn) ?>
      </div>
    </section>
  </main>

  <div id="siteFooterMount"></div>

  <script>
    async function inject(id, url){
      const el = document.getElementById(id);
      if(!el) return;
      const res = await fetch(url + (url.includes('?') ? '&' : '?') + 'v=2');
      if(res.ok) el.innerHTML = await res.text();
    }

    async function loadScript(url){
      return new Promise((ok, bad) => {
        const s = document.createElement('script');
        s.src = url + (url.includes('?') ? '&' : '?') + 'v=2';
        s.onload = ok;
        s.onerror = bad;
        document.body.appendChild(s);
      });
    }

    (async () => {
      await inject('siteHeaderMount', '/header.html');
      try {
        await loadScript('/app.js');
        if (typeof window.initHeader === 'function') window.initHeader();
      } catch (e) {}
      await inject('siteFooterMount', '/footer.html');
    })();
  </script>
</body>
</html>