<?php
require_once __DIR__ . '/admin/config.php';
$pdo = db();

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

function fmt_date(?string $dt): string {
  if (!$dt) return '';
  $ts = strtotime($dt);
  if (!$ts) return (string)$dt;
  return date('Y-m-d H:i', $ts);
}

/**
 * IMPORTANT:
 * Language comes from cookie (PHP can read cookie).
 * Your header buttons must set: document.cookie = "lang=en; path=/; ..."
 */
$lang = (($_COOKIE['lang'] ?? 'ka') === 'en') ? 'en' : 'ka';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$hasTitleEn = has_col($pdo, 'news', 'title_en');
$hasBodyEn  = has_col($pdo, 'news', 'body_en');

$sql = "
  SELECT
    id,
    title,
    " . ($hasTitleEn ? "title_en" : "'' AS title_en") . ",
    slug,
    body,
    " . ($hasBodyEn ? "body_en" : "'' AS body_en") . ",
    image_path,
    published_at,
    is_active
  FROM news
  WHERE id=?
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$n || (int)$n['is_active'] !== 1) {
  http_response_code(404);
  exit('Not found');
}

// SEO slug normalize
$slug = trim((string)($n['slug'] ?? ''));
if ($slug === '' || $slug === '-' || $slug === 'news') $slug = 'news-' . (int)$n['id'];

$reqSlug = trim((string)($_GET['slug'] ?? ''));
$correctUrl = "/youthagency/news/" . (int)$n['id'] . "/" . $slug;

if ($reqSlug !== '' && $reqSlug !== $slug) {
  header("Location: $correctUrl", true, 301);
  exit;
}

// Gallery
$gallery = [];
try {
  $g = $pdo->prepare("SELECT id,image_path FROM news_images WHERE news_id=? ORDER BY sort_order ASC, id ASC");
  $g->execute([$id]);
  $gallery = $g->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $gallery = [];
}

// Pick content by language (with fallback)
$titleKa = (string)($n['title'] ?? '');
$titleEn = (string)($n['title_en'] ?? '');
$bodyKa  = (string)($n['body'] ?? '');
$bodyEn  = (string)($n['body_en'] ?? '');

$viewTitle = $titleKa;
$viewBody  = $bodyKa;

if ($lang === 'en') {
  if (trim($titleEn) !== '') $viewTitle = $titleEn;
  else $viewTitle = $titleKa;

  if (trim($bodyEn) !== '') $viewBody = $bodyEn;
  else $viewBody = $bodyKa;
} else {
  // ka
  $viewTitle = (trim($titleKa) !== '') ? $titleKa : $titleEn;
  $viewBody  = (trim($bodyKa) !== '') ? $bodyKa : $bodyEn;
}

$metaDescription = trim(preg_replace('/\s+/u', ' ', strip_tags($viewBody)) ?? '');
if ($metaDescription === '') $metaDescription = 'Youth Agency-ის სიახლე და დეტალური ინფორმაცია.';
if (mb_strlen($metaDescription) > 170) $metaDescription = mb_substr($metaDescription, 0, 167) . '...';
$heroImage = trim((string)($n['image_path'] ?? ''));
$heroImageUrl = $heroImage !== '' ? 'https://sspm.ge/youthagency/' . ltrim($heroImage, '/') : 'https://sspm.ge/youthagency/imgs/youthagencyicon.png';
$canonicalUrl = 'https://sspm.ge/youthagency/news/' . (int)$n['id'] . '/' . rawurlencode($slug);

?>
<!doctype html>
<html lang="<?=h($lang)?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h($viewTitle)?></title>
  <meta name="description" content="<?=h($metaDescription)?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="<?=h($canonicalUrl)?>">
  <link rel="icon" type="image/png" href="/youthagency/imgs/youthagencyicon.png">
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?=h($viewTitle)?>">
  <meta property="og:description" content="<?=h($metaDescription)?>">
  <meta property="og:url" content="<?=h($canonicalUrl)?>">
  <meta property="og:image" content="<?=h($heroImageUrl)?>">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="stylesheet" href="/youthagency/assets.css?v=1">

  <style>
    .wrap{max-width:1000px;margin:30px auto;padding:0 18px}
    .heroimg{width:100%;max-height:440px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb}
    .meta{opacity:.7;margin:10px 0 18px}
    .gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:18px}
    .gallery img{width:100%;height:170px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb}
    .btn{display:inline-block;padding:10px 12px;border-radius:12px;border:1px solid #ddd;background:#fff;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <a class="btn" href="/youthagency/news/">← Back to news</a>

    <h1 style="margin:14px 0"><?=h($viewTitle)?></h1>

    <div class="meta"><?=h(fmt_date($n['published_at'] ?? ''))?></div>

    <?php if (!empty($n['image_path'])): ?>
      <img class="heroimg" src="/youthagency/<?=h($n['image_path'])?>" alt="">
    <?php endif; ?>

    <?php if (trim($viewBody) !== ''): ?>
      <div style="margin-top:18px;line-height:1.7;white-space:pre-wrap"><?=h($viewBody)?></div>
    <?php endif; ?>

    <?php if (!empty($gallery)): ?>
      <h3 style="margin-top:22px">Gallery</h3>
      <div class="gallery">
        <?php foreach($gallery as $img): ?>
          <img src="/youthagency/<?=h($img['image_path'])?>" alt="">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Keep app.js for header/menu translations -->
  <script src="/youthagency/app.js?v=2"></script>
</body>
</html>
