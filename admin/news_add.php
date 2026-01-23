<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();
$error = '';

function slugify(string $text): string {
  $text = trim(mb_strtolower($text, 'UTF-8'));
  $text = preg_replace('~[^\pL\pN]+~u', '-', $text);
  $text = trim($text, '-');
  return mb_substr($text, 0, 180, 'UTF-8');
}
function saveUpload(string $tmp, string $folder, string $prefix, string $mime): string {
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($allowed[$mime])) throw new RuntimeException('Allowed: JPG, PNG, WebP');
  $ext=$allowed[$mime];
  $name=$prefix.'_'.bin2hex(random_bytes(8)).'.'.$ext;
  $dir=UPLOAD_DIR.'/'.$folder; if(!is_dir($dir)) mkdir($dir,0775,true);
  $dest=$dir.'/'.$name;
  if(!move_uploaded_file($tmp,$dest)) throw new RuntimeException('Upload failed.');
  return 'uploads/'.$folder.'/'.$name;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_check($_POST['csrf'] ?? '');
  $title = trim($_POST['title'] ?? '');
  $body  = trim($_POST['body'] ?? '');
  $order = (int)($_POST['order'] ?? 0);
  $active= !empty($_POST['is_active']) ? 1 : 0;

  if ($title==='') $error='Title is required.';
  else {
    try{
      $slug = slugify($title);

      $mainImg = null;
      if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $mime = mime_content_type($_FILES['image']['tmp_name']) ?: '';
        $mainImg = saveUpload($_FILES['image']['tmp_name'],'news','news',$mime);
      }

      $pdo->prepare("INSERT INTO news(title,slug,body,image_path,sort_order,is_active) VALUES(?,?,?,?,?,?)")
          ->execute([$title,$slug,$body?:null,$mainImg,$order,$active]);

      $id = (int)$pdo->lastInsertId();

      if ($slug==='' || $slug==='-') {
        $slug = 'news-'.$id;
        $pdo->prepare("UPDATE news SET slug=? WHERE id=?")->execute([$slug,$id]);
      }

      header("Location: news_edit.php?id=".$id);
      exit;

    } catch(Throwable $e){ $error=$e->getMessage(); }
  }
}

$title = 'Add News';
ob_start();
?>
<div class="card">
  <a class="btn" href="news.php">← Back</a>
  <h3 style="margin:12px 0">Add News</h3>

  <?php if($error): ?><div style="color:#ef4444;margin-bottom:10px"><?=h($error)?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="display:grid;gap:10px;max-width:720px">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">

    <label class="muted">Title *</label>
    <input name="title" value="<?=h($_POST['title'] ?? '')?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted">Body (optional)</label>
    <textarea name="body" rows="6" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)"><?=h($_POST['body'] ?? '')?></textarea>

    <label class="muted">Order</label>
    <input name="order" type="number" value="<?=h($_POST['order'] ?? 0)?>" style="padding:10px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

    <label class="muted">
      <input type="checkbox" name="is_active" value="1" <?=(!isset($_POST['is_active']) || !empty($_POST['is_active'])) ? 'checked' : ''?>>
      Active
    </label>

    <label class="muted">Image (optional)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

    <button class="btn ac" type="submit">Save</button>
  </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
