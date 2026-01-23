<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
  $pdo = db();

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
