<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Youth Agency • About</title>

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
      margin:0 0 14px;
      color:#1f2a44;
      font-size:28px;
      font-weight:800;
    }
    .page-card p{
      margin:0;
      color:#3b445a;
      font-size:16px;
      line-height:1.7;
    }
    @media (max-width: 768px){
      .page{
        padding:32px 16px 46px;
      }
      .page-card{
        padding:22px;
      }
    }
    @media (max-width: 480px){
      .page{
        padding:24px 14px 40px;
      }
      .page-card{
        padding:18px;
      }
      .page-card h1{
        font-size:22px;
      }
      .page-card p{
        font-size:15px;
      }
    }
  </style>
</head>
<body>
  <div id="siteHeaderMount"></div>

  <main class="page">
    <section class="page-card">
      <h1 data-i18n="about.title">ჩვენ შესახებ</h1>
      <p data-i18n="about.body">
        სსიპ ახალგაზრდობის სააგენტო არის საჯარო სამართლის იურიდიული პირი, რომელიც შექმნილია სახელმწიფო ახალგაზრდული პოლიტიკის სტრატეგიის შემუშავების, განხორციელებისა და კოორდინაციის მიზნით. ახალგაზრდობა არის ქვეყნის მდგრადი განვითარების მამოძრავებელი ძალა და ადამიანური კაპიტალის მთავარი განახლებადი წყარო. სახელმწიფო ახალგაზრდებისთვის და ახალგაზრდებთან ერთად ქმნის მათი, როგორც ინდივიდებისა და საზოგადოების სრულფასოვანი წევრების განვითარების მხარდამჭერ გარემოს, რაც ხელს შეუწყობს თითოეულის პოტენციალის სრულად გამოყენებას, ეკონომიკურ გაძლიერებასა და ქვეყნის განვითარების პროცესში აქტიურ მონაწილეობას.
      </p>
    </section>
  </main>

  <div id="siteFooterMount"></div>

  <script>
    async function inject(id, file) {
      const el = document.getElementById(id);
      if (!el) throw new Error(`Mount element not found: #${id}`);
      const res = await fetch(file + '?v=1');
      if (!res.ok) throw new Error(`${file} not found. Status: ${res.status}`);
      el.innerHTML = await res.text();
    }

    async function loadScript(src) {
      return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src + '?v=1';
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
