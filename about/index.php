<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Youth Agency • About</title>
  <meta name="description" content="გაიგეთ მეტი Youth Agency-ის მისიის, სახელმწიფო ახალგაზრდული პოლიტიკის და სააგენტოს საქმიანობის შესახებ.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://sspm.ge/about/">
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Youth Agency • About">
  <meta property="og:description" content="ინფორმაცია სააგენტოს მისიის, როლის და ახალგაზრდებისთვის შექმნილი გარემოს შესახებ.">
  <meta property="og:url" content="https://sspm.ge/about/">
  <meta property="og:image" content="https://sspm.ge/imgs/youthagencyicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets.css?v=1">
  <style>
    .page{padding:48px 18px 60px;}
    .page-card{max-width:980px;margin:0 auto;background:#fff;border-radius:22px;padding:32px;box-shadow:0 18px 36px rgba(15,23,42,.08);border:1px solid rgba(15,23,42,.08);}
    .page-card h1{margin:0 0 14px;color:#1f2a44;font-size:28px;font-weight:800;}
    .page-card p{margin:0;color:#3b445a;font-size:16px;line-height:1.7;}
    @media (max-width:768px){.page{padding:32px 16px 46px}.page-card{padding:24px}}
    @media (max-width:480px){.page{padding:24px 14px 40px}.page-card{padding:18px}.page-card h1{font-size:22px}.page-card p{font-size:15px}}
  </style>
</head>
<body>
  <div id="siteHeaderMount"></div>
  <main class="page">
    <section class="page-card">
      <div class="hero-icon"><i class="fa-solid fa-circle-info"></i></div>
      <h1 data-i18n="about.title">ჩვენ შესახებ</h1>
      <p data-i18n="about.body">სსიპ ახალგაზრდობის სააგენტო არის საჯარო სამართლის იურიდიული პირი, რომელიც შექმნილია სახელმწიფო ახალგაზრდული პოლიტიკის სტრატეგიის შემუშავების, განხორციელებისა და კოორდინაციის მიზნით. ახალგაზრდობა არის ქვეყნის მდგრადი განვითარების მამოძრავებელი ძალა და ადამიანური კაპიტალის მთავარი განახლებადი წყარო. სახელმწიფო ახალგაზრდებისთვის და ახალგაზრდებთან ერთად ქმნის მათი, როგორც ინდივიდებისა და საზოგადოების სრულფასოვანი წევრების განვითარების მხარდამჭერ გარემოს, რაც ხელს შეუწყობს თითოეულის პოტენციალის სრულად გამოყენებას, ეკონომიკურ გაძლიერებასა და ქვეყნის განვითარების პროცესში აქტიურ მონაწილეობას.</p>
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
