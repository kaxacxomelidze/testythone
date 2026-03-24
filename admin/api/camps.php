<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php'; // must define $pdo (PDO)
security_headers(true);
enforce_http_method(['GET', 'POST'], true);
enforce_same_origin_post(true);
enforce_content_length(25 * 1024 * 1024, true);
enforce_rate_limit('admin_api_camps', 600, 60, true);

// Make sure session exists (sometimes config.php already starts it, but safe)
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

// ALWAYS JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Prevent broken JSON from warnings/notices
@ini_set('display_errors', '0');
if (ob_get_level() === 0) { ob_start(); }

/* ----------------------------- JSON helpers ------------------------------ */
function out(array $j): void {
  if (ob_get_level() > 0) { @ob_end_clean(); }
  echo json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function ok(array $extra = []): void { out(["ok"=>true] + $extra); }
function fail(string $msg, int $code = 400): void { http_response_code($code); out(["ok"=>false,"error"=>$msg]); }

/* ----------------------------- auth + db --------------------------------- */
if (empty($_SESSION['admin_logged_in']) || (int)($_SESSION['admin_logged_in']) !== 1) {
  fail("Unauthorized", 401);
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  fail("DB not initialized: \$pdo missing from admin/db.php", 500);
}

$action = (string)($_GET['action'] ?? '');

/* ----------------------------- small utils -------------------------------- */
function slugify(string $s): string {
  $s = trim(mb_strtolower($s, 'UTF-8'));
  $s = preg_replace('/\s+/u', '-', $s);
  $s = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $s);
  $s = preg_replace('/\-{2,}/', '-', $s);
  return trim($s, '-') ?: 'camp';
}

function json_in(): array {
  $raw = file_get_contents('php://input') ?: '';
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
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
  try { $pdo->exec("ALTER TABLE `$table` $sql"); } catch(Throwable $e) {}
}
function ensure_camps_i18n(PDO $pdo): void {
  if (!has_col($pdo, 'camps', 'name_en'))       add_col($pdo, 'camps', "ADD COLUMN name_en VARCHAR(255) NULL AFTER name");
  if (!has_col($pdo, 'camps', 'card_text_en'))  add_col($pdo, 'camps', "ADD COLUMN card_text_en TEXT NULL AFTER card_text");
  if (!has_col($pdo, 'camps_fields', 'label_en')) add_col($pdo, 'camps_fields', "ADD COLUMN label_en VARCHAR(255) NULL AFTER label");
  if (!has_col($pdo, 'camps_posts', 'title_en'))  add_col($pdo, 'camps_posts', "ADD COLUMN title_en VARCHAR(255) NULL AFTER title");
  if (!has_col($pdo, 'camps_posts', 'body_en'))   add_col($pdo, 'camps_posts', "ADD COLUMN body_en MEDIUMTEXT NULL AFTER body");
}

function upload_image(string $fieldName, string $subdir, array $allowedExt = ['jpg','jpeg','png','webp','gif']): string {
  if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return '';

  $tmp = $_FILES[$fieldName]['tmp_name'];
  $orig = basename((string)$_FILES[$fieldName]['name']);
  $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) fail("File must be: " . implode(", ", $allowedExt));

  $dir = __DIR__ . '/../../uploads/' . $subdir;
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) fail("Cannot create uploads folder: /uploads/$subdir", 500);
  }

  $fname = "up_" . time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;
  $dest = $dir . "/" . $fname;
  if (!move_uploaded_file($tmp, $dest)) fail("Upload failed", 500);

  return "/uploads/$subdir/" . $fname;
}

function upload_many_images(string $fieldName, string $subdir): array {
  if (empty($_FILES[$fieldName])) return [];

  $paths = [];
  $allowed = ['jpg','jpeg','png','webp','gif'];

  $dir = __DIR__ . '/../../uploads/' . $subdir;
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) fail("Cannot create uploads folder: /uploads/$subdir", 500);
  }

  $names = $_FILES[$fieldName]['name'] ?? [];
  $tmps  = $_FILES[$fieldName]['tmp_name'] ?? [];
  $errs  = $_FILES[$fieldName]['error'] ?? [];

  for ($i=0; $i<count($names); $i++) {
    if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
    $orig = basename((string)$names[$i]);
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) continue;

    $fname = "gal_" . time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;
    $dest = $dir . "/" . $fname;

    if (move_uploaded_file($tmps[$i], $dest)) {
      $paths[] = "/uploads/$subdir/" . $fname;
    }
  }
  return $paths;
}

/**
 * label/type -> field_key (Georgian mapping)
 */
function guess_field_key(string $type, string $label): string {
  $t = mb_strtolower(trim($type), 'UTF-8');
  $l = mb_strtolower(trim($label), 'UTF-8');

  if ($t === 'pid') return 'pid';
  if ($t === 'email') return 'email';
  if ($t === 'phone') return 'phone';

  $map = [
    'first_name' => ['სახელი'],
    'last_name'  => ['გვარი'],
    'age'        => ['ასაკი'],
    'email'      => ['ელფოსტა', 'მეილი'],
    'phone'      => ['ტელეფონი', 'მობილური'],
    'university' => ['უნივერსიტეტი'],
    'faculty'    => ['ფაკულტეტი'],
    'course'     => ['კურსი'],
    'birth_date' => ['დაბადების', 'დაბადება'],
    'address'    => ['მისამართი'],
  ];

  foreach ($map as $key => $words) {
    foreach ($words as $w) {
      if (mb_strpos($l, $w) !== false) return $key;
    }
  }
  return '';
}

/* -------- Attendance / history table (for History button) -------- */
function ensureAttendanceTable(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS camps_attendance (
      id INT NOT NULL AUTO_INCREMENT,
      unique_key VARCHAR(255) NOT NULL,
      camp_id INT NOT NULL,
      registration_id INT DEFAULT NULL,
      camp_name VARCHAR(255) NOT NULL,
      start_date DATE DEFAULT NULL,
      end_date DATE DEFAULT NULL,
      approved_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uq_unique_camp (unique_key, camp_id),
      KEY idx_unique_key (unique_key),
      KEY idx_camp_id (camp_id),
      CONSTRAINT fk_att_camp FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
}

try {
  // safe add i18n columns if missing
  ensure_camps_i18n($pdo);

  /* ============================= LIST CAMPS ============================= */
  if ($action === 'list') {
    $in = json_in();
    $q = trim((string)($in['q'] ?? ''));

    if ($q !== '') {
      $stmt = $pdo->prepare("
        SELECT c.*,
          (SELECT COUNT(*) FROM camps_fields f WHERE f.camp_id=c.id) AS fieldsCount
        FROM camps c
        WHERE c.name LIKE ? OR c.card_text LIKE ?
        ORDER BY c.id DESC
        LIMIT 200
      ");
      $like = "%{$q}%";
      $stmt->execute([$like, $like]);
    } else {
      $stmt = $pdo->query("
        SELECT c.*,
          (SELECT COUNT(*) FROM camps_fields f WHERE f.camp_id=c.id) AS fieldsCount
        FROM camps c
        ORDER BY c.id DESC
        LIMIT 200
      ");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
      $r['id'] = (int)$r['id'];
      $r['closed'] = (int)$r['closed'] === 1;
      $r['windowDays'] = (int)$r['window_days'];
      $r['cardText'] = (string)($r['card_text'] ?? '');
      $r['cardTextEn'] = (string)($r['card_text_en'] ?? '');
      $r['cover'] = (string)($r['cover'] ?? '');
      $r['nameEn'] = (string)($r['name_en'] ?? '');
      unset($r['card_text'], $r['card_text_en'], $r['window_days']);
    }
    ok(["camps"=>$rows]);
  }

  /* ============================= GET CAMP ============================== */
  if ($action === 'get') {
    $in = json_in();
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail("Bad id");

    $stmt = $pdo->prepare("SELECT * FROM camps WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) fail("Not found", 404);

    $f = $pdo->prepare("SELECT id,label,label_en,type,required,options_json,field_key
                        FROM camps_fields WHERE camp_id=? ORDER BY sort_order ASC, id ASC");
    $f->execute([$id]);
    $fields = $f->fetchAll(PDO::FETCH_ASSOC);

    $form = [];
    foreach ($fields as $x) {
      $form[] = [
        "id" => (int)$x["id"],
        "label" => (string)$x["label"],
        "label_en" => (string)($x["label_en"] ?? ""),
        "type" => (string)$x["type"],
        "req" => (int)$x["required"] === 1,
        "options" => (string)($x["options_json"] ?? ""),
        "field_key" => (string)($x["field_key"] ?? ""),
      ];
    }

    $p = $pdo->prepare("SELECT id,title,title_en,cover,body,body_en,created_at FROM camps_posts WHERE camp_id=? ORDER BY id DESC");
    $p->execute([$id]);
    $posts = $p->fetchAll(PDO::FETCH_ASSOC);

    $postIds = [];
    foreach ($posts as &$pp) {
      $pp["id"] = (int)$pp["id"];
      $pp["media"] = [];
      $postIds[] = (int)$pp["id"];
    }

    if ($postIds) {
      $inQ = implode(',', array_fill(0, count($postIds), '?'));
      $m = $pdo->prepare("SELECT id,post_id,path,sort_order FROM camps_post_media
                          WHERE post_id IN ($inQ) ORDER BY sort_order ASC, id ASC");
      $m->execute($postIds);

      $byPost = [];
      while ($row = $m->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int)$row['post_id'];
        $byPost[$pid][] = [
          "id" => (int)$row['id'],
          "path" => (string)$row['path'],
          "sort_order" => (int)$row['sort_order'],
        ];
      }
      foreach ($posts as &$pp) {
        $pid = (int)$pp['id'];
        $pp['media'] = $byPost[$pid] ?? [];
      }
    }

    $camp = [
      "id" => (int)$c["id"],
      "name" => (string)$c["name"],
      "nameEn" => (string)($c["name_en"] ?? ""),
      "slug" => (string)($c["slug"] ?? ""),
      "cover" => (string)($c["cover"] ?? ""),
      "cardText" => (string)($c["card_text"] ?? ""),
      "cardTextEn" => (string)($c["card_text_en"] ?? ""),
      "start" => (string)$c["start_date"],
      "end" => (string)$c["end_date"],
      "closed" => (int)$c["closed"] === 1,
      "windowDays" => (int)$c["window_days"],
      "form" => $form,
      "posts" => $posts,
    ];
    ok(["camp"=>$camp]);
  }

  /* ============================= SAVE CAMP ============================= */
  if ($action === 'save') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;

    $name = trim((string)($_POST['name'] ?? ''));
    $nameEn = trim((string)($_POST['name_en'] ?? ''));
    $cardText = trim((string)($_POST['cardText'] ?? ''));
    $cardTextEn = trim((string)($_POST['cardText_en'] ?? ''));
    $start = (string)($_POST['start'] ?? '');
    $end = (string)($_POST['end'] ?? '');
    $closed = ((string)($_POST['closed'] ?? '0') === '1') ? 1 : 0;
    $windowDays = (int)($_POST['windowDays'] ?? 365);

    if ($name === '' || $start === '' || $end === '') fail("name/start/end required");
    if ($windowDays < 1) $windowDays = 365;

    $slug = slugify($name);

    $oldCover = '';
    if ($id > 0) {
      $st = $pdo->prepare("SELECT cover FROM camps WHERE id=? LIMIT 1");
      $st->execute([$id]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      $oldCover = (string)($row['cover'] ?? '');
    }

    $coverPath = $oldCover;
    $newCover = upload_image('cover_file', 'camps');
    if ($newCover !== '') $coverPath = $newCover;

    if ($id > 0) {
      $stmt = $pdo->prepare("
        UPDATE camps SET name=?, name_en=?, slug=?, cover=?, card_text=?, card_text_en=?, start_date=?, end_date=?, window_days=?, closed=?
        WHERE id=?
      ");
      $stmt->execute([
        $name, ($nameEn !== '' ? $nameEn : null),
        $slug, $coverPath, $cardText, ($cardTextEn !== '' ? $cardTextEn : null),
        $start, $end, $windowDays, $closed, $id
      ]);
      ok(["id"=>$id]);
    } else {
      $stmt = $pdo->prepare("
        INSERT INTO camps(name,name_en,slug,cover,card_text,card_text_en,start_date,end_date,window_days,closed,created_at)
        VALUES(?,?,?,?,?,?,?,?,?,?,NOW())
      ");
      $stmt->execute([
        $name, ($nameEn !== '' ? $nameEn : null),
        $slug, $coverPath, $cardText, ($cardTextEn !== '' ? $cardTextEn : null),
        $start, $end, $windowDays, $closed
      ]);
      ok(["id"=>(int)$pdo->lastInsertId()]);
    }
  }

  /* ============================= DELETE CAMP ============================ */
  if ($action === 'delete') {
    $in = json_in();
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail("Bad id");

    $pdo->prepare("DELETE FROM camps_registrations WHERE camp_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM camps_post_media WHERE post_id IN (SELECT id FROM camps_posts WHERE camp_id=?)")->execute([$id]);
    $pdo->prepare("DELETE FROM camps_posts WHERE camp_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM camps_fields WHERE camp_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM camps_pid_blocklist WHERE camp_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM camps WHERE id=?")->execute([$id]);
    ok();
  }

  /* ============================= SAVE FORM ============================= */
  if ($action === 'saveForm') {
    $in = json_in();
    $campId = (int)($in['campId'] ?? 0);
    $fields = $in['fields'] ?? null;
    if ($campId <= 0 || !is_array($fields)) fail("Bad campId/fields");

    $pdo->prepare("DELETE FROM camps_fields WHERE camp_id=?")->execute([$campId]);

    $sort = 1;
    $stmt = $pdo->prepare("
      INSERT INTO camps_fields(camp_id,sort_order,label,label_en,type,required,options_json,field_key)
      VALUES(?,?,?,?,?,?,?,?)
    ");

    foreach ($fields as $f) {
      $label = trim((string)($f['label'] ?? ''));
      $labelEn = trim((string)($f['label_en'] ?? ''));
      $type  = (string)($f['type'] ?? 'text');
      $req   = !empty($f['req']) ? 1 : 0;
      $opts  = trim((string)($f['options'] ?? ''));

      $fk = trim((string)($f['field_key'] ?? ''));
      if ($fk === '') {
        $oj = json_decode($opts !== '' ? $opts : 'null', true);
        if (is_array($oj) && !empty($oj['autofill'])) $fk = trim((string)$oj['autofill']);
      }
      if ($fk === '') $fk = guess_field_key($type, $label);

      if ($label === '') continue;
      $stmt->execute([
        $campId,
        $sort++,
        $label,
        ($labelEn !== '' ? $labelEn : null),
        $type,
        $req,
        $opts,
        ($fk !== '' ? $fk : null)
      ]);
    }
    ok();
  }

  /* ============================= POSTS SAVE ============================= */
  if ($action === 'postSave') {
    $campId = (int)($_POST['campId'] ?? 0);
    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $titleEn = trim((string)($_POST['title_en'] ?? ''));
    $body  = trim((string)($_POST['body'] ?? ''));
    $bodyEn  = trim((string)($_POST['body_en'] ?? ''));
    if ($campId<=0 || $title==='' || $body==='') fail("campId/title/body required");

    $oldCover = '';
    if ($id > 0) {
      $st = $pdo->prepare("SELECT cover FROM camps_posts WHERE id=? AND camp_id=? LIMIT 1");
      $st->execute([$id,$campId]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row) fail("Post not found", 404);
      $oldCover = (string)($row['cover'] ?? '');
    }

    $coverPath = $oldCover;
    $newCover = upload_image('cover_file', 'camps/posts');
    if ($newCover !== '') $coverPath = $newCover;

    if ($id > 0) {
      $pdo->prepare("UPDATE camps_posts SET title=?, title_en=?, cover=?, body=?, body_en=? WHERE id=? AND camp_id=?")
          ->execute([$title, ($titleEn !== '' ? $titleEn : null), $coverPath, $body, ($bodyEn !== '' ? $bodyEn : null), $id, $campId]);
      $postId = $id;
    } else {
      $pdo->prepare("INSERT INTO camps_posts(camp_id,title,title_en,cover,body,body_en,created_at) VALUES(?,?,?,?,?,?,NOW())")
          ->execute([$campId,$title,($titleEn !== '' ? $titleEn : null),$coverPath,$body,($bodyEn !== '' ? $bodyEn : null)]);
      $postId = (int)$pdo->lastInsertId();
    }

    $paths = upload_many_images('gallery_files', 'camps/posts');
    if ($paths) {
      $mx = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0) AS mx FROM camps_post_media WHERE post_id=?");
      $mx->execute([$postId]);
      $base = (int)($mx->fetch(PDO::FETCH_ASSOC)['mx'] ?? 0);

      $ins = $pdo->prepare("INSERT INTO camps_post_media(post_id,path,sort_order,created_at) VALUES(?,?,?,NOW())");
      $n = 1;
      foreach ($paths as $p) $ins->execute([$postId,$p,$base + $n++]);
    }

    ok(["id"=>$postId]);
  }

  if ($action === 'postDelete') {
    $in = json_in();
    $campId = (int)($in['campId'] ?? 0);
    $id = (int)($in['id'] ?? 0);
    if ($campId<=0 || $id<=0) fail("Bad campId/id");
    $pdo->prepare("DELETE FROM camps_post_media WHERE post_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM camps_posts WHERE id=? AND camp_id=?")->execute([$id,$campId]);
    ok();
  }

  if ($action === 'postMediaDelete') {
    $in = json_in();
    $id = (int)($in['id'] ?? 0);
    if ($id<=0) fail("Bad id");

    $st = $pdo->prepare("SELECT path FROM camps_post_media WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) fail("Not found", 404);

    $pdo->prepare("DELETE FROM camps_post_media WHERE id=?")->execute([$id]);

    $path = (string)$row['path'];
    if (str_starts_with($path, "/uploads/")) {
      $abs = __DIR__ . "/../.." . ltrim($path, "/");
      if (is_file($abs)) @unlink($abs);
    }
    ok();
  }

  /* ============================= APPLICANTS ============================= */
  if ($action === 'applicants') {
    $in = json_in();
    $campId = (int)($in['campId'] ?? 0);
    $status = trim((string)($in['status'] ?? '')); // pending/approved/rejected or ""
    $q = trim((string)($in['q'] ?? ''));
    if ($campId <= 0) fail("Bad campId");

    ensureAttendanceTable($pdo);

    $f = $pdo->prepare("SELECT id,label,label_en,type,field_key FROM camps_fields WHERE camp_id=? ORDER BY sort_order ASC, id ASC");
    $f->execute([$campId]);
    $fields = $f->fetchAll(PDO::FETCH_ASSOC);

    $where = "WHERE r.camp_id=?";
    $params = [$campId];

    if ($status !== '' && in_array($status, ['pending','approved','rejected'], true)) {
      $where .= " AND r.status=?";
      $params[] = $status;
    }

    if ($q !== '') {
      $where .= " AND (r.unique_key LIKE ? OR r.values_json LIKE ?)";
      $like = "%{$q}%";
      $params[] = $like;
      $params[] = $like;
    }

    $sql = "
      SELECT
        r.id, r.created_at, r.unique_key, r.ip, r.status, r.admin_note, r.updated_at, r.values_json,
        EXISTS(SELECT 1 FROM camps_attendance a WHERE a.unique_key = r.unique_key LIMIT 1) AS has_history
      FROM camps_registrations r
      $where
      ORDER BY r.id DESC
      LIMIT 1000
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    $rows = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $vals = json_decode((string)($row['values_json'] ?? '[]'), true);
      if (!is_array($vals)) $vals = [];

      $rows[] = [
        "id" => (int)$row["id"],
        "created_at" => (string)$row["created_at"],
        "unique_key" => (string)$row["unique_key"],
        "ip" => (string)($row["ip"] ?? ""),
        "status" => (string)($row["status"] ?? "pending"),
        "admin_note" => (string)($row["admin_note"] ?? ""),
        "updated_at" => (string)($row["updated_at"] ?? ""),
        "has_history" => ((int)($row["has_history"] ?? 0) === 1),
        "values" => $vals
      ];
    }

    ok(["fields"=>$fields, "rows"=>$rows]);
  }

  /* ================== STATUS UPDATE + ATTENDANCE WRITE ================== */
  if ($action === 'applicantStatus') {
    $in = json_in();
    $id = (int)($in['id'] ?? 0);
    $status = (string)($in['status'] ?? '');
    $note = trim((string)($in['note'] ?? ''));

    if ($id <= 0) fail("Bad id");
    if (!in_array($status, ['pending','approved','rejected'], true)) fail("Bad status");

    ensureAttendanceTable($pdo);

    $st = $pdo->prepare("SELECT id,camp_id,unique_key FROM camps_registrations WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $reg = $st->fetch(PDO::FETCH_ASSOC);
    if (!$reg) fail("Registration not found", 404);

    $campId = (int)$reg['camp_id'];
    $uniqueKey = (string)$reg['unique_key'];

    try {
      if (!$pdo->inTransaction()) $pdo->beginTransaction();

      $pdo->prepare("UPDATE camps_registrations SET status=?, admin_note=?, updated_at=NOW() WHERE id=?")
          ->execute([$status, $note, $id]);

      if ($status === 'approved') {
        $cst = $pdo->prepare("SELECT name,start_date,end_date FROM camps WHERE id=? LIMIT 1");
        $cst->execute([$campId]);
        $camp = $cst->fetch(PDO::FETCH_ASSOC);

        $campName = (string)($camp['name'] ?? '');
        $start = $camp['start_date'] ?? null;
        $end   = $camp['end_date'] ?? null;

        $ins = $pdo->prepare("
          INSERT INTO camps_attendance(unique_key,camp_id,registration_id,camp_name,start_date,end_date,approved_at,created_at)
          VALUES(?,?,?,?,?,?,NOW(),NOW())
          ON DUPLICATE KEY UPDATE
            registration_id = VALUES(registration_id),
            camp_name = VALUES(camp_name),
            start_date = VALUES(start_date),
            end_date = VALUES(end_date),
            approved_at = VALUES(approved_at)
        ");
        $ins->execute([$uniqueKey,$campId,$id,$campName,$start,$end]);
      } else {
        $del = $pdo->prepare("DELETE FROM camps_attendance WHERE unique_key=? AND camp_id=?");
        $del->execute([$uniqueKey, $campId]);
      }

      $pdo->commit();
      ok();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      fail("Server error: " . $e->getMessage(), 500);
    }
  }

  /* ====================== ATTENDANCE HISTORY (MODAL) ===================== */
  if ($action === 'attendanceByUniqueKey') {
    $in = json_in();
    $uniqueKey = trim((string)($in['unique_key'] ?? ''));
    if ($uniqueKey === '') fail("unique_key required");

    ensureAttendanceTable($pdo);

    $st = $pdo->prepare("
      SELECT id, unique_key, camp_id, registration_id, camp_name, start_date, end_date, approved_at
      FROM camps_attendance
      WHERE unique_key = ?
      ORDER BY approved_at DESC, id DESC
      LIMIT 200
    ");
    $st->execute([$uniqueKey]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
      $r['id'] = (int)$r['id'];
      $r['camp_id'] = (int)$r['camp_id'];
      $r['registration_id'] = isset($r['registration_id']) ? (int)$r['registration_id'] : null;
      $r['camp_name'] = (string)($r['camp_name'] ?? '');
      $r['start_date'] = (string)($r['start_date'] ?? '');
      $r['end_date'] = (string)($r['end_date'] ?? '');
      $r['approved_at'] = (string)($r['approved_at'] ?? '');
    }
    ok(["rows"=>$rows]);
  }

  /* ============================= PID BLOCKLIST =========================== */
  if ($action === 'pidBlockAdd') {
    $in = json_in();
    $campId = (isset($in['campId']) && (string)$in['campId'] !== '') ? (int)$in['campId'] : null;
    $pid = preg_replace('/\s+/', '', trim((string)($in['pid'] ?? '')));
    $reason = trim((string)($in['reason'] ?? ''));

    if ($pid === '') fail("PID required");

    $st = $pdo->prepare("INSERT INTO camps_pid_blocklist(camp_id,pid,reason)
                         VALUES(?,?,?)
                         ON DUPLICATE KEY UPDATE reason=VALUES(reason)");
    $st->execute([$campId, $pid, $reason]);
    ok();
  }

  if ($action === 'pidBlockRemove') {
    $in = json_in();
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail("Bad id");
    $pdo->prepare("DELETE FROM camps_pid_blocklist WHERE id=?")->execute([$id]);
    ok();
  }

  if ($action === 'pidBlockList') {
    $in = json_in();
    $campId = (isset($in['campId']) && (string)$in['campId'] !== '') ? (int)$in['campId'] : null;

    if ($campId === null) {
      $st = $pdo->query("SELECT id,camp_id,pid,reason,created_at FROM camps_pid_blocklist ORDER BY id DESC LIMIT 2000");
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $st = $pdo->prepare("SELECT id,camp_id,pid,reason,created_at
                           FROM camps_pid_blocklist
                           WHERE camp_id IS NULL OR camp_id = ?
                           ORDER BY id DESC
                           LIMIT 2000");
      $st->execute([$campId]);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    }
    ok(["rows"=>$rows]);
  }

  fail("Unknown action", 400);

} catch (Throwable $e) {
  fail("Server error: " . $e->getMessage(), 500);
}
