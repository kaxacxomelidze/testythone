<?php
require_once __DIR__ . '/config.php';
require_login();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
  // log
  if (function_exists('log_admin')) {
    log_admin('news_delete', 'news', $id, []);
  }

  // delete will cascade news_images
  $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$id]);
}

header('Location: news.php');
exit;
