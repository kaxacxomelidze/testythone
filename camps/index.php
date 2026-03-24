<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
date_default_timezone_set('Asia/Tbilisi');

$pdo = db();

if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function has_col(PDO $pdo, string $table, string $col): bool {
  $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $table);
  $col   = preg_replace('/[^a-zA-Z0-9_]+/', '', $col);
  if ($table === '' || $col === '') return false;
  try{
    $q = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$q->fetch(PDO::FETCH_ASSOC);
  }catch(Throwable $e){
    return false;
  }
}

function fmtDate(?string $d): string {
  $d = trim((string)$d);
  if ($d === '') return '';
  $ts = strtotime($d);
  if ($ts === false) return $d;
  return date('Y-m-d', $ts);
}

function normalize_public_path(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) return $path;

  $path = str_replace('\\', '/', $path);
  $path = preg_replace('~/+~', '/', $path) ?? $path;
  if (!str_starts_with($path, '/')) $path = '/' . ltrim($path, '/');

  if (str_starts_with($path, '/youthagency/')) {
    $path = '/' . ltrim(substr($path, strlen('/youthagency/')), '/');
  }
  return $path;
}

/**
 * Convert date string to timestamp
 * - If "YYYY-MM-DD" => use end-of-day (23:59:59) when $endOfDay=true
 * - Else => strtotime as-is
 */
function dateToTs(?string $raw, bool $endOfDay = false): ?int {
  $raw = trim((string)$raw);
  if ($raw === '') return null;

  $ts = strtotime($raw);
  if ($ts === false) return null;

  if ($endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
    $ts += 86399; // 23:59:59
  }
  return $ts;
}

/**
 * Status rules:
 * - closed if admin closed OR end_date passed
 * - upcoming if start_date is in future AND not closed
 * - open otherwise
 */
function campStatus(array $c): string {
  $adminClosed = ((int)($c['closed'] ?? 0) === 1);

  $startTs = dateToTs($c['start_date'] ?? null, false);
  $endTs   = dateToTs($c['end_date'] ?? null, true);

  $now = time();

  $timeClosed = ($endTs !== null && $now > $endTs);

  if ($adminClosed || $timeClosed) return 'closed';

  $upcoming = ($startTs !== null && $startTs > $now);
  if ($upcoming) return 'upcoming';

  return 'open';
}

$nameEnSelect = has_col($pdo, 'camps', 'name_en') ? 'name_en' : "'' AS name_en";
$cardTextEnSelect = has_col($pdo, 'camps', 'card_text_en') ? 'card_text_en' : "'' AS card_text_en";
$stmt = $pdo->query("
  SELECT id,name,{$nameEnSelect},slug,cover,card_text,{$cardTextEnSelect},start_date,end_date,closed
  FROM camps
  ORDER BY id DESC
  LIMIT 200
");
$camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Youth Agency • Camps</title>
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets.css?v=1">

  <style>
    :root{
      --bg:#0b1220;

      --panel: rgba(17, 28, 51, .64);
      --panel2: rgba(17, 28, 51, .42);

      --line:#1e2a45;
      --txt:#e5e7eb;
      --muted:#9ca3af;

      --accent:#60a5fa;
      --accent2:#3b82f6;
      --good:#22c55e;
      --bad:#ef4444;
      --warn:#f59e0b;

      --shadow: 0 14px 40px rgba(0,0,0,.34);
      --shadow2: 0 10px 28px rgba(0,0,0,.26);
    }

    body{
      margin:0;
      color:var(--txt);
      font-family:'Noto Sans Georgian',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      background:
        radial-gradient(900px 380px at 10% -10%, rgba(96,165,250,.22), transparent 58%),
        radial-gradient(900px 380px at 90% 0%, rgba(34,197,94,.12), transparent 60%),
        var(--bg);
    }

    .wrap{max-width:1200px;margin:0 auto;padding:22px}

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

    .search-intro{flex:1 1 320px}

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

    .cimg{
      width:100%;
      height:190px;
      object-fit:cover;
      display:block;
      filter:saturate(1.05) contrast(1.03);
    }
    .cimg.placeholder{
      height:190px;
      background: rgba(11,18,32,.35);
      border-bottom:1px solid rgba(30,42,69,.95);
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
    .pill.upcoming{
      border-color: rgba(245,158,11,.72);
      background: rgba(245,158,11,.12);
      color:#fff7ed;
    }

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
      .cimg.placeholder{height:170px}
    }
  </style>
</head>

<body>
  <div id="siteHeaderMount"></div>

  <main class="wrap">
    <section class="bar"><div class="search-intro"><h1 style="margin:0 0 6px;font-size:30px;color:#fff" data-i18n="camps.heroTitle">ბანაკები</h1><div style="color:rgba(229,231,235,.78);font-weight:700" data-i18n="camps.heroSubtitle">აღმოაჩინე ახალგაზრდული ბანაკები, თარიღები და რეგისტრაციის დეტალები.</div></div>
      
      <div class="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="q" type="search" placeholder="ძიება ბანაკებში..." data-i18n-placeholder="camps.searchPlaceholder">
      </div>

      <div class="filters" aria-label="Filters" data-i18n-aria="camps.filtersAria">
        <div class="btn active" data-filter="all">
          <span data-i18n="camps.filterAll">ყველა</span> <span class="count" id="cAll"><?=count($camps)?></span>
        </div>

        <div class="btn" data-filter="open">
          <i class="fa-solid fa-circle-check" style="color:var(--good)"></i>
          <span data-i18n="camps.filterOpen">ღია</span> <span class="count" id="cOpen">0</span>
        </div>

        <div class="btn" data-filter="upcoming">
          <i class="fa-solid fa-clock" style="color:var(--warn)"></i>
          <span data-i18n="camps.filterUpcoming">მალე</span> <span class="count" id="cUpcoming">0</span>
        </div>

        <div class="btn" data-filter="closed">
          <i class="fa-solid fa-circle-xmark" style="color:var(--bad)"></i>
          <span data-i18n="camps.filterClosed">დახურული</span> <span class="count" id="cClosed">0</span>
        </div>
      </div>
    </section>
    <br>

    <section class="grid" id="grid" aria-live="polite">
      <?php foreach($camps as $c): ?>
        <?php
          $id = (int)$c['id'];
          $slug = (string)($c['slug'] ?? '');
          if ($slug === '') $slug = 'camp-' . $id;

          $url = "/camps/$id/" . rawurlencode($slug);

          $name  = (string)($c['name'] ?? '');
          $nameEn  = (string)($c['name_en'] ?? '');
          $desc  = (string)($c['card_text'] ?? '');
          $descEn  = (string)($c['card_text_en'] ?? '');
          $start = fmtDate((string)($c['start_date'] ?? ''));
          $end   = fmtDate((string)($c['end_date'] ?? ''));
          $cover = normalize_public_path((string)($c['cover'] ?? ''));

          $status = campStatus($c);

          $statusLabelKey = ($status === 'closed') ? 'camps.statusClosed' : (($status === 'upcoming') ? 'camps.statusUpcoming' : 'camps.statusOpen');
          $statusLabel = ($status === 'closed') ? 'დახურულია' : (($status === 'upcoming') ? 'მალე' : 'ღია');

          $search = mb_strtolower(trim($name.' '.$nameEn.' '.$desc.' '.$descEn.' '.$start.' '.$end.' '.$statusLabel), 'UTF-8');
        ?>
        <a class="card"
           href="<?=h($url)?>"
           data-status="<?=h($status)?>"
           data-search="<?=h($search)?>">
          <?php if ($cover !== ''): ?>
            <img class="cimg" src="<?=h($cover)?>" alt="">
          <?php else: ?>
            <div class="cimg placeholder"></div>
          <?php endif; ?>

          <div class="p">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
              <div class="name" data-i18n-text data-text-ka="<?=h($name)?>" data-text-en="<?=h($nameEn)?>"><?=h($name)?></div>

              <span class="pill <?=h($status)?>">
                <?php if ($status === 'closed'): ?>
                  <i class="fa-solid fa-lock"></i>
                <?php elseif ($status === 'upcoming'): ?>
                  <i class="fa-solid fa-clock"></i>
                <?php else: ?>
                  <i class="fa-solid fa-unlock"></i>
                <?php endif; ?>
                <span data-i18n="<?=h($statusLabelKey)?>"><?=h($statusLabel)?></span>
              </span>
            </div>

            <?php if ($desc !== '' || $descEn !== ''): ?>
              <div class="desc" data-i18n-text data-text-ka="<?=h($desc)?>" data-text-en="<?=h($descEn)?>"><?=h($desc !== '' ? $desc : $descEn)?></div>
            <?php endif; ?>

            <div class="meta">
              <span><i class="fa-regular fa-calendar"></i> <?=h($start)?> → <?=h($end)?></span>
              <span>•</span>
              <span><i class="fa-solid fa-hashtag"></i> <?=h((string)$id)?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </section>

    <?php if (!$camps): ?>
      <div class="empty">
        <b data-i18n="camps.emptyTitle">ჯერ ბანაკები არ დამატებულა.</b>
        <div class="muted" data-i18n="camps.emptySubtitle">ადმინისტრატორის პანელიდან დაამატე პირველი ბანაკი და აქ გამოჩნდება.</div>
      </div>
    <?php endif; ?>

    <div id="clientEmpty" class="empty" style="display:none">
      <b data-i18n="camps.noResultsTitle">შედეგი ვერ მოიძებნა.</b>
      <div class="muted" data-i18n="camps.noResultsSubtitle">სცადე სხვა სიტყვა ან შეცვალე ფილტრი.</div>
    </div>
  </main>

  <div id="siteFooterMount"></div>

  <script>
    async function inject(id, file) {
      const el = document.getElementById(id);
      const res = await fetch(file + (file.includes('?') ? '&' : '?') + 'v=2');
      if (res.ok) el.innerHTML = await res.text();
    }

    async function loadScript(src) {
      return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src + (src.includes('?') ? '&' : '?') + 'v=2';
        s.onload = resolve;
        s.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.body.appendChild(s);
      });
    }

    function normalizeStr(s){
      return (s || '').toString().toLowerCase().trim();
    }

    function initCampsClassic(){
      const q = document.getElementById('q');
      const grid = document.getElementById('grid');
      const cards = Array.from(grid.querySelectorAll('.card'));
      const buttons = Array.from(document.querySelectorAll('.btn'));
      const clientEmpty = document.getElementById('clientEmpty');

      const cOpen = document.getElementById('cOpen');
      const cClosed = document.getElementById('cClosed');
      const cUpcoming = document.getElementById('cUpcoming');

      let active = 'all';

      function countStatuses(){
        let open = 0, closed = 0, upcoming = 0;
        cards.forEach(c => {
          const st = c.dataset.status;
          if (st === 'open') open++;
          else if (st === 'upcoming') upcoming++;
          else closed++;
        });
        cOpen.textContent = String(open);
        cUpcoming.textContent = String(upcoming);
        cClosed.textContent = String(closed);
      }

      function apply(){
        const term = normalizeStr(q.value);
        let shown = 0;

        cards.forEach(c => {
          const okStatus = (active === 'all') ? true : (c.dataset.status === active);
          const okSearch = term ? normalizeStr(c.dataset.search).includes(term) : true;
          const show = okStatus && okSearch;
          c.style.display = show ? '' : 'none';
          if (show) shown++;
        });

        clientEmpty.style.display = (shown === 0 && cards.length) ? '' : 'none';
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
        t = setTimeout(apply, 70);
      });

      countStatuses();
      apply();
    }

    (async () => {
      try {
        await inject('siteHeaderMount', '/header.html');
        try{
          await loadScript('/app.js');
          if (typeof window.initHeader === 'function') window.initHeader();
        }catch(e){}

        await inject('siteFooterMount', '/footer.html');

        initCampsClassic();
      } catch (err) {
        console.error('HEADER/FOOTER ERROR:', err);
      }
    })();
  </script>
</body>
</html>
