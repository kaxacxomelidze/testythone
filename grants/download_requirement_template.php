<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';

$pdo = db();
$grantId = (int)($_GET['grant_id'] ?? 0);
$reqId = (int)($_GET['req_id'] ?? 0);

if ($grantId <= 0 || $reqId <= 0) {
  http_response_code(400);
  exit('არასწორი მოთხოვნა');
}

$hasEnabled = false;
try {
  $q = $pdo->query("SHOW COLUMNS FROM grant_file_requirements LIKE 'is_enabled'");
  $hasEnabled = (bool)$q->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $hasEnabled = false;
}

$sql = "SELECT template_file_path, template_file_name FROM grant_file_requirements WHERE id=? AND grant_id=? ";
if ($hasEnabled) $sql .= "AND is_enabled=1 ";
$sql .= "LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([$reqId, $grantId]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) {
  http_response_code(404);
  exit('ფაილი ვერ მოიძებნა');
}

$rel = trim((string)($row['template_file_path'] ?? ''));
if ($rel === '') {
  http_response_code(404);
  exit('ფაილი ვერ მოიძებნა');
}

$safeRel = ltrim(str_replace('..', '', str_replace('\\', '/', $rel)), '/');
$abs = realpath(__DIR__ . '/../' . $safeRel);
$base = realpath(__DIR__ . '/../uploads/grants/requirements');

if (!$abs || !$base || !str_starts_with($abs, $base . DIRECTORY_SEPARATOR) || !is_file($abs)) {
  http_response_code(404);
  exit('ფაილი ვერ მოიძებნა');
}

$name = trim((string)($row['template_file_name'] ?? ''));
if ($name === '') $name = basename($abs);

$mime = (string)(mime_content_type($abs) ?: 'application/octet-stream');

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($name));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . (string)filesize($abs));

readfile($abs);
exit;
