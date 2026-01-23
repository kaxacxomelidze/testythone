<?php
require_once __DIR__ . '/config.php';
require_login();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("UPDATE slides SET is_active=0 WHERE id=? LIMIT 1");
$stmt->execute([$id]);

header('Location: index.php');
exit;
