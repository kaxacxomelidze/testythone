<?php
require_once __DIR__ . '/admin/config.php';
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$stmt = $pdo->prepare("
  SELECT id,title,slug,body,image_path,published_at,is_active
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


    <h1 style="margin:14px 0"><?=h($n['title'])?></h1>
    <div class="meta"><?=h(fmt_date($n['published_at'] ?? ''))?></div>

    <?php if (!empty($n['image_path'])): ?>
      <img class="heroimg" src="/youthagency/<?=h($n['image_path'])?>" alt="">
    <?php endif; ?>

    <?php if (!empty($n['body'])): ?>
      <div style="margin-top:18px;line-height:1.7">
        <?=nl2br(h($n['body']))?>
      </div>
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
</body>
</html>
