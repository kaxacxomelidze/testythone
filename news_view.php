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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$stmt = $pdo->prepare("
  SELECT id,title," . (has_col($pdo, 'news', 'title_en') ? "title_en" : "'' AS title_en") . ",
         slug,body," . (has_col($pdo, 'news', 'body_en') ? "body_en" : "'' AS body_en") . ",
         image_path,published_at,is_active
  FROM news
  WHERE id=? LIMIT 1
");
$stmt->execute([$id]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$n || (int)$n['is_active'] !== 1) {
  http_response_code(404);
  exit('Not found');
}

$slug = trim((string)($n['slug'] ?? ''));
if ($slug === '' || $slug === '-' || $slug === 'news') $slug = 'news-' . (int)$n['id'];

$reqSlug = trim((string)($_GET['slug'] ?? ''));
$correctUrl = "/youthagency/news/" . (int)$n['id'] . "/" . $slug;

// if slug is wrong -> redirect to correct SEO url
if ($reqSlug !== '' && $reqSlug !== $slug) {
  header("Location: $correctUrl", true, 301);
  exit;
}

// gallery
$gallery = [];
try {
  $g = $pdo->prepare("SELECT id,image_path FROM news_images WHERE news_id=? ORDER BY sort_order ASC, id ASC");
  $g->execute([$id]);
  $gallery = $g->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $gallery = []; }

function fmt_date(?string $dt): string {
  if (!$dt) return '';
  $ts = strtotime($dt);
  if (!$ts) return $dt;
  return date('Y-m-d H:i', $ts);
}

$titleEn = (string)($n['title_en'] ?? '');
$bodyText = (string)($n['body'] ?? '');
$bodyTextEn = (string)($n['body_en'] ?? '');
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h($n['title'])?></title>

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
    <a class="btn" href="javascript:history.back()">← Back</a>


    <h1 style="margin:14px 0" data-i18n-text data-text-ka="<?=h((string)$n['title'])?>" data-text-en="<?=h($titleEn)?>"><?=h($n['title'])?></h1>
    <div class="meta"><?=h(fmt_date($n['published_at'] ?? ''))?></div>

    <?php if (!empty($n['image_path'])): ?>
      <img class="heroimg" src="/youthagency/<?=h($n['image_path'])?>" alt="">
    <?php endif; ?>

    <?php if ($bodyText !== '' || $bodyTextEn !== ''): ?>
      <div style="margin-top:18px;line-height:1.7;white-space:pre-wrap" data-i18n-text data-text-ka="<?=h($bodyText)?>" data-text-en="<?=h($bodyTextEn)?>"><?=h($bodyText !== '' ? $bodyText : $bodyTextEn)?></div>
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

  <script src="/youthagency/app.js?v=1"></script>
</body>
</html>
