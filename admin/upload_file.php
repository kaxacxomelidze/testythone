<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/config.php';
require_login();
security_headers(false);

$id = (int)($_GET['id'] ?? 0);
$mode = strtolower(trim((string)($_GET['mode'] ?? 'open')));
$inline = $mode !== 'download';

if ($id <= 0) {
  http_response_code(400);
  exit('Invalid file id');
}

$pdo = db();
$st = $pdo->prepare("
  SELECT id, file_path, original_name, stored_name, mime_type
  FROM grant_uploads
  WHERE id = ? AND deleted_at IS NULL
  LIMIT 1
");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  exit('File not found');
}

$rawPath = trim((string)($row['file_path'] ?? ''));
if ($rawPath === '') {
  http_response_code(404);
  exit('File path is empty');
}

$uploadsBase = realpath(UPLOAD_DIR);
if ($uploadsBase === false) {
  http_response_code(500);
  exit('Upload directory missing');
}

if (preg_match('~^[a-zA-Z]:[\\\\/]~', $rawPath) || str_starts_with($rawPath, '/')) {
  $candidate = $rawPath;
} else {
  $candidate = dirname(__DIR__) . '/' . ltrim($rawPath, '/');
}

$fileReal = realpath($candidate);
if ($fileReal === false || !is_file($fileReal)) {
  http_response_code(404);
  exit('File does not exist');
}

$uploadsBaseNorm = rtrim(str_replace('\\', '/', $uploadsBase), '/');
$fileRealNorm = str_replace('\\', '/', $fileReal);
if ($fileRealNorm !== $uploadsBaseNorm && !str_starts_with($fileRealNorm, $uploadsBaseNorm . '/')) {
  http_response_code(403);
  exit('Access denied');
}

$mime = trim((string)($row['mime_type'] ?? ''));
if ($mime === '') {
  $detected = @mime_content_type($fileReal);
  $mime = is_string($detected) && $detected !== '' ? $detected : 'application/octet-stream';
}

$downloadName = trim((string)($row['original_name'] ?? ''));
if ($downloadName === '') {
  $downloadName = trim((string)($row['stored_name'] ?? basename($fileReal)));
}
$downloadName = preg_replace('/[\\r\\n]+/', ' ', $downloadName) ?: 'file';

if (!headers_sent()) {
  header('Content-Type: ' . $mime);
  header('Content-Length: ' . (string)filesize($fileReal));
  header('X-Content-Type-Options: nosniff');
  header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($downloadName) . '"');
  header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');
}

readfile($fileReal);
exit;
