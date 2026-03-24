<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';

$dataFile = DATA_DIR . '/contact_messages.json';
$errors = [];
$success = false;

function save_message(string $path, array $message): bool {
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }

  $fp = fopen($path, 'c+');
  if (!$fp) {
    return false;
  }

  if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    return false;
  }

  $raw = stream_get_contents($fp);
  $items = [];
  if ($raw !== false && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $items = $decoded;
    }
  }

  $items[] = $message;

  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);
  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? null);

  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $message = trim((string)($_POST['message'] ?? ''));

  if ($name === '') $errors[] = 'name';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
  if ($message === '') $errors[] = 'message';

  if (!$errors) {
    $entry = [
      'id' => bin2hex(random_bytes(8)),
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'message' => $message,
      'created_at' => date('Y-m-d H:i:s'),
      'ip' => client_ip(),
    ];

    if (save_message($dataFile, $entry)) {
      $success = true;
    } else {
      $errors[] = 'save';
    }
  }
}
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Youth Agency • Contact</title>
  <meta name="description" content="დაუკავშირდით Youth Agency-ს — საკონტაქტო ფორმა, მისამართი, ტელეფონი და ელფოსტა ერთ გვერდზე.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://sspm.ge/contact/">
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Youth Agency • Contact">
  <meta property="og:description" content="დაგვიკავშირდით Youth Agency-ს ფორმის, ელფოსტის ან ტელეფონის საშუალებით.">
  <meta property="og:url" content="https://sspm.ge/contact/">
  <meta property="og:image" content="https://sspm.ge/imgs/youthagencyicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets.css?v=1">
  <style>
    .page{padding:48px 18px 60px}
    .page-grid{max-width:1180px;margin:0 auto;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);gap:24px;align-items:start}
    .page-card{background:#fff;border-radius:22px;padding:28px;box-shadow:0 18px 36px rgba(15,23,42,.08);border:1px solid rgba(15,23,42,.08)}
    .page-card h1{margin:0 0 8px;color:#1f2a44;font-size:28px;font-weight:800}.page-card p{margin:0 0 22px;color:#3b445a;font-size:15px;line-height:1.6}
    .form-grid{display:grid;gap:16px}.form-row{display:grid;gap:8px}label{font-size:14px;font-weight:700;color:#243252}input,textarea{width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(15,23,42,.16);font-family:inherit;font-size:14px;background:#f8fafc}textarea{min-height:140px;resize:vertical}
    .btn-submit{border:0;padding:12px 18px;border-radius:12px;background:var(--btn);color:#fff;font-weight:800;cursor:pointer;font-size:14px;width:fit-content}.btn-submit:hover{background:var(--btn-hover)}
    .notice{padding:12px 14px;border-radius:12px;margin-bottom:18px;font-size:14px}.notice.success{background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:#14532d}.notice.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#7f1d1d}
    .info-card{display:grid;gap:18px}.info-panel,.map-card{padding:22px;border-radius:20px;border:1px solid rgba(15,23,42,.12);background:linear-gradient(135deg,#f8fafc,#f1f5f9)}
    .info-panel h2,.map-card h2{margin:0 0 14px;font-size:18px;color:#1f2a44;font-weight:800}.info-list{display:grid;gap:10px}.info-row{color:#334155;font-weight:700;padding:12px 14px;border-radius:12px;background:#fff;border:1px solid rgba(15,23,42,.08)}
    .map-frame{width:100%;height:320px;border:0;border-radius:16px;display:block;box-shadow:0 14px 28px rgba(15,23,42,.08)}
    .map-note{margin:12px 0 0;color:#475569;font-size:14px;line-height:1.6}
    @media (max-width:980px){.page-grid{grid-template-columns:1fr}.info-card{order:2}}
    @media (max-width:768px){.page{padding:32px 16px 46px}.page-card{padding:22px}.info-panel,.map-card{padding:18px}}
    @media (max-width:480px){.page{padding:24px 14px 40px}.page-card{padding:18px}.page-card h1{font-size:22px}.page-card p{font-size:14px}.form-grid{gap:12px}.btn-submit{width:100%}}
  </style>
</head>
<body>
  <div id="siteHeaderMount"></div>
  <main class="page">
    <section class="page-grid">
      <article class="page-card">
        <h1 data-i18n="contact.title">კონტაქტი</h1>
        <p data-i18n="contact.subtitle">გამოგვიგზავნეთ შეტყობინება და მალე დაგიკავშირდებით.</p>
        <?php if ($success): ?><div class="notice success"><strong data-i18n="contact.successTitle">გმადლობთ!</strong> <span data-i18n="contact.successText">თქვენი შეტყობინება მიღებულია. მალე დაგიკავშირდებით.</span></div><?php elseif ($errors): ?><div class="notice error"><span data-i18n="contact.error">გთხოვთ სწორად შეავსოთ აუცილებელი ველები.</span></div><?php endif; ?>
        <form class="form-grid" method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <div class="form-row"><label for="name" data-i18n="contact.name">სახელი და გვარი</label><input id="name" name="name" type="text" value="<?=h($_POST['name'] ?? '')?>" required></div>
          <div class="form-row"><label for="email" data-i18n="contact.email">ელფოსტა</label><input id="email" name="email" type="email" value="<?=h($_POST['email'] ?? '')?>" required></div>
          <div class="form-row"><label for="phone" data-i18n="contact.phone">ტელეფონი (არასავალდებულო)</label><input id="phone" name="phone" type="text" value="<?=h($_POST['phone'] ?? '')?>"></div>
          <div class="form-row"><label for="message" data-i18n="contact.message">შეტყობინება</label><textarea id="message" name="message" required><?=h($_POST['message'] ?? '')?></textarea></div>
          <button class="btn-submit" type="submit" data-i18n="contact.submit">გაგზავნა</button>
        </form>
      </article>
      <aside class="info-card">
        <div class="info-panel">
          <h2 data-i18n="contact.infoTitle">საკონტაქტო ინფორმაცია</h2>
          <div class="info-list">
            <div class="info-row"><span data-i18n="contact.address">ვაჟა ფშაველას ქ. #76</span></div>
            <div class="info-row"><span data-i18n="contact.phoneInfo">032 230 51 65</span></div>
            <div class="info-row"><span data-i18n="contact.emailInfo">info@youth.ge</span></div>
          </div>
        </div>
        <div class="map-card">
          <h2 data-i18n="contact.mapTitle">მდებარეობა რუკაზე</h2>
          <iframe class="map-frame" title="Youth Agency location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=76%20Vazha-Pshavela%20Ave%2C%20Tbilisi&z=15&output=embed"></iframe>
          <p class="map-note" data-i18n="contact.mapNote">ოფისი შეგიძლიათ მარტივად ნახოთ რუკაზე და დაგეგმოთ ვიზიტი წინასწარ.</p>
        </div>
      </aside>
    </section>
  </main>
  <div id="siteFooterMount"></div>
  <script>
    async function inject(id, file) { const el = document.getElementById(id); if (!el) throw new Error(`Mount element not found: #${id}`); const res = await fetch(file + '?v=2'); if (!res.ok) throw new Error(`${file} not found. Status: ${res.status}`); el.innerHTML = await res.text(); }
    async function loadScript(src) { return new Promise((resolve, reject) => { const s = document.createElement('script'); s.src = src + '?v=2'; s.onload = resolve; s.onerror = () => reject(new Error(`Failed to load script: ${src}`)); document.body.appendChild(s); }); }
    (async () => { try { await inject('siteHeaderMount', '/header.html'); await loadScript('/app.js'); if (typeof window.initHeader === 'function') window.initHeader(); await inject('siteFooterMount', '/footer.html'); } catch (err) { console.error(err); } })();
  </script>
</body>
</html>
