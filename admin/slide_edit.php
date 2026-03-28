<?php
require_once __DIR__ . '/config.php';
require_login();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM slides WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
  header('Location: index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? '');

  $title = trim($_POST['title'] ?? '');   // optional
  $link  = trim($_POST['link'] ?? '');    // optional
  $order = (int)($_POST['order'] ?? 0);
  $image_path = $s['image_path'];

  // optional image replace
  if (!empty($_FILES['image']['name'])) {
    $f = $_FILES['image'];
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime = mime_content_type($f['tmp_name']) ?: '';

    if (!isset($allowed[$mime])) {
      $error = 'Allowed: JPG, PNG, WebP';
    } else {
      $ext  = $allowed[$mime];
      $base = 'slide_' . bin2hex(random_bytes(8));
      $name = $base . '.webp';

      $dir = UPLOAD_DIR . '/slides';
      if (!is_dir($dir)) mkdir($dir, 0775, true);

      $dest = $dir . '/' . $name;

      if (!convert_image_to_webp($f['tmp_name'], $dest, 90)) {
        $name = $base . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
          $error = 'Upload failed.';
        }
      }
      if (!$error) {
        // delete old image
        $old = __DIR__ . '/../' . $image_path;
        if (is_file($old)) @unlink($old);

        $image_path = 'uploads/slides/' . $name;
      }
    }
  }

  if (!$error) {
    // Optional: store NULL instead of empty string (only if DB columns allow NULL)
    // $titleDb = ($title === '') ? null : $title;
    // $linkDb  = ($link === '') ? null : $link;

    $titleDb = $title; // keep as empty string
    $linkDb  = $link;  // keep as empty string

    $pdo->prepare("
      UPDATE slides
      SET title=?, link=?, image_path=?, sort_order=?
      WHERE id=?
    ")->execute([$titleDb, $linkDb, $image_path, $order, $id]);

    header('Location: index.php');
    exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit slide</title>
  <style>
    body{font-family:system-ui;background:#f3f4f7;margin:0}
    .wrap{max-width:700px;margin:0 auto;padding:22px}
    .box{background:#fff;border-radius:14px;padding:16px}
    input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;margin:8px 0}
    button{padding:10px 14px;border-radius:10px;border:0;background:#364379;color:#fff;font-weight:700;cursor:pointer}
    a{color:#364379;text-decoration:none;font-weight:700}
    img{width:260px;height:120px;object-fit:cover;border-radius:12px;border:1px solid #eee}
    .err{color:#b00020}
    .muted{color:#667085;font-size:13px}
  </style>
</head>
<body>
<div class="wrap">
  <a href="index.php">← Back</a>
  <h2>Edit slide</h2>

  <form class="box" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <?php if($error): ?><div class="err"><?=h($error)?></div><?php endif; ?>

    <div style="margin-bottom:10px">
      <img src="<?=h('../' . $s['image_path'])?>" alt="">
    </div>

    <label>Title (optional)</label>
    <input name="title" value="<?=h($s['title'] ?? '')?>">
    <div class="muted">Leave empty to show only image.</div>

    <label>Link (optional)</label>
    <input name="link" value="<?=h($s['link'] ?? '')?>">
    <div class="muted">Leave empty to hide button.</div>

    <label>Order</label>
    <input name="order" type="number" value="<?=h($s['sort_order'] ?? 0)?>">

    <label>Replace image (optional)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

    <button type="submit">Save changes</button>
  </form>
</div>
</body>
</html>
