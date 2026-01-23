<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../db.php';

if (empty($_SESSION['admin_logged_in']) || (int)$_SESSION['admin_logged_in'] !== 1) {
  http_response_code(401);
  exit('Unauthorized');
}

$campId = (int)($_GET['campId'] ?? 0);
$status = trim((string)($_GET['status'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

if ($campId <= 0) { http_response_code(400); exit('Bad campId'); }

// ✅ Install with composer in /youthagency/admin/
// composer require phpoffice/phpspreadsheet
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function asArrayValues($values): array {
  if (!$values) return [];
  if (is_string($values)) {
    $j = json_decode($values, true);
    if (json_last_error() === JSON_ERROR_NONE) $values = $j;
    else return [$values];
  }
  if (is_array($values)) {
    // associative object -> values
    $isAssoc = array_keys($values) !== range(0, count($values) - 1);
    return $isAssoc ? array_values($values) : $values;
  }
  return [$values];
}

/* ------------------- LOAD FIELDS ------------------- */
$fs = $pdo->prepare("SELECT id,label FROM camps_fields WHERE camp_id=? ORDER BY sort_order ASC, id ASC");
$fs->execute([$campId]);
$fields = $fs->fetchAll(PDO::FETCH_ASSOC);

/* ------------------- LOAD ROWS ------------------- */
$where = ["camp_id=?"];
$args = [$campId];

if ($status !== '') { $where[] = "status=?"; $args[] = $status; }

$sql = "SELECT id,created_at,unique_key,status,admin_note,values_json
        FROM camps_registrations
        WHERE ".implode(" AND ", $where)."
        ORDER BY id DESC";
$st = $pdo->prepare($sql);
$st->execute($args);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// optional search (q) inside unique_key + values_json
if ($q !== '') {
  $qq = mb_strtolower($q);
  $rows = array_values(array_filter($rows, function($r) use ($qq){
    $u = mb_strtolower((string)($r['unique_key'] ?? ''));
    $v = mb_strtolower((string)($r['values_json'] ?? ''));
    return str_contains($u, $qq) || str_contains($v, $qq);
  }));
}

/* ------------------- BUILD XLSX ------------------- */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Applicants");

$headers = ["ID","Created","Unique","Status","Note"];
foreach ($fields as $f) $headers[] = (string)$f['label'];

$sheet->fromArray($headers, null, 'A1');

$r = 2;
foreach ($rows as $row) {
  $vals = asArrayValues($row['values_json'] ?? '');

  $base = [
    $row['id'] ?? '',
    $row['created_at'] ?? '',
    $row['unique_key'] ?? '',
    $row['status'] ?? '',
    $row['admin_note'] ?? '',
  ];

  // pad to fields count
  $extra = [];
  for ($i=0; $i<count($fields); $i++) $extra[] = $vals[$i] ?? '';

  $sheet->fromArray(array_merge($base, $extra), null, 'A'.$r);
  $r++;
}

// simple autosize (cheap)
$maxCol = $sheet->getHighestColumn();
foreach (range('A', $maxCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$filename = "applicants_{$campId}_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
