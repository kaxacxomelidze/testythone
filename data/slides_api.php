<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
  $pdo = db();

  $normalizePath = static function (?string $path): string {
    $p = trim((string)$path);
    if ($p === '') return '';
    if (preg_match('~^(https?:)?//~i', $p) || str_starts_with($p, 'data:')) return $p;
    $p = str_replace('\\', '/', $p);
    $p = preg_replace('~/+~', '/', $p) ?? $p;
    if (!str_starts_with($p, '/')) $p = '/' . ltrim($p, '/');
    if (str_starts_with($p, '/youthagency/')) $p = '/' . ltrim(substr($p, strlen('/youthagency/')), '/');
    return $p;
  };

  // settings
  $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key`='autoplay_ms' LIMIT 1");
  $stmt->execute();
  $autoplay_ms = (int)($stmt->fetchColumn() ?: 4500);

  // slides
  $slides = $pdo->query("
    SELECT
      id,
      title,
      link,
      image_path AS image,
      sort_order AS `order`
    FROM slides
    WHERE is_active = 1
    ORDER BY sort_order ASC, id DESC
  ")->fetchAll();

  foreach ($slides as &$s) {
    $s['image'] = $normalizePath((string)($s['image'] ?? ''));
  }
  unset($s);

  echo json_encode([
    'settings' => ['autoplay_ms' => $autoplay_ms],
    'slides'   => $slides
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'settings' => ['autoplay_ms' => 4500],
    'slides'   => [],
    'error'    => 'Server error'
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
