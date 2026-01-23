<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

function slugify(string $text): string {
  $text = trim(mb_strtolower($text, 'UTF-8'));
  $text = preg_replace('~[^\pL\pN]+~u', '-', $text);
  $text = trim($text, '-');
  return mb_substr($text, 0, 180, 'UTF-8');
}

function saveUpload(string $tmp, string $folder, string $prefix, string $mime): string {
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($allowed[$mime])) throw new RuntimeException('Allowed: JPG, PNG, WebP');

  $ext  = $allowed[$mime];
  $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

  $dir = UPLOAD_DIR . '/' . $folder;
  if (!is_dir($dir)) mkdir($dir, 0775, true);

  $dest = $dir . '/' . $name;
  if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException('Upload failed.');

  return 'uploads/' . $folder . '/' . $name;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM news WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$n) {
  header('Location: news.php');
  exit;
}

$error = '';

/** delete gallery image */
if (isset($_GET['delimg'])) {
  $imgId = (int)($_GET['delimg'] ?? 0);
  if ($imgId > 0) {
    $pdo->prepare("DELETE FROM news_images WHERE id=? AND news_id=?")->execute([$imgId, $id]);
  }
  header("Location: news_edit.php?id=$id");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? '');

  $title  = trim($_POST['title'] ?? '');
  $body   = trim($_POST['body'] ?? '');
  $order  = (int)($_POST['order'] ?? 0);
  $active = !empty($_POST['is_active']) ? 1 : 0;

  if ($title === '') {
    $error = 'Title is required.';
  } else {
    try {
      $slug = slugify($title);
      if ($slug === '' || $slug === '-') $slug = 'news-' . $id;

      $image_path = $n['image_path'];

      // replace main image (optional)
      if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $mime = mime_content_type($_FILES['image']['tmp_name']) ?: '';
        $image_path = saveUpload($_FILES['image']['tmp_name'], 'news', 'news', $mime);
      }

      $pdo->prepare("
        UPDATE news
        SET title=?, slug=?, body=?, image_path=?, sort_order=?, is_active=?
        WHERE id=?
      ")->execute([$title, $slug, ($body !== '' ? $body : null), $image_path, $order, $active, $id]);

      // add gallery images (optional)
      if (!empty($_FILES['gallery']['tmp_name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $i => $tmp) {
          if (!$tmp || !is_uploaded_file($tmp)) continue;
          $mime = mime_content_type($tmp) ?: '';
          try {
            $path = saveUpload($tmp, 'news_gallery', 'gallery', $mime);
            $pdo->prepare("INSERT INTO news_images(news_id, image_path, sort_order) VALUES(?,?,?)")
                ->execute([$id, $path, $i]);
          } catch (Throwable $e) {
            // skip bad file
          }
        }
      }

      header("Location: news_edit.php?id=$id");
      exit;

    } catch (Throwable $e) {
      $error = $e->getMessage();
    }
  }
}

// reload current row (optional but nice)
$stmt = $pdo->prepare("SELECT * FROM news WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);

// load gallery
$g = [];
try {
  $st = $pdo->prepare("SELECT id,image_path FROM news_images WHERE news_id=? ORDER BY sort_order ASC, id ASC");
  $st->execute([$id]);
  $g = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $g = [];
}

$slug = trim((string)($n['slug'] ?? ''));
if ($slug === '' || $slug === '-' || $slug === 'news') $slug = 'news-' . $id;
$open = "/youthagency/news/" . $id . "/" . $slug;

$titlePage = 'Edit News';
ob_start();
?>

<div class="card">
  <a class="btn" href="news.php">← Back</a>
  <a class="btn" target="_blank" href="<?=h($open)?>">Open public page</a>
  <h3 style="margin:12px 0">Edit News</h3>

  <?php if($error): ?>
    <div style="color:#ef4444;margin-bottom:10px"><?=h($error)?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="display:grid;gap:10px;max-width:720px">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">

    <label class="muted">Title *</label>
    <input name="title" value="<?=h($n['title'] ?? '')?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted">Body</label>
    <textarea name="body" rows="8" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)"><?=h($n['body'] ?? '')?></textarea>

    <label class="muted">Order</label>
    <input name="order" type="number" value="<?=h($n['sort_order'] ?? 0)?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted">
      <input type="checkbox" name="is_active" value="1" <?=((int)($n['is_active'] ?? 0) === 1) ? 'checked' : ''?>>
      Active
    </label>

    <label class="muted">Replace main image (optional)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

    <label class="muted">Add gallery photos (optional, multiple)</label>
    <input type="file" name="gallery[]" accept=".jpg,.jpeg,.png,.webp" multiple>

    <button class="btn ac" type="submit">Save</button>
  </form>
</div>

<?php if (!empty($g)): ?>
  <div class="card">
    <h3 style="margin:0 0 10px">Gallery</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
      <?php foreach($g as $img): ?>
        <div>
          <img src="<?=h('../'.$img['image_path'])?>" style="width:100%;height:140px;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
          <a class="btn bad" style="margin-top:8px;display:inline-block"
             href="news_edit.php?id=<?=h($id)?>&delimg=<?=h($img['id'])?>"
             onclick="return confirm('Delete image?')">Delete</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = $titlePage;
require __DIR__ . '/layout.php';
