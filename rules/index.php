<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Youth Agency • Rules</title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/youthagency/assets.css?v=1">

  <style>
    .page{
      padding:48px 18px 60px;
    }
    .page-card{
      max-width:980px;
      margin:0 auto;
      background:#fff;
      border-radius:18px;
      padding:28px;
      box-shadow:0 18px 36px rgba(15,23,42,.08);
      border:1px solid rgba(15,23,42,.08);
    }
    .page-card h1{
      margin:0 0 12px;
      color:#1f2a44;
      font-size:28px;
      font-weight:800;
    }
    .page-card p{
      margin:0 0 16px;
      color:#3b445a;
      font-size:16px;
      line-height:1.7;
    }
    .rules{
      display:grid;
      gap:16px;
    }
    .rule{
      border:1px solid rgba(15,23,42,.08);
      border-radius:14px;
      padding:16px;
      background:#f8fafc;
    }
    .rule h2{
      margin:0 0 8px;
      font-size:18px;
      color:#1f2a44;
    }
    .rule ul{
      margin:0;
      padding-left:18px;
      color:#3b445a;
      line-height:1.7;
    }
    .rule li{margin-bottom:6px;}
    @media (max-width: 768px){
      .page{padding:32px 16px 46px;}
      .page-card{padding:22px;}
    }
    @media (max-width: 480px){
      .page{padding:24px 14px 40px;}
      .page-card{padding:18px;}
      .page-card h1{font-size:22px;}
      .page-card p{font-size:15px;}
      .rule h2{font-size:16px;}
    }
  </style>
</head>
<body>
  <div id="siteHeaderMount"></div>

  <main class="page">
    <section class="page-card">
      <h1>წესები და პირობები</h1>
      <p>
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
      </p>

      <div class="rules">
        <article class="rule">
          <h2>წესი 1 — რეგისტრაცია</h2>
          <ul>
            <li>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</li>
            <li>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</li>
            <li>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</li>
          </ul>
        </article>

        <article class="rule">
          <h2>წესი 2 — კონფიდენციალურობა</h2>
          <ul>
            <li>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore.</li>
            <li>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia.</li>
            <li>Deserunt mollit anim id est laborum, lorem ipsum dolor sit amet.</li>
          </ul>
        </article>

        <article class="rule">
          <h2>წესი 3 — განაცხადის შევსება</h2>
          <ul>
            <li>Curabitur pretium tincidunt lacus, nulla gravida orci a odio.</li>
            <li>Nullam varius, turpis et commodo pharetra, est eros bibendum elit.</li>
            <li>Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.</li>
          </ul>
        </article>

        <article class="rule">
          <h2>წესი 4 — ვადები და პასუხისმგებლობა</h2>
          <ul>
            <li>Nam dui ligula, fringilla a, euismod sodales, sollicitudin vel, wisi.</li>
            <li>Morbi in sem quis dui placerat ornare. Pellentesque odio nisi.</li>
            <li>Nulla facilisi. Aenean nec eros. Vestibulum ante ipsum primis.</li>
          </ul>
        </article>
      </div>
    </section>
  </main>

  <div id="siteFooterMount"></div>

  <script>
    async function inject(id, file) {
      const el = document.getElementById(id);
      if (!el) throw new Error(`Mount element not found: #${id}`);
      const res = await fetch(file + '?v=2');
      if (!res.ok) throw new Error(`${file} not found. Status: ${res.status}`);
      el.innerHTML = await res.text();
    }

    async function loadScript(src) {
      return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src + '?v=2';
        s.onload = resolve;
        s.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.body.appendChild(s);
      });
    }

    (async () => {
      try {
        await inject('siteHeaderMount', '/youthagency/header.html');
        await loadScript('/youthagency/app.js');
        if (typeof window.initHeader === 'function') window.initHeader();
        await inject('siteFooterMount', '/youthagency/footer.html');
      } catch (err) {
        console.error(err);
      }
    })();
  </script>
</body>
</html>
