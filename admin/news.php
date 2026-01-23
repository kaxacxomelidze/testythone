<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();

if (isset($_GET['delete'])) {
  $id = (int)($_GET['delete'] ?? 0);
  if ($id > 0) $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$id]);
  header('Location: news.php'); exit;
}

$items = $pdo->query("SELECT id,title,slug,sort_order,is_active FROM news ORDER BY sort_order ASC, id DESC")
             ->fetchAll(PDO::FETCH_ASSOC);

$title = 'News';
ob_start();
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <h3 style="margin:0">News</h3>
    <a class="btn ac" href="news_add.php">+ Add News</a>
  </div>

  <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr class="muted">
          <th style="padding:10px;border-bottom:1px solid var(--line)">Title</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Open</th>
          <th style="padding:10px;border-bottom:1px solid var(--line)">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($items as $n): ?>
        <?php
          $slug = trim((string)($n['slug'] ?? ''));
          if ($slug === '' || $slug === '-' || $slug === 'news') $slug = 'news-' . (int)$n['id'];
          $open = "/youthagency/news/" . (int)$n['id'] . "/" . $slug;
        ?>
        <tr>
          <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h($n['title'])?></td>
          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <a class="btn" target="_blank" href="<?=h($open)?>">Open</a>
          </td>
          <td style="padding:10px;border-bottom:1px solid var(--line)">
            <a class="btn" href="news_edit.php?id=<?=h($n['id'])?>">Edit</a>
            <a class="btn bad" href="news.php?delete=<?=h($n['id'])?>" onclick="return confirm('Delete?')">Delete</a>
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
