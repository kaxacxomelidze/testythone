<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../db.php';

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

if (empty($_SESSION['admin_logged_in']) || (int)$_SESSION['admin_logged_in'] !== 1) {
  http_response_code(401);
  exit('Unauthorized');
}

$campId = (int)($_GET['campId'] ?? 0);
$status = trim((string)($_GET['status'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

if ($campId <= 0) { http_response_code(400); exit('Bad campId'); }

/* ------------------ PHP 7 COMPAT: str_contains ------------------ */
if (!function_exists('str_contains')) {
  function str_contains(string $haystack, string $needle): bool {
    return $needle === '' || strpos($haystack, $needle) !== false;
  }
}

function asArrayValues($values): array {
  if (!$values) return [];
  if (is_string($values)) {
    $j = json_decode($values, true);
    if (json_last_error() === JSON_ERROR_NONE) $values = $j;
    else return [$values];
  }
  if (is_array($values)) {
    $isAssoc = array_keys($values) !== range(0, count($values) - 1);
    return $isAssoc ? array_values($values) : $values;
  }
  return [$values];
}

try {
  /* ------------------- LOAD FIELDS ------------------- */
  $fs = $pdo->prepare("SELECT id,label FROM camps_fields WHERE camp_id=? ORDER BY sort_order ASC, id ASC");
  $fs->execute([$campId]);
  $fields = $fs->fetchAll(PDO::FETCH_ASSOC);

  /* ------------------- LOAD ROWS ------------------- */
  $where = ["camp_id=?"];
  $args = [$campId];

  if ($status !== '' && in_array($status, ['pending','approved','rejected'], true)) {
    $where[] = "status=?";
    $args[] = $status;
  }

  $sql = "SELECT id,created_at,unique_key,status,admin_note,values_json
          FROM camps_registrations
          WHERE ".implode(" AND ", $where)."
          ORDER BY id DESC";
  $st = $pdo->prepare($sql);
  $st->execute($args);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // optional search (q) inside unique_key + values_json
  if ($q !== '') {
    $qq = mb_strtolower($q, 'UTF-8');
    $rows = array_values(array_filter($rows, function($r) use ($qq){
      $u = mb_strtolower((string)($r['unique_key'] ?? ''), 'UTF-8');
      $v = mb_strtolower((string)($r['values_json'] ?? ''), 'UTF-8');
      return str_contains($u, $qq) || str_contains($v, $qq);
    }));
  }

  /* ------------------- BUILD EXCEL-COMPATIBLE TSV ------------------- */
  $filename = "applicants_{$campId}_" . date("Ymd_His") . ".xls";
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Cache-Control: max-age=0');

  $out = fopen('php://output', 'wb');
  if ($out === false) {
    throw new RuntimeException('Cannot open output stream');
  }

  // UTF-8 BOM helps Excel open Georgian text correctly
  fwrite($out, "\xEF\xBB\xBF");

  $headers = ["ID","Created","Unique","Status","Note"];
  foreach ($fields as $f) $headers[] = (string)$f['label'];
  fputcsv($out, $headers, "\t");

  foreach ($rows as $row) {
    $vals = asArrayValues($row['values_json'] ?? '');
    $line = [
      (string)($row['id'] ?? ''),
      (string)($row['created_at'] ?? ''),
      (string)($row['unique_key'] ?? ''),
      (string)($row['status'] ?? ''),
      (string)($row['admin_note'] ?? ''),
    ];

    for ($i=0; $i<count($fields); $i++) {
      $line[] = isset($vals[$i]) ? (string)$vals[$i] : '';
    }

    fputcsv($out, $line, "\t");
  }

  fclose($out);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo "Export error: " . $e->getMessage();
  exit;
}
