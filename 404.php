<?php http_response_code(404); ?><!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Youth Agency • 404</title>
  <meta name="robots" content="noindex,follow">
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets.css?v=1">
  <style>
    .page{min-height:calc(100vh - 160px);display:grid;place-items:center;padding:40px 18px;background:radial-gradient(circle at top, rgba(96,165,250,.14), transparent 36%), #f8fafc}
    .card{max-width:720px;width:100%;background:#fff;border-radius:24px;padding:34px;text-align:center;border:1px solid rgba(15,23,42,.08);box-shadow:0 24px 48px rgba(15,23,42,.08)}
     .code{margin:0;color:#dc2626;font-size:14px;font-weight:900;letter-spacing:.3em;text-transform:uppercase}.card h1{margin:10px 0 12px;font-size:34px;color:#0f172a}.card p{margin:0 auto 22px;max-width:540px;color:#475569;line-height:1.7}.actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}.btn-link{display:inline-flex;align-items:center;gap:8px;padding:12px 18px;border-radius:999px;text-decoration:none;font-weight:800;border:1px solid rgba(15,23,42,.12);color:#1e293b;background:#fff}.btn-link.primary{background:var(--btn);color:#fff;border-color:transparent}
    @media (max-width:480px){.card{padding:24px}.card h1{font-size:28px}}
  </style>
</head>
<body>
  <div id="siteHeaderMount"></div>
  <main class="page"><section class="card"><div class="code">404</div><h1 data-i18n="notFound.title">გვერდი ვერ მოიძებნა</h1><p data-i18n="notFound.body">შესაძლოა მისამართი არასწორად აკრიფეთ ან მოთხოვნილი გვერდი გადატანილია.</p><div class="actions"><a class="btn-link primary" href="/"><span data-i18n="notFound.home">მთავარზე დაბრუნება</span></a><a class="btn-link" href="/contact/"><span data-i18n="notFound.contact">დაგვიკავშირდით</span></a></div></section></main>
  <div id="siteFooterMount"></div>
  <script>
    async function inject(id, file) { const el = document.getElementById(id); if (!el) throw new Error(`Mount element not found: #${id}`); const res = await fetch(file + '?v=2'); if (!res.ok) throw new Error(`${file} not found. Status: ${res.status}`); el.innerHTML = await res.text(); }
    async function loadScript(src) { return new Promise((resolve, reject) => { const s = document.createElement('script'); s.src = src + '?v=2'; s.onload = resolve; s.onerror = () => reject(new Error(`Failed to load script: ${src}`)); document.body.appendChild(s); }); }
    (async () => { try { await inject('siteHeaderMount', '/header.php'); await loadScript('/app.js'); if (typeof window.initHeader === 'function') window.initHeader(); await inject('siteFooterMount', '/footer.php'); } catch (err) { console.error(err); } })();
  </script>
</body>
</html>
