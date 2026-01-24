<?php
require_once __DIR__ . '/config.php';
require_login();

$file = DATA_DIR . '/contact_messages.json';
$messages = [];

if (file_exists($file)) {
  $raw = file_get_contents($file);
  $decoded = json_decode($raw ?: '', true);
  if (is_array($decoded)) {
    $messages = array_reverse($decoded);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? null);
  $deleteId = trim((string)($_POST['delete_id'] ?? ''));
  if ($deleteId !== '' && file_exists($file)) {
    $items = json_decode(file_get_contents($file) ?: '', true);
    if (is_array($items)) {
      $items = array_values(array_filter($items, fn($item) => ($item['id'] ?? '') !== $deleteId));
      file_put_contents($file, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
      $messages = array_reverse($items);
    }
  }
}

$title = 'Contact Messages';
ob_start();
?>
<div class="card">
  <h3 style="margin:0 0 12px">Contact Messages</h3>
  <?php if (!$messages): ?>
    <div class="muted">No messages yet.</div>
  <?php else: ?>
    <div style="overflow:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr class="muted">
            <th style="padding:10px;border-bottom:1px solid var(--line)">Date</th>
            <th style="padding:10px;border-bottom:1px solid var(--line)">Name</th>
            <th style="padding:10px;border-bottom:1px solid var(--line)">Email</th>
            <th style="padding:10px;border-bottom:1px solid var(--line)">Phone</th>
            <th style="padding:10px;border-bottom:1px solid var(--line)">Message</th>
            <th style="padding:10px;border-bottom:1px solid var(--line)">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($messages as $msg): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--line);white-space:nowrap"><?=h($msg['created_at'] ?? '')?></td>
            <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($msg['name'] ?? '')?></td>
            <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($msg['email'] ?? '')?></td>
            <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($msg['phone'] ?? '')?></td>
            <td style="padding:10px;border-bottom:1px solid var(--line);min-width:240px"><?=nl2br(h($msg['message'] ?? ''))?></td>
            <td style="padding:10px;border-bottom:1px solid var(--line)">
              <form method="post" onsubmit="return confirm('Delete this message?')">
                <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="delete_id" value="<?=h($msg['id'] ?? '')?>">
                <button class="btn bad" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
