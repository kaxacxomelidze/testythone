<?php
declare(strict_types=1);

require __DIR__ . "/../inc/db.php";
$pdo = db();

/* ----------------- JSON OUTPUT + NO CACHE ----------------- */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!function_exists('json_out')) {
  function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }
}

/* ----------------- DEBUG MODE (localhost shows real error) ----------------- */
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1'));
$DEBUG = $isLocal;

/* ----------------- HELPERS ----------------- */
function norm_phone(string $s): string {
  $s = trim($s);
  $s = preg_replace('~[^\d\+]~u', '', $s) ?? '';
  return $s;
}

function safe_ext(string $ext): string {
  $ext = strtolower(trim($ext));
  $ext = preg_replace('~[^a-z0-9]~', '', $ext) ?? '';
  return $ext;
}

function table_exists(PDO $pdo, string $table): bool {
  try {
    $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
    return true;
  } catch (Throwable $e) {
    return false;
  }
}

function pick_table(PDO $pdo, array $candidates): string {
  foreach ($candidates as $t) {
    if (table_exists($pdo, $t)) return $t;
  }
  return '';
}

function get_columns(PDO $pdo, string $table): array {
  $cols = [];
  $st = $pdo->query("DESCRIBE {$table}");
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = (string)$r['Field'];
  }
  return $cols;
}

function pick_col(array $cols, array $candidates, string $fallback = ''): string {
  foreach ($candidates as $c) {
    if (in_array($c, $cols, true)) return $c;
  }
  return $fallback;
}

function upload_file(string $inputName, int $campId, int $fieldId, string $uploadDir): array {
  if (empty($_FILES[$inputName]) || !isset($_FILES[$inputName]['error'])) {
    return ['ok' => false, 'path' => '', 'error' => 'No file uploaded'];
  }
  if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'path' => '', 'error' => 'Upload error'];
  }

  $maxBytes = 8 * 1024 * 1024; // 8 MB
  $size = (int)($_FILES[$inputName]['size'] ?? 0);
  if ($size <= 0 || $size > $maxBytes) {
    return ['ok' => false, 'path' => '', 'error' => 'File too large (max 8MB)'];
  }

  $tmp  = (string)$_FILES[$inputName]['tmp_name'];
  $orig = basename((string)$_FILES[$inputName]['name']);
  $ext  = safe_ext(pathinfo($orig, PATHINFO_EXTENSION));

  $allowed = ['jpg','jpeg','png','webp','gif','pdf','doc','docx'];
  if ($ext === '' || !in_array($ext, $allowed, true)) {
    return ['ok' => false, 'path' => '', 'error' => 'Invalid file type'];
  }

  if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
  }

  $name = "camp{$campId}_field{$fieldId}_" . bin2hex(random_bytes(10)) . "." . $ext;
  $dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $name;

  if (!move_uploaded_file($tmp, $dest)) {
    return ['ok' => false, 'path' => '', 'error' => 'Upload failed (check permissions)'];
  }

  // IMPORTANT: must match where your web server serves uploads
  $publicPath = "/youthagency/uploads/camps/" . $name;

  return ['ok' => true, 'path' => $publicPath, 'error' => ''];
}

/* ----------------- INPUT ----------------- */
$campId = (int)($_POST["camp_id"] ?? 0);
if ($campId <= 0) json_out(["ok" => false, "error" => "Bad camp"], 400);

/* ----------------- TABLE DETECTION ----------------- */
$campsTable = pick_table($pdo, ['camps']);
if ($campsTable === '') json_out(["ok"=>false, "error"=>"DB error: camps table not found"], 500);

$fieldsTable = pick_table($pdo, ['camps_fields', 'camp_fields']);
if ($fieldsTable === '') json_out(["ok"=>false, "error"=>"DB error: fields table not found (camps_fields/camp_fields)"], 500);

$regsTable = pick_table($pdo, ['camps_registrations', 'camp_registrations']);
if ($regsTable === '') json_out(["ok"=>false, "error"=>"DB error: registrations table not found (camps_registrations/camp_registrations)"], 500);

$valsTable = pick_table($pdo, ['camps_registration_values', 'camp_registration_values']);
if ($valsTable === '') json_out(["ok"=>false, "error"=>"DB error: values table not found (camps_registration_values/camp_registration_values)"], 500);

$pidBlockTable = pick_table($pdo, ['camp_pid_blocklist', 'camps_pid_blocklist']); // optional

/* ----------------- LOAD CAMP ----------------- */
$campCols = get_columns($pdo, $campsTable);

$campClosedCol = pick_col($campCols, ['manual_closed', 'closed'], 'closed');
$campEndCol    = pick_col($campCols, ['end_date'], 'end_date');
$campWindowCol = pick_col($campCols, ['window_days'], 'window_days');

$st = $pdo->prepare("SELECT * FROM {$campsTable} WHERE id=? LIMIT 1");
$st->execute([$campId]);
$camp = $st->fetch(PDO::FETCH_ASSOC);
if (!$camp) json_out(["ok" => false, "error" => "Camp not found"], 404);

/* ----------------- CHECK CLOSED ----------------- */
$today = date("Y-m-d");
$manualClosed = (int)($camp[$campClosedCol] ?? 0) === 1;
$endDate = (string)($camp[$campEndCol] ?? '');

if ($manualClosed || ($endDate !== '' && $endDate < $today)) {
  json_out(["ok" => false, "error" => "Camp closed"], 400);
}

/* ----------------- LOAD FIELDS ----------------- */
$fieldCols = get_columns($pdo, $fieldsTable);

$fieldCampIdCol   = pick_col($fieldCols, ['camp_id'], 'camp_id');
$fieldLabelCol    = pick_col($fieldCols, ['label'], 'label');
$fieldTypeCol     = pick_col($fieldCols, ['type'], 'type');
$fieldReqCol      = pick_col($fieldCols, ['required'], 'required');
$fieldSortCol     = pick_col($fieldCols, ['sort_order'], 'sort_order');
$fieldOptionsCol  = pick_col($fieldCols, ['options_json', 'options_text'], 'options_json');

$f = $pdo->prepare("
  SELECT id, {$fieldLabelCol} AS label, {$fieldTypeCol} AS type, {$fieldReqCol} AS required, {$fieldOptionsCol} AS options_any
  FROM {$fieldsTable}
  WHERE {$fieldCampIdCol} = ?
  ORDER BY {$fieldSortCol} ASC, id ASC
");
$f->execute([$campId]);
$fields = $f->fetchAll(PDO::FETCH_ASSOC);

if (!$fields) {
  json_out(["ok" => false, "error" => "No fields configured for this camp"], 400);
}

/* ----------------- COLLECT + VALIDATE ----------------- */
$values = []; // field_id => [value_text, file_path]
$errors = [];

$pid = "";
$email = "";
$phone = "";

/* optional PID blocklist support */
$hasPidBlocklist = ($pidBlockTable !== '' && table_exists($pdo, $pidBlockTable));

$uploadDir = __DIR__ . "/../uploads/camps";

foreach ($fields as $field) {
  $fid = (int)($field["id"] ?? 0);
  $type = (string)($field["type"] ?? '');
  $required = (int)($field["required"] ?? 0) === 1;
  $label = (string)($field["label"] ?? ("Field #".$fid));
  $key = "f_" . $fid;

  $valText = null;
  $filePath = null;

  if ($type === "file") {
    if (!empty($_FILES[$key]) && ($_FILES[$key]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
      $up = upload_file($key, $campId, $fid, $uploadDir);
      if (!$up['ok']) {
        $errors[] = $label . ": " . $up['error'];
      } else {
        $filePath = $up['path'];
      }
    } else {
      if ($required) $errors[] = $label . " is required";
    }
  } else {
    $valText = trim((string)($_POST[$key] ?? ""));

    if ($required && $valText === "") {
      $errors[] = $label . " is required";
    }

    if ($type === "email" && $valText !== "") {
      $valText = mb_strtolower($valText);
      if (!filter_var($valText, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $label . " invalid email";
      } else {
        $email = $valText;
      }
    }

    if ($type === "phone" && $valText !== "") {
      $valText = norm_phone($valText);
      $phone = $valText;
    }

    if ($type === "pid" && $valText !== "") {
      $valText = preg_replace('~\D~', '', $valText) ?? '';
      $pid = $valText;
    }

    if ($type === "select" && $valText !== "") {
      $raw = (string)($field["options_any"] ?? '');
      $opts = [];

      // JSON {"choices":[...]} OR comma-separated
      $j = json_decode($raw, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($j) && isset($j['choices']) && is_array($j['choices'])) {
        $opts = array_values(array_filter(array_map('trim', array_map('strval', $j['choices']))));
      } else {
        $opts = array_values(array_filter(array_map("trim", explode(",", $raw))));
      }

      if ($opts && !in_array($valText, $opts, true)) {
        $errors[] = $label . " invalid option";
      }
    }
  }

  $values[$fid] = ["value_text" => $valText, "file_path" => $filePath];
}

/* ----------------- PID BLOCKLIST (optional) ----------------- */
if ($hasPidBlocklist && $pid !== "") {
  try {
    $b = $pdo->prepare("SELECT id FROM {$pidBlockTable} WHERE pid = ? AND (camp_id IS NULL OR camp_id = ?) LIMIT 1");
    $b->execute([$pid, $campId]);
    if ($b->fetch(PDO::FETCH_ASSOC)) {
      // Do not expose block status to end users. Pretend success, but do not save.
      json_out(["ok" => true, "id" => null, "message" => "You registered ✅"]);
    }
  } catch (Throwable $e) {
    // if blocklist table schema differs, ignore safely
  }
}

/* ----------------- ERRORS ----------------- */
if ($errors) {
  json_out(["ok" => false, "error" => "forms are not filled: " . implode(" | ", $errors)], 400);
}

/* ----------------- UNIQUE KEY ----------------- */
$unique = "";
if ($pid !== "") $unique = "pid:" . $pid;
else if ($email !== "") $unique = "email:" . $email;
else if ($phone !== "") $unique = "phone:" . $phone;

if ($unique === "") {
  json_out(["ok" => false, "error" => "Need PID or Email or Phone field. Add one in admin form."], 400);
}

/* ----------------- WINDOW DAYS RULE ----------------- */
$windowDays = (int)($camp[$campWindowCol] ?? 0);
if ($windowDays > 0) {
  $since = date("Y-m-d H:i:s", time() - ($windowDays * 86400));
  $chk = $pdo->prepare("SELECT id FROM {$regsTable} WHERE camp_id=? AND unique_key=? AND created_at >= ? LIMIT 1");
  $chk->execute([$campId, $unique, $since]);
  if ($chk->fetch(PDO::FETCH_ASSOC)) {
    json_out(["ok" => false, "error" => "You already registered recently (windowDays rule)."], 400);
  }
}

/* ----------------- SAVE ----------------- */
$regsCols = get_columns($pdo, $regsTable);
$valsCols = get_columns($pdo, $valsTable);

// required columns check (so we don't explode silently)
$needRegs = ['camp_id','unique_key'];
foreach ($needRegs as $c) {
  if (!in_array($c, $regsCols, true)) {
    json_out(["ok"=>false,"error"=>"DB schema error: {$regsTable} missing column {$c}"], 500);
  }
}
$needVals = ['registration_id','field_id'];
foreach ($needVals as $c) {
  if (!in_array($c, $valsCols, true)) {
    json_out(["ok"=>false,"error"=>"DB schema error: {$valsTable} missing column {$c}"], 500);
  }
}

$pdo->beginTransaction();

try {
  $ip = $_SERVER["REMOTE_ADDR"] ?? "";
  $ua = substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 255);

  // build dynamic insert for registrations (only columns that exist)
  $cols = ['camp_id','unique_key'];
  $vals = [$campId, $unique];

  if (in_array('ip', $regsCols, true)) { $cols[]='ip'; $vals[]=$ip; }
  if (in_array('user_agent', $regsCols, true)) { $cols[]='user_agent'; $vals[]=$ua; }

  $place = implode(',', array_fill(0, count($cols), '?'));
  $colsSql = implode(',', $cols);

  $ins = $pdo->prepare("INSERT INTO {$regsTable} ({$colsSql}) VALUES ({$place})");
  $ins->execute($vals);
  $regId = (int)$pdo->lastInsertId();

  // build dynamic insert for values
  $colsV = ['registration_id','field_id'];
  $placeV = ['?','?'];

  $hasValueText = in_array('value_text', $valsCols, true);
  $hasFilePath  = in_array('file_path', $valsCols, true);

  if ($hasValueText) { $colsV[]='value_text'; $placeV[]='?'; }
  if ($hasFilePath)  { $colsV[]='file_path';  $placeV[]='?'; }

  $insV = $pdo->prepare("INSERT INTO {$valsTable} (".implode(',',$colsV).") VALUES (".implode(',',$placeV).")");

  foreach ($values as $fid => $v) {
    $row = [$regId, (int)$fid];
    if ($hasValueText) $row[] = $v["value_text"];
    if ($hasFilePath)  $row[] = $v["file_path"];
    $insV->execute($row);
  }

  $pdo->commit();
  json_out(["ok" => true, "id" => $regId, "message" => "You registered ✅"]);

} catch (Throwable $e) {
  $pdo->rollBack();
  if ($DEBUG) {
    json_out([
      "ok" => false,
      "error" => "Server error: " . $e->getMessage(),
      "debug" => [
        "fieldsTable" => $fieldsTable,
        "regsTable" => $regsTable,
        "valsTable" => $valsTable
      ]
    ], 500);
  }
  json_out(["ok" => false, "error" => "Server error"], 500);
}
