<?php
require_once __DIR__ . '/admin/config.php';
$pdo = db();

function table_cols(PDO $pdo, string $table): array {
  static $cache = [];
  $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $table);
  if ($table === '') return [];
  if (isset($cache[$table])) return $cache[$table];
  try{
    $q = $pdo->query("SHOW COLUMNS FROM `$table`");
    $cols = $q ? $q->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    $cache[$table] = array_flip($cols ?: []);
    return $cache[$table];
  }catch(Throwable $e){
    $cache[$table] = [];
    return $cache[$table];
  }
}

function fmt_date(?string $dt): string {
  if (!$dt) return '';
  $ts = strtotime($dt);
  return $ts ? date('d M Y', $ts) : $dt;
}
function excerpt(?string $text, int $len = 190): string {
  $t = trim((string)$text);
  $t = preg_replace('/\s+/', ' ', $t);
  if (mb_strlen($t, 'UTF-8') <= $len) return $t;
  return mb_substr($t, 0, $len, 'UTF-8') . '…';
}
function news_url(array $n): string {
  $id = (int)($n['id'] ?? 0);
  $slug = trim((string)($n['slug'] ?? ''));
  if ($slug === '' || $slug === '-' || $slug === 'news') $slug = 'news-' . $id;
  return "/news/$id/$slug";
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

function public_path_to_fs(string $publicPath): ?string {
  $publicPath = normalize_public_path($publicPath);
  if ($publicPath === '' || !str_starts_with($publicPath, '/uploads/')) return null;
  $base = realpath(__DIR__ . '/uploads');
  if ($base === false) return null;
  $candidate = realpath(__DIR__ . $publicPath);
  if ($candidate === false || !is_file($candidate)) return null;
  $baseNorm = rtrim(str_replace('\\', '/', $base), '/');
  $candNorm = str_replace('\\', '/', $candidate);
  if ($candNorm !== $baseNorm && !str_starts_with($candNorm, $baseNorm . '/')) return null;
  return $candidate;
}

function ensure_news_thumb(string $publicPath, int $w, int $h, int $quality = 78): string {
  $srcFile = public_path_to_fs($publicPath);
  if (!$srcFile || $w < 40 || $h < 40) return $publicPath;
  if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) return $publicPath;

  $cacheDirFs = __DIR__ . '/uploads/cache/news';
  if (!is_dir($cacheDirFs) && !@mkdir($cacheDirFs, 0775, true) && !is_dir($cacheDirFs)) {
    return $publicPath;
  }

  $fingerprint = md5($srcFile . '|' . (string)@filemtime($srcFile) . "|{$w}x{$h}|{$quality}");
  $targetName = $fingerprint . ".webp";
  $targetFs = $cacheDirFs . '/' . $targetName;
  $targetPublic = '/uploads/cache/news/' . $targetName;

  if (is_file($targetFs) && filesize($targetFs) > 0) return $targetPublic;

  $ext = strtolower(pathinfo($srcFile, PATHINFO_EXTENSION));
  $src = null;
  if ($ext === 'jpg' || $ext === 'jpeg') $src = @imagecreatefromjpeg($srcFile);
  elseif ($ext === 'png') $src = @imagecreatefrompng($srcFile);
  elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($srcFile);
  elseif ($ext === 'gif') $src = @imagecreatefromgif($srcFile);

  if (!$src) return $publicPath;

  $sw = imagesx($src);
  $sh = imagesy($src);
  if ($sw < 1 || $sh < 1) {
    imagedestroy($src);
    return $publicPath;
  }

  $srcRatio = $sw / $sh;
  $dstRatio = $w / $h;
  if ($srcRatio > $dstRatio) {
    $cropH = $sh;
    $cropW = (int)round($sh * $dstRatio);
    $cropX = (int)floor(($sw - $cropW) / 2);
    $cropY = 0;
  } else {
    $cropW = $sw;
    $cropH = (int)round($sw / $dstRatio);
    $cropX = 0;
    $cropY = (int)floor(($sh - $cropH) / 2);
  }

  $dst = imagecreatetruecolor($w, $h);
  imagealphablending($dst, true);
  imagesavealpha($dst, true);
  imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $w, $h, $cropW, $cropH);

  $ok = false;
  if (function_exists('imagewebp')) {
    $ok = @imagewebp($dst, $targetFs, $quality);
  }
  imagedestroy($dst);
  imagedestroy($src);

  return $ok && is_file($targetFs) ? $targetPublic : $publicPath;
}

$news_cols = table_cols($pdo, 'news');
$has_title_en = isset($news_cols['title_en']);
$has_body_en = isset($news_cols['body_en']);

$items = $pdo->query("
  SELECT id, title, " . ($has_title_en ? "title_en" : "'' AS title_en") . ", slug,
         body, " . ($has_body_en ? "body_en" : "'' AS body_en") . ", image_path, published_at
  FROM news
  WHERE is_active=1
  ORDER BY sort_order ASC, id DESC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$featured = $items[0] ?? null;
$list = array_slice($items, 1, 4);
?>
<section class="mag-news">
  <div class="mag-head">
    <div class="mag-kicker" data-i18n="news.kicker">Youth Agency</div>
    <div class="mag-row">
      <h2 class="mag-title" data-i18n="news.title">სიახლეები</h2>
      <a class="mag-all" href="/news/" data-i18n="news.all">ნახე მეტი ↗</a>
    </div>
  </div>

  <?php if (!$featured): ?>
    <div class="mag-empty">
      <div class="mag-emptyIcon">🗞️</div>
      <div>
        <div class="mag-emptyTitle" data-i18n="news.empty">No news yet</div>
      </div>
    </div>
  <?php else: ?>

    <?php
      $fUrl  = news_url($featured);
      $fImg  = normalize_public_path((string)($featured['image_path'] ?? ''));
      $fImgSm = ensure_news_thumb($fImg, 640, 360);
      $fImgLg = ensure_news_thumb($fImg, 1280, 720);
      $fDate = fmt_date($featured['published_at'] ?? '');
      $fDesc = excerpt($featured['body'] ?? '', 200);
      $fTitleEn = (string)($featured['title_en'] ?? '');
      $fDescEn = excerpt($featured['body_en'] ?? '', 200);
    ?>

    <div class="mag-grid">
      <!-- FEATURED -->
      <article class="mag-feature">
        <a class="mag-feature__link" href="<?=h($fUrl)?>">
          <div class="mag-feature__media">
            <?php if ($fImg): ?>
              <img src="<?=h($fImgSm)?>" srcset="<?=h($fImgSm)?> 640w, <?=h($fImgLg)?> 1280w" sizes="(max-width:980px) 100vw, 65vw" alt="<?=h($featured['title'])?>" loading="eager" fetchpriority="high" decoding="async" width="640" height="360">
            <?php else: ?>
              <div class="mag-fallback"></div>
            <?php endif; ?>
            <div class="mag-feature__shade"></div>

            <div class="mag-feature__badge" data-i18n="news.featured">Featured</div>
          </div>

          <div class="mag-feature__body">
            <?php if ($fDate): ?><div class="mag-date"><?=h($fDate)?></div><?php endif; ?>
            <h3 class="mag-feature__title" data-i18n-text data-text-ka="<?=h((string)$featured['title'])?>" data-text-en="<?=h($fTitleEn)?>"><?=h($featured['title'])?></h3>
            <?php if ($fDesc !== '' || $fDescEn !== ''): ?>
              <p class="mag-feature__desc" data-i18n-text data-text-ka="<?=h($fDesc)?>" data-text-en="<?=h($fDescEn)?>"><?=h($fDesc !== '' ? $fDesc : $fDescEn)?></p>
            <?php endif; ?>

            <div class="mag-feature__cta">
              <span class="mag-cta" data-i18n="news.cta">გაიგე მეტი</span>
              <span class="mag-ctaArrow">→</span>
            </div>
          </div>
        </a>
      </article>

      <!-- RIGHT LIST -->
      <div class="mag-side">
        <?php if (!$list): ?>
          <div class="mag-sideEmpty" data-i18n="news.morePosts">More posts will appear here.</div>
        <?php else: ?>
          <?php foreach ($list as $n): ?>
            <?php
              $url  = news_url($n);
              $img  = normalize_public_path((string)($n['image_path'] ?? ''));
              $imgSm = ensure_news_thumb($img, 275, 183);
              $imgLg = ensure_news_thumb($img, 550, 366);
              $date = fmt_date($n['published_at'] ?? '');
              $desc = excerpt($n['body'] ?? '', 95);
              $titleEn = (string)($n['title_en'] ?? '');
              $descEn = excerpt($n['body_en'] ?? '', 95);
            ?>
            <article class="mag-mini">
              <a class="mag-mini__link" href="<?=h($url)?>">
                <div class="mag-mini__thumb">
                  <?php if ($img): ?>
                    <img src="<?=h($imgSm)?>" srcset="<?=h($imgSm)?> 275w, <?=h($imgLg)?> 550w" sizes="(max-width:980px) 45vw, 275px" alt="<?=h($n['title'])?>" loading="lazy" fetchpriority="auto" decoding="async" width="275" height="183">
                  <?php else: ?>
                    <div class="mag-mini__fallback"></div>
                  <?php endif; ?>
                </div>

                <div class="mag-mini__body">
                  <?php if ($date): ?><div class="mag-mini__meta"><?=h($date)?></div><?php endif; ?>
                  <div class="mag-mini__title" data-i18n-text data-text-ka="<?=h((string)$n['title'])?>" data-text-en="<?=h($titleEn)?>"><?=h($n['title'])?></div>
                  <?php if ($desc !== '' || $descEn !== ''): ?>
                    <div class="mag-mini__desc" data-i18n-text data-text-ka="<?=h($desc)?>" data-text-en="<?=h($descEn)?>"><?=h($desc !== '' ? $desc : $descEn)?></div>
                  <?php endif; ?>
                </div>

                <div class="mag-mini__go">→</div>
              </a>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  <?php endif; ?>
</section>

<style>
  /* ===== Unique Magazine Section ===== */
  .mag-news{max-width:1220px;margin:54px auto;padding:0 18px}
  .mag-head{margin-bottom:18px}
  .mag-kicker{
    font:900 12px/1 system-ui;letter-spacing:.18em;text-transform:uppercase;
    opacity:.65;margin-bottom:8px
  }
  .mag-row{display:flex;align-items:end;justify-content:space-between;gap:14px}
  .mag-title{margin:0;font:900 32px/1.15 system-ui;color:#0b1220}
  .mag-sub{margin-top:8px;opacity:.75;font:700 14px/1.45 system-ui}
  .mag-all{
    display:inline-flex;align-items:center;gap:10px;
    text-decoration:none;font:900 13px/1 system-ui;
    padding:10px 12px;border-radius:14px;
    background:rgba(11,18,32,.06);
    border:1px solid rgba(11,18,32,.12);
    color:#0b1220;
    transition:transform .18s ease, background .18s ease;
    white-space:nowrap;
  }
  .mag-all:hover{transform:translateY(-2px);background:rgba(11,18,32,.10)}

  /* Layout */
  .mag-grid{
    display:grid;
    grid-template-columns: 1.35fr .95fr;
    gap:16px;
  }
  @media(max-width:980px){
    .mag-grid{grid-template-columns:1fr}
    .mag-row{flex-direction:column;align-items:flex-start}
  }

  /* Featured */
  .mag-feature{
    border-radius:24px;
    overflow:hidden;
    border:1px solid rgba(0,0,0,.08);
    background:rgba(255,255,255,.85);
    box-shadow:0 22px 60px rgba(0,0,0,.12);
  }
  .mag-feature__link{display:block;text-decoration:none;color:inherit}
  .mag-feature__media{position:relative}
  .mag-feature__media img{width:100%;height:360px;object-fit:cover;display:block}
  .mag-fallback{height:360px;background:linear-gradient(135deg,#eef2ff,#ffffff)}
  .mag-feature__shade{
    position:absolute;inset:0;
    background:radial-gradient(1200px 460px at 20% 10%, rgba(255,255,255,.20), rgba(0,0,0,.55));
    pointer-events:none;
  }
  .mag-feature__badge{
    position:absolute;top:14px;left:14px;
    padding:8px 12px;border-radius:999px;
    font:900 12px/1 system-ui;
    background:rgba(255,255,255,.86);
    border:1px solid rgba(0,0,0,.10);
    backdrop-filter: blur(10px);
  }
  .mag-feature__body{padding:16px 16px 18px}
  .mag-date{opacity:.7;font:900 12px/1 system-ui;margin-bottom:10px}
  .mag-feature__title{
    margin:0;
    font:950 22px/1.2 system-ui;
    letter-spacing:-.01em;
  }
  .mag-feature__desc{
    margin:12px 0 0;
    opacity:.82;line-height:1.6;
  }
  .mag-feature__cta{
    margin-top:16px;
    display:flex;align-items:center;justify-content:space-between;
    padding:12px 14px;border-radius:16px;
    background:linear-gradient(135deg, rgba(11,18,32,.06), rgba(11,18,32,.02));
    border:1px solid rgba(11,18,32,.10);
  }
  .mag-cta{font:950 13px/1 system-ui}
  .mag-ctaArrow{font:950 18px/1 system-ui}

  /* Side list */
  .mag-side{
    display:flex;flex-direction:column;gap:12px;
  }
  .mag-mini{
    border-radius:18px;
    border:1px solid rgba(0,0,0,.08);
    background:rgba(255,255,255,.85);
    box-shadow:0 14px 36px rgba(0,0,0,.08);
    overflow:hidden;
    transition:transform .18s ease, box-shadow .18s ease;
  }
  .mag-mini:hover{transform:translateY(-3px);box-shadow:0 18px 46px rgba(0,0,0,.12)}
  .mag-mini__link{
    display:grid;
    grid-template-columns:120px 1fr 40px;
    align-items:stretch;
    text-decoration:none;color:inherit;
  }
  .mag-mini__thumb img{width:100%;height:100%;object-fit:cover;display:block}
  .mag-mini__fallback{width:100%;height:100%;background:linear-gradient(135deg,#eef2ff,#ffffff)}
  .mag-mini__body{padding:12px 12px 12px 12px}
  .mag-mini__meta{opacity:.65;font:900 12px/1 system-ui;margin-bottom:8px}
  .mag-mini__title{
    font:950 14px/1.25 system-ui;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
  }
  .mag-mini__desc{
    margin-top:8px;opacity:.78;font:700 13px/1.45 system-ui;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
  }
  .mag-mini__go{
    display:flex;align-items:center;justify-content:center;
    font:950 18px/1 system-ui;
    border-left:1px solid rgba(0,0,0,.08);
    background:rgba(11,18,32,.04);
  }

  /* Empty */
  .mag-empty{
    display:flex;gap:14px;align-items:center;
    padding:18px;border-radius:20px;
    border:1px dashed rgba(0,0,0,.18);
    background:rgba(255,255,255,.75);
  }
  .mag-emptyIcon{font-size:22px}
  .mag-emptyTitle{font:950 16px/1.2 system-ui}
  .mag-emptySub{opacity:.75;margin-top:4px}
  .mag-sideEmpty{padding:16px;border-radius:18px;border:1px dashed rgba(0,0,0,.16);opacity:.75}
</style>
