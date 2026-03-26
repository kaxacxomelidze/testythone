<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

// --------------------------
// Admin UI language (cookie)
// --------------------------
$lang = (($_COOKIE['lang'] ?? 'ka') === 'en') ? 'en' : 'ka';

function t(string $ka, string $en): string {
  global $lang;
  return $lang === 'en' ? $en : $ka;
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
function add_col(PDO $pdo, string $table, string $sql): void {
  $pdo->exec("ALTER TABLE `$table` $sql");
}
function ensure_news_i18n(PDO $pdo): void {
  if (!has_col($pdo, 'news', 'title_en')) {
    try { add_col($pdo, 'news', "ADD COLUMN title_en VARCHAR(255) NULL AFTER title"); } catch(Throwable $e) {}
  }
  if (!has_col($pdo, 'news', 'body_en')) {
    try { add_col($pdo, 'news', "ADD COLUMN body_en MEDIUMTEXT NULL AFTER body"); } catch(Throwable $e) {}
  }
}

function slugify(string $text): string {
  $text = trim(mb_strtolower($text, 'UTF-8'));
  $text = preg_replace('~[^\pL\pN]+~u', '-', $text);
  $text = trim($text, '-');
  return mb_substr($text, 0, 180, 'UTF-8');
}

function saveUpload(string $tmp, string $folder, string $prefix, string $mime): string {
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
  if (!isset($allowed[$mime])) throw new RuntimeException('Allowed: JPG, PNG, WebP');

  $ext  = $allowed[$mime];
  $base = $prefix . '_' . bin2hex(random_bytes(8));
  $name = $base . '.webp';

  $dir = UPLOAD_DIR . '/' . $folder;
  if (!is_dir($dir)) mkdir($dir, 0775, true);

  $dest = $dir . '/' . $name;
  if (!convert_image_to_webp($tmp, $dest, 90)) {
    $name = $base . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException('Upload failed.');
  }

  return 'uploads/' . $folder . '/' . $name;
}

$id = (int)($_GET['id'] ?? 0);
ensure_news_i18n($pdo);

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

  $title     = trim($_POST['title'] ?? '');
  $title_en  = trim($_POST['title_en'] ?? '');
  $body      = trim($_POST['body'] ?? '');
  $body_en   = trim($_POST['body_en'] ?? '');
  $order     = (int)($_POST['order'] ?? 0);
  $active    = !empty($_POST['is_active']) ? 1 : 0;

  if ($title === '') {
    $error = t('სათაური სავალდებულოა.', 'Title is required.');
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

      $hasTitleEn = has_col($pdo, 'news', 'title_en');
      $hasBodyEn  = has_col($pdo, 'news', 'body_en');

      $set  = "title=?, slug=?, body=?, image_path=?, sort_order=?, is_active=?";
      $vals = [$title, $slug, ($body !== '' ? $body : null), $image_path, $order, $active];

      if ($hasTitleEn) {
        $set .= ", title_en=?";
        $vals[] = ($title_en !== '' ? $title_en : null);
      }
      if ($hasBodyEn) {
        $set .= ", body_en=?";
        $vals[] = ($body_en !== '' ? $body_en : null);
      }

      $vals[] = $id;

      $pdo->prepare("UPDATE news SET {$set} WHERE id=?")->execute($vals);

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

// reload current row
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
$open = "/news/" . $id . "/" . $slug;

$titlePage = t('სიახლის რედაქტირება', 'Edit News');
ob_start();
?>
<div class="card">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <a class="btn" href="news.php">← <?=h(t('უკან', 'Back'))?></a>
    <a class="btn" target="_blank" href="<?=h($open)?>"><?=h(t('საჯარო გვერდის ნახვა', 'Open public page'))?></a>

    <!-- optional quick language buttons for admin -->
    <div style="margin-left:auto;display:flex;gap:6px">
      <a class="btn" href="?id=<?=h($id)?>" onclick="document.cookie='lang=ka; path=/; max-age=31536000; SameSite=Lax'">KA</a>
      <a class="btn" href="?id=<?=h($id)?>" onclick="document.cookie='lang=en; path=/; max-age=31536000; SameSite=Lax'">EN</a>
    </div>
  </div>

  <h3 style="margin:12px 0"><?=h($titlePage)?></h3>

  <?php if($error): ?>
    <div style="color:#ef4444;margin-bottom:10px"><?=h($error)?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="display:grid;gap:10px;max-width:720px">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">

    <label class="muted"><?=h(t('სათაური *', 'Title *'))?></label>
    <input name="title" value="<?=h($n['title'] ?? '')?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted"><?=h(t('სათაური (EN)', 'Title (EN)'))?></label>
    <input name="title_en" value="<?=h($n['title_en'] ?? '')?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted"><?=h(t('ტექსტი', 'Body'))?></label>
    <textarea name="body" rows="8" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)"><?=h($n['body'] ?? '')?></textarea>

    <label class="muted"><?=h(t('ტექსტი (EN)', 'Body (EN)'))?></label>
    <textarea name="body_en" rows="8" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)"><?=h($n['body_en'] ?? '')?></textarea>

    <label class="muted"><?=h(t('რიგითობა', 'Order'))?></label>
    <input name="order" type="number" value="<?=h($n['sort_order'] ?? 0)?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted">
      <input type="checkbox" name="is_active" value="1" <?=((int)($n['is_active'] ?? 0) === 1) ? 'checked' : ''?>>
      <?=h(t('აქტიური', 'Active'))?>
    </label>

    <label class="muted"><?=h(t('მთავარი სურათის შეცვლა (არასავალდებულო)', 'Replace main image (optional)'))?></label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

    <label class="muted"><?=h(t('გალერეის სურათების დამატება (არასავალდებულო, multiple)', 'Add gallery photos (optional, multiple)'))?></label>
    <input type="file" name="gallery[]" accept=".jpg,.jpeg,.png,.webp" multiple>

    <button class="btn ac" type="submit"><?=h(t('შენახვა', 'Save'))?></button>
  </form>
</div>

<?php if (!empty($g)): ?>
  <div class="card">
    <h3 style="margin:0 0 10px"><?=h(t('გალერეა', 'Gallery'))?></h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
      <?php foreach($g as $img): ?>
        <div>
          <img src="<?=h('../'.$img['image_path'])?>" style="width:100%;height:140px;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
          <a class="btn bad" style="margin-top:8px;display:inline-block"
             href="news_edit.php?id=<?=h($id)?>&delimg=<?=h($img['id'])?>"
             onclick="return confirm('<?=h(t('წაშლა?', 'Delete image?'))?>')"><?=h(t('წაშლა', 'Delete'))?></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = $titlePage;
require __DIR__ . '/layout.php';
