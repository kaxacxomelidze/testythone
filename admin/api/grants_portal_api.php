<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

/* ----------------------------- response helpers ----------------------------- */

function json_out(array $payload, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('X-Content-Type-Options: nosniff');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_ok(array $data = []): void {
  json_out(['ok' => true] + $data, 200);
}
function json_err(string $msg, int $code = 400, array $extra = []): void {
  json_out(['ok' => false, 'error' => $msg] + $extra, $code);
}

function get_action(): string {
  return (string)($_GET['action'] ?? '');
}

/* ----------------------------- request helpers ----------------------------- */

function is_json_request(): bool {
  $ct = (string)($_SERVER['CONTENT_TYPE'] ?? '');
  return stripos($ct, 'application/json') !== false;
}

/** Read JSON body once (safe) */
function read_json_body_once(): array {
  static $cached = null;
  if ($cached !== null) return $cached;
  $raw = file_get_contents('php://input');
  if (!$raw) return $cached = [];
  $j = json_decode($raw, true);
  return $cached = (is_array($j) ? $j : []);
}

function get_csrf_from_headers(): string {
  $h = $_SERVER['HTTP_X_CSRF'] ?? '';
  if (!$h) $h = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!$h) $h = $_SERVER['REDIRECT_HTTP_X_CSRF'] ?? '';
  if (!$h) $h = $_SERVER['REDIRECT_HTTP_X_CSRF_TOKEN'] ?? '';
  return (string)$h;
}

/* ----------------------------- AUTH (API friendly) ----------------------------- */

/**
 * ✅ API should NEVER redirect to HTML login.
 * This function returns JSON 401 instead.
 */
function require_login_api(): void {
  if (function_exists('is_logged_in')) {
    if (!is_logged_in()) json_err('Unauthorized (login required)', 401);
    return;
  }

  // fallback session keys (change if your project uses different ones)
  $ok =
    !empty($_SESSION['user_id']) ||
    !empty($_SESSION['admin_id']) ||
    !empty($_SESSION['auth']) ||
    !empty($_SESSION['user']) ||
    !empty($_SESSION['logged_in']);

  if (!$ok) json_err('Unauthorized (login required)', 401);
}

/* ----------------------------- CSRF ----------------------------- */

function csrf_check_api(array $jsonBody = []): void {
  $sess = (string)($_SESSION['csrf'] ?? '');
  if ($sess === '') json_err('CSRF სესია არ არსებობს', 403);

  $csrf = get_csrf_from_headers();
  if ($csrf === '' && isset($_POST['csrf'])) $csrf = (string)$_POST['csrf'];
  if ($csrf === '' && isset($jsonBody['csrf'])) $csrf = (string)$jsonBody['csrf'];

  if ($csrf === '' || !hash_equals($sess, $csrf)) json_err('CSRF შეცდომა', 403);
}

/* ----------------------------- utils ----------------------------- */

function slugify(string $s): string {
  $s = trim($s);
  if ($s === '') return '';
  $s = mb_strtolower($s, 'UTF-8');
  $s = preg_replace('~[^\pL\pN]+~u', '-', $s);
  $s = trim((string)$s, '-');
  $s = preg_replace('~-+~', '-', (string)$s);
  return $s ?: '';
}

function db_name(PDO $pdo): string {
  $st = $pdo->query("SELECT DATABASE()");
  return (string)($st ? $st->fetchColumn() : '');
}
function has_col(PDO $pdo, string $table, string $col): bool {
  $db = db_name($pdo);
  if ($db === '') return false;

  $st = $pdo->prepare("
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = ?
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
    LIMIT 1
  ");
  $st->execute([$db, $table, $col]);
  return (bool)$st->fetchColumn();
}
function has_table(PDO $pdo, string $table): bool {
  $db = db_name($pdo);
  if ($db === '') return false;
  $st = $pdo->prepare("
    SELECT 1
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
    LIMIT 1
  ");
  $st->execute([$db, $table]);
  return (bool)$st->fetchColumn();
}
function add_col(PDO $pdo, string $table, string $ddl): void {
  $pdo->exec("ALTER TABLE `{$table}` {$ddl}");
}
function has_index(PDO $pdo, string $table, string $keyName): bool {
  $db = db_name($pdo);
  if ($db === '') return false;

  $st = $pdo->prepare("
    SELECT 1
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = ?
      AND TABLE_NAME = ?
      AND INDEX_NAME = ?
    LIMIT 1
  ");
  $st->execute([$db, $table, $keyName]);
  return (bool)$st->fetchColumn();
}

/* ----------------------------- schema ----------------------------- */

/**
 * ✅ Perf improvement:
 * Don’t run ensure_schema on every request.
 * Default: once per session per day. Force with ?debug_schema=1
 */
function should_ensure_schema(): bool {
  if (!empty($_GET['debug_schema'])) return true;

  $today = date('Y-m-d');
  $k = 'schema_checked_' . $today;
  if (!empty($_SESSION[$k])) return false;

  $_SESSION[$k] = 1;
  return true;
}

function ensure_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS grants (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      title_en VARCHAR(255) NULL,
      slug VARCHAR(255) NOT NULL,
      description VARCHAR(255) NULL,
      description_en VARCHAR(255) NULL,
      body MEDIUMTEXT NOT NULL,
      body_en MEDIUMTEXT NULL,
      deadline DATE NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'current',
      apply_url VARCHAR(255) NULL,
      max_amount_person DECIMAL(12,2) NULL,
      max_amount_org    DECIMAL(12,2) NULL,
      sort_order INT NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      image_path VARCHAR(255) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_grants_slug (slug),
      KEY idx_grants_status (status),
      KEY idx_grants_active (is_active),
      KEY idx_grants_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  if (!has_col($pdo, 'grants', 'max_amount_person')) { try { add_col($pdo, 'grants', "ADD COLUMN max_amount_person DECIMAL(12,2) NULL AFTER apply_url"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grants', 'max_amount_org'))    { try { add_col($pdo, 'grants', "ADD COLUMN max_amount_org DECIMAL(12,2) NULL AFTER max_amount_person"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grants', 'title_en'))          { try { add_col($pdo, 'grants', "ADD COLUMN title_en VARCHAR(255) NULL AFTER title"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grants', 'description_en'))    { try { add_col($pdo, 'grants', "ADD COLUMN description_en VARCHAR(255) NULL AFTER description"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grants', 'body_en'))           { try { add_col($pdo, 'grants', "ADD COLUMN body_en MEDIUMTEXT NULL AFTER body"); } catch(Throwable $e) {} }

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS grant_steps (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      grant_id INT UNSIGNED NOT NULL,
      name VARCHAR(255) NOT NULL,
      step_key VARCHAR(255) NOT NULL,
      sort_order INT NOT NULL DEFAULT 0,
      is_enabled TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY idx_steps_grant (grant_id),
      KEY idx_steps_sort (grant_id, sort_order),
      CONSTRAINT fk_steps_grant FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS grant_fields (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      grant_id INT UNSIGNED NOT NULL,
      step_id INT UNSIGNED NOT NULL,
      label VARCHAR(255) NOT NULL,
      type VARCHAR(40) NOT NULL DEFAULT 'text',
      is_required TINYINT(1) NOT NULL DEFAULT 0,
      show_for VARCHAR(10) NOT NULL DEFAULT 'all',
      options_json MEDIUMTEXT NULL,
      sort_order INT NOT NULL DEFAULT 0,
      is_enabled TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY idx_fields_step (step_id, sort_order),
      KEY idx_fields_grant (grant_id),
      CONSTRAINT fk_fields_grant FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE,
      CONSTRAINT fk_fields_step FOREIGN KEY (step_id) REFERENCES grant_steps(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS grant_file_requirements (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      grant_id INT UNSIGNED NOT NULL,
      name VARCHAR(255) NOT NULL,
      is_required TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      is_enabled TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY idx_filereqs_grant (grant_id, sort_order),
      CONSTRAINT fk_filereqs_grant FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  /* ✅ MIGRATIONS (for older DBs) - safe add missing columns */
  if (!has_col($pdo, 'grant_steps', 'is_enabled')) { try { add_col($pdo, 'grant_steps', "ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grant_fields', 'is_enabled')) { try { add_col($pdo, 'grant_fields', "ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grant_file_requirements', 'is_enabled')) { try { add_col($pdo, 'grant_file_requirements', "ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order"); } catch(Throwable $e) {} }

  if (!has_col($pdo, 'grant_steps', 'step_key')) { try { add_col($pdo, 'grant_steps', "ADD COLUMN step_key VARCHAR(255) NOT NULL DEFAULT '' AFTER name"); } catch(Throwable $e) {} }
  if (!has_col($pdo, 'grant_fields', 'options_json')) { try { add_col($pdo, 'grant_fields', "ADD COLUMN options_json MEDIUMTEXT NULL AFTER show_for"); } catch(Throwable $e) {} }

  // legacy migrate from old table if exists
  if (has_table($pdo, 'grant_requirements')) {
    try{
      $c1 = (int)($pdo->query("SELECT COUNT(*) FROM grant_file_requirements")->fetchColumn() ?: 0);
      if ($c1 === 0) {
        $pdo->exec("
          INSERT INTO grant_file_requirements (id, grant_id, name, is_required, sort_order, is_enabled, created_at, updated_at)
          SELECT id, grant_id, name, is_required, sort_order, 1, created_at, updated_at
          FROM grant_requirements
        ");
      }
    }catch(Throwable $e){}
  }

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS grant_applications (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      grant_id INT UNSIGNED NOT NULL,
      applicant_name VARCHAR(255) NULL,
      email VARCHAR(255) NULL,
      phone VARCHAR(60) NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'submitted',
      rating INT NOT NULL DEFAULT 0,
      admin_note MEDIUMTEXT NULL,
      form_data_json LONGTEXT NULL,
      deleted_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      KEY idx_apps_grant (grant_id),
      KEY idx_apps_status (grant_id, status),
      KEY idx_apps_created (grant_id, created_at),
      KEY idx_apps_deleted (deleted_at),
      CONSTRAINT fk_apps_grant FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
  if (!has_col($pdo, 'grant_applications', 'deleted_at')) { try { add_col($pdo, 'grant_applications', "ADD COLUMN deleted_at DATETIME NULL AFTER form_data_json"); } catch(Throwable $e) {} }
  if (!has_index($pdo, 'grant_applications', 'idx_apps_deleted')) { try { $pdo->exec("ALTER TABLE `grant_applications` ADD KEY idx_apps_deleted (deleted_at)"); } catch(Throwable $e) {} }

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS grant_uploads (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      application_id INT UNSIGNED NOT NULL,
      grant_id INT UNSIGNED NOT NULL,
      requirement_id INT UNSIGNED NULL,
      field_id INT UNSIGNED NULL,
      field_name VARCHAR(120) NULL,
      original_name VARCHAR(255) NOT NULL,
      stored_name VARCHAR(255) NOT NULL,
      file_path VARCHAR(500) NOT NULL,
      size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
      mime_type VARCHAR(120) NULL,
      deleted_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      KEY idx_up_app (application_id),
      KEY idx_up_grant (grant_id),
      KEY idx_up_req (requirement_id),
      KEY idx_up_field (field_id),
      KEY idx_up_deleted (deleted_at),
      CONSTRAINT fk_up_app FOREIGN KEY (application_id) REFERENCES grant_applications(id) ON DELETE CASCADE,
      CONSTRAINT fk_up_grant FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
  if (!has_col($pdo, 'grant_uploads', 'field_name')) { try { add_col($pdo, 'grant_uploads', "ADD COLUMN field_name VARCHAR(120) NULL AFTER field_id"); } catch(Throwable $e) {} }
}

/* ----------------------------- sorting helpers ----------------------------- */

function max_sort(PDO $pdo, string $table, string $whereSql, array $params): int {
  $st = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0) FROM {$table} WHERE {$whereSql}");
  $st->execute($params);
  return (int)($st->fetchColumn() ?: 0);
}

function move_sort(PDO $pdo, string $table, int $id, string $scopeSql, array $scopeParams, int $dir): void {
  $st = $pdo->prepare("SELECT id, sort_order FROM {$table} WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $cur = $st->fetch(PDO::FETCH_ASSOC);
  if (!$cur) json_err("ჩანაწერი ვერ მოიძებნა");

  $curSort = (int)$cur['sort_order'];

  if ($dir < 0) {
    $st2 = $pdo->prepare("
      SELECT id, sort_order FROM {$table}
      WHERE {$scopeSql} AND sort_order < ?
      ORDER BY sort_order DESC LIMIT 1
    ");
    $st2->execute(array_merge($scopeParams, [$curSort]));
  } else {
    $st2 = $pdo->prepare("
      SELECT id, sort_order FROM {$table}
      WHERE {$scopeSql} AND sort_order > ?
      ORDER BY sort_order ASC LIMIT 1
    ");
    $st2->execute(array_merge($scopeParams, [$curSort]));
  }
  $nb = $st2->fetch(PDO::FETCH_ASSOC);
  if (!$nb) return;

  $pdo->beginTransaction();
  try {
    $stU = $pdo->prepare("UPDATE {$table} SET sort_order=? WHERE id=?");
    $stU->execute([(int)$nb['sort_order'], $id]);
    $stU->execute([$curSort, (int)$nb['id']]);
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

/* ----------------------------- slug ----------------------------- */

function unique_slug(PDO $pdo, string $slug, int $ignoreId = 0): string {
  $slug = $slug ?: 'grant';
  $base = $slug;
  $i = 0;
  while (true) {
    $try = $i === 0 ? $base : ($base . '-' . $i);
    $st = $pdo->prepare("SELECT id FROM grants WHERE slug=? " . ($ignoreId > 0 ? "AND id<>?" : "") . " LIMIT 1");
    $st->execute($ignoreId > 0 ? [$try, $ignoreId] : [$try]);
    $id = (int)($st->fetchColumn() ?: 0);
    if ($id === 0) return $try;
    $i++;
    if ($i > 9999) return $base . '-' . time();
  }
}

/* ----------------------------- cover image ----------------------------- */

function save_cover_image(?array $file): string {
  if (!$file || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return '';
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_err("სურათის ატვირთვის შეცდომა");

  $maxBytes = 4 * 1024 * 1024;
  if ((int)($file['size'] ?? 0) > $maxBytes) json_err("სურათი ძალიან დიდია (მაქს 4MB)");

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = (string)$finfo->file($file['tmp_name']);

  $ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => '',
  };
  if ($ext === '') json_err("სურათის ფორმატი არაა მხარდაჭერილი (jpeg/png/webp/gif)");

  $dir = __DIR__ . '/../uploads/grants';
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) json_err("ვერ შეიქმნა uploads/grants");
  }

  $name = 'grant_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $pathAbs = $dir . '/' . $name;

  if (!move_uploaded_file($file['tmp_name'], $pathAbs)) json_err("ვერ მოხერხდა სურათის შენახვა");
  return 'uploads/grants/' . $name;
}

/* ----------------------------- uploads (applications) ----------------------------- */

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) json_err("ვერ შეიქმნა საქაღალდე: " . $dir, 500);
  }
}

function allow_upload_mime(string $mime): bool {
  $mime = strtolower(trim($mime));
  $allowed = [
    'application/pdf',
    'image/jpeg','image/png','image/webp','image/gif',
    'application/zip','application/x-zip-compressed',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword','application/vnd.ms-excel',
    'text/plain',
  ];
  return in_array($mime, $allowed, true);
}

function safe_ext_from_mime(string $mime, string $fallbackName): string {
  $mime = strtolower(trim($mime));
  $map = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'application/zip' => 'zip',
    'application/x-zip-compressed' => 'zip',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/msword' => 'doc',
    'application/vnd.ms-excel' => 'xls',
    'text/plain' => 'txt',
  ];
  if (isset($map[$mime])) return $map[$mime];

  $ext = strtolower(pathinfo($fallbackName, PATHINFO_EXTENSION));
  $ext = preg_replace('~[^a-z0-9]+~', '', (string)$ext);
  return $ext ?: 'bin';
}

function flatten_files(array $files): array {
  $out = [];
  foreach ($files as $topField => $info) {
    if (!is_array($info) || !isset($info['name'])) continue;

    $names = $info['name'];
    $types = $info['type'] ?? [];
    $tmps  = $info['tmp_name'] ?? [];
    $errs  = $info['error'] ?? [];
    $sizes = $info['size'] ?? [];

    if (!is_array($names)) {
      $out[] = [
        'field'    => (string)$topField,
        'name'     => (string)$names,
        'type'     => (string)($types ?? ''),
        'tmp_name' => (string)($tmps ?? ''),
        'error'    => (int)($errs ?? UPLOAD_ERR_NO_FILE),
        'size'     => (int)($sizes ?? 0),
      ];
      continue;
    }

    foreach ($names as $k => $nm) {
      $fld = (string)$topField;
      if ($fld === 'req_file') $fld = 'req_' . (int)$k;
      elseif ($fld === 'field_file') $fld = 'field_' . (int)$k;

      $out[] = [
        'field'    => $fld,
        'name'     => (string)($nm ?? ''),
        'type'     => (string)($types[$k] ?? ''),
        'tmp_name' => (string)($tmps[$k] ?? ''),
        'error'    => (int)($errs[$k] ?? UPLOAD_ERR_NO_FILE),
        'size'     => (int)($sizes[$k] ?? 0),
      ];
    }
  }
  return $out;
}

function save_application_uploads(PDO $pdo, int $grantId, int $appId): array {
  if (empty($_FILES) || !is_array($_FILES)) return [];
  $flat = flatten_files($_FILES);
  if (!$flat) return [];

  $baseDirAbs = __DIR__ . '/../uploads/grants/apps/' . $appId;
  ensure_dir($baseDirAbs);

  $maxEach = 25 * 1024 * 1024;
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $saved = [];

  foreach ($flat as $f) {
    $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) json_err("ფაილის ატვირთვის შეცდომა: " . ($f['name'] ?? ''), 400);

    $tmp = (string)($f['tmp_name'] ?? '');
    if (!$tmp || !is_uploaded_file($tmp)) continue;

    $size = (int)($f['size'] ?? 0);
    if ($size <= 0) continue;
    if ($size > $maxEach) json_err("ფაილი ძალიან დიდია (მაქს 25MB): " . ($f['name'] ?? ''), 400);

    $mime = (string)$finfo->file($tmp);
    if ($mime === '') $mime = (string)($f['type'] ?? '');
    if (!allow_upload_mime($mime)) json_err("ფაილის ტიპი არაა დაშვებული: " . ($f['name'] ?? '') . " (" . $mime . ")", 400);

    $orig = trim((string)($f['name'] ?? 'file'));
    if ($orig === '') $orig = 'file';

    $ext = safe_ext_from_mime($mime, $orig);
    $stored = 'up_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $abs = rtrim($baseDirAbs, '/\\') . DIRECTORY_SEPARATOR . $stored;

    if (!move_uploaded_file($tmp, $abs)) json_err("ვერ მოხერხდა ფაილის შენახვა: " . $orig, 500);

    $fieldName = (string)($f['field'] ?? '');
    $requirement_id = null;
    $field_id = null;
    if (preg_match('~^req_(\d+)$~', $fieldName, $m)) $requirement_id = (int)$m[1];
    if (preg_match('~^field_(\d+)$~', $fieldName, $m)) $field_id = (int)$m[1];

    $relPath = 'uploads/grants/apps/' . $appId . '/' . $stored;

    $pdo->prepare("
      INSERT INTO grant_uploads(
        application_id, grant_id, requirement_id, field_id, field_name,
        original_name, stored_name, file_path, size_bytes, mime_type
      ) VALUES (?,?,?,?,?,?,?,?,?,?)
    ")->execute([
      $appId, $grantId, $requirement_id, $field_id,
      ($fieldName !== '' ? $fieldName : null),
      $orig, $stored, $relPath, $size, ($mime !== '' ? $mime : null),
    ]);

    $saved[] = [
      'field_name' => $fieldName,
      'requirement_id' => $requirement_id,
      'field_id' => $field_id,
      'original_name' => $orig,
      'stored_name' => $stored,
      'file_path' => $relPath,
      'mime_type' => $mime,
      'size_bytes' => $size,
    ];
  }

  return $saved;
}

/* ----------------------------- API BOOT ----------------------------- */

function api_boot(string $action): array {
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF, X-CSRF-TOKEN');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    exit;
  }

  $json = read_json_body_once();

  // submit is public, others require admin
  if ($action !== 'submit') require_login_api();

  // CSRF required for ALL actions (including submit)
  csrf_check_api($json);

  return $json;
}

/* ----------------------------- main ----------------------------- */

try {
  if (should_ensure_schema()) ensure_schema($pdo);

  $action = get_action();
  if ($action === '') json_err('action არ არის');

  $json = api_boot($action);

  /* ----------------------------- SUBMIT (PUBLIC) ----------------------------- */
  if ($action === 'submit') {
    $grant_id = (int)($_POST['grant_id'] ?? ($json['grant_id'] ?? 0));
    if ($grant_id <= 0) json_err('grant_id არასწორია');

    $stg = $pdo->prepare("SELECT id, status, is_active FROM grants WHERE id=? LIMIT 1");
    $stg->execute([$grant_id]);
    $g = $stg->fetch(PDO::FETCH_ASSOC);
    if (!$g) json_err('გრანტი ვერ მოიძებნა', 404);
    if ((int)$g['is_active'] !== 1) json_err('გრანტი გამორთულია', 400);
    if ((string)$g['status'] === 'closed') json_err('გრანტი დახურულია', 400);

    $applicant_name = trim((string)($_POST['applicant_name'] ?? ($json['applicant_name'] ?? '')));
    $email = trim((string)($_POST['email'] ?? ($json['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ($json['phone'] ?? '')));

    $formData = null;
    $rawForm = null;

    if (isset($_POST['form_data'])) $rawForm = (string)$_POST['form_data'];
    if ($rawForm === null && isset($_POST['form_json'])) $rawForm = (string)$_POST['form_json'];

    if ($rawForm !== null) {
      $decoded = json_decode($rawForm, true);
      $formData = is_array($decoded) ? $decoded : null;
      if ($formData === null && $rawForm !== '') $formData = ['raw' => $rawForm];
    } elseif (isset($json['form_data']) && is_array($json['form_data'])) {
      $formData = $json['form_data'];
    } elseif (isset($json['form_json'])) {
      $decoded = json_decode((string)$json['form_json'], true);
      $formData = is_array($decoded) ? $decoded : null;
    } else {
      $tmp = $_POST ?: [];
      unset($tmp['csrf'], $tmp['grant_id'], $tmp['applicant_name'], $tmp['email'], $tmp['phone'], $tmp['form_data'], $tmp['form_json']);
      if ($tmp) $formData = $tmp;
    }

    $form_json = $formData ? json_encode($formData, JSON_UNESCAPED_UNICODE) : null;

    $pdo->beginTransaction();
    try{
      $pdo->prepare("
        INSERT INTO grant_applications(grant_id, applicant_name, email, phone, status, form_data_json)
        VALUES(?,?,?,?, 'submitted', ?)
      ")->execute([
        $grant_id,
        ($applicant_name !== '' ? $applicant_name : null),
        ($email !== '' ? $email : null),
        ($phone !== '' ? $phone : null),
        $form_json
      ]);

      $appId = (int)$pdo->lastInsertId();
      $savedFiles = save_application_uploads($pdo, $grant_id, $appId);

      $pdo->commit();

      json_ok([
        'id' => $appId,
        'app_id' => $appId,
        'application_id' => $appId,
        'files_saved' => $savedFiles
      ]);
    } catch(Throwable $e){
      $pdo->rollBack();
      throw $e;
    }
  }

  /* ----------------------------- GRANTS ----------------------------- */
  if ($action === 'grants_list') {
    $q = trim((string)($json['q'] ?? ''));
    $status = (string)($json['status'] ?? 'all');
    $sort = (string)($json['sort'] ?? 'new');

    $where = [];
    $params = [];

    if ($q !== '') {
      $where[] = "(title LIKE ? OR slug LIKE ?)";
      $params[] = '%' . $q . '%';
      $params[] = '%' . $q . '%';
    }
    if ($status === 'current' || $status === 'closed') {
      $where[] = "status = ?";
      $params[] = $status;
    }

    $sql = "SELECT id,title,title_en,slug,description,description_en,body,body_en,deadline,status,apply_url,
                   max_amount_person,max_amount_org,
                   sort_order,is_active,image_path,created_at,updated_at
            FROM grants";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);

    $sql .= ($sort === 'old')
      ? " ORDER BY created_at ASC, id ASC"
      : " ORDER BY created_at DESC, id DESC";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    json_ok(['items' => $st->fetchAll(PDO::FETCH_ASSOC) ?: []]);
  }

  if ($action === 'grants_save') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $title_en = trim((string)($_POST['title_en'] ?? ''));
    $slug  = trim((string)($_POST['slug'] ?? ''));
    $desc  = trim((string)($_POST['description'] ?? ''));
    $desc_en  = trim((string)($_POST['description_en'] ?? ''));
    $deadline = (string)($_POST['deadline'] ?? '');
    $status = (string)($_POST['status'] ?? 'current');
    $apply_url = trim((string)($_POST['apply_url'] ?? ''));
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 1);
    $body = trim((string)($_POST['body'] ?? ''));
    $body_en = trim((string)($_POST['body_en'] ?? ''));
    $existing = trim((string)($_POST['existing_image_path'] ?? ''));

    $maxP = trim((string)($_POST['max_amount_person'] ?? ''));
    $maxO = trim((string)($_POST['max_amount_org'] ?? ''));
    $max_amount_person = ($maxP === '' ? null : (float)$maxP);
    $max_amount_org    = ($maxO === '' ? null : (float)$maxO);

    if ($title === '') json_err('სათაური სავალდებულოა');
    if ($body === '') json_err('სრული აღწერა სავალდებულოა');
    if ($status !== 'current' && $status !== 'closed') $status = 'current';

    $slug = ($slug === '') ? slugify($title) : slugify($slug);
    $slug = unique_slug($pdo, $slug, $id);

    $deadlineSql = null;
    if ($deadline !== '') {
      if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $deadline)) json_err('ვადის ფორმატი არასწორია');
      $deadlineSql = $deadline;
    }

    $image_path = $existing;
    if (isset($_FILES['cover_image']) && is_array($_FILES['cover_image'])) {
      $newPath = save_cover_image($_FILES['cover_image']);
      if ($newPath) $image_path = $newPath;
    }

    if ($id > 0) {
      $st = $pdo->prepare("UPDATE grants
        SET title=?, title_en=?, slug=?, description=?, description_en=?, body=?, body_en=?, deadline=?, status=?, apply_url=?,
            max_amount_person=?, max_amount_org=?,
            sort_order=?, is_active=?, image_path=?
        WHERE id=?");
      $st->execute([
        $title, ($title_en !== '' ? $title_en : null), $slug,
        ($desc !== '' ? $desc : null), ($desc_en !== '' ? $desc_en : null),
        $body, ($body_en !== '' ? $body_en : null),
        $deadlineSql, $status, ($apply_url !== '' ? $apply_url : null),
        $max_amount_person, $max_amount_org,
        $sort_order, $is_active ? 1 : 0, ($image_path !== '' ? $image_path : null),
        $id
      ]);
    } else {
      $st = $pdo->prepare("INSERT INTO grants(
          title,title_en,slug,description,description_en,body,body_en,deadline,status,apply_url,
          max_amount_person,max_amount_org,
          sort_order,is_active,image_path
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $st->execute([
        $title, ($title_en !== '' ? $title_en : null), $slug,
        ($desc !== '' ? $desc : null), ($desc_en !== '' ? $desc_en : null),
        $body, ($body_en !== '' ? $body_en : null),
        $deadlineSql, $status, ($apply_url !== '' ? $apply_url : null),
        $max_amount_person, $max_amount_org,
        $sort_order, $is_active ? 1 : 0, ($image_path !== '' ? $image_path : null)
      ]);
      $id = (int)$pdo->lastInsertId();
    }

    json_ok(['id' => $id, 'slug' => $slug, 'image_path' => $image_path]);
  }

  if ($action === 'grants_delete') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');
    $pdo->prepare("DELETE FROM grants WHERE id=?")->execute([$id]);
    json_ok();
  }

  if ($action === 'grants_toggle_active') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');
    $pdo->prepare("UPDATE grants SET is_active = IF(is_active=1,0,1) WHERE id=?")->execute([$id]);
    json_ok();
  }

  /* ----------------------------- BUILDER LOAD ----------------------------- */
  if ($action === 'builder_load') {
    $grant_id = (int)($json['grant_id'] ?? 0);
    if ($grant_id <= 0) json_err('grant_id არასწორია');

    // ✅ SAFE SELECT: if column missing, use constant 1 AS is_enabled
    $stepsIsEnabled = has_col($pdo, 'grant_steps', 'is_enabled') ? 'is_enabled' : '1 AS is_enabled';
    $fieldsIsEnabled = has_col($pdo, 'grant_fields', 'is_enabled') ? 'is_enabled' : '1 AS is_enabled';
    $reqIsEnabled = has_col($pdo, 'grant_file_requirements', 'is_enabled') ? 'is_enabled' : '1 AS is_enabled';

    $steps = $pdo->prepare("SELECT id,grant_id,name,step_key,sort_order, {$stepsIsEnabled}
                            FROM grant_steps WHERE grant_id=? ORDER BY sort_order ASC, id ASC");
    $steps->execute([$grant_id]);
    $stepsList = $steps->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $fields = $pdo->prepare("SELECT id,grant_id,step_id,label,type,is_required,show_for,options_json,sort_order, {$fieldsIsEnabled}
                             FROM grant_fields WHERE grant_id=? ORDER BY step_id ASC, sort_order ASC, id ASC");
    $fields->execute([$grant_id]);
    $rows = $fields->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $fieldsByStep = [];
    foreach ($rows as $r) {
      $sid = (int)$r['step_id'];
      $fieldsByStep[$sid] ??= [];
      $fieldsByStep[$sid][] = $r;
    }

    $reqs = $pdo->prepare("SELECT id,grant_id,name,is_required,sort_order, {$reqIsEnabled}
                           FROM grant_file_requirements
                           WHERE grant_id=?
                           ORDER BY sort_order ASC, id ASC");
    $reqs->execute([$grant_id]);
    $reqList = $reqs->fetchAll(PDO::FETCH_ASSOC) ?: [];

    json_ok(['builder' => ['steps'=>$stepsList,'fieldsByStep'=>$fieldsByStep,'reqs'=>$reqList]]);
  }

  /* ----------------------------- STEPS CRUD ----------------------------- */
  if ($action === 'step_add') {
    $grant_id = (int)($json['grant_id'] ?? 0);
    $name = trim((string)($json['name'] ?? ''));
    if ($grant_id <= 0) json_err('grant_id არასწორია');
    if ($name === '') json_err('ნაბიჯის სახელი სავალდებულოა');

    $sort = max_sort($pdo, 'grant_steps', 'grant_id=?', [$grant_id]) + 1;
    $key = slugify($name);
    if ($key === '') $key = 'step-' . $sort;

    $hasIsEnabled = has_col($pdo, 'grant_steps', 'is_enabled');
    if ($hasIsEnabled) {
      $st = $pdo->prepare("INSERT INTO grant_steps(grant_id,name,step_key,sort_order,is_enabled) VALUES(?,?,?,?,1)");
      $st->execute([$grant_id, $name, $key, $sort]);
    } else {
      $st = $pdo->prepare("INSERT INTO grant_steps(grant_id,name,step_key,sort_order) VALUES(?,?,?,?)");
      $st->execute([$grant_id, $name, $key, $sort]);
    }

    json_ok(['id' => (int)$pdo->lastInsertId()]);
  }

  if ($action === 'step_update') {
    $step_id = (int)($json['step_id'] ?? 0);
    $name = trim((string)($json['name'] ?? ''));
    if ($step_id <= 0) json_err('step_id არასწორია');
    if ($name === '') json_err('სახელი სავალდებულოა');

    $key = slugify($name);
    if ($key === '') $key = 'step-' . $step_id;

    $pdo->prepare("UPDATE grant_steps SET name=?, step_key=? WHERE id=?")->execute([$name, $key, $step_id]);
    json_ok();
  }

  if ($action === 'step_delete') {
    $step_id = (int)($json['step_id'] ?? 0);
    if ($step_id <= 0) json_err('step_id არასწორია');
    $pdo->prepare("DELETE FROM grant_steps WHERE id=?")->execute([$step_id]);
    json_ok();
  }

  if ($action === 'step_toggle') {
    $step_id = (int)($json['step_id'] ?? 0);
    if ($step_id <= 0) json_err('step_id არასწორია');
    if (!has_col($pdo, 'grant_steps', 'is_enabled')) json_err('DB schema: grant_steps.is_enabled არ არსებობს', 500);
    $pdo->prepare("UPDATE grant_steps SET is_enabled = IF(is_enabled=1,0,1) WHERE id=?")->execute([$step_id]);
    json_ok();
  }

  if ($action === 'step_move') {
    $step_id = (int)($json['step_id'] ?? 0);
    $dir = (int)($json['dir'] ?? 0);
    if ($step_id <= 0) json_err('step_id არასწორია');
    if ($dir === 0) json_err('dir არასწორია');

    $st = $pdo->prepare("SELECT grant_id FROM grant_steps WHERE id=? LIMIT 1");
    $st->execute([$step_id]);
    $grant_id = (int)($st->fetchColumn() ?: 0);
    if ($grant_id <= 0) json_err('ნაბიჯი ვერ მოიძებნა');

    move_sort($pdo, 'grant_steps', $step_id, 'grant_id=?', [$grant_id], $dir);
    json_ok();
  }

  /* ----------------------------- FIELDS CRUD ----------------------------- */
  if ($action === 'field_add') {
    $grant_id = (int)($json['grant_id'] ?? 0);
    $step_id  = (int)($json['step_id'] ?? 0);
    $label = trim((string)($json['label'] ?? ''));
    $type  = trim((string)($json['type'] ?? 'text'));
    $is_required = (int)($json['is_required'] ?? 0);
    $show_for = (string)($json['show_for'] ?? 'all');
    $options_json = $json['options_json'] ?? null;

    if ($grant_id <= 0 || $step_id <= 0) json_err('grant_id/step_id არასწორია');
    if ($label === '') json_err('label სავალდებულოა');

    $allowedShow = ['all','person','org'];
    if (!in_array($show_for, $allowedShow, true)) $show_for = 'all';

    $st = $pdo->prepare("SELECT id FROM grant_steps WHERE id=? AND grant_id=? LIMIT 1");
    $st->execute([$step_id, $grant_id]);
    if (!(int)($st->fetchColumn() ?: 0)) json_err('ნაბიჯი ამ გრანტს არ ეკუთვნის');

    $sort = max_sort($pdo, 'grant_fields', 'grant_id=? AND step_id=?', [$grant_id, $step_id]) + 1;

    $optStr = null;
    if ($options_json !== null) {
      if (is_string($options_json)) $optStr = trim($options_json) !== '' ? $options_json : null;
      else $optStr = json_encode($options_json, JSON_UNESCAPED_UNICODE);
    } else {
      $opts = $json['options'] ?? null;
      if (is_array($opts) && $opts) $optStr = json_encode(array_values($opts), JSON_UNESCAPED_UNICODE);
    }

    if (has_col($pdo, 'grant_fields', 'is_enabled')) {
      $pdo->prepare("INSERT INTO grant_fields(grant_id,step_id,label,type,is_required,show_for,options_json,sort_order,is_enabled)
                     VALUES(?,?,?,?,?,?,?,?,1)")
          ->execute([$grant_id,$step_id,$label,$type,$is_required?1:0,$show_for,$optStr,$sort]);
    } else {
      $pdo->prepare("INSERT INTO grant_fields(grant_id,step_id,label,type,is_required,show_for,options_json,sort_order)
                     VALUES(?,?,?,?,?,?,?,?)")
          ->execute([$grant_id,$step_id,$label,$type,$is_required?1:0,$show_for,$optStr,$sort]);
    }

    json_ok(['id' => (int)$pdo->lastInsertId()]);
  }

  if ($action === 'field_update') {
    $field_id = (int)($json['field_id'] ?? 0);
    if ($field_id <= 0) json_err('field_id არასწორია');

    $label = trim((string)($json['label'] ?? ''));
    $type  = trim((string)($json['type'] ?? 'text'));
    $is_required = (int)($json['is_required'] ?? 0);
    $show_for = (string)($json['show_for'] ?? 'all');
    $allowedShow = ['all','person','org'];
    if (!in_array($show_for, $allowedShow, true)) $show_for = 'all';

    $options_json = $json['options_json'] ?? null;
    $optStr = null;
    if ($options_json !== null) {
      if (is_string($options_json)) $optStr = trim((string)$options_json) !== '' ? (string)$options_json : null;
      else $optStr = json_encode($options_json, JSON_UNESCAPED_UNICODE);
    } else {
      $opts = $json['options'] ?? null;
      if (is_array($opts) && $opts) $optStr = json_encode(array_values($opts), JSON_UNESCAPED_UNICODE);
    }

    if ($label === '') json_err('label სავალდებულოა');

    $pdo->prepare("UPDATE grant_fields SET label=?, type=?, is_required=?, show_for=?, options_json=? WHERE id=?")
        ->execute([$label,$type,$is_required?1:0,$show_for,$optStr,$field_id]);

    json_ok();
  }

  if ($action === 'field_toggle') {
    $field_id = (int)($json['field_id'] ?? 0);
    if ($field_id <= 0) json_err('field_id არასწორია');
    if (!has_col($pdo, 'grant_fields', 'is_enabled')) json_err('DB schema: grant_fields.is_enabled არ არსებობს', 500);
    $pdo->prepare("UPDATE grant_fields SET is_enabled = IF(is_enabled=1,0,1) WHERE id=?")->execute([$field_id]);
    json_ok();
  }

  if ($action === 'field_delete') {
    $field_id = (int)($json['field_id'] ?? 0);
    if ($field_id <= 0) json_err('field_id არასწორია');
    $pdo->prepare("DELETE FROM grant_fields WHERE id=?")->execute([$field_id]);
    json_ok();
  }

  if ($action === 'field_move') {
    $field_id = (int)($json['field_id'] ?? 0);
    $dir = (int)($json['dir'] ?? 0);
    if ($field_id <= 0) json_err('field_id არასწორია');
    if ($dir === 0) json_err('dir არასწორია');

    $st = $pdo->prepare("SELECT grant_id, step_id FROM grant_fields WHERE id=? LIMIT 1");
    $st->execute([$field_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_err('ველი ვერ მოიძებნა');

    $grant_id = (int)$row['grant_id'];
    $step_id  = (int)$row['step_id'];

    move_sort($pdo, 'grant_fields', $field_id, 'grant_id=? AND step_id=?', [$grant_id, $step_id], $dir);
    json_ok();
  }

  /* ----------------------------- FILE REQUIREMENTS CRUD ----------------------------- */
  if ($action === 'req_add') {
    $grant_id = (int)($json['grant_id'] ?? 0);
    $name = trim((string)($json['name'] ?? ''));
    $is_required = (int)($json['is_required'] ?? 1);

    if ($grant_id <= 0) json_err('grant_id არასწორია');
    if ($name === '') json_err('მოთხოვნა სავალდებულოა');

    $sort = max_sort($pdo, 'grant_file_requirements', 'grant_id=?', [$grant_id]) + 1;

    if (has_col($pdo, 'grant_file_requirements', 'is_enabled')) {
      $pdo->prepare("INSERT INTO grant_file_requirements(grant_id,name,is_required,sort_order,is_enabled) VALUES(?,?,?,?,1)")
          ->execute([$grant_id,$name,$is_required?1:0,$sort]);
    } else {
      $pdo->prepare("INSERT INTO grant_file_requirements(grant_id,name,is_required,sort_order) VALUES(?,?,?,?)")
          ->execute([$grant_id,$name,$is_required?1:0,$sort]);
    }

    json_ok(['id' => (int)$pdo->lastInsertId()]);
  }

  if ($action === 'req_update') {
    $id = (int)($json['id'] ?? 0);
    $name = trim((string)($json['name'] ?? ''));
    $is_required = (int)($json['is_required'] ?? 1);
    if ($id <= 0) json_err('id არასწორია');
    if ($name === '') json_err('მოთხოვნა სავალდებულოა');

    $pdo->prepare("UPDATE grant_file_requirements SET name=?, is_required=? WHERE id=?")
        ->execute([$name, $is_required?1:0, $id]);

    json_ok();
  }

  if ($action === 'req_toggle') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');
    if (!has_col($pdo, 'grant_file_requirements', 'is_enabled')) json_err('DB schema: grant_file_requirements.is_enabled არ არსებობს', 500);

    $pdo->prepare("UPDATE grant_file_requirements SET is_enabled = IF(is_enabled=1,0,1) WHERE id=?")->execute([$id]);
    json_ok();
  }

  if ($action === 'req_move') {
    $id = (int)($json['id'] ?? 0);
    $dir = (int)($json['dir'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');
    if ($dir === 0) json_err('dir არასწორია');

    $st = $pdo->prepare("SELECT grant_id FROM grant_file_requirements WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $grant_id = (int)($st->fetchColumn() ?: 0);
    if ($grant_id <= 0) json_err('მოთხოვნა ვერ მოიძებნა');

    move_sort($pdo, 'grant_file_requirements', $id, 'grant_id=?', [$grant_id], $dir);
    json_ok();
  }

  if ($action === 'req_delete') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');
    $pdo->prepare("DELETE FROM grant_file_requirements WHERE id=?")->execute([$id]);
    json_ok();
  }

  /* ----------------------------- APPLICATIONS (ADMIN) ----------------------------- */
  if ($action === 'apps_list') {
    $grant_id = (int)($json['grant_id'] ?? 0);
    $q = trim((string)($json['q'] ?? ''));
    $status = (string)($json['status'] ?? 'all');

    $where = ["a.deleted_at IS NULL"];
    $params = [];

    if ($grant_id > 0) { $where[] = "a.grant_id=?"; $params[] = $grant_id; }
    if ($status !== 'all' && $status !== '') { $where[] = "a.status=?"; $params[] = $status; }

    if ($q !== '') {
      $where[] = "(a.applicant_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ? OR CAST(a.id AS CHAR) LIKE ?)";
      $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    }

    $sql = "
      SELECT
        a.id, a.grant_id,
        g.title AS grant_title,
        a.applicant_name, a.email, a.phone,
        a.status, a.rating, a.created_at
      FROM grant_applications a
      LEFT JOIN grants g ON g.id = a.grant_id
      WHERE " . implode(" AND ", $where) . "
      ORDER BY a.id DESC
      LIMIT 500
    ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    json_ok(['items' => $st->fetchAll(PDO::FETCH_ASSOC) ?: []]);
  }

  if ($action === 'app_get') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');

    $st = $pdo->prepare("
      SELECT a.*, g.title AS grant_title
      FROM grant_applications a
      LEFT JOIN grants g ON g.id = a.grant_id
      WHERE a.id=? AND a.deleted_at IS NULL
      LIMIT 1
    ");
    $st->execute([$id]);
    $a = $st->fetch(PDO::FETCH_ASSOC);
    if (!$a) json_err('განაცხადი ვერ მოიძებნა', 404);

    $fd = [];
    if (!empty($a['form_data_json'])) {
      $tmp = json_decode((string)$a['form_data_json'], true);
      if (is_array($tmp)) $fd = $tmp;
    }

    $fieldMap = [];
    try{
      $stF = $pdo->prepare("SELECT id,label,type FROM grant_fields WHERE grant_id=?");
      $stF->execute([(int)$a['grant_id']]);
      foreach ($stF->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $key = 'field_' . (int)$r['id'];
        $fieldMap[$key] = ['label' => (string)$r['label'], 'type' => (string)($r['type'] ?? 'text')];
      }
    }catch(Throwable $e){}

    $fdResolved = [];
    foreach ($fd as $k => $v) {
      $key = (string)$k;
      if (isset($fieldMap[$key])) {
        $fdResolved[] = ['key'=>$key,'label'=>$fieldMap[$key]['label'],'type'=>$fieldMap[$key]['type'],'value'=>$v];
      } else {
        $fdResolved[] = ['key'=>$key,'label'=>$key,'type'=>'raw','value'=>$v];
      }
    }

    $up = $pdo->prepare("
      SELECT id, file_path, original_name, stored_name, size_bytes, mime_type,
             requirement_id, field_id, field_name, created_at
      FROM grant_uploads
      WHERE application_id=? AND deleted_at IS NULL
      ORDER BY id ASC
    ");
    $up->execute([(int)$a['id']]);
    $uploads = $up->fetchAll(PDO::FETCH_ASSOC) ?: [];

    json_ok(['app' => [
      'id' => (int)$a['id'],
      'grant_id' => (int)$a['grant_id'],
      'grant_title' => $a['grant_title'] ?? null,
      'applicant_name' => $a['applicant_name'],
      'email' => $a['email'],
      'phone' => $a['phone'],
      'status' => $a['status'],
      'rating' => (int)$a['rating'],
      'admin_note' => $a['admin_note'],
      'created_at' => $a['created_at'],
      'updated_at' => $a['updated_at'],
      'form_data' => $fd,
      'form_data_resolved' => $fdResolved,
      'uploads' => $uploads
    ]]);
  }

  if ($action === 'grant_fields_map') {
    $grant_id = (int)($json['grant_id'] ?? 0);
    if ($grant_id <= 0) json_err('grant_id არასწორია');

    $stF = $pdo->prepare("SELECT id,label FROM grant_fields WHERE grant_id=?");
    $stF->execute([$grant_id]);
    $map = [];
    foreach ($stF->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $key = 'field_' . (int)$r['id'];
      $map[$key] = (string)($r['label'] ?? '');
    }
    json_ok(['map' => $map]);
  }

  if ($action === 'app_update_meta') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');

    $status = (string)($json['status'] ?? 'submitted');
    $allowed = ['submitted','in_review','need_clarification','approved','rejected'];
    if (!in_array($status, $allowed, true)) $status = 'submitted';

    $rating = (int)($json['rating'] ?? 0);
    if ($rating < 0) $rating = 0;
    if ($rating > 100) $rating = 100;

    $admin_note = (string)($json['admin_note'] ?? '');

    $pdo->prepare("UPDATE grant_applications SET status=?, rating=?, admin_note=? WHERE id=? AND deleted_at IS NULL")
        ->execute([$status,$rating,$admin_note,$id]);

    json_ok();
  }

  if ($action === 'app_delete') {
    $id = (int)($json['id'] ?? 0);
    if ($id <= 0) json_err('id არასწორია');

    $pdo->prepare("UPDATE grant_applications SET deleted_at = NOW() WHERE id=? AND deleted_at IS NULL")
        ->execute([$id]);

    json_ok();
  }

  if ($action === 'upload_delete') {
    $upload_id = (int)($json['upload_id'] ?? 0);
    if ($upload_id <= 0) json_err('upload_id არასწორია');

    $pdo->prepare("UPDATE grant_uploads SET deleted_at = NOW() WHERE id=? AND deleted_at IS NULL")
        ->execute([$upload_id]);

    json_ok();
  }

  json_err('უცნობი action: ' . $action, 404);

} catch (Throwable $e) {
  json_err('Server error: ' . $e->getMessage(), 500);
}
