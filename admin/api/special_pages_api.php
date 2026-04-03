<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function sp_json_out(array $payload, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}
function sp_ok(array $data = []): void { sp_json_out(['ok'=>true] + $data); }
function sp_err(string $message, int $status = 400): void { sp_json_out(['ok'=>false,'error'=>$message], $status); }

if (!is_logged_in()) sp_err('Unauthorized', 401);

$csrf = (string)($_SESSION['csrf'] ?? '');
$hdr = (string)($_SERVER['HTTP_X_CSRF'] ?? '');
if ($csrf === '' || $hdr === '' || !hash_equals($csrf, $hdr)) sp_err('CSRF failed', 403);

$action = (string)($_GET['action'] ?? '');
if ($action === '') sp_err('action required');

$root = dirname(__DIR__, 2);
$dataFile = $root . '/data/special_pages.json';
$uploadDir = $root . '/uploads/special_pages';

function sp_load_data(string $file): array {
  if (!is_file($file)) return ['pages'=>[]];
  $raw = (string)@file_get_contents($file);
  if ($raw === '') return ['pages'=>[]];
  $j = json_decode($raw, true);
  if (!is_array($j)) return ['pages'=>[]];
  if (!is_array($j['pages'] ?? null)) $j['pages'] = [];
  return $j;
}

function sp_save_data(string $file, array $data): void {
  $dir = dirname($file);
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) sp_err('Cannot create data dir', 500);
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) sp_err('Encode failed', 500);
  if (@file_put_contents($file, $json . "\n", LOCK_EX) === false) sp_err('Cannot save data', 500);
}

function sp_slug(string $value): string {
  $s = trim($value);
  $s = preg_replace('~[^a-zA-Z0-9_\-]+~', '_', $s);
  $s = trim((string)$s, '_-');
  return $s;
}

function sp_save_uploaded_file(array $file, string $uploadDir): ?string {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) sp_err('File upload error');
  if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) sp_err('Invalid file upload');

  if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) sp_err('Cannot create upload dir', 500);

  $orig = trim((string)($file['name'] ?? 'file'));
  $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $ext = preg_replace('~[^a-z0-9]+~', '', $ext);
  if ($ext === '') $ext = 'bin';

  $name = 'sp_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
  $abs = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $name;
  if (!move_uploaded_file((string)$file['tmp_name'], $abs)) sp_err('Failed to move uploaded file', 500);
  return '/uploads/special_pages/' . $name;
}

function sp_ensure_page_folder(string $root, string $slug): void {
  $dir = $root . '/' . $slug;
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) sp_err('Cannot create page folder', 500);

  $indexPath = $dir . '/index.php';
  if (is_file($indexPath)) return;

  $tpl = "<?php\ndeclare(strict_types=1);\n\nrequire_once __DIR__ . '/../special_page_template.php';\n\nrender_special_page('" . addslashes($slug) . "');\n";
  if (@file_put_contents($indexPath, $tpl, LOCK_EX) === false) sp_err('Cannot create page index', 500);
}

$data = sp_load_data($dataFile);

if ($action === 'list') {
  $items = [];
  foreach (($data['pages'] ?? []) as $slug => $page) {
    if (!is_array($page)) continue;
    $items[] = [
      'slug' => (string)$slug,
      'title' => (string)($page['title'] ?? ''),
      'description' => (string)($page['description'] ?? ''),
      'logo' => (string)($page['logo'] ?? ''),
      'links' => is_array($page['links'] ?? null) ? $page['links'] : [],
    ];
  }
  usort($items, fn($a,$b) => strcmp($a['slug'], $b['slug']));
  sp_ok(['items' => $items]);
}

if ($action === 'save') {
  $slug = sp_slug((string)($_POST['slug'] ?? ''));
  $title = trim((string)($_POST['title'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $logo = trim((string)($_POST['logo'] ?? ''));
  $linksJson = (string)($_POST['links_json'] ?? '[]');

  if ($slug === '') sp_err('Slug required');
  if ($title === '') sp_err('Title required');

  $links = json_decode($linksJson, true);
  if (!is_array($links)) $links = [];

  $logoUpload = sp_save_uploaded_file($_FILES['logo_file'] ?? ['error'=>UPLOAD_ERR_NO_FILE], $uploadDir);
  if ($logoUpload !== null) $logo = $logoUpload;

  $cleanLinks = [];
  foreach ($links as $i => $lnk) {
    if (!is_array($lnk)) continue;
    $label = trim((string)($lnk['label'] ?? ''));
    $url = trim((string)($lnk['url'] ?? ''));
    $linkType = trim((string)($lnk['link_type'] ?? 'open'));
    if ($linkType !== 'download' && $linkType !== 'open') $linkType = 'open';

    $fileKey = 'link_file_' . (int)$i;
    $up = sp_save_uploaded_file($_FILES[$fileKey] ?? ['error'=>UPLOAD_ERR_NO_FILE], $uploadDir);
    if ($up !== null) $url = $up;

    if ($label === '' || $url === '') continue;
    $cleanLinks[] = ['label'=>$label,'url'=>$url,'link_type'=>$linkType];
  }

  $data['pages'][$slug] = [
    'title' => $title,
    'description' => $description,
    'logo' => $logo,
    'links' => $cleanLinks,
  ];

  sp_save_data($dataFile, $data);
  sp_ensure_page_folder($root, $slug);
  sp_ok(['slug'=>$slug, 'url'=> '/' . $slug . '/']);
}

if ($action === 'delete') {
  $raw = json_decode((string)file_get_contents('php://input'), true);
  $slug = sp_slug((string)($raw['slug'] ?? ''));
  if ($slug === '') sp_err('Slug required');
  unset($data['pages'][$slug]);
  sp_save_data($dataFile, $data);
  sp_ok();
}

sp_err('Unknown action', 404);
