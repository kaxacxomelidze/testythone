<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/config.php';

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
  $items = $pdo->query("
    SELECT id, title, slug, body, image_path, published_at
    FROM news
    WHERE is_active = 1
    ORDER BY sort_order ASC, COALESCE(published_at, created_at) DESC, id DESC
    LIMIT 30
  ")->fetchAll(PDO::FETCH_ASSOC);

  foreach ($items as &$item) {
    $item['image_path'] = $normalizePath((string)($item['image_path'] ?? ''));
  }
  unset($item);

  echo json_encode(['ok'=>true, 'news'=>$items], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false], JSON_UNESCAPED_UNICODE);
}
