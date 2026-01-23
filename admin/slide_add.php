<?php
require_once __DIR__ . '/config.php';
require_login();

$pdo = db();
$error = '';

function detect_mime(string $tmp): string {
  if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
      $m = finfo_file($finfo, $tmp);
      finfo_close($finfo);
      return (string)$m;
    }
  }
  return (string)(mime_content_type($tmp) ?: '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? '');

  $title = trim($_POST['title'] ?? '');   // optional
  $link  = trim($_POST['link'] ?? '');    // optional
  $order = (int)($_POST['order'] ?? 0);

  // ✅ only image required
  if (empty($_FILES['image']['name'])) {
    $error = 'Image is required.';
  } else {
    $f = $_FILES['image'];

    if (!isset($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
      $error = 'Upload failed (no tmp file).';
    } elseif (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      $error = 'Upload error code: ' . (int)$f['error'];
    } elseif (($f['size'] ?? 0) > 8 * 1024 * 1024) {
      $error = 'Max image size is 8MB.';
    } else {
      $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      $mime = detect_mime($f['tmp_name']);

      if (!isset($allowed[$mime])) {
        $error = 'Allowed: JPG, PNG, WebP';
      } else {
        $ext  = $allowed[$mime];
        $name = 'slide_' . bin2hex(random_bytes(8)) . '.' . $ext;

        $dir = UPLOAD_DIR . '/slides';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $dest = $dir . '/' . $name;

        if (!move_uploaded_file($f['tmp_name'], $dest)) {
          $error = 'Upload failed.';
        } else {
          $path = 'uploads/slides/' . $name;

          // keep empty strings if user leaves blank
          $titleDb = $title;
          $linkDb  = $link;

          $stmt = $pdo->prepare("
            INSERT INTO slides (title, link, image_path, sort_order, is_active)
            VALUES (?, ?, ?, ?, 1)
          ");
          $stmt->execute([$titleDb, $linkDb, $path, $order]);

          // ✅ LOG ADMIN ACTIVITY
          $slideId = (int)$pdo->lastInsertId();
          if (function_exists('log_admin')) {
            log_admin('slide_create', 'slides', $slideId, [
              'title' => $titleDb,
              'link'  => $linkDb,
              'image' => $path,
              'order' => $order
            ]);
          }

          header('Location: index.php');
          exit;
        }
      }
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add slide</title>
  <style>
    body{font-family:system-ui;background:#f3f4f7;margin:0}
    .wrap{max-width:700px;margin:0 auto;padding:22px}
    .box{background:#fff;border-radius:14px;padding:16px}
    input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;margin:8px 0}
    button{padding:10px 14px;border-radius:10px;border:0;background:#364379;color:#fff;font-weight:700;cursor:pointer}
    a{color:#364379;text-decoration:none;font-weight:700}
    .err{color:#b00020}
    .muted{color:#667085;font-size:13px}
  </style>
</head>
<body>
<div class="wrap">
  <a href="index.php">← Back</a>
  <h2>Add slide</h2>

  <form class="box" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <?php if($error): ?><div class="err"><?=h($error)?></div><?php endif; ?>

    <label>Title (optional)</label>
    <input name="title" value="<?=h($_POST['title'] ?? '')?>">
    <div class="muted">If empty, slider will show only the image.</div>

    <label>Link (optional)</label>
    <input name="link" value="<?=h($_POST['link'] ?? '')?>">
    <div class="muted">If empty, button won’t appear.</div>

    <label>Order</label>
    <input name="order" type="number" value="<?=h($_POST['order'] ?? 0)?>">

    <label>Image (required)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>

    <button type="submit">Save</button>
  </form>
</div>
</body>
</html>
