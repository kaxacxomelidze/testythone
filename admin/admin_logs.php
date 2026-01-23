<?php
require_once __DIR__ . '/config.php';
require_login();
require_super_admin();

$pdo = db();

$limit = 200;

$logs = $pdo->query("
  SELECT id, admin_name, action, entity, entity_id, ip, created_at, details
  FROM admin_logs
  ORDER BY id DESC
  LIMIT {$limit}
")->fetchAll();

$title = 'Admin Logs';

ob_start();
?>
<div class="card">
  <h3 style="margin:0 0 10px">Admin activity logs (last <?=h($limit)?>)</h3>

  <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr class="muted">
          <th style="padding:10px;border-bottom:1px solid var(--line)">Time</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Admin</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Action</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Entity</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">IP</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Details</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($logs as $l): ?>
        <tr>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($l['created_at'])?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($l['admin_name'] ?? '-')?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($l['action'])?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <?=h(($l['entity'] ?? '-') . ($l['entity_id'] ? ' #' . $l['entity_id'] : ''))?>
          </td>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($l['ip'] ?? '-')?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line);max-width:520px;white-space:pre-wrap">
            <?=h($l['details'] ?? '')?>
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
