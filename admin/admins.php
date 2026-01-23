<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

require_login();
require_super_admin();

$title = 'Admins';
$csrf = csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? '');

  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $pass = (string)($_POST['pass'] ?? '');
    $role = ($_POST['role'] ?? 'admin') === 'super' ? 'super' : 'admin';

    if (strlen($username) < 3 || strlen($pass) < 8) {
      $msg = "Username მინ 3, Password მინ 8 სიმბოლო.";
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?,?,?)");
      try {
        $stmt->execute([$username, $hash, $role]);
        $msg = "დამატებულია.";
      } catch (Throwable $e) {
        $msg = "ვერ დაემატა (შეიძლება username უკვე არსებობს).";
      }
    }
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $role = ($_POST['role'] ?? 'admin') === 'super' ? 'super' : 'admin';
    $active = !empty($_POST['is_active']) ? 1 : 0;

    // don’t allow disabling yourself
    if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
      $msg = "საკუთარ თავს ვერ გათიშავ.";
    } else {
      $pdo->prepare("UPDATE admin_users SET role=?, is_active=? WHERE id=?")->execute([$role, $active, $id]);
      $msg = "შენახულია.";
    }

    // optional password reset
    $newPass = (string)($_POST['new_pass'] ?? '');
    if ($newPass !== '') {
      if (strlen($newPass) < 8) {
        $msg = "Password მინ 8 სიმბოლო უნდა იყოს.";
      } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
        $msg = "Password განახლდა.";
      }
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
      $msg = "საკუთარ თავს ვერ წაშლი.";
    } else {
      $pdo->prepare("DELETE FROM admin_users WHERE id=?")->execute([$id]);
      $msg = "წაიშალა.";
    }
  }
}

$admins = $pdo->query("SELECT id, username, role, is_active, created_at, last_login_at FROM admin_users ORDER BY role DESC, id ASC")->fetchAll();

ob_start();
?>
<div class="card">
  <div class="muted" style="margin-bottom:10px">Only SUPER ADMIN can manage admins.</div>
  <?php if (!empty($msg)): ?>
    <div style="margin-bottom:10px;color:var(--muted)"><?=h($msg)?></div>
  <?php endif; ?>

  <h3 style="margin:0 0 10px">Add Admin</h3>
  <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
    <input type="hidden" name="csrf" value="<?=h($csrf)?>">
    <input type="hidden" name="action" value="create">

    <div>
      <label class="muted">Username</label>
      <input name="username" required style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">
    </div>
    <div>
      <label class="muted">Password (min 8)</label>
      <input name="pass" type="password" required style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">
    </div>
    <div>
      <label class="muted">Role</label>
      <select name="role" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">
        <option value="admin">admin</option>
        <option value="super">super</option>
      </select>
    </div>
    <button class="btn ac" type="submit">Create</button>
  </form>
</div>

<div class="card">
  <h3 style="margin:0 0 10px">Admins list</h3>

  <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr class="muted">
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">User</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Role</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Active</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Last login</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($admins as $a): ?>
        <tr>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($a['username'])?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($a['role'])?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?= (int)$a['is_active'] ? 'yes' : 'no' ?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($a['last_login_at'] ?? '-')?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <form method="post" style="display:grid;grid-template-columns:140px 120px 120px 140px auto auto;gap:8px;align-items:center">
              <input type="hidden" name="csrf" value="<?=h($csrf)?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?=h($a['id'])?>">

              <select name="role" style="padding:8px;border-radius:10px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">
                <option value="admin" <?=$a['role']==='admin'?'selected':''?>>admin</option>
                <option value="super" <?=$a['role']==='super'?'selected':''?>>super</option>
              </select>

              <label style="display:flex;gap:8px;align-items:center" class="muted">
                <input type="checkbox" name="is_active" value="1" <?= (int)$a['is_active'] ? 'checked':'' ?>>
                active
              </label>

              <input name="new_pass" type="password" placeholder="new password" style="padding:8px;border-radius:10px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)">

              <button class="btn ac" type="submit">Save</button>
            </form>

            <form method="post" style="margin-top:8px" onsubmit="return confirm('Delete admin?')">
              <input type="hidden" name="csrf" value="<?=h($csrf)?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?=h($a['id'])?>">
              <button class="btn bad" type="submit">Delete</button>
            </form>

          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
