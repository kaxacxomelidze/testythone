<?php
declare(strict_types=1);

/**
 * =========================
 * config.php (FINAL SAFE)
 * =========================
 */

/**
 * =========================
 * Session security
 * =========================
 */
$secure = (
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);

if (session_status() !== PHP_SESSION_ACTIVE) {
  if (!headers_sent()) {
    session_set_cookie_params([
      'lifetime' => 0,
      'path'     => '/',
      'domain'   => '',
      'secure'   => $secure,
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
  }
  session_start();
}

/**
 * =========================
 * Constants (SAFE DEFINE)
 * =========================
 */
if (!defined('ADMIN_USER')) define('ADMIN_USER', 'admin');
if (!defined('ADMIN_PASS')) define('ADMIN_PASS', 'admin123');

if (!defined('DATA_DIR'))   define('DATA_DIR', __DIR__ . '/../data');
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads');

if (!function_exists('env_or')) {
  function env_or(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return (string)$_SERVER[$key];
    return $default;
  }
}

if (!defined('DB_HOST')) define('DB_HOST', env_or('DB_HOST', '127.0.0.1'));
if (!defined('DB_NAME')) define('DB_NAME', env_or('DB_NAME', 'youthagency_hub'));
if (!defined('DB_USER')) define('DB_USER', env_or('DB_USER', 'youthagency_lider'));
if (!defined('DB_PASS')) define('DB_PASS', env_or('DB_PASS', 'eKH+TOt-@xZ?dbcI'));

if (!function_exists('security_headers')) {
  function security_headers(bool $isJson = false): void {
    if (headers_sent()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if ($isJson) {
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
    }
  }
}

if (!function_exists('rate_limit_exceeded')) {
  function rate_limit_exceeded(string $bucket, int $max = 120, int $windowSec = 60): bool {
    $ip = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash('sha256', $bucket . '|' . $ip);
    $dir = sys_get_temp_dir() . '/youthagency_rate_limits';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = $dir . '/' . $key . '.json';

    $now = time();
    $data = ['start' => $now, 'count' => 0];

    $fp = @fopen($file, 'c+');
    if (!$fp) return false;
    try {
      if (!flock($fp, LOCK_EX)) return false;
      $raw = stream_get_contents($fp);
      if (is_string($raw) && trim($raw) !== '') {
        $j = json_decode($raw, true);
        if (is_array($j) && isset($j['start'], $j['count'])) {
          $data = ['start' => (int)$j['start'], 'count' => (int)$j['count']];
        }
      }

      if (($now - $data['start']) >= $windowSec) {
        $data = ['start' => $now, 'count' => 0];
      }

      $data['count']++;

      ftruncate($fp, 0);
      rewind($fp);
      fwrite($fp, json_encode($data));
      fflush($fp);
      flock($fp, LOCK_UN);
    } finally {
      fclose($fp);
    }

    return $data['count'] > $max;
  }
}

if (!function_exists('convert_image_to_webp')) {
  function convert_image_to_webp(string $srcPath, string $destPath, int $quality = 90): bool {
    if (!is_file($srcPath) || !function_exists('imagewebp')) return false;

    $info = @getimagesize($srcPath);
    $mime = strtolower((string)($info['mime'] ?? ''));
    if ($mime === '') return false;

    $img = match ($mime) {
      'image/jpeg' => @imagecreatefromjpeg($srcPath),
      'image/png'  => @imagecreatefrompng($srcPath),
      'image/gif'  => @imagecreatefromgif($srcPath),
      'image/webp' => @imagecreatefromwebp($srcPath),
      default      => false,
    };
    if (!$img) return false;

    if ($mime === 'image/png' || $mime === 'image/webp') {
      imagepalettetotruecolor($img);
      imagealphablending($img, true);
      imagesavealpha($img, true);
    }

    $ok = @imagewebp($img, $destPath, max(60, min(100, $quality)));
    imagedestroy($img);
    return (bool)$ok;
  }
}

if (!function_exists('enforce_rate_limit')) {
  function enforce_rate_limit(string $bucket, int $max = 120, int $windowSec = 60, bool $json = true): void {
    if (!rate_limit_exceeded($bucket, $max, $windowSec)) return;
    http_response_code(429);
    if ($json) {
      if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Too many requests. Please try again later.']);
    } else {
      echo 'Too many requests. Please try again later.';
    }
    exit;
  }
}

if (!function_exists('enforce_http_method')) {
  function enforce_http_method(array $allowed, bool $json = true): void {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $allowed = array_map(static fn($m) => strtoupper((string)$m), $allowed);
    if (in_array($method, $allowed, true)) return;

    http_response_code(405);
    if (!headers_sent()) header('Allow: ' . implode(', ', $allowed));
    if ($json) {
      if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    } else {
      echo 'Method Not Allowed';
    }
    exit;
  }
}

if (!function_exists('enforce_content_length')) {
  function enforce_content_length(int $maxBytes = 1048576, bool $json = true): void {
    $len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len <= 0 || $len <= $maxBytes) return;

    http_response_code(413);
    if ($json) {
      if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Payload too large']);
    } else {
      echo 'Payload too large';
    }
    exit;
  }
}

if (!function_exists('enforce_same_origin_post')) {
  function enforce_same_origin_post(bool $json = true): void {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') return;

    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') return;

    $originHost = parse_url($origin, PHP_URL_HOST);
    if (!is_string($originHost) || $originHost === '' || strcasecmp($originHost, $host) === 0) return;

    http_response_code(403);
    if ($json) {
      if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Forbidden origin']);
    } else {
      echo 'Forbidden origin';
    }
    exit;
  }
}

/**
 * =========================
 * PDO Database
 * =========================
 */
if (!function_exists('db')) {
  function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
    } catch (PDOException $e) {
      if (str_contains($e->getMessage(), 'Unknown database')) {
        exit("Database '" . DB_NAME . "' not found. Create it in phpMyAdmin.");
      }
      throw $e;
    }

    return $pdo;
  }
}

/**
 * =========================
 * Ensure directories
 * =========================
 */
foreach ([DATA_DIR, UPLOAD_DIR, UPLOAD_DIR . '/slides'] as $dir) {
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }
}

/**
 * =========================
 * Auth
 * =========================
 */
if (!function_exists('require_login')) {
  function require_login(): void {
    if (empty($_SESSION['admin_logged_in'])) {
      header('Location: login.php');
      exit;
    }

    $self = basename((string)($_SERVER['PHP_SELF'] ?? ''));
    if ($self === '' || $self === 'login.php' || $self === 'logout.php') return;
    if (is_super_admin()) return;

    if (!admin_has_page_access($self)) {
      http_response_code(403);
      echo '403 Forbidden - ამ გვერდზე წვდომა არ გაქვთ';
      exit;
    }
  }
}

if (!function_exists('admin_page_catalog')) {
  function admin_page_catalog(): array {
    return [
      'index.php' => 'სლაიდერი / პარამეტრები',
      'news.php' => 'სიახლეები',
      'camps.php' => 'ბანაკები',
      'camp_applicants.php' => 'ბანაკის აპლიკანტები',
      'admin_grants.php' => 'გრანტები',
      'admin_builder.php' => 'გრანტების ბილდერი',
      'admin_apps.php' => 'განაცხადები',
      'special_pages.php' => 'სპეციალური გვერდები',
      'contact_messages.php' => 'საკონტაქტო შეტყობინებები',
      'admins.php' => 'ადმინები',
      'admin_logs.php' => 'ადმინის ლოგები',
    ];
  }
}

if (!function_exists('admin_page_alias_map')) {
  function admin_page_alias_map(): array {
    return [
      // slider/settings sub-pages -> index.php permission
      'slide_add.php' => 'index.php',
      'slide_edit.php' => 'index.php',
      'slide_delete.php' => 'index.php',

      // news sub-pages -> news.php permission
      'news_add.php' => 'news.php',
      'news_edit.php' => 'news.php',
      'news_delete.php' => 'news.php',

      // grants/applications builder related pages
      'admin_builder.php' => 'admin_builder.php',
      'admin_apps.php' => 'admin_apps.php',
      'grants_edit.php' => 'admin_grants.php',
      'export_application_word.php' => 'admin_apps.php',
      'upload_file.php' => 'admin_apps.php',

      // admin management/log helper pages
      'create_user.php' => 'admins.php',
      'test_log.php' => 'admin_logs.php',
    ];
  }
}

if (!function_exists('ensure_admin_permissions_table')) {
  function ensure_admin_permissions_table(): void {
    try {
      $pdo = db();
      $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_page_permissions (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          admin_id INT UNSIGNED NOT NULL,
          page_key VARCHAR(120) NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uniq_admin_page (admin_id, page_key),
          KEY idx_admin (admin_id),
          CONSTRAINT fk_admin_perm_admin FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      ");
    } catch (Throwable $e) {
      // ignore: fallback is allow access
    }
  }
}

if (!function_exists('get_admin_page_permissions')) {
  function get_admin_page_permissions(int $adminId): array {
    if ($adminId <= 0) return [];
    try {
      ensure_admin_permissions_table();
      $pdo = db();
      $st = $pdo->prepare("SELECT page_key FROM admin_page_permissions WHERE admin_id=?");
      $st->execute([$adminId]);
      return array_values(array_unique(array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    } catch (Throwable $e) {
      return [];
    }
  }
}

if (!function_exists('set_admin_page_permissions')) {
  function set_admin_page_permissions(int $adminId, array $pages): void {
    if ($adminId <= 0) return;
    ensure_admin_permissions_table();
    $allowed = array_keys(admin_page_catalog());
    $pages = array_values(array_unique(array_filter(array_map('strval', $pages), fn($p) => in_array($p, $allowed, true))));

    $pdo = db();
    $pdo->beginTransaction();
    try {
      $pdo->prepare("DELETE FROM admin_page_permissions WHERE admin_id=?")->execute([$adminId]);
      if ($pages) {
        $ins = $pdo->prepare("INSERT INTO admin_page_permissions(admin_id, page_key) VALUES(?,?)");
        foreach ($pages as $p) $ins->execute([$adminId, $p]);
      }
      $pdo->commit();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $e;
    }
  }
}

if (!function_exists('admin_has_page_access')) {
  function admin_has_page_access(string $pageKey): bool {
    $pageKey = basename(trim($pageKey));
    if ($pageKey === '' || $pageKey === 'logout.php') return true;
    if (is_super_admin()) return true;

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) return false;

    $aliases = admin_page_alias_map();
    $checkKey = $aliases[$pageKey] ?? $pageKey;

    $catalog = admin_page_catalog();
    if (!isset($catalog[$checkKey])) return false;

    $perms = get_admin_page_permissions($adminId);
    if (!$perms) return false; // explicit permissions required for non-super admins
    return in_array($checkKey, $perms, true);
  }
}

/**
 * =========================
 * HTML escape
 * =========================
 */
if (!function_exists('h')) {
  function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

/**
 * =========================
 * CSRF
 * =========================
 */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
  }
}

if (!function_exists('csrf_check')) {
  function csrf_check(?string $token): void {
    if (
      empty($token) ||
      empty($_SESSION['csrf']) ||
      !hash_equals($_SESSION['csrf'], (string)$token)
    ) {
      http_response_code(400);
      exit('Bad Request (CSRF)');
    }
  }
}

/**
 * =========================
 * Admin roles helpers
 * =========================
 */
if (!function_exists('is_super_admin')) {
  function is_super_admin(): bool {
    return !empty($_SESSION['admin_logged_in'])
      && ($_SESSION['admin_role'] ?? '') === 'super';
  }
}

if (!function_exists('require_super_admin')) {
  function require_super_admin(): void {
    if (!is_super_admin()) {
      http_response_code(403);
      echo '403 Forbidden - Super admin only';
      exit;
    }
  }
}

if (!function_exists('admin_name')) {
  function admin_name(): string {
    return $_SESSION['admin_name'] ?? 'Admin';
  }
}

if (!function_exists('client_ip')) {
  function client_ip(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
      if (!empty($_SERVER[$k])) {
        return trim(explode(',', $_SERVER[$k])[0]);
      }
    }
    return 'unknown';
  }
}

if (!function_exists('log_admin')) {
  function log_admin(string $action, ?string $entity = null, ?int $entityId = null, $details = null): void {
    if (!function_exists('db')) {
      error_log("log_admin(): db() function not found.");
      return;
    }

    try {
      $pdo = db();
    } catch (Throwable $e) {
      error_log("log_admin(): db() failed: " . $e->getMessage());
      return;
    }

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $adminName = (string)($_SESSION['admin_user'] ?? ($_SESSION['admin_name'] ?? 'Admin'));

    if ($details !== null && !is_string($details)) {
      $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $stmt = $pdo->prepare("
      INSERT INTO admin_logs (admin_id, admin_name, action, entity, entity_id, details, ip, user_agent)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    try {
      $ok = $stmt->execute([
        $adminId ?: null,
        $adminName ?: null,
        $action,
        $entity,
        $entityId,
        $details,
        client_ip(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
      ]);
      if (!$ok) {
        $err = $stmt->errorInfo();
        error_log("log_admin(): insert failed: " . print_r($err, true));
      }
    } catch (Throwable $e) {
      error_log("log_admin(): exception: " . $e->getMessage());
    }
  }
}

if (!function_exists('log_admin_safe')) {
  function log_admin_safe(string $action, ?string $entity = null, ?int $entityId = null, $details = null): void {
    try {
      log_admin($action, $entity, $entityId, $details);
    } catch (Throwable $e) {
    }
  }
}

if (!function_exists('bootstrap_admin_audit')) {
  function bootstrap_admin_audit(): void {
    static $booted = false;
    if ($booted) return;
    $booted = true;

    if (PHP_SAPI === 'cli') return;
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if (!str_contains($uri, '/admin/')) return;

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

    $startedAt = microtime(true);
    $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
    $entity = basename((string)$path);
    $postKeys = array_keys($_POST ?? []);
    $queryAction = (string)($_GET['action'] ?? '');

    register_shutdown_function(function () use ($startedAt, $method, $path, $entity, $postKeys, $queryAction): void {
      $status = http_response_code();
      if ($status === false) $status = 200;
      $durationMs = (int)round((microtime(true) - $startedAt) * 1000);

      log_admin_safe('admin_request', $entity, null, [
        'method' => $method,
        'path' => $path,
        'query_action' => $queryAction,
        'post_keys' => $postKeys,
        'status' => $status,
        'duration_ms' => $durationMs,
      ]);
    });
  }
}

bootstrap_admin_audit();

security_headers(false);
