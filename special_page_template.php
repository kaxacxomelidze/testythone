<?php
declare(strict_types=1);

function sp_find_existing_path(array $paths): ?string {
  foreach ($paths as $path) {
    if ($path && is_file($path)) return $path;
  }
  return null;
}

function sp_load_pages_data(string $dataPath): array {
  if (!is_file($dataPath)) return [];
  $raw = (string)@file_get_contents($dataPath);
  if ($raw === '') return [];
  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

function render_special_page(string $slug): void {
  $slug = trim($slug);
  if ($slug === '') {
    http_response_code(404);
    echo 'Page not found.';
    return;
  }

  $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
  $rootDir = __DIR__;
  $dataPath = $rootDir . '/data/special_pages.json';

  $all = sp_load_pages_data($dataPath);
  $pages = is_array($all['pages'] ?? null) ? $all['pages'] : [];
  $page = is_array($pages[$slug] ?? null) ? $pages[$slug] : null;

  if (!$page) {
    http_response_code(404);
    echo 'Page not found.';
    return;
  }

  $title = trim((string)($page['title'] ?? 'Special Page'));
  $description = trim((string)($page['description'] ?? ''));
  $logo = trim((string)($page['logo'] ?? ''));
  $links = is_array($page['links'] ?? null) ? $page['links'] : [];

  $headerPath = sp_find_existing_path([
    $rootDir . '/header.php',
    $documentRoot . '/header.php',
  ]);

  $footerPath = sp_find_existing_path([
    $rootDir . '/footer.php',
    $documentRoot . '/footer.php',
  ]);
  ?>
  <!DOCTYPE html>
  <html lang="ka">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
      <meta name="description" content="<?= htmlspecialchars($description !== '' ? $description : $title, ENT_QUOTES, 'UTF-8') ?>">
      <link rel="stylesheet" href="/assets.css?v=2">

      <style>
          body { margin:0; padding:0; font-family: Arial, "Noto Sans Georgian", sans-serif; background:#f8fafc; color:#1f2937; }
          .container { max-width:1100px; margin:0 auto; padding:50px 20px; }
          .files-box { background:#fff; border-radius:18px; padding:30px; box-shadow:0 8px 30px rgba(0,0,0,0.06); }
          .top-logo { text-align:center; margin-bottom:24px; }
          .top-logo img { max-width:460px; width:100%; height:auto; display:inline-block; }
          .files-title { margin:0 0 12px 0; font-size:28px; font-weight:700; color:#111827; text-align:center; }
          .files-desc { margin:0 0 25px 0; text-align:center; color:#4b5563; font-weight:600; }
          .files-list { list-style:none; padding:0; margin:0; display:grid; gap:14px; }
          .files-list a { display:block; padding:16px 18px; border:1px solid #d1d5db; border-radius:12px; text-decoration:none; font-size:18px; font-weight:600; color:#0f766e; background:#fff; transition:all .2s ease; word-break:break-word; }
          .files-list a:hover { background:#f0fdfa; border-color:#0f766e; transform:translateY(-1px); }
          @media (max-width:768px){ .container{padding:30px 15px} .files-box{padding:20px} .top-logo{margin-bottom:18px} .top-logo img{max-width:320px} .files-title{font-size:22px} .files-list a{font-size:16px;padding:14px 15px} }
      </style>
  </head>
  <body>

  <?php if ($headerPath) require_once $headerPath; ?>

  <div class="container">
      <div class="files-box">
          <?php if ($logo !== ''): ?>
              <div class="top-logo">
                  <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
              </div>
          <?php endif; ?>

          <h1 class="files-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
          <?php if ($description !== ''): ?>
              <p class="files-desc"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <ul class="files-list">
              <?php foreach ($links as $item):
                if (!is_array($item)) continue;
                $label = trim((string)($item['label'] ?? ''));
                $url = trim((string)($item['url'] ?? ''));
                $type = trim((string)($item['link_type'] ?? 'open'));
                if ($label === '' || $url === '') continue;
                $download = ($type === 'download');
              ?>
                  <li>
                      <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" <?= $download ? 'download' : 'target="_blank" rel="noopener"' ?>>
                          <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                      </a>
                  </li>
              <?php endforeach; ?>
          </ul>
      </div>
  </div>

  <?php if ($footerPath) require_once $footerPath; ?>
  <script src="/app.js?v=2" defer></script>
  <script>
  window.addEventListener("DOMContentLoaded", () => {
      if (typeof window.initHeader === "function") window.initHeader();
      if (typeof window.initFooterAccordion === "function") window.initFooterAccordion();
  }, { once: true });
  </script>

  </body>
  </html>
  <?php
}
