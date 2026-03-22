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
if (!defined('ADMIN_PASS')) define('ADMIN_PASS', 'admin123'); // შეცვალე!

if (!defined('DATA_DIR'))   define('DATA_DIR', __DIR__ . '/../data');
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads');


if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'spg_portal'); // <-- შენი რეალური DB
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
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
        exit("Database '".DB_NAME."' not found. Create it in phpMyAdmin.");
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
      echo '403 Forbidden — Super admin only';
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
    $keys = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
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
    // ✅ if db() is missing -> show error immediately
    if (!function_exists('db')) {
      die("log_admin(): db() function not found. Add db() into config.php or include db.php before calling.");
    }

    $pdo = db();

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $adminName = (string)($_SESSION['admin_user'] ?? ($_SESSION['admin_name'] ?? 'Admin'));

    if ($details !== null && !is_string($details)) {
      $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $stmt = $pdo->prepare("
      INSERT INTO admin_logs (admin_id, admin_name, action, entity, entity_id, details, ip, user_agent)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

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
      die("log_admin(): insert failed: " . print_r($err, true));
    }
  }
}
