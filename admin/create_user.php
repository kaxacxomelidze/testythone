<?php
require __DIR__ . '/config.php';
require_login();
require_super_admin();

$pdo = db();

/**
 * Run once, then DELETE this file.
 * Creates/updates:
 *  - super / YOUR_STRONG_PASS
 *  - admin / YOUR_STRONG_PASS
 */

$usersToEnsure = [
  ['username' => 'super', 'pass' => 'Super@12345678', 'role' => 'super'],
  ['username' => 'admin', 'pass' => 'Admin@12345678', 'role' => 'admin'],
];

foreach ($usersToEnsure as $u) {
  $hash = password_hash($u['pass'], PASSWORD_DEFAULT);

  // if exists -> update; else insert
  $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username=? LIMIT 1");
  $stmt->execute([$u['username']]);
  $row = $stmt->fetch();

  if ($row) {
    $pdo->prepare("UPDATE admin_users SET password_hash=?, role=?, is_active=1 WHERE id=?")
        ->execute([$hash, $u['role'], $row['id']]);
    if (function_exists('log_admin_safe')) {
      log_admin_safe('admin_user_updated', 'admin_users', (int)$row['id'], [
        'username' => $u['username'],
        'role' => $u['role'],
        'source' => 'create_user.php',
      ]);
    }
    echo "Updated: {$u['username']} ({$u['role']})<br>";
  } else {
    $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, is_active) VALUES (?,?,?,1)")
        ->execute([$u['username'], $hash, $u['role']]);
    $newId = (int)$pdo->lastInsertId();
    if (function_exists('log_admin_safe')) {
      log_admin_safe('admin_user_created', 'admin_users', $newId, [
        'username' => $u['username'],
        'role' => $u['role'],
        'source' => 'create_user.php',
      ]);
    }
    echo "Created: {$u['username']} ({$u['role']})<br>";
  }
}

echo "<hr>DONE. ახლა აუცილებლად წაშალე: admin/create_user.php";
