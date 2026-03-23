<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
date_default_timezone_set('Asia/Tbilisi');

$pdo = db();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('~/grants/grants_index\.php$~', $requestPath)) {
  $qs = $_SERVER['QUERY_STRING'] ?? '';
  header('Location: /youthagency/grants/' . ($qs !== '' ? ('?' . $qs) : ''), true, 301);
  exit;
}

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

function grants_url(array $g): string {
  $id = (int)($g['id'] ?? 0);
  $slug = trim((string)($g['slug'] ?? ''));
  if ($slug === '') $slug = 'grant-' . $id;
  return "/youthagency/grants/$id/" . rawurlencode($slug);
}

function excerpt(string $text, int $len = 170): string {
  $t = trim(strip_tags($text));
  $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
  return (mb_strlen($t) > $len) ? (mb_substr($t, 0, $len) . '…') : $t;
}

function fmt_date(?string $raw): string {
  $raw = trim((string)$raw);
  if ($raw === '') return '—';
  $ts = strtotime($raw);
  if (!$ts) return $raw;
  return date('Y-m-d', $ts);
}

/**
 * ✅ returns true if deadline is in past
 * Rules:
 * - if deadline is "YYYY-MM-DD" => consider end of day 23:59:59
 * - if includes time => compare as-is
 */
function is_deadline_passed(?string $deadline): bool {
  $deadlineRaw = trim((string)$deadline);
  if ($deadlineRaw === '') return false;

  $ts = strtotime($deadlineRaw);
  if (!$ts) return false;

  // If only date is provided, treat deadline as end of that day
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadlineRaw)) {
    $ts += 86399; // 23:59:59
  }
  return time() > $ts;
}

$applyDefault = "/youthagency/rules/";
$hasTitleEn = has_col($pdo, 'grants', 'title_en');
$hasDescEn = has_col($pdo, 'grants', 'description_en');
$hasBodyEn = has_col($pdo, 'grants', 'body_en');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

/**
 * ✅ We only show active grants. (same as before)
 * If you ever want to show closed too, remove status filter in SQL.
 */
$where = "WHERE is_active=1";

/**
 * ✅ Count items for pagination (must match list query)
 */
$stCount = $pdo->query("SELECT COUNT(*) FROM grants $where");
$total = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/**
 * ✅ List items
 */
$st = $pdo->prepare("
  SELECT id,title," . ($hasTitleEn ? "title_en" : "'' AS title_en") . ",
         slug,description," . ($hasDescEn ? "description_en" : "'' AS description_en") . ",
         body," . ($hasBodyEn ? "body_en" : "'' AS body_en") . ",deadline,status,apply_url,sort_order
  FROM grants
  $where
  ORDER BY sort_order ASC, deadline ASC, id DESC
  LIMIT :limit OFFSET :offset
");
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$items = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="/youthagency/imgs/youthagencyicon.png">
  <title>Youth Agency • Grants</title>
  <meta name="description" content="იხილეთ Youth Agency-ის აქტიური საგრანტო პროგრამები და მონაწილეობის პირობები.">

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/youthagency/assets.css?v=1">

  <style>
    :root{
      --bg:#151A2C;
      --panel:#1B2238;
      --panel2:#1A2034;
      --card:#1C2340;
      --cardHover:#202A4A;

      --line: rgba(255,255,255,.10);
      --line2: rgba(255,255,255,.16);

      --text:#F3F5FA;
      --muted: rgba(243,245,250,.70);
      --muted2: rgba(243,245,250,.58);

      --btn:#243055;
      --btnHover:#2A3863;

      --radius:16px;
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

    *{box-sizing:border-box}
    body{
      margin:0;
      background: var(--bg) !important;
      color: var(--text);
      font-family:"Noto Sans Georgian", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .wrap{max-width:1180px;margin:0 auto;padding:26px 18px 70px;}

    .hero{
      border:1px solid var(--line);
      border-radius:20px;
      background: var(--panel);
      box-shadow: var(--shadow);
    }
    .hero-inner{
      padding:24px 22px;
      display:flex;
      justify-content:space-between;
      gap:18px;
      align-items:center;
      flex-wrap:wrap;
    }
    h1{
      margin:0;
      font-size:32px;
      letter-spacing:-.4px;
    }
    .hero p{
      margin:10px 0 0;
      color: var(--muted);
      max-width:760px;
      line-height:1.65;
      font-size:14px;
    }

    .btn{
      padding:12px 16px;
      border-radius:14px;
      border:1px solid var(--line);
      color:var(--text);
      text-decoration:none;
      font-weight:900;
      display:inline-flex;
      gap:10px;
      align-items:center;
      transition: background .12s ease, border-color .12s ease, transform .12s ease;
      user-select:none;
    }
    .btn:hover{ background: var(--btnHover); border-color: var(--line2); }
    .btn:active{ transform: translateY(1px); }
    .btn:focus{ outline:none; box-shadow: var(--focus); }

    .head{
      margin:18px 0 12px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }
    .section-title{
      margin:0;
      font-size:13px;
      font-weight:950;
      color: var(--muted);
      letter-spacing:.3px;
      text-transform:uppercase;
    }
    .counter{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid var(--line);
      background: rgba(0,0,0,.12);
      color: var(--muted);
      font-size:12px;
      font-weight:900;
      white-space:nowrap;
    }
    .counter b{ color: var(--text); }

    .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    @media(max-width:980px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:640px){.grid{grid-template-columns:1fr}}

    .card{
      border:1px solid var(--line);
      border-radius:var(--radius);
      background: var(--card);
      box-shadow: var(--shadow2);
      overflow:hidden;
      display:flex;
      flex-direction:column;
      min-height:220px;
      transition: transform .14s ease, border-color .14s ease, background .14s ease;
    }
    .card:hover{
      transform: translateY(-2px);
      border-color: var(--line2);
      background: var(--cardHover);
    }

    .card-top{
      padding:14px 14px 10px;
      display:flex;
      justify-content:space-between;
      gap:10px;
      align-items:flex-start;
    }
    .card-title{
      margin:0;
      font-size:15px;
      font-weight:950;
      line-height:1.28;
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
    .pill.current{
      background: var(--okBg);
      border-color: var(--okBd);
      color: var(--okTx);
    }
    .pill.closed{
      background: var(--badBg);
      border-color: var(--badBd);
      color: var(--badTx);
    }

    .card-desc{
      padding:0 14px 12px;
      color: var(--muted2);
      font-size:13px;
      line-height:1.62;
      min-height:66px;
    }

    .meta{
      margin-top:auto;
      display:flex;
      justify-content:space-between;
      gap:10px;
      padding:12px 14px;
      border-top:1px solid var(--line);
      background: rgba(0,0,0,.10);
      align-items:center;
      flex-wrap:wrap;
    }
    .small{
      font-size:12px;
      color: var(--muted);
      display:inline-flex;
      gap:8px;
      align-items:center;
      white-space:nowrap;
    }

    .details{
      padding:10px 12px;
      border-radius:12px;
      border:1px solid var(--line);
      color:var(--text);
      text-decoration:none;
      font-weight:950;
      background: rgba(255,255,255,.06);
      transition: background .12s ease, border-color .12s ease;
      white-space:nowrap;
    }
    .details:hover{ border-color: var(--line2); background: rgba(255,255,255,.10); }
    .details:focus{ outline:none; box-shadow: var(--focus); }

    .pager{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
    .pager a{
      padding:9px 12px;
      border-radius:12px;
      border:1px solid var(--line);
      text-decoration:none;
      color:var(--text);
      font-weight:950;
      background: rgba(255,255,255,.06);
      transition: background .12s ease, border-color .12s ease;
    }
    .pager a:hover{ background: rgba(255,255,255,.10); border-color: var(--line2); }
    .pager a.active{
      border-color: var(--line2);
      background: rgba(255,255,255,.14);
    }

    .empty{
      margin-top:14px;
      padding:16px;
      border:1px dashed var(--line2);
      border-radius:var(--radius);
      color: var(--muted);
      background: rgba(0,0,0,.10);
    }
  </style>
</head>

<body>
  <div id="siteHeaderMount"></div>

  <main class="wrap">
    <section class="hero">
      <div class="hero-inner">
        <div>
          <h1><i class="fa-solid fa-hand-holding-heart"></i> <span data-i18n="grants.title">საგრანტო პროგრამები</span></h1>
          <p data-i18n="grants.subtitle">ახალგაზრდებისთვის განკუთვნილი საგრანტო შესაძლებლობები იდეებისა და პროექტების მხარდასაჭერად</p>
        </div>

        <a class="btn" href="<?=h($applyDefault)?>">
          <i class="fa-solid fa-file-pen"></i> <span data-i18n="grants.rulesCta">წესები და პირობები</span>
        </a>
      </div>
    </section>

    <div class="head">
      <h2 class="section-title" data-i18n="grants.listTitle">საგრანტო პროგრამების ჩამონათვალი</h2>
      <div class="counter"><i class="fa-regular fa-folder-open"></i> <span data-i18n="grants.recordsLabel">ჩანაწერები:</span> <b><?= (int)$total ?></b></div>
    </div>

    <?php if(!$items): ?>
      <div class="empty" data-i18n="grants.empty">ამჟამად საგრანტო პროგრამები არ მოიძებნა.</div>
    <?php else: ?>
      <section class="grid">
        <?php foreach($items as $g): ?>
          <?php
            // ✅ CLOSED if admin closed OR deadline passed
            $adminClosed = (strtolower(trim((string)($g['status'] ?? ''))) === 'closed');
            $timeClosed  = is_deadline_passed((string)($g['deadline'] ?? ''));

            $isClosed = ($adminClosed || $timeClosed);

            $statusLabelKey = $isClosed ? 'grants.statusClosed' : 'grants.statusOpen';
            $statusLabel = $isClosed ? 'დახურულია' : 'მიმდინარე';
            $pillClass   = $isClosed ? 'closed' : 'current';

            $descSrc = (string)($g['description'] ?? '');
            if ($descSrc === '' && !empty($g['body'])) $descSrc = (string)$g['body'];
            $desc = excerpt($descSrc, 170);
            $descSrcEn = (string)($g['description_en'] ?? '');
            if ($descSrcEn === '' && !empty($g['body_en'])) $descSrcEn = (string)$g['body_en'];
            $descEn = excerpt($descSrcEn, 170);

            $url = grants_url($g);
          ?>
          <article class="card">
            <div class="card-top">
              <h3 class="card-title" data-i18n-text data-text-ka="<?=h((string)$g['title'])?>" data-text-en="<?=h((string)($g['title_en'] ?? ''))?>">
                <?=h((string)$g['title'])?>
              </h3>

              <span class="pill <?=h($pillClass)?>">
                <i class="fa-solid fa-circle" style="font-size:8px;opacity:.8"></i>
                <span data-i18n="<?=h($statusLabelKey)?>"><?=h($statusLabel)?></span>
              </span>
            </div>

            <div class="card-desc" data-i18n-text data-text-ka="<?=h($desc)?>" data-text-en="<?=h($descEn)?>"><?=h($desc !== '' ? $desc : $descEn)?></div>

            <div class="meta">
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <span class="small">
                  <i class="fa-regular fa-calendar"></i>
                  <span data-i18n="grants.deadlineLabel">ვადა:</span> <?=h(fmt_date((string)($g['deadline'] ?? '')))?>
                </span>
              </div>

              <a class="details" href="<?=h($url)?>" data-i18n="grants.details">დეტალურად</a>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <?php if($totalPages>1): ?>
        <nav class="pager" aria-label="Pagination" data-i18n-aria="grants.paginationAria">
          <?php for($p=1;$p<=$totalPages;$p++): ?>
            <a class="<?= $p===$page?'active':'' ?>" href="/youthagency/grants/?page=<?=$p?>"><?=$p?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <div id="siteFooterMount"></div>

  <script>
    async function inject(id, url){
      const el = document.getElementById(id);
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
      try{ await loadScript('/youthagency/app.js'); if(typeof window.initHeader==='function') window.initHeader(); }catch(e){}
      await inject('siteFooterMount','/youthagency/footer.html');
    })();
  </script>
</body>
</html>
