<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/config.php';

try {
  $pdo = db();
  $items = $pdo->query("
    SELECT id, title, slug, body, image_path, published_at
    FROM news
    WHERE is_active = 1
    ORDER BY sort_order ASC, COALESCE(published_at, created_at) DESC, id DESC
    LIMIT 30
  ")->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true, 'news'=>$items], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false], JSON_UNESCAPED_UNICODE);
}
