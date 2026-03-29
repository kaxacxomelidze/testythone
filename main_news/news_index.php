<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
$pdo = db();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('~/main_news/news_index\.php$~', $requestPath)) {
  $qs = $_SERVER['QUERY_STRING'] ?? '';
  header('Location: /news/' . ($qs !== '' ? ('?' . $qs) : ''), true, 301);
  exit;
}

if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function has_col(PDO $pdo, string $table, string $col): bool {
  static $cache = [];
  $key = $table.'.'.$col;
  if (isset($cache[$key])) return $cache[$key];
  $st = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
  return $cache[$key] = (bool)$st->fetch(PDO::FETCH_ASSOC);
}

function news_url(array $n): string {
  $id = (int)$n['id'];
  $slug = trim((string)($n['slug'] ?? ''));
  if ($slug === '' || $slug === '-' || $slug === 'news') $slug = 'news-' . $id;
  return "/news/$id/" . rawurlencode($slug);
}

function excerpt(string $text, int $len = 240): string {
  $t = trim(strip_tags($text));
  $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
  return (mb_strlen($t) > $len) ? (mb_substr($t, 0, $len) . '…') : $t;
}

function fmt_date(?string $raw): string {
  $raw = trim((string)$raw);
  if ($raw === '') return '';
  $ts = strtotime($raw);
  if (!$ts) return $raw;
  return date('M d, Y', $ts);
}

/**
 * Convert stored path to a public URL.
 * - If it's already http(s) or data: -> keep it
 * - If it starts with "/" -> keep it
 * - Else assume relative to /
 */
function public_url(string $path): string {
  $p = trim($path);
  if ($p === '') return '';
  if (preg_match('~^(https?:)?//~i', $p)) return $p;
  if (str_starts_with($p, 'data:')) return $p;
  if (!str_starts_with($p, '/')) $p = '/' . ltrim($p, '/');
  if (str_starts_with($p, '/youthagency/')) {
    return '/' . ltrim(substr($p, strlen('/youthagency/')), '/');
  }
  return $p;
}

/* detect columns safely */
$hasImagePath = has_col($pdo, 'news', 'image_path'); // ✅ your admin uses this
$hasCover     = has_col($pdo, 'news', 'cover');      // fallback for older DBs
$hasBody      = has_col($pdo, 'news', 'body');
$hasTitleEn   = has_col($pdo, 'news', 'title_en');
$hasBodyEn    = has_col($pdo, 'news', 'body_en');
$hasActive    = has_col($pdo, 'news', 'is_active');
$hasSort      = has_col($pdo, 'news', 'sort_order');
$hasCreated   = has_col($pdo, 'news', 'created_at');

/* search */
$q = trim((string)($_GET['q'] ?? ''));

/* pagination */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

/* WHERE */
$where = [];
$params = [];
if ($hasActive) $where[] = "is_active=1";

if ($q !== '') {
  $parts = ["title LIKE ?"];
  $params[] = "%$q%";
  if ($hasBody) {
    $parts[] = "body LIKE ?";
    $params[] = "%$q%";
  }
  if ($hasTitleEn) {
    $parts[] = "title_en LIKE ?";
    $params[] = "%$q%";
  }
  if ($hasBodyEn) {
    $parts[] = "body_en LIKE ?";
    $params[] = "%$q%";
  }
  $where[] = "(" . implode(" OR ", $parts) . ")";
}
$whereSql = $where ? "WHERE ".implode(" AND ", $where) : "";

/* ORDER */
$order = [];
if ($hasSort) $order[] = "sort_order ASC";
if ($hasCreated) $order[] = "created_at DESC";
$order[] = "id DESC";
$orderSql = "ORDER BY ".implode(", ", $order);

/* COUNT */
$stCount = $pdo->prepare("SELECT COUNT(*) FROM news $whereSql");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* SELECT */
$cols = ['id','title','slug'];
if ($hasTitleEn) $cols[] = 'title_en'; else $cols[] = "'' AS title_en";
if ($hasBody)      $cols[] = 'body';
if ($hasBodyEn)    $cols[] = 'body_en'; else $cols[] = "'' AS body_en";
if ($hasCreated)   $cols[] = 'created_at';
if ($hasImagePath) $cols[] = 'image_path';
if ($hasCover)     $cols[] = 'cover';

$sql = "SELECT ".implode(',', $cols)." FROM news $whereSql $orderSql LIMIT $perPage OFFSET $offset";
$st = $pdo->prepare($sql);
$st->execute($params);
$items = $st->fetchAll(PDO::FETCH_ASSOC);

/* placeholder */
$placeholder = "data:image/svg+xml;charset=UTF-8," . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="420">
    <defs>
      <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="#eef2ff"/>
        <stop offset="1" stop-color="#f1f5f9"/>
      </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#g)"/>
    <rect x="34" y="34" width="572" height="352" rx="22" fill="#ffffff" opacity="0.9"/>
    <path d="M150 290l82-88 68 62 70-98 118 124H150z" fill="#cbd5e1"/>
    <circle cx="210" cy="170" r="26" fill="#cbd5e1"/>
    <text x="320" y="330" text-anchor="middle" font-family="Arial" font-size="18" fill="#64748b">No image</text>
  </svg>'
);

$seoTitle = 'Youth Agency • News';
$seoDescription = $q !== ''
  ? ('მოძებნე სიახლეები Youth Agency-ის ვებგვერდზე: ' . $q)
  : 'Youth Agency-ის სიახლეები, განცხადებები და განახლებები ერთ გვერდზე.';
$canonicalUrl = 'https://sspm.ge/news/' . ($q !== '' ? '?q=' . rawurlencode($q) : '');
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?=h($seoTitle)?></title>
  <meta name="description" content="<?=h($seoDescription)?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="<?=h($canonicalUrl)?>">
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?=h($seoTitle)?>">
  <meta property="og:description" content="<?=h($seoDescription)?>">
  <meta property="og:url" content="<?=h($canonicalUrl)?>">
  <meta property="og:image" content="https://sspm.ge/imgs/youthagencyicon.png">
  <meta name="twitter:card" content="summary_large_image">

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Main CSS (your global styles) -->
  <link rel="stylesheet" href="/assets.css?v=1">

<style>
  :root{
    --bg:#f8fafc;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --soft:#f1f5f9;
    --link:#2563eb;
    --shadow: 0 14px 40px rgba(15,23,42,.08);
    --shadow2: 0 18px 60px rgba(15,23,42,.12);
    --radius: 18px;
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    background:
      radial-gradient(900px 380px at 20% -10%, rgba(37,99,235,.10), transparent 60%),
      radial-gradient(900px 380px at 90% 0%, rgba(16,185,129,.10), transparent 55%),
      var(--bg);
    color:var(--text);
    font-family: "Noto Sans Georgian", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  }

  /* ✅ IMPORTANT: prevent global CSS from turning titles white */
  body.news-page main.wrap h1,
  body.news-page main.wrap .title a{
    color: #0f172a !important;
  }

  .wrap{max-width:1180px;margin:0 auto;padding:34px 18px 70px;}
  .header{display:flex; align-items:flex-end; justify-content:space-between; gap:14px; margin-bottom:18px;}
  h1{margin:0;font-size:40px;letter-spacing:-0.8px;line-height:1.05; position:relative; display:inline-block;}
  h1::after{
    content:"";
    position:absolute;
    left:0;
    bottom:-10px;
    width:60%;
    height:4px;
    border-radius:4px;
    background: linear-gradient(90deg, #2563eb, #10b981);
  }

  /* ✅ SEARCH BAR (improved) */
  .bar{
    display:flex;
    justify-content:space-between;
    gap:12px;
    padding:14px;
    border:2px solid #e2e8f0;              /* thicker border */
    border-radius: var(--radius);
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(6px);
    box-shadow: var(--shadow);
  }
  .bar form{
    display:flex;
    gap:10px;
    width:100%;
    max-width:640px;                      /* wider */
    align-items:center;
  }

  /* input wrap for icon */
  .in-wrap{ position:relative; flex:1; }
  .in-ico{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
    font-size:14px;
    pointer-events:none;
  }
  .in{
    width:100%;
    padding:12px 14px 12px 40px;          /* space for icon */
    border:2px solid #e2e8f0;
    border-radius:14px;
    outline:none;
    background:#fff;
    color: var(--text);
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  .in::placeholder{color:#94a3b8}
  .in:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.16);
  }

  /* buttons */
  .btn{
    padding:12px 16px;
    border:2px solid #e2e8f0;
    border-radius:14px;
    cursor:pointer;
    font-weight:900;
    color: var(--text);
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
    transition: filter .15s ease, transform .05s ease, border-color .15s ease;
  }

  /* primary search button */
  .btn.primary{
    background: #364379;
  }
  .btn.primary:hover{filter:brightness(1.03)}
  .btn:active{transform:translateY(1px)}

  /* secondary reset */
  .btn.ghost{
    background:#fff;
  }
  .btn.ghost:hover{
    border-color: rgba(37,99,235,.35);
  }

  .hint{color:var(--muted); font-size:13px; align-self:center;}
  @media (max-width:760px){
    .bar{flex-direction:column}
    .bar form{
      max-width:100%;
      flex-wrap:wrap;
    }
    .in-wrap{width:100%}
    .btn{
      width:100%;
      justify-content:center;
    }
    .hint{width:100%}
  }
  @media (max-width:640px){
    .wrap{padding:24px 16px 50px;}
    h1{font-size:28px;}
    .bar{padding:12px;}
    .in{padding:10px 12px 10px 38px;}
  }

  .news-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:18px 22px;
    margin-top:18px;
  }
  @media (max-width: 980px){ .news-grid{grid-template-columns:1fr} }

  .news-item{
    display:flex;
    gap:16px;
    padding:16px;
    border:1px solid var(--line);
    border-radius: var(--radius);
    background: var(--card);
    box-shadow: var(--shadow);
    transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
    min-height: 176px;
  }
  .news-item:hover{
    transform: translateY(-4px) scale(1.005);
    box-shadow: var(--shadow2);
    border-color: rgba(37,99,235,.22);
  }

  .thumb{
    width: 250px;
    min-width: 250px;
    height: 158px;
    border-radius: 16px;
    overflow:hidden;
    background: var(--soft);
    border:1px solid #eef2f7;
    position:relative;
  }
  .thumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transform: scale(1.01);
    transition: transform .20s ease;
  }
  .news-item:hover .thumb img{ transform: scale(1.06); }
  .thumb::after{
    content:"";
    position:absolute; inset:0;
    background: linear-gradient(
      135deg,
      rgba(15,23,42,.05),
      rgba(15,23,42,.18)
    );
    pointer-events:none;
  }

  @media (max-width: 520px){
    .news-item{flex-direction:column}
    .thumb{width:100%; min-width:unset; height:210px}
  }
  @media (max-width: 420px){
    .thumb{height:180px}
    .actions{flex-direction:column; align-items:flex-start;}
  }

  .content{min-width:0; flex:1; display:flex; flex-direction:column;}

  .title{
    margin:0;
    font-size:18px;
    line-height:1.25;
    font-weight:950;
    letter-spacing:-0.2px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }
  .title a{color:var(--text); text-decoration:none;}
  .title a:hover{text-decoration:underline}

  .meta{
    margin-top:10px;
    display:flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
  }
  .chip{
    font-size:12px;
    color: #0f172a;
    background: #f1f5f9;
    border:1px solid #e2e8f0;
    padding:6px 10px;
    border-radius: 999px;
    font-weight:850;
  }

  .text{
    margin-top:10px;
    color:#1e293b;
    font-size:14px;
    line-height:1.65;
    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }

  .actions{
    margin-top:auto;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding-top:12px;
  }
  .read{
    color:var(--link);
    font-weight:950;
    font-size:14px;
    text-decoration:none;
    position:relative;
  }
  .read::after{
    content:"";
    position:absolute;
    left:0;
    bottom:-3px;
    width:0;
    height:2px;
    background: currentColor;
    transition: width .2s ease;
  }
  .read:hover::after{ width:100%; }

  .tiny{font-size:12px;color: var(--muted);}

  .pager{ display:flex; flex-wrap:wrap; gap:10px; margin-top:26px; }
  .pager a{
    padding:9px 13px;
    border:1px solid var(--line);
    border-radius:14px;
    color:var(--text);
    text-decoration:none;
    font-weight:950;
    font-size:14px;
    background:#fff;
    box-shadow: 0 8px 22px rgba(15,23,42,.06);
  }
  .pager a:hover{border-color:rgba(37,99,235,.25)}
  .pager a.active{
    background: linear-gradient(135deg, rgba(37,99,235,.16), rgba(16,185,129,.12));
    border-color: rgba(37,99,235,.28);
    color:#0f172a;
    box-shadow: inset 0 0 0 1px rgba(37,99,235,.35),
                0 10px 25px rgba(37,99,235,.15);
  }

  .empty{
    margin-top:18px;
    padding:18px;
    border:1px dashed #dbeafe;
    background: rgba(255,255,255,.9);
    border-radius: var(--radius);
    color: var(--muted);
  }
</style>
</head>

<body class="news-page">

  <!-- ✅ HEADER (injected) -->
  <div id="siteHeaderMount"></div>

  <main class="wrap">

    <div class="header">
      <div>
        <h1 data-i18n="newsIndex.title">News</h1>
      </div>
    </div>

    <!-- ✅ improved search UI INSIDE your bar -->
    <div class="bar">
      <form method="get" action="/news/">
        <div class="in-wrap">
          <i class="fa-solid fa-magnifying-glass in-ico" aria-hidden="true"></i>
          <input class="in" type="text" name="q" value="<?=h($q)?>" placeholder="Search news..." data-i18n-placeholder="newsIndex.searchPlaceholder">
        </div>

        <button class="btn primary" type="submit">
          <i class="fa-solid fa-search" style="margin-right:8px;"></i> <span data-i18n="newsIndex.searchButton">Search</span>
        </button>

        <?php if ($q !== ''): ?>
          <a class="btn ghost" href="/news/">
            <i class="fa-solid fa-rotate-left" style="margin-right:8px;"></i> <span data-i18n="newsIndex.resetButton">Reset</span>
          </a>
        <?php endif; ?>
      </form>

      <div class="hint">
        <?php if ($q !== ''): ?>
          <span data-i18n="newsIndex.resultsPrefix">Showing results for:</span>
          <span>“<?=h($q)?>”</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$items): ?>
      <div class="empty">
        <span data-i18n="newsIndex.empty">No news found</span>
        <?php if ($q !== ''): ?>
          <span data-i18n="newsIndex.emptyFor">for</span>
          <span>“<?=h($q)?>”</span>
        <?php endif; ?>
        <span>.</span>
      </div>
    <?php else: ?>
      <section class="news-grid">
        <?php foreach ($items as $n): ?>
          <?php
            $url = news_url($n);

            $rawImg = '';
            if ($hasImagePath && !empty($n['image_path'])) $rawImg = (string)$n['image_path'];
            elseif ($hasCover && !empty($n['cover'])) $rawImg = (string)$n['cover'];

            $img = $rawImg ? public_url($rawImg) : $placeholder;

            $dateText = ($hasCreated && !empty($n['created_at'])) ? fmt_date((string)$n['created_at']) : '';
          ?>
          <article class="news-item">
            <a class="thumb" href="<?=h($url)?>">
              <img loading="lazy" src="<?=h($img)?>" alt="">
            </a>

            <div class="content">
              <h2 class="title">
                <a href="<?=h($url)?>" data-i18n-text data-text-ka="<?=h((string)$n['title'])?>" data-text-en="<?=h((string)($n['title_en'] ?? ''))?>">
                  <?=h($n['title'])?>
                </a>
              </h2>

              <div class="meta">
                <span class="chip"><?= $dateText !== '' ? h($dateText) : ('ID: '.(int)$n['id']) ?></span>
              </div>

              <?php
                $bodyText = (string)($n['body'] ?? '');
                $bodyTextEn = (string)($n['body_en'] ?? '');
                $excerptKa = ($hasBody && $bodyText !== '') ? excerpt($bodyText, 240) : '';
                $excerptEn = ($hasBodyEn && $bodyTextEn !== '') ? excerpt($bodyTextEn, 240) : '';
              ?>
              <?php if ($excerptKa !== '' || $excerptEn !== ''): ?>
                <div class="text" data-i18n-text data-text-ka="<?=h($excerptKa)?>" data-text-en="<?=h($excerptEn)?>"><?=h($excerptKa !== '' ? $excerptKa : $excerptEn)?></div>
              <?php else: ?>
                <div class="text" style="color:#64748b;" data-i18n="newsIndex.noBody">Click to read the full article.</div>
              <?php endif; ?>

              <div class="actions">
                <a class="read" href="<?=h($url)?>" data-i18n="newsIndex.readMore">Read more →</a>
                <span class="tiny">
                  <?php if (!$hasBody || empty($n['body'])): ?>
                    <span data-i18n="newsIndex.noPreview">No preview text</span>
                  <?php endif; ?>
                </span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <?php if ($totalPages > 1): ?>
        <nav class="pager" aria-label="Pagination" data-i18n-aria="newsIndex.paginationAria">
          <?php
            $qPart = ($q !== '') ? '&q='.rawurlencode($q) : '';
            for ($p=1;$p<=$totalPages;$p++):
          ?>
            <a class="<?= $p===$page ? 'active':'' ?>"
               href="/news/?page=<?=$p?><?=$qPart?>">
               <?=$p?>
            </a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </main>

  <!-- ✅ FOOTER (injected) -->
  <div id="siteFooterMount"></div>

  <!-- ✅ HEADER / FOOTER LOADER (absolute paths) -->
  <script>
    async function inject(id, url) {
      const el = document.getElementById(id);
      if (!el) throw new Error(`Mount element not found: #${id}`);
      const res = await fetch(url + (url.includes('?') ? '&' : '?') + 'v=2');
      if (!res.ok) throw new Error(`${url} not found. Status: ${res.status}`);
      el.innerHTML = await res.text();
    }

    async function loadScript(url) {
      return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = url + (url.includes('?') ? '&' : '?') + 'v=2';
        s.onload = resolve;
        s.onerror = () => reject(new Error(`Failed to load script: ${url}`));
        document.body.appendChild(s);
      });
    }

    (async () => {
      try {
        await inject('siteHeaderMount', '/header.php');
        await loadScript('/app.js');
        if (typeof window.initHeader === 'function') window.initHeader();
        await inject('siteFooterMount', '/footer.php');
      } catch (err) {
        console.error('HEADER/FOOTER ERROR:', err);
      }
    })();
  </script>

</body>
</html>
