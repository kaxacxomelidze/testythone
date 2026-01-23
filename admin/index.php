<?php
require_once __DIR__ . '/config.php';
require_login();

$pdo = db();

/**
 * =========================
 * Load settings (autoplay_ms) from SQL
 * =========================
 */
$stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key`='autoplay_ms' LIMIT 1");
$stmt->execute();
$autoplay_ms = (int)($stmt->fetchColumn() ?: 4500);

/**
 * =========================
 * Save settings to SQL
 * =========================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autoplay_ms'])) {
  csrf_check($_POST['csrf'] ?? '');

  $ms = max(1500, (int)$_POST['autoplay_ms']);

  $pdo->prepare("
    INSERT INTO settings(`key`,`value`)
    VALUES ('autoplay_ms', ?)
    ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)
  ")->execute([(string)$ms]);

  header("Location: index.php");
  exit;
}

/**
 * =========================
 * Load slides from SQL
 * =========================
 */
$slides = $pdo->query("
  SELECT id, title, link, image_path, sort_order
  FROM slides
  WHERE is_active = 1
  ORDER BY sort_order ASC, id DESC
")->fetchAll();

$title = 'Slider / Settings';

ob_start();
?>

<div class="card">
  <div class="muted" style="margin-bottom:10px">Slider autoplay settings</div>

  <form method="post" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">

    <label class="muted">Autoplay time (ms)</label>

    <input
      type="number"
      name="autoplay_ms"
      value="<?=h($autoplay_ms)?>"
      min="1500"
      step="100"
      style="padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:rgba(17,28,51,.55);color:var(--txt)"
    >

    <button class="btn ac" type="submit">Save</button>

    <span class="pill"><?=h($autoplay_ms)?> = <?=h(number_format($autoplay_ms/1000, 1))?> seconds</span>
  </form>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <h3 style="margin:0">Slides</h3>
    <a class="btn ac" href="slide_add.php">+ Add slide</a>
  </div>

  <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr class="muted">
          <th style="padding:10px;border-bottom:1px solid var(--line)">Preview</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Title</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Link</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Order</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php foreach($slides as $s): ?>
        <tr>
          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <img
              src="<?=h('../' . ($s['image_path'] ?? ''))?>"
              style="width:120px;height:60px;object-fit:cover;border-radius:10px;border:1px solid var(--line)"
              alt=""
            >
          </td>

          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <?=h($s['title'] ?? '')?>
          </td>

          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <?=h($s['link'] ?? '')?>
          </td>

          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <?=h($s['sort_order'] ?? 0)?>
          </td>

          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <a class="btn" href="slide_edit.php?id=<?=h($s['id'])?>">Edit</a>
            <a class="btn bad" href="slide_delete.php?id=<?=h($s['id'])?>" onclick="return confirm('Delete this slide?')">Delete</a>
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
