<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../admin/db.php'; // must provide $pdo (PDO)

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$stmt = $pdo->query("SELECT id,name,slug,cover,card_text,start_date,end_date,closed
                     FROM camps
                     ORDER BY id DESC
                     LIMIT 200");
$camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
$openCount = 0;
$closedCount = 0;
foreach ($camps as $c) {
  if ((int)($c['closed'] ?? 0) === 1) {
    $closedCount++;
  } else {
    $openCount++;
  }
}

function fmtDate(?string $d): string {
  $d = (string)$d;
  if ($d === '') return '';
  $ts = strtotime($d);
  if ($ts === false) return $d;
  return date('Y-m-d', $ts);
}
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/youthagency/assets.css?v=1">

  <style>
    :root{
      --bg:#0b1220;

      /* richer panel colors */
      --panel: rgba(17, 28, 51, .64);
      --panel2: rgba(17, 28, 51, .42);

      --line:#1e2a45;
      --txt:#e5e7eb;
      --muted:#9ca3af;

      /* accents */
      --accent:#60a5fa;     /* softer blue */
      --accent2:#3b82f6;    /* strong blue */
      --good:#22c55e;
      --bad:#ef4444;

      --shadow: 0 14px 40px rgba(0,0,0,.34);
      --shadow2: 0 10px 28px rgba(0,0,0,.26);
    }

    body{
      margin:0;
      color:var(--txt);
      font-family:'Noto Sans Georgian',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;

      /* nicer background but still classic */
      background:
        radial-gradient(900px 380px at 10% -10%, rgba(96,165,250,.22), transparent 58%),
        radial-gradient(900px 380px at 90% 0%, rgba(34,197,94,.12), transparent 60%),
        var(--bg);
    }

    .wrap{max-width:1200px;margin:0 auto;padding:22px}

    .top{
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:16px;
      flex-wrap:wrap;
      margin-bottom:18px;
    }

    .hero-title{
      margin:0;
      color:#ffffff;
      font-weight:950;
      letter-spacing:.2px;
      font-size:1.7rem;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .hero-title i{color:rgba(255,255,255,.92)}
    .hero-sub{
      margin-top:6px;
      color:rgba(229,231,235,.85);
      font-weight:700;
      font-size:.98rem;
      max-width:560px;
    }

    .stats{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }
    .stat{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 12px;
      border-radius:14px;
      border:1px solid rgba(30,42,69,.9);
      background:rgba(11,18,32,.38);
      font-weight:900;
      color:#fff;
      min-width:140px;
    }
    .stat .label{color:var(--muted);font-weight:800;font-size:.82rem}
    .stat .value{font-size:1.1rem}

    /* Classic filter bar — improved colors/sizes */
    .bar{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      align-items:center;
      justify-content:space-between;

      padding:12px;
      border:1px solid rgba(30,42,69,.9);
      border-radius:16px;
      background: linear-gradient(180deg, rgba(17,28,51,.55), rgba(17,28,51,.32));
      box-shadow: var(--shadow);
    }

    .search{
      flex:1 1 360px;
      display:flex;
      align-items:center;
      gap:10px;

      border:1px solid rgba(30,42,69,.95);
      border-radius:14px;
      background: rgba(11,18,32,.45);
      padding:11px 12px;
    }
    .search i{color:rgba(229,231,235,.9)}
    .search input{
      width:100%;
      border:0;
      outline:none;
      background:transparent;
      color:#fff;
      font-weight:800;
      font-size:1rem;
    }
    .search input::placeholder{color:rgba(156,163,175,.95);font-weight:700}

    .filters{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
      justify-content:flex-end;
    }

    .btn{
      border:1px solid rgba(30,42,69,.95);
      background: rgba(11,18,32,.40);
      color:#fff;

      font-weight:950;
      border-radius:999px;
      padding:10px 14px;
      cursor:pointer;
      user-select:none;

      display:inline-flex;
      gap:9px;
      align-items:center;

      transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .btn:hover{
      transform: translateY(-1px);
      border-color: rgba(96,165,250,.95);
      box-shadow: var(--shadow2);
    }
    .btn.active{
      border-color: rgba(96,165,250,1);
      background: rgba(96,165,250,.16);
    }

    .count{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:28px;
      padding:2px 10px;
      border-radius:999px;
      border:1px solid rgba(30,42,69,.95);
      color:rgba(229,231,235,.92);
      font-weight:950;
      font-size:.88rem;
      background: rgba(17,28,51,.45);
    }

    /* Grid & cards — a bit larger and cleaner */
    .grid{
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
      gap:14px;
      margin-top:16px;
    }

    .card{
      background: linear-gradient(180deg, rgba(17,28,51,.70), rgba(17,28,51,.52));
      border:1px solid rgba(30,42,69,.95);
      border-radius:18px;
      overflow:hidden;
      display:block;
      text-decoration:none;
      color:inherit;

      transform: translateY(0);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .card:hover{
      transform: translateY(-4px);
      border-color: rgba(96,165,250,.95);
      box-shadow: var(--shadow2);
    }

    .media{
      position:relative;
    }
    .cimg{
      width:100%;
      height:190px;
      object-fit:cover;
      display:block;
      filter:saturate(1.05) contrast(1.03);
    }
    .cimg-fallback{
      height:190px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(135deg, rgba(96,165,250,.22), rgba(34,197,94,.12));
      color:rgba(255,255,255,.9);
      font-weight:900;
      letter-spacing:.3px;
    }
    .shade{
      position:absolute;
      inset:0;
      background:linear-gradient(180deg, rgba(2,6,23,.0) 30%, rgba(2,6,23,.45) 100%);
    }
    .badge{
      position:absolute;
      left:12px;
      bottom:12px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.2);
      background:rgba(15,23,42,.65);
      color:#fff;
      font-weight:900;
      font-size:.8rem;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    .p{padding:14px}

    .name{
      font-weight:950;
      color:#fff;
      letter-spacing:.15px;
      font-size:1.08rem;
      line-height:1.1;
    }

    .desc{
      margin-top:8px;
      color:rgba(229,231,235,.88);
      font-weight:700;
      line-height:1.35;
      font-size:.98rem;
    }

    .meta{
      margin-top:12px;
      color:rgba(156,163,175,.98);
      font-weight:800;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
      font-size:.92rem;
    }
    .meta i{color:rgba(156,163,175,.95)}

    .card-footer{
      margin-top:14px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
    }
    .cta{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid rgba(96,165,250,.75);
      color:#e0f2fe;
      font-weight:900;
      font-size:.88rem;
      background:rgba(96,165,250,.12);
    }

    /* Status pill — more premium */
    .pill{
      display:inline-flex;
      align-items:center;
      gap:8px;

      padding:7px 11px;
      border-radius:999px;
      border:1px solid rgba(30,42,69,.95);
      font-weight:950;
      font-size:.86rem;
      white-space:nowrap;

      background: rgba(11,18,32,.35);
    }

    .pill.open{
      border-color: rgba(34,197,94,.72);
      background: rgba(34,197,94,.10);
      color:#d1fae5;
    }
    .pill.closed{
      border-color: rgba(239,68,68,.72);
      background: rgba(239,68,68,.10);
      color:#ffe4e6;
    }

    /* empty states */
    .empty{
      margin-top:18px;
      border:1px dashed rgba(148,163,184,.35);
      border-radius:16px;
      background: rgba(11,18,32,.22);
      padding:16px;
      color:rgba(229,231,235,.92);
    }
    .empty .muted{color:var(--muted);font-weight:700;margin-top:6px}

    @media (max-width:520px){
      .wrap{padding:14px}
      .grid{grid-template-columns:repeat(auto-fill,minmax(260px,1fr))}
      .cimg{height:170px}
    .hero-title{font-size:1.45rem}
    }
  </style>
</head>

<body>

  <!-- HEADER (injected) -->
  <div id="siteHeaderMount"></div>

  <main class="wrap">
    <div class="top">
      <div>
        <div class="hero-title">
          <i class="fa-solid fa-campground"></i>
          ბანაკები
        </div>
        <div class="hero-sub">
          აქ ნახავთ მიმდინარე და დაგეგმილ ახალგაზრდულ ბანაკებს. გამოიყენეთ ძიება ან ფილტრი,
          რათა სწრაფად იპოვოთ თქვენთვის საინტერესო პროგრამა.
        </div>
      </div>

      <div class="stats" aria-label="Camp stats">
        <div class="stat">
          <div>
            <div class="label">სულ</div>
            <div class="value"><?=h((string)count($camps))?></div>
          </div>
        </div>
        <div class="stat">
          <div>
            <div class="label">ღია</div>
            <div class="value" id="statOpen"><?=h((string)$openCount)?></div>
          </div>
        </div>
        <div class="stat">
          <div>
            <div class="label">დახურული</div>
            <div class="value" id="statClosed"><?=h((string)$closedCount)?></div>
          </div>
        </div>
        <div class="stat">
          <div>
            <div class="label">შედეგი</div>
            <div class="value" id="statShown"><?=h((string)count($camps))?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="bar">
      <div class="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="q" type="search" placeholder="ძიება ბანაკებში...">
      </div>

      <div class="filters" aria-label="Filters">
        <div class="btn active" data-filter="all">
          ყველა <span class="count" id="cAll"><?=count($camps)?></span>
        </div>
        <div class="btn" data-filter="open">
          <i class="fa-solid fa-circle-check" style="color:var(--good)"></i>
          ღია <span class="count" id="cOpen">0</span>
        </div>
        <div class="btn" data-filter="closed">
          <i class="fa-solid fa-circle-xmark" style="color:var(--bad)"></i>
          დახურული <span class="count" id="cClosed">0</span>
        </div>
      </div>
    </div>
    <br>

    <section class="grid" id="grid" aria-live="polite">
      <?php foreach($camps as $c): ?>
        <?php
          $id = (int)$c['id'];
          $slug = (string)($c['slug'] ?? '');
          $url = "/youthagency/camps/$id/" . rawurlencode($slug);
          $closed = (int)$c['closed'] === 1;

          $name = (string)($c['name'] ?? '');
          $desc = (string)($c['card_text'] ?? '');
          $start = fmtDate((string)($c['start_date'] ?? ''));
          $end = fmtDate((string)($c['end_date'] ?? ''));
          $cover = (string)($c['cover'] ?? '');
          $status = $closed ? 'closed' : 'open';

          $search = mb_strtolower(trim($name.' '.$desc.' '.$start.' '.$end), 'UTF-8');
        ?>
        <a class="card"
           href="<?=h($url)?>"
           data-status="<?=h($status)?>"
           data-search="<?=h($search)?>">
          <div class="media">
            <?php if ($cover !== ''): ?>
              <img class="cimg" src="<?=h($cover)?>" alt="">
            <?php else: ?>
              <div class="cimg-fallback">Youth Camp</div>
            <?php endif; ?>
            <div class="shade"></div>
            <div class="badge">
              <i class="fa-regular fa-calendar"></i>
              <?=h($start)?> → <?=h($end)?>
            </div>
          </div>

          <div class="p">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
              <div class="name"><?=h($name)?></div>
              <span class="pill <?=$closed ? 'closed' : 'open'?>">
                <i class="fa-solid <?=$closed ? 'fa-lock' : 'fa-unlock'?>"></i>
                <?=$closed ? 'დახურული' : 'ღია'?>
              </span>
            </div>

            <?php if ($desc !== ''): ?>
              <div class="desc"><?=h($desc)?></div>
            <?php endif; ?>

            <div class="meta">
              <span><i class="fa-solid fa-hashtag"></i> <?=h((string)$id)?></span>
              <span>•</span>
              <span><i class="fa-solid fa-location-dot"></i> ახალგაზრდობის სააგენტო</span>
            </div>

            <div class="card-footer">
              <span class="cta">
                დეტალები
                <i class="fa-solid fa-arrow-right"></i>
              </span>
              <span class="small" style="color:var(--muted);font-weight:800">განაცხადი ონლაინ</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </section>

    <?php if (!$camps): ?>
      <div class="empty">
        <b>ჯერ ბანაკები არ დამატებულა.</b>
        <div class="muted">ადმინისტრატორის პანელიდან დაამატე პირველი ბანაკი და აქ გამოჩნდება.</div>
      </div>
    <?php endif; ?>

    <div id="clientEmpty" class="empty" style="display:none">
      <b>შედეგი ვერ მოიძებნა.</b>
      <div class="muted">სცადე სხვა სიტყვა ან შეცვალე ფილტრი.</div>
    </div>
  </main>

  <!-- FOOTER (injected) -->
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

    function initCampsClassic(){
      const q = document.getElementById('q');
      const grid = document.getElementById('grid');
      const cards = Array.from(grid.querySelectorAll('.card'));
      const buttons = Array.from(document.querySelectorAll('.btn'));
      const clientEmpty = document.getElementById('clientEmpty');

      const cOpen = document.getElementById('cOpen');
      const cClosed = document.getElementById('cClosed');
      const statShown = document.getElementById('statShown');

      let active = 'all';

      function countStatuses(){
        let open = 0, closed = 0;
        cards.forEach(c => (c.dataset.status === 'open') ? open++ : closed++);
        cOpen.textContent = String(open);
        cClosed.textContent = String(closed);
        const statOpen = document.getElementById('statOpen');
        const statClosed = document.getElementById('statClosed');
        if (statOpen) statOpen.textContent = String(open);
        if (statClosed) statClosed.textContent = String(closed);
      }

      function apply(){
        const term = (q.value || '').trim().toLowerCase();
        let shown = 0;

        cards.forEach(c => {
          const okStatus = (active === 'all') ? true : (c.dataset.status === active);
          const okSearch = term ? (c.dataset.search || '').includes(term) : true;
          const show = okStatus && okSearch;
          c.style.display = show ? '' : 'none';
          if (show) shown++;
        });

        clientEmpty.style.display = (shown === 0 && cards.length) ? '' : 'none';
        if (statShown) statShown.textContent = String(shown);
      }

      buttons.forEach(b => {
        b.addEventListener('click', () => {
          buttons.forEach(x => x.classList.remove('active'));
          b.classList.add('active');
          active = b.dataset.filter || 'all';
          apply();
        });
      });

      let t = null;
      q.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(apply, 60);
      });

      countStatuses();
      apply();
    }

    (async () => {
      try {
        await inject('siteHeaderMount', '/youthagency/header.html');
        await loadScript('/youthagency/app.js');
        if (typeof window.initHeader === 'function') window.initHeader();

        await inject('siteFooterMount', '/youthagency/footer.html');

        initCampsClassic();
      } catch (err) {
        console.error('HEADER/FOOTER ERROR:', err);
      }
    })();
  </script>

</body>
</html>
