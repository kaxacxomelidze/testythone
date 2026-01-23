<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function slugify(string $s): string {
  $s = trim(mb_strtolower($s));
  $s = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $s) ?? $s;
  $s = preg_replace('/\s+/u', '-', $s) ?? $s;
  $s = preg_replace('/-+/u', '-', $s) ?? $s;
  $s = trim($s, '-');
  return $s !== '' ? $s : 'grant';
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
}

function upload_image(array $file, string $destDir, string $webPrefix): array {
  // returns [ok=>bool, path=>string, error=>string]
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return ['ok'=>true, 'path'=>'', 'error'=>''];
  }
  if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
    return ['ok'=>false, 'path'=>'', 'error'=>'Upload error'];
  }
  $tmp = (string)$file['tmp_name'];
  $name = (string)$file['name'];

  // size limit 5MB
  if ((int)($file['size'] ?? 0) > 5*1024*1024) {
    return ['ok'=>false,'path'=>'','error'=>'Image too large (max 5MB)'];
  }

  $info = @getimagesize($tmp);
  if (!$info) return ['ok'=>false,'path'=>'','error'=>'Not an image'];

  $mime = $info['mime'] ?? '';
  $ext = match($mime){
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default => ''
  };
  if ($ext === '') return ['ok'=>false,'path'=>'','error'=>'Only JPG/PNG/WEBP allowed'];

  ensure_dir($destDir);
  $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
  $base = trim($base, '-') ?: 'grant';
  $fn = $base . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;

  $abs = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fn;
  if (!move_uploaded_file($tmp, $abs)) {
    return ['ok'=>false,'path'=>'','error'=>'Failed to move upload'];
  }

  $webPath = rtrim($webPrefix, '/') . '/' . $fn;
  return ['ok'=>true,'path'=>$webPath,'error'=>''];
}

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$row = [
  'title'=>'',
  'slug'=>'',
  'description'=>'',
  'body'=>'',
  'image_path'=>'',
  'deadline'=>null,
  'status'=>'current',
  'apply_url'=>'',
  'sort_order'=>100,
  'is_active'=>1,
];

if ($isEdit) {
  $st = $pdo->prepare("SELECT * FROM grants WHERE id=?");
  $st->execute([$id]);
  $dbRow = $st->fetch(PDO::FETCH_ASSOC);
  if (!$dbRow) { http_response_code(404); echo "Not found"; exit; }
  $row = array_merge($row, $dbRow);
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $slug  = trim((string)($_POST['slug'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $body = trim((string)($_POST['body'] ?? ''));
  $deadline = trim((string)($_POST['deadline'] ?? ''));
  $status = (string)($_POST['status'] ?? 'current');
  $apply_url = trim((string)($_POST['apply_url'] ?? ''));
  $sort_order = (int)($_POST['sort_order'] ?? 100);
  $is_active = (int)($_POST['is_active'] ?? 1);

  if ($title === '') $err = "Title is required.";

  if ($slug === '') $slug = slugify($title);
  else $slug = slugify($slug);

  if (!in_array($status, ['current','closed'], true)) $status = 'current';
  if ($deadline === '') $deadline = null;

  // image upload (optional)
  $uploadDirAbs = __DIR__ . '/../uploads/grants';
  $uploadWebPrefix = '/youthagency/uploads/grants';
  $up = upload_image($_FILES['image'] ?? [], $uploadDirAbs, $uploadWebPrefix);
  if (!$up['ok']) $err = $up['error'];

  $image_path = $row['image_path'] ?? '';
  if ($up['ok'] && $up['path'] !== '') {
    $image_path = $up['path'];
  }

  if ($err === '') {
    if ($isEdit) {
      $st = $pdo->prepare("
        UPDATE grants SET
          title=?, slug=?, description=?, body=?, image_path=?, deadline=?, status=?, apply_url=?,
          sort_order=?, is_active=?, updated_at=NOW()
        WHERE id=?
      ");
      $st->execute([$title,$slug,$description,$body,$image_path,$deadline,$status,$apply_url,$sort_order,$is_active,$id]);
    } else {
      $st = $pdo->prepare("
        INSERT INTO grants (title,slug,description,body,image_path,deadline,status,apply_url,sort_order,is_active,created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
      ");
      $st->execute([$title,$slug,$description,$body,$image_path,$deadline,$status,$apply_url,$sort_order,$is_active]);
      $id = (int)$pdo->lastInsertId();
      $isEdit = true;
    }
    header("Location: grants.php"); exit;
  }

  $row = [
    'title'=>$title,
    'slug'=>$slug,
    'description'=>$description,
    'body'=>$body,
    'image_path'=>$image_path,
    'deadline'=>$deadline,
    'status'=>$status,
    'apply_url'=>$apply_url,
    'sort_order'=>$sort_order,
    'is_active'=>$is_active,
  ];
}
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin • <?= $isEdit ? 'Edit Grant' : 'Add Grant' ?></title>
  <link rel="stylesheet" href="assets.css?v=1">
  <style>
    body{font-family:system-ui,Arial;margin:0;background:#f7f7fb}
    .wrap{max-width:980px;margin:0 auto;padding:18px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:860px){.row{grid-template-columns:1fr}}
    label{font-size:12px;color:#64748b;font-weight:900;display:block;margin-bottom:6px}
    input,textarea,select{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:12px;outline:none}
    textarea{min-height:140px;resize:vertical}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:#111;font-weight:900;background:#fff;cursor:pointer}
    .btn.primary{background:#111;color:#fff;border-color:#111}
    .top{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
    .err{margin:10px 0;padding:10px 12px;border:1px solid #ef444433;background:#ef44440f;border-radius:12px;color:#991b1b;font-weight:900}
    .imgprev{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;background:#f1f5f9}
    .imgprev img{width:100%;height:220px;object-fit:cover;display:block}
    .hint{color:#64748b;font-size:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div style="display:flex;align-items:center;gap:10px">
        <a class="btn" href="grants.php">← Back</a>
        <h2 style="margin:0"><?= $isEdit ? 'Edit Grant' : 'Add Grant' ?></h2>
      </div>
      <div class="hint">Images save to: <code>/uploads/grants</code></div>
    </div>

    <?php if($err): ?><div class="err"><?=h($err)?></div><?php endif; ?>

    <form class="card" method="post" enctype="multipart/form-data">
      <div class="row">
        <div>
          <label>Title *</label>
          <input name="title" value="<?=h($row['title'])?>" required>
        </div>
        <div>
          <label>Slug (auto if empty)</label>
          <input name="slug" value="<?=h($row['slug'])?>" placeholder="example-slug">
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <div>
          <label>Deadline</label>
          <input type="date" name="deadline" value="<?=h((string)($row['deadline'] ?? ''))?>">
        </div>
        <div>
          <label>Status</label>
          <select name="status">
            <option value="current" <?= $row['status']==='current'?'selected':'' ?>>current</option>
            <option value="closed"  <?= $row['status']==='closed'?'selected':'' ?>>closed</option>
          </select>
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <div>
          <label>Apply URL (button link)</label>
          <input name="apply_url" value="<?=h($row['apply_url'])?>" placeholder="/youthagency/grants_apply.php">
        </div>
        <div>
          <label>Sort order (smaller = earlier)</label>
          <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>">
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <div>
          <label>Active</label>
          <select name="is_active">
            <option value="1" <?= (int)$row['is_active']===1?'selected':'' ?>>Yes</option>
            <option value="0" <?= (int)$row['is_active']===0?'selected':'' ?>>No</option>
          </select>
        </div>
        <div>
          <label>Image (JPG/PNG/WEBP, max 5MB)</label>
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
        </div>
      </div>

      <?php if(!empty($row['image_path'])): ?>
        <div style="margin-top:12px" class="imgprev">
          <img src="<?=h($row['image_path'])?>" alt="">
        </div>
      <?php endif; ?>

      <div style="margin-top:12px">
        <label>Short description (card text)</label>
        <textarea name="description"><?=h($row['description'])?></textarea>
      </div>

      <div style="margin-top:12px">
        <label>Full text (detail page)</label>
        <textarea name="body" style="min-height:220px"><?=h($row['body'])?></textarea>
      </div>

      <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn primary" type="submit">Save</button>
        <a class="btn" href="grants.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
