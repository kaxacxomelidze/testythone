<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ახალგაზრდობის სააგენტო</title>
  <meta name="description" content="ახალგაზრდული პროგრამები, ბანაკები, საგრანტო შესაძლებლობები, სიახლეები და საკონტაქტო ინფორმაცია ერთ სივრცეში.">
  <meta name="keywords" content="ახალგაზრდობის სააგენტო, Youth Agency, youthagency.gov.ge, ახალგაზრდული პოლიტიკა, სიახლეები, ბანაკები, გრანტები">
  <meta name="author" content="სსიპ ახალგაზრდობის სააგენტო">
  <meta name="robots" content="index,follow">
  <meta name="googlebot" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
  <link rel="canonical" href="https://sspm.ge/">
  <link rel="alternate" href="https://sspm.ge/" hreflang="ka-ge">
  <link rel="alternate" href="https://sspm.ge/en/" hreflang="en">
  <link rel="alternate" href="https://sspm.ge/" hreflang="x-default">

  <link rel="icon" type="image/png" sizes="32x32" href="/imgs/youthagencyicon.png?v=6">
  <link rel="icon" type="image/png" sizes="16x16" href="/imgs/youthagencyicon.png?v=6">
  <link rel="apple-touch-icon" href="/imgs/youthagencyicon.png?v=6">

  <meta property="og:type" content="website">
  <meta property="og:locale" content="ka_GE">
  <meta property="og:site_name" content="ახალგაზრდობის სააგენტო">
  <meta property="og:title" content="ახალგაზრდობის სააგენტო">
  <meta property="og:description" content="ახალგაზრდული პროგრამები, სიახლეები, ბანაკები და საგრანტო შესაძლებლობები.">
  <meta property="og:url" content="https://sspm.ge/">
  <meta property="og:image" content="https://sspm.ge/imgs/youthagencyicon.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="ახალგაზრდობის სააგენტო">
  <meta name="twitter:description" content="სიახლეები, ბანაკები, საგრანტო შესაძლებლობები და საკონტაქტო ინფორმაცია.">
  <meta name="twitter:image" content="https://sspm.ge/imgs/youthagencyicon.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

  <link rel="stylesheet" href="/assets.css?v=2">
  <link rel="stylesheet" href="/slider.css?v=2">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" media="print" onload="this.media='all'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  </noscript>

  <style>
    .visually-hidden{position:absolute !important;width:1px !important;height:1px !important;padding:0 !important;margin:-1px !important;overflow:hidden !important;clip:rect(0, 0, 0, 0) !important;white-space:nowrap !important;border:0 !important;}
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/header.php'; ?>

  <main id="mainContent">
    <section class="hero">
      <h2 class="visually-hidden" data-i18n="home.sliderLabel">მთავარი სლაიდერი</h2>
      <div class="slider" id="heroSlider" role="region" aria-label="Main slider" data-i18n-aria="home.sliderLabel">
        <div class="slides" id="slidesTrack"></div>

        <button class="nav prev" type="button" id="prevBtn" aria-label="Previous slide" data-i18n-aria="home.prevSlide">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <button class="nav next" type="button" id="nextBtn" aria-label="Next slide" data-i18n-aria="home.nextSlide">
          <i class="fa-solid fa-chevron-right"></i>
        </button>

        <div class="dots" id="dots"></div>
      </div>
    </section>

    <?php require __DIR__ . '/news_list.php'; ?>
  </main>

  <?php require_once __DIR__ . '/footer.php'; ?>

  <script src="/app.js?v=2" defer></script>
  <script>window.addEventListener("DOMContentLoaded",()=>{if(typeof window.initHeader==="function") window.initHeader(); if(typeof window.initFooterAccordion==="function") window.initFooterAccordion();},{once:true});</script>
  <script src="/news.js?v=2" defer></script>
  <script src="/slider.js?v=2" defer></script>
</body>
</html>
