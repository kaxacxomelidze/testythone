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
  try{
    $q = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$q->fetch(PDO::FETCH_ASSOC);
  }catch(Throwable $e){
    return false;
  }
}
function fmt_date(?string $raw): string {
  $raw = trim((string)$raw);
  if ($raw === '') return '—';
  $t = strtotime($raw);
  return $t ? date('Y-m-d', $t) : $raw;
}
function safe_html_paragraphs(string $text): string {
  $text = trim($text);
  if ($text === '') return '—';
  return nl2br(h($text), false);
}

/**
 * Make sure the apply URL is always absolute and always contains ?id=GRANT_ID
 * - If DB has apply_url, we respect it but still append id.
 * - If DB apply_url is empty, we use /youthagency/grants_apply.php
 */
function build_apply_url(int $grantId, string $dbUrl): string {
  $url = trim($dbUrl);

  // default apply page (ABSOLUTE)
  if ($url === '') $url = '/youthagency/grants/grants_apply.php';

  // if someone put relative path like "grants_apply.php" -> fix to absolute
  if ($url !== '' && $url[0] !== '/' && !preg_match('~^https?://~i', $url)) {
    $url = '/youthagency/' . ltrim($url, '/');
  }

  // Ensure grant id is present (id=) once
  if (!preg_match('/(?:^|[?&])id=\d+(?:&|$)/', $url)) {
    $sep = (str_contains($url, '?')) ? '&' : '?';
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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$st = $pdo->prepare("SELECT id,title," . (has_col($pdo, 'grants', 'title_en') ? "title_en" : "'' AS title_en") . ",
  slug,description," . (has_col($pdo, 'grants', 'description_en') ? "description_en" : "'' AS description_en") . ",
  body," . (has_col($pdo, 'grants', 'body_en') ? "body_en" : "'' AS body_en") . ",
  deadline,status,apply_url,is_active,image_path
  FROM grants WHERE id=? AND is_active=1 LIMIT 1");
$st->execute([$id]);
$g = $st->fetch(PDO::FETCH_ASSOC);
if (!$g) { http_response_code(404); exit('Not found'); }

$isClosed = (($g['status'] ?? 'current') === 'closed') || is_deadline_passed((string)($g['deadline'] ?? ''));
$statusLabel = $isClosed ? 'დახურული' : 'მიმდინარე';

// ✅ FIXED APPLY URL (always /youthagency/grants_apply.php?id=ID)
$applyUrl = build_apply_url($id, (string)($g['apply_url'] ?? ''));

$img = trim((string)($g['image_path'] ?? ''));
if ($img === '') {
  $img = "data:image/svg+xml;charset=UTF-8," . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="800">
      <rect width="100%" height="100%" fill="#1C2340"/>
      <rect x="60" y="60" width="1280" height="680" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="4"/>
      <text x="50%" y="50%" text-anchor="middle" fill="rgba(243,245,250,.55)" font-size="44" font-family="Arial">Grant Image</text>
    </svg>'
  );
}

$title = (string)($g['title'] ?? 'საგრანტო პროგრამა');
$titleEn = (string)($g['title_en'] ?? '');
$desc  = trim((string)($g['description'] ?? ''));
$descEn  = trim((string)($g['description_en'] ?? ''));
$body  = (string)($g['body'] ?? '');
$bodyEn  = (string)($g['body_en'] ?? '');
$deadline = (string)($g['deadline'] ?? '');
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h($title)?></title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/youthagency/assets.css?v=1">

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
    }

    html, body{ background: var(--bg) !important; }
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

    .btn.disabled{
      opacity:.55;
      pointer-events:none;
      filter:saturate(.7);
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
      font-size:22px;
      font-weight:950;
      line-height:1.25;
      letter-spacing:-.2px;
    }

    .heroDesc{
      margin:10px 0 0;
      color: var(--muted);
      font-size:14px;
      line-height:1.65;
      max-width:860px;
      padding:0 18px 16px;
    }

    .layout{
      display:grid;
      grid-template-columns: 1.35fr .85fr;
      gap:14px;
      padding:18px;
    }
    @media(max-width:980px){ .layout{ grid-template-columns: 1fr; } }

    .photo{
      border:1px solid var(--line);
      border-radius: var(--radius);
      overflow:hidden;
      background: var(--card2);
      min-height: 260px;
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
    .pill.closed{  background: var(--badBg); border-color: var(--badBd); color: var(--badTx); }

    .pill.neutral{
      background: rgba(255,255,255,.06);
      border: 1px solid var(--line2);
      color: rgba(243,245,250,.86);
    }

    .actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:14px;
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
      font-size:15px;
      font-weight:950;
      letter-spacing:.1px;
    }
    .content{
      color: rgba(243,245,250,.72);
      font-size:14px;
      line-height:1.8;
      white-space:pre-wrap;
    }

    a{ -webkit-tap-highlight-color: transparent; }
  </style>
</head>

<body>
  <div id="siteHeaderMount"></div>

  <main class="wrap">

    <div class="topbar">
      <a class="btn secondary" href="/youthagency/grants/" onclick="if(history.length>1){ history.back(); return false; }">
        <i class="fa-solid fa-arrow-left"></i> <span data-i18n="grantsView.back">უკან</span>
      </a>
    </div>

    <section class="hero">
      <div class="heroHead">
        <h1 class="heroTitle" data-i18n-text data-text-ka="<?=h($title)?>" data-text-en="<?=h($titleEn)?>"><?=h($title)?></h1>

        <span class="pill <?= $isClosed ? 'closed' : 'current' ?>">
          <i class="fa-solid fa-circle" style="font-size:8px;opacity:.85"></i>
          <span data-i18n="<?= $isClosed ? 'grantsView.statusClosed' : 'grantsView.statusOpen' ?>"><?=h($statusLabel)?></span>
        </span>
      </div>

      <?php if($desc !== '' || $descEn !== ''): ?>
        <div class="heroDesc" data-i18n-text data-text-ka="<?=h($desc)?>" data-text-en="<?=h($descEn)?>"><?=h($desc !== '' ? $desc : $descEn)?></div>
      <?php endif; ?>

      <div class="layout">
        <div class="photo">
          <img src="<?=h($img)?>" alt="">
        </div>

        <aside class="side">
          <div class="metaRow">
            <span class="pill neutral">
              <i class="fa-regular fa-calendar"></i>
              <span data-i18n="grantsView.deadlineLabel">ვადა:</span> <?=h(fmt_date($deadline))?>
            </span>
          </div>

          <div class="actions">
            <?php if(!$isClosed): ?>
              <!-- ✅ FIXED: always absolute + always includes grant id -->
              <a class="btn" href="<?=h($applyUrl)?>">
                <i class="fa-solid fa-file-pen"></i> <span data-i18n="grantsView.apply">განაცხადის შევსება</span>
              </a>
            <?php endif; ?>

            <a class="btn secondary" href="/youthagency/grants/">
              <i class="fa-solid fa-list"></i> <span data-i18n="grantsView.all">ყველა საგრანტო</span>
            </a>
          </div>
        </aside>
      </div>
    </section>

    <section class="block">
      <h2 data-i18n="grantsView.detailsTitle">დეტალური აღწერა</h2>
      <div class="content" data-i18n-text data-text-ka="<?=h($body)?>" data-text-en="<?=h($bodyEn)?>"><?=h($body !== '' ? $body : $bodyEn)?></div>
    </section>

  </main>

  <div id="siteFooterMount"></div>

  <script>
    async function inject(id, url){
      const el = document.getElementById(id);
      if(!el) return;
      const res = await fetch(url + (url.includes('?')?'&':'?') + 'v=2');
      if(res.ok) el.innerHTML = await res.text();
    }
    async function loadScript(url){
      return new Promise((ok, bad)=>{
        const s=document.createElement('script');
        s.src=url + (url.includes('?')?'&':'?') + 'v=2';
        s.onload=ok; s.onerror=bad;
        document.body.appendChild(s);
      });
    }
    (async()=>{
      await inject('siteHeaderMount','/youthagency/header.html');
      try{
        await loadScript('/youthagency/app.js');
        if(typeof window.initHeader==='function') window.initHeader();
      }catch(e){}
      await inject('siteFooterMount','/youthagency/footer.html');
    })();
  </script>
</body>
</html>
