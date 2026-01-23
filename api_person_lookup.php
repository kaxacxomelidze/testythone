<?php
require_once __DIR__ . '/admin/config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$pid = trim($_GET['personal_id'] ?? '');
if ($pid === '') { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT personal_id, first_name, last_name, phone, email FROM persons WHERE personal_id=? LIMIT 1");
$stmt->execute([$pid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row ?: []);
