<?php
/**
 * ==========================================================
 * FILE: grants/grants_apply.php   (USER SIDE)
 * ==========================================================
 * - Dynamic multi-step application form from DB
 * - Submits to: ../admin/api/grants_portal_api.php?action=submit (multipart/form-data)
 */

declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$pdo = db();

/* ---------- helpers ---------- */
function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_open_grant(array $g): bool {
  $today = date('Y-m-d');
  $active = (string)($g['is_active'] ?? '0') === '1';
  $status = (string)($g['status'] ?? 'current');
  $deadline = (string)($g['deadline'] ?? '');
  if (!$active) return false;
  if ($status === 'closed') return false;
  if ($deadline !== '' && $deadline < $today) return false;
  return true;
}

function has_col(PDO $pdo, string $table, string $col): bool {
  $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $table);
  $col   = preg_replace('/[^a-zA-Z0-9_]+/', '', $col);
  if ($table === '' || $col === '') return false;
  try {
    $q = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$q->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    return false;
  }
}

function build_public_image_url(string $rawPath): string {
  $rawPath = trim($rawPath);
  if ($rawPath === '') return '';

  if (
    preg_match('~^(https?:)?//~i', $rawPath) ||
    stripos($rawPath, 'data:image/') === 0
  ) {
    return $rawPath;
  }

  $path = str_replace('\\', '/', $rawPath);
  $path = preg_replace('~/+~', '/', $path);

  if (str_starts_with($path, '/')) {
    return $path;
  }

  $segments = [];
  foreach (explode('/', $path) as $seg) {
    $seg = trim($seg);
    if ($seg === '' || $seg === '.') continue;
    if ($seg === '..') {
      array_pop($segments);
      continue;
    }
    $segments[] = $seg;
  }

  $clean = implode('/', $segments);
  if ($clean === '') return '';

  if (stripos($clean, 'youthagency/') === 0) {
    return '/' . ltrim(substr($clean, strlen('youthagency/')), '/');
  }

  if (stripos($clean, 'admin/uploads/') === 0) {
    return '/' . $clean;
  }

  if (stripos($clean, 'uploads/') === 0) {
    return '/admin/' . $clean;
  }

  return '/' . ltrim($clean, '/');
}

function parse_json_array(?string $json): array {
  $json = trim((string)$json);
  if ($json === '') return [];
  try {
    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return is_array($decoded) ? $decoded : [];
  } catch (Throwable $e) {
    return [];
  }
}

function normalize_applicant_type_label(string $value): string {
  $v = mb_strtolower(trim($value));

  if (
    $v === 'person' ||
    str_contains($v, 'ფიზ') ||
    str_contains($v, 'individual')
  ) {
    return 'ფიზიკური პირი';
  }

  if (
    $v === 'org' ||
    str_contains($v, 'ორგ') ||
    str_contains($v, 'organization') ||
    str_contains($v, 'organisation') ||
    str_contains($v, 'company') ||
    str_contains($v, 'ngo')
  ) {
    return 'ორგანიზაცია / იურიდიული პირი';
  }

  return $value;
}

function format_money_value($value): string {
  $num = (float)$value;
  if ($num <= 0) return '—';
  return number_format($num, 0, '.', ' ') . ' ₾';
}

/* ---------- input ---------- */
$grantId = (int)($_GET['id'] ?? 0);
if ($grantId <= 0) {
  http_response_code(404);
  echo "Grant not found";
  exit;
}

/* ---------- load grant ---------- */
$hasMaxBudget = has_col($pdo, 'grants', 'max_budget');
$hasTitleEn   = has_col($pdo, 'grants', 'title_en');
$hasDescEn    = has_col($pdo, 'grants', 'description_en');
$hasBodyEn    = has_col($pdo, 'grants', 'body_en');

$sql = "SELECT id,title," . ($hasTitleEn ? "title_en" : "'' AS title_en") . ",
        slug,description," . ($hasDescEn ? "description_en" : "'' AS description_en") . ",
        body," . ($hasBodyEn ? "body_en" : "'' AS body_en") . ",
        deadline,status,is_active,image_path,apply_url";
if ($hasMaxBudget) $sql .= ", max_budget";
$sql .= " FROM grants WHERE id=? LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([$grantId]);
$grant = $st->fetch(PDO::FETCH_ASSOC);
if (!$grant) {
  http_response_code(404);
  echo "Grant not found";
  exit;
}

/* ---------- load builder ---------- */
$steps = [];
$fieldsByStep = [];
$allFields = [];

/* steps */
$st = $pdo->prepare("
  SELECT id,grant_id,step_key,name,sort_order,is_enabled
  FROM grant_steps
  WHERE grant_id=? AND is_enabled=1
  ORDER BY sort_order ASC, id ASC
");
$st->execute([$grantId]);
$steps = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* fields */
if ($steps) {
  $stepIds = array_map(fn($r) => (int)$r['id'], $steps);
  $in = implode(',', array_fill(0, count($stepIds), '?'));

  $st = $pdo->prepare("
    SELECT id,grant_id,step_id,label,type,options_json,is_required,show_for,sort_order,is_enabled
    FROM grant_fields
    WHERE step_id IN ($in) AND is_enabled=1
    ORDER BY sort_order ASC, id ASC
  ");
  $st->execute($stepIds);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($rows as $f) {
    $sid = (int)$f['step_id'];
    $fieldsByStep[$sid] = $fieldsByStep[$sid] ?? [];
    $fieldsByStep[$sid][] = $f;
    $allFields[] = $f;
  }
}

/* ---------- requirements ---------- */
$reqs = [];
$reqTable = 'grant_file_requirements';
$reqHasEnabled = has_col($pdo, $reqTable, 'is_enabled');

$reqSql = "SELECT id,grant_id,name,is_required FROM {$reqTable} WHERE grant_id=? ";
if ($reqHasEnabled) $reqSql .= "AND is_enabled=1 ";
$reqSql .= "ORDER BY id ASC";

$st = $pdo->prepare($reqSql);
$st->execute([$grantId]);
$reqs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ---------- fallback ---------- */
if (!$steps) {
  $steps = [
    ['id'=>0,'step_key'=>'applicant','name'=>'განმცხადებელი','sort_order'=>0,'is_enabled'=>1],
    ['id'=>0,'step_key'=>'project','name'=>'პროექტი','sort_order'=>1,'is_enabled'=>1],
    ['id'=>0,'step_key'=>'budget','name'=>'ბიუჯეტი','sort_order'=>2,'is_enabled'=>1],
    ['id'=>0,'step_key'=>'files','name'=>'ფაილები','sort_order'=>3,'is_enabled'=>1],
  ];
}

$open = is_open_grant($grant);

/* ---------- summary info ---------- */
$requiredReqs = array_values(array_filter($reqs, fn($r) => (int)($r['is_required'] ?? 0) === 1));
$optionalReqs = array_values(array_filter($reqs, fn($r) => (int)($r['is_required'] ?? 0) !== 1));

$applicantTypes = [];
foreach ($allFields as $f) {
  $label = mb_strtolower(trim((string)($f['label'] ?? '')));
  $type  = mb_strtolower(trim((string)($f['type'] ?? '')));
  if (
    ($type === 'select' || $type === 'radio' || $type === 'checkbox') &&
    (str_contains($label, 'ტიპ') || str_contains($label, 'applicant type'))
  ) {
    $opts = parse_json_array((string)($f['options_json'] ?? ''));
    foreach ($opts as $opt) {
      if (is_array($opt)) {
        $opt = (string)($opt['label'] ?? $opt['value'] ?? '');
      } else {
        $opt = (string)$opt;
      }
      $opt = trim($opt);
      if ($opt !== '') {
        $applicantTypes[] = normalize_applicant_type_label($opt);
      }
    }
    if ($applicantTypes) break;
  }
}
$applicantTypes = array_values(array_unique(array_filter($applicantTypes)));

$grantImage = build_public_image_url((string)($grant['image_path'] ?? ''));
$stepNames = array_values(array_filter(array_map(fn($s) => trim((string)($s['name'] ?? '')), $steps)));

/* ---------- payload ---------- */
$payload = [
  'csrf' => $csrf,
  'grant' => [
    'id' => (int)$grant['id'],
    'title' => (string)$grant['title'],
    'title_en' => (string)($grant['title_en'] ?? ''),
    'description' => (string)($grant['description'] ?? ''),
    'description_en' => (string)($grant['description_en'] ?? ''),
    'body' => (string)($grant['body'] ?? ''),
    'body_en' => (string)($grant['body_en'] ?? ''),
    'deadline' => (string)($grant['deadline'] ?? ''),
    'status' => (string)($grant['status'] ?? 'current'),
    'is_active' => (int)($grant['is_active'] ?? 0),
    'image_path' => (string)($grant['image_path'] ?? ''),
    'max_budget' => $hasMaxBudget ? (float)($grant['max_budget'] ?? 0) : 0.0,
  ],
  'steps' => $steps,
  'fieldsByStep' => $fieldsByStep,
  'requirements' => $reqs,
  'isOpen' => $open,
];
?><!doctype html>
<html lang="ka">
<head>
  <link rel="icon" type="image/png" href="/imgs/youthagencyicon.png">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h((string)$grant['title']) ?> • Grant Portal</title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets.css?v=1">

  <style>
    :root{
      --bg:#0b1220;
      --panel:#0f172a;
      --card:#111827;
      --card2:#0d1527;
      --line:#22314a;
      --line2:#30435f;
      --text:#e5e7eb;
      --muted:#94a3b8;
      --muted2:#b6c5d8;
      --accent:#2563eb;
      --accent2:#3b82f6;
      --ok:#16a34a;
      --warn:#f59e0b;
      --bad:#dc2626;
      --radius:16px;
      --shadow:0 16px 50px rgba(0,0,0,.28);
    }

    *{box-sizing:border-box}
    html,body{background:var(--bg)!important}
    body{
      margin:0;
      font-family:'Noto Sans Georgian',system-ui,-apple-system,Segoe UI,Arial,sans-serif;
      background:var(--bg);
      color:var(--text);
    }

    a{color:inherit;text-decoration:none}
    .wrap{max-width:1180px;margin:0 auto;padding:18px}

    .card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      padding:14px;
      box-shadow:var(--shadow);
    }

    .btn{
      border:0;
      border-radius:12px;
      padding:11px 15px;
      font-weight:900;
      cursor:pointer;
      transition:.15s ease;
    }
    .btn:hover{transform:translateY(-1px)}
    .btn-ac{background:var(--accent);color:#fff}
    .btn-ok{background:var(--ok);color:#fff}
    .btn-ghost{background:#0a0f1c;color:#fff;border:1px solid var(--line)}
    .btn-bad{background:var(--bad);color:#fff}
    .btn:disabled{opacity:.5;cursor:not-allowed;transform:none}

    .pill{
      display:inline-flex;
      gap:8px;
      align-items:center;
      padding:7px 11px;
      border-radius:999px;
      border:1px solid var(--line);
      background:#0a0f1c;
      font-weight:900;
      font-size:12px;
      color:#cbd5e1;
    }
    .pill.open{border-color:rgba(22,163,74,.35);color:#86efac}
    .pill.closed{border-color:rgba(245,158,11,.35);color:#fde68a}
    .pill.info{border-color:rgba(59,130,246,.35);color:#bfdbfe}

    .banner{
      border:1px solid var(--line);
      border-radius:22px;
      overflow:hidden;
      background:
        radial-gradient(1100px 500px at 20% 0%, rgba(37,99,235,.30), transparent 55%),
        radial-gradient(900px 500px at 70% 30%, rgba(245,158,11,.16), transparent 55%),
        linear-gradient(135deg, #0a0f1c 0%, #0f172a 60%, #0a0f1c 100%);
      padding:22px;
      display:grid;
      grid-template-columns: 1.15fr .85fr;
      gap:18px;
      align-items:center;
      box-shadow:var(--shadow);
    }
    .banner h1{margin:0 0 8px 0;font-size:28px;line-height:1.22}
    .banner p{margin:0 0 14px 0;color:var(--muted);font-weight:800;line-height:1.6}
    .bannerMedia{
      min-height:230px;
      border-radius:18px;
      border:1px solid var(--line);
      overflow:hidden;
      background:
        radial-gradient(160px 120px at 30% 40%, rgba(37,99,235,.55), transparent 70%),
        radial-gradient(180px 120px at 70% 60%, rgba(16,185,129,.28), transparent 70%),
        radial-gradient(220px 140px at 60% 20%, rgba(245,158,11,.25), transparent 70%),
        linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
    }
    .bannerMedia img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .bannerMedia .fallback{
      color:rgba(255,255,255,.55);
      font-weight:900;
      font-size:18px;
      letter-spacing:.3px;
    }

    @media(max-width:900px){
      .banner{grid-template-columns:1fr}
      .bannerMedia{min-height:170px}
    }

    .summaryGrid{
      display:grid;
      grid-template-columns:repeat(4,1fr);
      gap:12px;
      margin-top:14px;
    }
    @media(max-width:980px){ .summaryGrid{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:560px){ .summaryGrid{grid-template-columns:1fr} }

    .summaryCard{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:16px;
      padding:14px;
      box-shadow:var(--shadow);
    }
    .summaryLabel{
      color:var(--muted);
      font-size:12px;
      font-weight:900;
      margin-bottom:7px;
    }
    .summaryValue{
      color:var(--text);
      font-size:18px;
      font-weight:900;
      line-height:1.35;
      word-break:break-word;
    }
    .summaryValue.smallText{font-size:15px}

    .notice{
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(245,158,11,.35);
      background:rgba(245,158,11,.08);
      color:#fde68a;
      font-weight:900;
    }
    .okbox{
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(22,163,74,.35);
      background:rgba(22,163,74,.08);
      color:#86efac;
      font-weight:900;
    }
    .err{
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(220,38,38,.35);
      background:rgba(220,38,38,.08);
      color:#fecaca;
      font-weight:900;
    }

    .portal{
      display:grid;
      grid-template-columns:280px 1fr;
      gap:14px;
      margin-top:14px;
    }
    @media(max-width:960px){ .portal{grid-template-columns:1fr} }

    .steps{
      position:sticky;
      top:14px;
      align-self:start;
    }
    @media(max-width:960px){ .steps{position:relative;top:0} }

    .stepHeader{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
      margin-bottom:10px;
    }

    .stepItem{
      display:flex;
      gap:10px;
      align-items:center;
      padding:11px 12px;
      border-radius:14px;
      border:1px solid var(--line);
      background:#0a0f1c;
      margin-top:10px;
      cursor:pointer;
      transition:.15s ease;
    }
    .stepItem:hover{border-color:var(--line2)}
    .dot{
      width:30px;
      height:30px;
      border-radius:999px;
      border:1px solid var(--line);
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:1000;
      flex:0 0 30px;
    }
    .stepItem.done{border-color:rgba(22,163,74,.35)}
    .stepItem.done .dot{border-color:rgba(22,163,74,.35);color:#86efac}
    .stepItem.active{
      border-color:rgba(37,99,235,.45);
      box-shadow:0 0 0 2px rgba(37,99,235,.15) inset;
      background:rgba(37,99,235,.08);
    }
    .stepItem.disabled{opacity:.62;cursor:not-allowed}

    input,select,textarea{
      width:100%;
      background:#0a0f1c;
      color:var(--text);
      border:1px solid var(--line);
      border-radius:12px;
      padding:11px 12px;
      outline:none;
      font-weight:900;
    }
    input:focus,select:focus,textarea:focus{
      border-color:#3b82f6;
      box-shadow:0 0 0 3px rgba(59,130,246,.14);
    }

    textarea{min-height:110px;resize:vertical}
    label{display:block;color:#93c5fd;font-weight:1000;font-size:12px;margin-bottom:6px}
    .field{margin-top:12px}
    table{width:100%;border-collapse:collapse}
    th,td{
      padding:10px;
      border-bottom:1px solid var(--line);
      text-align:left;
      font-size:14px;
      vertical-align:top
    }
    th{color:#93c5fd}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .sp{justify-content:space-between}
    .tableScroll{overflow:auto;margin-top:10px}
    .muted{color:var(--muted);font-weight:900}
    .small{font-size:12px;color:var(--muted);font-weight:800}
    .hr{border:0;border-top:1px solid var(--line);margin:14px 0}

    .sectionTitle{
      margin:0 0 6px 0;
      font-size:22px;
      line-height:1.2;
    }

    .topNote{
      margin-top:12px;
      border:1px solid rgba(59,130,246,.28);
      background:rgba(59,130,246,.08);
      color:#cfe3ff;
      border-radius:14px;
      padding:12px 14px;
      font-weight:800;
      line-height:1.6;
    }

    .listPlain{
      margin:0;
      padding-left:18px;
      color:var(--muted2);
      line-height:1.8;
      font-weight:800;
    }
  </style>
</head>

<body>
  <div id="siteHeaderMount"></div>

  <div class="wrap">
    <div class="banner">
      <div>
        <h1 data-i18n-text data-text-ka="<?=h((string)$grant['title'])?>" data-text-en="<?=h((string)($grant['title_en'] ?? ''))?>">
          <?= h((string)$grant['title']) ?>
        </h1>

        <p data-i18n-text data-text-ka="<?=h((string)($grant['description'] ?? ''))?>" data-text-en="<?=h((string)($grant['description_en'] ?? ''))?>">
          <?= h((string)($grant['description'] ?? '')) ?>
        </p>

        <div class="row">
          <span class="pill <?= $open ? 'open' : 'closed' ?>">
            <i class="fa-solid fa-circle" style="font-size:8px;opacity:.85"></i>
            <span data-i18n="<?= $open ? 'grantsApply.statusOpen' : 'grantsApply.statusClosed' ?>">
              <?= $open ? 'მიმდინარე' : 'დახურული' ?>
            </span>
          </span>

          <span class="pill">
            <i class="fa-regular fa-calendar"></i>
            <span data-i18n="grantsApply.deadlineLabel">ვადა:</span>
            <b><?= h((string)($grant['deadline'] ?: '—')) ?></b>
          </span>

          <?php if ($hasMaxBudget && (float)($grant['max_budget'] ?? 0) > 0): ?>
            <span class="pill info">
              <i class="fa-solid fa-wallet"></i>
              მაქსიმუმი: <b><?= h(format_money_value($grant['max_budget'] ?? 0)) ?></b>
            </span>
          <?php endif; ?>
        </div>

        <?php if ($hasMaxBudget && (float)($grant['max_budget'] ?? 0) > 0): ?>
          <div class="topNote">
            ბიუჯეტის ცხრილში შეტანილი თანხების ჯამი არ უნდა აჭარბებდეს
            <b><?= h(format_money_value($grant['max_budget'] ?? 0)) ?></b>-ს.
          </div>
        <?php endif; ?>
      </div>

      <div class="bannerMedia" aria-hidden="true">
        <?php if ($grantImage !== ''): ?>
          <img src="<?= h($grantImage) ?>" alt="">
        <?php else: ?>
          <div class="fallback">Grant Portal</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="summaryGrid">
      <div class="summaryCard">
        <div class="summaryLabel">ნაბიჯების რაოდენობა</div>
        <div class="summaryValue"><?= count($stepNames) ?></div>
      </div>

      <div class="summaryCard">
        <div class="summaryLabel">სავალდებულო დოკუმენტები</div>
        <div class="summaryValue"><?= count($requiredReqs) ?></div>
      </div>

      <div class="summaryCard">
        <div class="summaryLabel">დამატებითი დოკუმენტები</div>
        <div class="summaryValue"><?= count($optionalReqs) ?></div>
      </div>

      <div class="summaryCard">
        <div class="summaryLabel">ვინ შეიძლება იყოს განმცხადებელი</div>
        <div class="summaryValue smallText">
          <?= $applicantTypes ? h(implode(', ', $applicantTypes)) : 'ფორმაშია განსაზღვრული' ?>
        </div>
      </div>
    </div>

    <?php if ($stepNames): ?>
      <div class="card" style="margin-top:14px">
        <div class="summaryLabel" style="margin-bottom:8px">განაცხადის ნაბიჯები</div>
        <ol class="listPlain">
          <?php foreach ($stepNames as $stepName): ?>
            <li><?= h($stepName) ?></li>
          <?php endforeach; ?>
        </ol>
      </div>
    <?php endif; ?>

    <?php if(!$open): ?>
      <div class="card" style="margin-top:14px">
        <div class="notice" data-i18n="grantsApply.closedNotice">
          ამ საგრანტო პროგრამაზე განაცხადების მიღება დასრულებულია ან გამორთულია.
        </div>
      </div>
    <?php else: ?>
      <input type="hidden" name="budget_json" id="budget_json" value="">
      <input type="hidden" name="action_plan_json" id="action_plan_json" value="">

      <div class="portal">
        <div class="card steps">
          <div class="stepHeader">
            <b data-i18n="grantsApply.stepsTitle">ნაბიჯები</b>
            <span class="pill" data-i18n="grantsApply.stepsHint">შეავსეთ ნაბიჯობრივად</span>
          </div>
          <div id="stepsList"></div>
        </div>

        <div class="card" id="stepContent"></div>
      </div>
    <?php endif; ?>
  </div>

  <div id="siteFooterMount"></div>

  <script>
    const DATA = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const API  = "../admin/api/grants_portal_api.php";

    function formatMoney(n){
      n = Number(n || 0);
      try { return n.toLocaleString("ka-GE"); }
      catch(_) { return String(n); }
    }

    function esc(s){
      return (s ?? "").toString()
        .replaceAll("&","&amp;")
        .replaceAll("<","&lt;")
        .replaceAll(">","&gt;")
        .replaceAll('"',"&quot;")
        .replaceAll("'","&#039;");
    }

    function escAttr(s){
      return esc(s).replaceAll("\n"," ");
    }

    function parseJsonMaybe(v){
      if(v == null) return null;
      if(typeof v === "string"){
        const s = v.trim();
        if(!s) return null;
        try { return JSON.parse(s); }
        catch(e){ return null; }
      }
      return v;
    }

    function bytesToMB(b){
      return Math.round((Number(b||0)/1024/1024)*10)/10;
    }

    function localizeApplicantOption(label){
      const raw = String(label || "").trim();
      const s = raw.toLowerCase();

      if (s === "person" || s.includes("ფიზ") || s.includes("individual")) {
        return "ფიზიკური პირი";
      }

      if (
        s === "org" ||
        s.includes("ორგ") ||
        s.includes("organization") ||
        s.includes("organisation") ||
        s.includes("company") ||
        s.includes("ngo")
      ) {
        return "ორგანიზაცია / იურიდიული პირი";
      }

      return raw;
    }

    function normalizeOptionItem(opt){
      if(opt && typeof opt === "object" && !Array.isArray(opt)){
        const value = String(opt.value ?? opt.id ?? opt.key ?? opt.label ?? "");
        const rawLabel = String(opt.label ?? opt.text ?? opt.name ?? opt.value ?? "");
        return {
          value,
          label: localizeApplicantOption(rawLabel || value)
        };
      }

      const value = String(opt ?? "");
      return {
        value,
        label: localizeApplicantOption(value)
      };
    }

    const steps = (DATA.steps || []).map((s, idx) => Object.freeze({
      id: Number(s.id || 0),
      key: (s.step_key && String(s.step_key).trim()) ? String(s.step_key) : ("step_" + idx),
      name: String(s.name || ("ნაბიჯი " + (idx + 1))),
    }));

    if(!steps.some(s => s.key === "submit")){
      steps.push({id:0, key:"submit", name:"გაგზავნა"});
    }

    const state = {
      grant_id: Number(DATA.grant.id),
      currentKey: steps[0]?.key || "submit",
      validByStep: {},
      form_data: {},
      applicantType: "person",

      reqUploads: {},
      otherUploads: [],
      fieldUploads: {},

      data: {
        budget: { rows: [{}], total: 0 },
        actionPlan: { rows: [{}], total_rows: 0 }
      },
      lastBudgetError: "",
      lastActionPlanError: "",

      budgetTotal(){
        return (this.data.budget.rows || []).reduce((s,r) => s + Number((r && r.amount) ? r.amount : 0), 0);
      },

      actionPlanCount(){
        return (this.data.actionPlan.rows || []).filter(r => r && typeof r === "object").length;
      },
    };

    function getFieldsForStep(stepId){
      const fb = DATA.fieldsByStep || {};
      const list = (fb[String(stepId)] || fb[stepId] || []);
      return (list || []).map(f => Object.freeze({
        id: Number(f.id),
        label: String(f.label || ""),
        type: String(f.type || "text"),
        is_required: Number(f.is_required || 0),
        show_for: String(f.show_for || "all"),
        options_json: f.options_json ?? null,
      }));
    }

    function getAllFieldsFlat(){
      const out = [];
      for(const s of DATA.steps || []){
        const sid = Number(s.id || 0);
        for(const f of getFieldsForStep(sid)) out.push(f);
      }
      return out;
    }

    function normalizeApplicantType(v){
      const s = String(v || "").trim().toLowerCase();
      if(s === "org" || s === "organization" || s.includes("ორგ")) return "org";
      if(s === "person" || s.includes("ფიზ")) return "person";
      return "";
    }

    function findApplicantTypeField(){
      const all = getAllFieldsFlat();
      return all.find(f =>
        ["select","radio","checkbox"].includes(String(f.type).toLowerCase()) &&
        String(f.label).toLowerCase().includes("ტიპ")
      ) || null;
    }

    function findBudgetTableFieldForStep(stepId){
      const fields = getFieldsForStep(stepId);
      return fields.find(f => f.type === "budget_table") || null;
    }

    function findAnyBudgetTableField(){
      return getAllFieldsFlat().find(f => f.type === "budget_table") || null;
    }

    function findActionPlanTableFieldForStep(stepId){
      const fields = getFieldsForStep(stepId);
      return fields.find(f => f.type === "action_plan_table") || null;
    }

    function findAnyActionPlanTableField(){
      return getAllFieldsFlat().find(f => f.type === "action_plan_table") || null;
    }

    function isBudgetLikeField(field){
      const t = String(field?.type || "").toLowerCase();
      const l = String(field?.label || "").toLowerCase();
      return t === "budget_table" || t.includes("budget") || l.includes("ბიუჯ") || l.includes("budget");
    }

    function isActionPlanLikeField(field){
      const t = String(field?.type || "").toLowerCase();
      const l = String(field?.label || "").toLowerCase();
      return t === "action_plan_table" || t.includes("action_plan") || l.includes("სამოქმედო") || l.includes("action plan");
    }

    function isBudgetStep(dbStep){
      if(!dbStep) return false;
      const key = String(dbStep?.step_key || "").toLowerCase();
      const name = String(dbStep?.name || "").toLowerCase();
      if(key.includes("budget") || name.includes("ბიუჯ")) return true;
      const sid = Number(dbStep.id || 0);
      if(sid && findBudgetTableFieldForStep(sid)) return true;
      if(state.currentKey === "budget") return true;
      return false;
    }

    function isActionPlanStep(dbStep){
      if(!dbStep) return false;
      const key = String(dbStep?.step_key || "").toLowerCase();
      const name = String(dbStep?.name || "").toLowerCase();
      if(key.includes("action") || key.includes("plan") || name.includes("სამოქმედო")) return true;
      const sid = Number(dbStep.id || 0);
      if(sid && findActionPlanTableFieldForStep(sid)) return true;
      if(state.currentKey === "action_plan") return true;
      return false;
    }

    function isFilesStep(dbStep, localKey){
      const nm = String(dbStep?.name || "").toLowerCase();
      const ky = String(dbStep?.step_key || "").toLowerCase();
      return localKey === "files" || nm.includes("ფაილ") || ky.includes("file");
    }

    function resolveDbStepByKey(key){
      const dbSteps = (DATA.steps || []).filter(s => Number(s.id || 0) > 0);
      if(!dbSteps.length) return null;

      let s = dbSteps.find(x => String(x.step_key || "") === key) || null;
      if(s) return s;

      const nonSubmit = steps.filter(x => x.key !== "submit");
      const localIdx = nonSubmit.findIndex(x => x.key === key);
      return dbSteps[localIdx] || dbSteps[0] || null;
    }

    function budgetDefaultOptions(){
      return {
        currency: "₾",
        min_rows: 1,
        max_total: 0,
        columns: [
          {key:"cat",    label:"კატეგორია *", type:"text",   required:true, placeholder:"მაგ: აღჭურვილობა"},
          {key:"desc",   label:"აღწერა *",    type:"text",   required:true, placeholder:"დანიშნულება"},
          {key:"amount", label:"თანხა (₾) *", type:"number", required:true, min:0},
        ]
      };
    }

    function readBudgetOptionsFromField(field){
      const d = budgetDefaultOptions();
      const opt = parseJsonMaybe(field?.options_json) || null;
      if(!opt || typeof opt !== "object") return d;

      const currency = String(opt.currency || d.currency);
      const minRows = Math.max(1, Number(opt.min_rows || d.min_rows));

      let maxTotal = Number(opt.max_total ?? opt.max_budget ?? 0);
      if(!maxTotal && Number(DATA.grant?.max_budget || 0) > 0){
        maxTotal = Number(DATA.grant.max_budget || 0);
      }
      maxTotal = Math.max(0, Number(maxTotal || 0));

      let cols = Array.isArray(opt.columns) ? opt.columns : [];
      cols = cols.map(c => ({
        key: String(c.key || ""),
        label: String(c.label || c.key || ""),
        type: String((c.type || "text")).toLowerCase(),
        required: !!c.required,
        placeholder: c.placeholder ? String(c.placeholder) : "",
        min: (c.min != null) ? Number(c.min) : null,
      })).filter(c => c.key);

      if(!cols.length){
        const out = budgetDefaultOptions();
        out.currency = currency;
        out.min_rows = minRows;
        out.max_total = maxTotal;
        out.columns[2].label = `თანხა (${currency}) *`;
        return out;
      }

      cols = cols.map(c => {
        if(c.key === "amount" && !c.label.includes("(")){
          c.label = `თანხა (${currency}) *`;
        }
        return c;
      });

      return { currency, min_rows:minRows, max_total:maxTotal, columns: cols };
    }

    function actionPlanDefaultOptions(){
      return {
        min_rows: 1,
        auto_number: true,
        columns: [
          {key:"activity",   label:"აქტივობა *", type:"text", required:true, placeholder:"მაგ: ტრენინგის ჩატარება"},
          {key:"start_date", label:"დაწყების თარიღი *", type:"date", required:true, placeholder:""},
          {key:"end_date",   label:"დასრულების თარიღი *", type:"date", required:true, placeholder:""},
          {key:"coverage",   label:"აქტივობის გაშუქება", type:"text", required:false, placeholder:"სოციალური ან/და მედია-პლატფორმები"},
        ]
      };
    }

    function readActionPlanOptionsFromField(field){
      const d = actionPlanDefaultOptions();
      const opt = parseJsonMaybe(field?.options_json) || null;
      if(!opt || typeof opt !== "object") return d;

      const minRows = Math.max(1, Number(opt.min_rows || d.min_rows));
      const autoNumber = !!(opt.auto_number ?? true);

      let cols = Array.isArray(opt.columns) ? opt.columns : [];
      cols = cols.map(c => ({
        key: String(c.key || ""),
        label: String(c.label || c.key || ""),
        type: String((c.type || "text")).toLowerCase(),
        required: !!c.required,
        placeholder: c.placeholder ? String(c.placeholder) : "",
      })).filter(c => c.key);

      if(!cols.length){
        const out = actionPlanDefaultOptions();
        out.min_rows = minRows;
        out.auto_number = autoNumber;
        return out;
      }

      return { min_rows:minRows, auto_number:autoNumber, columns: cols };
    }

    function validateBudgetWithOptions(rows, opt){
      state.lastBudgetError = "";
      rows = rows || [];
      const minRows = Math.max(1, Number(opt.min_rows || 1));
      const cols = opt.columns || [];
      const currency = String(opt.currency || "₾");
      const maxTotal = Math.max(0, Number(opt.max_total || 0));

      if(rows.length < minRows){
        state.lastBudgetError = `ბიუჯეტში მინიმუმ ${minRows} სტრიქონი უნდა იყოს.`;
        return false;
      }

      const okCount = rows.filter(r => {
        if(!r || typeof r !== "object") return false;
        for(const c of cols){
          if(!c.required) continue;
          const v = r[c.key];
          if(c.type === "number"){
            if(Number(v || 0) <= 0) return false;
          }else{
            if(!String(v ?? "").trim()) return false;
          }
        }
        return true;
      }).length;

      if(okCount < 1){
        state.lastBudgetError = "ბიუჯეტში შეავსე მინიმუმ 1 სწორი ჩანაწერი (სავალდებულო სვეტები/თანხა > 0).";
        return false;
      }

      const total = rows.reduce((s,r) => s + Number(r?.amount || 0), 0);
      if(maxTotal > 0 && total > maxTotal){
        state.lastBudgetError = `მაქსიმალური ბიუჯეტი არის ${formatMoney(maxTotal)} ${currency}`;
        return false;
      }
      return true;
    }

    function normalizeBudgetRowForSubmit(row){
      const raw = row && typeof row === "object" ? row : {};
      const cat = String(raw.cat ?? raw.category ?? raw.name ?? "").trim();
      const desc = String(raw.desc ?? raw.description ?? raw.details ?? "").trim();
      const amount = Number(raw.amount ?? raw.sum ?? raw.total ?? 0);
      const clean = { cat, desc, amount: Number.isFinite(amount) ? amount : 0 };
      const hasContent = !!clean.cat || !!clean.desc || clean.amount > 0;
      return hasContent ? clean : null;
    }

    function collectBudgetRowsFromTable(){
      const trList = Array.from(document.querySelectorAll("#budgetStep tbody tr"));
      let rows = [];

      if(trList.length){
        rows = trList.map(tr => {
          const data = {};
          const inputs = Array.from(
            tr.querySelectorAll("input[data-bkey], textarea[data-bkey], select[data-bkey], input[data-k], textarea[data-k], select[data-k]")
          );

          inputs.forEach(inp => {
            const key = String(inp.getAttribute("data-bkey") || inp.getAttribute("data-k") || "").trim();
            if(!key) return;
            let val = inp.value;
            if(inp.type === "number") val = Number(val || 0);
            data[key] = val;
          });

          return normalizeBudgetRowForSubmit(data);
        }).filter(Boolean);
      } else {
        rows = (Array.isArray(state.data.budget.rows) ? state.data.budget.rows : [])
          .map(normalizeBudgetRowForSubmit)
          .filter(Boolean);
      }

      const total = rows.reduce((sum, r) => sum + Number(r.amount || 0), 0);
      return { rows, total };
    }

    function syncBudgetHiddenInput(){
      const budget = collectBudgetRowsFromTable();
      state.data.budget.rows = budget.rows;
      state.data.budget.total = budget.total;
      const hidden = document.getElementById("budget_json");
      if(hidden) hidden.value = JSON.stringify(budget);
      return budget;
    }

    function applyBudgetToFormData(){
      const budget = syncBudgetHiddenInput();
      state.form_data.budget = budget;
      const budgetTargets = getAllFieldsFlat().filter(isBudgetLikeField);
      budgetTargets.forEach(f => {
        state.form_data["field_" + f.id] = budget;
      });
      return budget;
    }

    function budgetPayloadForField(field){
      const opt = field ? readBudgetOptionsFromField(field) : budgetDefaultOptions();
      const cols = Array.isArray(opt.columns) ? opt.columns : [];
      return {
        rows: (state.data.budget.rows || []),
        columns: cols.map(c => ({
          key: String(c?.key || ''),
          label: String(c?.label || c?.key || ''),
          type: String(c?.type || 'text')
        })).filter(c => c.key)
      };
    }

    function normalizeActionPlanRowForSubmit(row, opt){
      const raw = row && typeof row === "object" ? row : {};
      const cols = Array.isArray(opt?.columns) ? opt.columns : [];
      const clean = {};
      let hasContent = false;

      cols.forEach(c => {
        const key = String(c?.key || "").trim();
        if(!key) return;
        const val = String(raw[key] ?? "").trim();
        clean[key] = val;
        if(val !== "") hasContent = true;
      });

      return hasContent ? clean : null;
    }

    function collectActionPlanRowsFromTable(opt){
      const trList = Array.from(document.querySelectorAll("#actionPlanStep tbody tr[data-aprow]"));
      let rows = [];

      if(trList.length){
        rows = trList.map(tr => {
          const data = {};
          const inputs = Array.from(
            tr.querySelectorAll("input[data-apkey], textarea[data-apkey], select[data-apkey], input[data-k], textarea[data-k], select[data-k]")
          );

          inputs.forEach(inp => {
            const key = String(inp.getAttribute("data-apkey") || inp.getAttribute("data-k") || "").trim();
            if(!key) return;
            data[key] = inp.value ?? "";
          });

          return normalizeActionPlanRowForSubmit(data, opt);
        }).filter(Boolean);
      } else {
        rows = (Array.isArray(state.data.actionPlan.rows) ? state.data.actionPlan.rows : [])
          .map(r => normalizeActionPlanRowForSubmit(r, opt))
          .filter(Boolean);
      }

      return { rows, total_rows: rows.length };
    }

    function syncActionPlanHiddenInput(opt){
      const plan = collectActionPlanRowsFromTable(opt || actionPlanDefaultOptions());
      state.data.actionPlan.rows = plan.rows;
      state.data.actionPlan.total_rows = plan.total_rows;
      const hidden = document.getElementById("action_plan_json");
      if(hidden) hidden.value = JSON.stringify(plan);
      return plan;
    }

    function applyActionPlanToFormData(){
      const actionField = findAnyActionPlanTableField();
      const opt = actionField ? readActionPlanOptionsFromField(actionField) : actionPlanDefaultOptions();
      const plan = syncActionPlanHiddenInput(opt);
      state.form_data.action_plan = plan;
      const actionTargets = getAllFieldsFlat().filter(isActionPlanLikeField);
      actionTargets.forEach(f => {
        state.form_data["field_" + f.id] = plan;
      });
      return plan;
    }

    function actionPlanPayloadForField(field){
      const opt = field ? readActionPlanOptionsFromField(field) : actionPlanDefaultOptions();
      const cols = Array.isArray(opt.columns) ? opt.columns : [];
      return {
        rows: (state.data.actionPlan.rows || []),
        columns: cols.map(c => ({
          key: String(c?.key || ''),
          label: String(c?.label || c?.key || ''),
          type: String(c?.type || 'text')
        })).filter(c => c.key)
      };
    }

    function validateActionPlanWithOptions(rows, opt){
      state.lastActionPlanError = "";
      rows = rows || [];
      const minRows = Math.max(1, Number(opt.min_rows || 1));
      const cols = opt.columns || [];

      if(rows.length < minRows){
        state.lastActionPlanError = `სამოქმედო გეგმაში მინიმუმ ${minRows} სტრიქონი უნდა იყოს.`;
        return false;
      }

      const okCount = rows.filter(r => {
        if(!r || typeof r !== "object") return false;
        for(const c of cols){
          if(!c.required) continue;
          const v = String(r[c.key] ?? "").trim();
          if(!v) return false;
        }
        return true;
      }).length;

      if(okCount < 1){
        state.lastActionPlanError = "სამოქმედო გეგმაში შეავსე მინიმუმ 1 სწორი ჩანაწერი.";
        return false;
      }

      return true;
    }

    function syncVisibleDomToState(){
      document.querySelectorAll("[data-field]").forEach(el => {
        const k = el.getAttribute("data-field");
        if(!k) return;
        state.form_data[k] = el.value ?? "";
      });

      const groups = new Set(
        Array.from(document.querySelectorAll("[data-group]"))
          .map(el => String(el.getAttribute("data-group") || "").trim())
          .filter(Boolean)
      );

      groups.forEach(k => {
        const list = Array.from(document.querySelectorAll(`input[data-group="${k}"]`));
        const isRadio = list.some(x => x.type === "radio");
        if(isRadio){
          const chosen = list.find(x => x.checked);
          state.form_data[k] = chosen ? chosen.value : "";
        }else{
          state.form_data[k] = list.filter(x => x.checked).map(x => x.value);
        }
      });

      const anyBudgetField = findAnyBudgetTableField();
      const anyBudgetOpt = anyBudgetField ? readBudgetOptionsFromField(anyBudgetField) : budgetDefaultOptions();

      const budgetInputs = Array.from(document.querySelectorAll("[data-brow][data-bkey]"));
      if(budgetInputs.length){
        budgetInputs.forEach(inp => {
          const i = Number(inp.getAttribute("data-brow"));
          const k = String(inp.getAttribute("data-bkey") || "");
          if(!Number.isFinite(i) || i < 0 || !k) return;
          state.data.budget.rows[i] = state.data.budget.rows[i] || {};
          const col = (anyBudgetOpt.columns || []).find(c => c.key === k);
          if(col && col.type === "number"){
            state.data.budget.rows[i][k] = Number(inp.value || 0);
          }else{
            state.data.budget.rows[i][k] = inp.value;
          }
        });
      }

      const anyActionPlanField = findAnyActionPlanTableField();
      const anyActionPlanOpt = anyActionPlanField ? readActionPlanOptionsFromField(anyActionPlanField) : actionPlanDefaultOptions();

      const actionInputs = Array.from(document.querySelectorAll("[data-aprow][data-apkey]"));
      if(actionInputs.length){
        actionInputs.forEach(inp => {
          const i = Number(inp.getAttribute("data-aprow"));
          const k = String(inp.getAttribute("data-apkey") || "");
          if(!Number.isFinite(i) || i < 0 || !k) return;
          state.data.actionPlan.rows[i] = state.data.actionPlan.rows[i] || {};
          const col = (anyActionPlanOpt.columns || []).find(c => c.key === k);
          state.data.actionPlan.rows[i][k] = inp.value;
        });
      }
    }

    function viewBudget(state, opt){
      const rows = state.data.budget.rows || [];
      const total = state.budgetTotal();
      const currency = String(opt.currency || "₾");
      const cols = opt.columns || [];
      const maxTotal = Math.max(0, Number(opt.max_total || 0));

      return `
        <h2 class="sectionTitle">ბიუჯეტი</h2>
        <div class="muted">დაამატეთ ხარჯები. ჯამი ითვლება ავტომატურად.</div>

        <div class="row" style="margin-top:10px;gap:10px">
          <div class="pill open">ჯამი: <b id="budgetTotal">${formatMoney(total)}</b> ${esc(currency)}</div>
          ${maxTotal > 0 ? `<div class="pill closed">მაქსიმუმი: <b>${formatMoney(maxTotal)}</b> ${esc(currency)}</div>` : ``}
        </div>

        <div style="overflow:auto;margin-top:10px">
          <table>
            <thead>
              <tr>
                ${cols.map(c => `<th>${esc(c.label || c.key)}</th>`).join("")}
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              ${rows.map((r,i) => `
                <tr>
                  ${cols.map(c => {
                    const k = c.key;
                    const t = (c.type === "number") ? "number" : "text";
                    const ph = c.placeholder ? `placeholder="${escAttr(c.placeholder)}"` : "";
                    const min = (t === "number" && c.min != null) ? `min="${Number(c.min)}"` : (t === "number" ? `min="0"` : "");
                    const val = (t === "number") ? Number(r?.[k] || 0) : escAttr(r?.[k] ?? "");
                    return `<td><input data-brow="${i}" data-bkey="${escAttr(k)}" data-k="${escAttr(k)}" type="${t}" ${min} ${ph} value="${val}"></td>`;
                  }).join("")}
                  <td><button class="btn btn-bad" data-del="${i}" type="button">X</button></td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>

        <div class="row sp" style="margin-top:10px">
          <button class="btn btn-ghost" id="btnAddRow" type="button">+ ჩანაწერის დამატება</button>
        </div>

        <div class="row" style="margin-top:14px;justify-content:space-between">
          <button class="btn btn-ghost" id="btnBackB" type="button">უკან</button>
          <button class="btn btn-ac" id="btnNextB" type="button">შემდეგი</button>
        </div>
      `;
    }

    function bindBudget(state, opt, onBack, onNext){
      const backBtn = document.getElementById("btnBackB");
      if(backBtn) backBtn.onclick = () => onBack?.();

      const addBtn = document.getElementById("btnAddRow");
      if(addBtn){
        addBtn.onclick = () => {
          state.data.budget.rows.push({});
          renderBudget(state, opt, onBack, onNext);
        };
      }

      document.querySelectorAll("[data-brow][data-bkey]").forEach(inp => {
        inp.oninput = () => {
          const i = Number(inp.getAttribute("data-brow"));
          const k = inp.getAttribute("data-bkey");
          state.data.budget.rows[i] = state.data.budget.rows[i] || {};
          const col = (opt.columns || []).find(c => c.key === k);
          if(col && col.type === "number"){
            state.data.budget.rows[i][k] = Number(inp.value || 0);
          }else{
            state.data.budget.rows[i][k] = inp.value;
          }
          const budget = syncBudgetHiddenInput();
          const totalEl = document.getElementById("budgetTotal");
          if(totalEl) totalEl.textContent = formatMoney(budget.total);
        };
      });

      document.querySelectorAll("[data-del]").forEach(btn => {
        btn.onclick = () => {
          const i = Number(btn.getAttribute("data-del"));
          state.data.budget.rows.splice(i,1);
          const minRows = Math.max(1, Number(opt.min_rows || 1));
          while(state.data.budget.rows.length < minRows) state.data.budget.rows.push({});
          renderBudget(state, opt, onBack, onNext);
        };
      });

      const nextBtn = document.getElementById("btnNextB");
      if(nextBtn){
        nextBtn.onclick = () => {
          const budget = applyBudgetToFormData();
          if(validateBudgetWithOptions(budget.rows, opt)){
            onNext?.();
          } else {
            alert(state.lastBudgetError || "ბიუჯეტის შეცდომა");
          }
        };
      }
    }

    function renderBudget(state, opt, onBack, onNext){
      const wrap = document.getElementById("budgetStep");
      if(!wrap) return;
      const minRows = Math.max(1, Number(opt.min_rows || 1));
      state.data.budget.rows = Array.isArray(state.data.budget.rows) ? state.data.budget.rows : [];
      while(state.data.budget.rows.length < minRows) state.data.budget.rows.push({});
      wrap.innerHTML = viewBudget(state, opt);
      bindBudget(state, opt, onBack, onNext);
      syncBudgetHiddenInput();
    }

    function viewActionPlan(state, opt){
      const rows = state.data.actionPlan.rows || [];
      const cols = opt.columns || [];
      const autoNumber = !!opt.auto_number;

      return `
        <h2 class="sectionTitle">სამოქმედო გეგმა</h2>
        <div class="muted">დაამატეთ აქტივობები. საჭიროების შემთხვევაში შეგიძლიათ ახალი რიგების დამატება.</div>

        <div class="row" style="margin-top:10px;gap:10px">
          <div class="pill info">ჩანაწერები: <b id="actionPlanCount">${rows.length}</b></div>
          <div class="pill">მინიმუმი: <b>${Math.max(1, Number(opt.min_rows || 1))}</b></div>
        </div>

        <div style="overflow:auto;margin-top:10px">
          <table>
            <thead>
              <tr>
                ${autoNumber ? `<th style="width:70px">N</th>` : ``}
                ${cols.map(c => `<th>${esc(c.label || c.key)}</th>`).join("")}
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              ${rows.map((r,i) => `
                <tr data-aprow="${i}">
                  ${autoNumber ? `<td><b>${i + 1}</b></td>` : ``}
                  ${cols.map(c => {
                    const k = c.key;
                    const t = String(c.type || "text").toLowerCase();
                    const ph = c.placeholder ? `placeholder="${escAttr(c.placeholder)}"` : "";
                    const val = escAttr(r?.[k] ?? "");
                    if(t === "textarea"){
                      return `<td><textarea data-aprow="${i}" data-apkey="${escAttr(k)}" data-k="${escAttr(k)}" ${ph} style="min-height:48px">${esc(r?.[k] ?? "")}</textarea></td>`;
                    }
                    const inputType = (t === "date") ? "date" : "text";
                    return `<td><input data-aprow="${i}" data-apkey="${escAttr(k)}" data-k="${escAttr(k)}" type="${inputType}" ${ph} value="${val}"></td>`;
                  }).join("")}
                  <td><button class="btn btn-bad" data-apdel="${i}" type="button">X</button></td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>

        <div class="row sp" style="margin-top:10px">
          <button class="btn btn-ghost" id="btnAddActionRow" type="button">+ ჩანაწერის დამატება</button>
        </div>

        <div class="row" style="margin-top:14px;justify-content:space-between">
          <button class="btn btn-ghost" id="btnBackAP" type="button">უკან</button>
          <button class="btn btn-ac" id="btnNextAP" type="button">შემდეგი</button>
        </div>
      `;
    }

    function bindActionPlan(state, opt, onBack, onNext){
      const backBtn = document.getElementById("btnBackAP");
      if(backBtn) backBtn.onclick = () => onBack?.();

      const addBtn = document.getElementById("btnAddActionRow");
      if(addBtn){
        addBtn.onclick = () => {
          state.data.actionPlan.rows.push({});
          renderActionPlan(state, opt, onBack, onNext);
        };
      }

      document.querySelectorAll("[data-aprow][data-apkey]").forEach(inp => {
        inp.oninput = () => {
          const i = Number(inp.getAttribute("data-aprow"));
          const k = inp.getAttribute("data-apkey");
          state.data.actionPlan.rows[i] = state.data.actionPlan.rows[i] || {};
          state.data.actionPlan.rows[i][k] = inp.value;
          const plan = syncActionPlanHiddenInput(opt);
          const countEl = document.getElementById("actionPlanCount");
          if(countEl) countEl.textContent = String(plan.total_rows);
        };
      });

      document.querySelectorAll("[data-apdel]").forEach(btn => {
        btn.onclick = () => {
          const i = Number(btn.getAttribute("data-apdel"));
          state.data.actionPlan.rows.splice(i, 1);
          const minRows = Math.max(1, Number(opt.min_rows || 1));
          while(state.data.actionPlan.rows.length < minRows) state.data.actionPlan.rows.push({});
          renderActionPlan(state, opt, onBack, onNext);
        };
      });

      const nextBtn = document.getElementById("btnNextAP");
      if(nextBtn){
        nextBtn.onclick = () => {
          const plan = applyActionPlanToFormData();
          if(validateActionPlanWithOptions(plan.rows, opt)){
            onNext?.();
          } else {
            alert(state.lastActionPlanError || "სამოქმედო გეგმის შეცდომა");
          }
        };
      }
    }

    function renderActionPlan(state, opt, onBack, onNext){
      const wrap = document.getElementById("actionPlanStep");
      if(!wrap) return;
      const minRows = Math.max(1, Number(opt.min_rows || 1));
      state.data.actionPlan.rows = Array.isArray(state.data.actionPlan.rows) ? state.data.actionPlan.rows : [];
      while(state.data.actionPlan.rows.length < minRows) state.data.actionPlan.rows.push({});
      wrap.innerHTML = viewActionPlan(state, opt);
      bindActionPlan(state, opt, onBack, onNext);
      syncActionPlanHiddenInput(opt);
    }

    const MAX_FILE_MB = 25;

    function validateClientFile(f){
      if(!f) return true;
      if(bytesToMB(f.size) > MAX_FILE_MB){
        alert(`ფაილი ძალიან დიდია: ${f.name}\nმაქს: ${MAX_FILE_MB}MB`);
        return false;
      }
      return true;
    }

    function renderFilesStep(){
      const reqs = DATA.requirements || [];
      const required = reqs.filter(r => Number(r.is_required || 0) === 1);

      const reqHtml = reqs.map(r => {
        const rid = String(r.id);
        const must = Number(r.is_required || 0) === 1;
        const f = state.reqUploads[rid];
        return `
          <div class="card" style="margin-top:10px;background:#0a0f1c;box-shadow:none">
            <div class="row sp">
              <div>
                <b>${esc(String(r.name || ""))}</b>
                <div class="small">${must ? "სავალდებულო" : "არასავალდებულო"}</div>
              </div>
              <span class="pill ${f ? "open":"closed"}">${f ? "არჩეულია":"არ არის"}</span>
            </div>

            <div class="field" style="margin-top:10px">
              <label>ატვირთვა ${must ? "*" : ""}</label>
              <input type="file" data-req-file="${escAttr(rid)}">
              ${f ? `<div class="small" style="margin-top:6px">ფაილი: <b>${esc(f.name)}</b> • ${bytesToMB(f.size)}MB</div>` : ``}
            </div>
          </div>
        `;
      }).join("") || `<div class="notice" style="margin-top:10px">მოთხოვნილი ფაილები არ არის განსაზღვრული.</div>`;

      const otherList = state.otherUploads.map((f,i) => `
        <tr>
          <td><b>${esc(f.name)}</b><div class="small">${bytesToMB(f.size)} MB</div></td>
          <td>${esc(f.type || "-")}</td>
          <td><button class="btn btn-bad" type="button" data-rm-other="${i}">Remove</button></td>
        </tr>
      `).join("") || `<tr><td colspan="3" class="muted">ჯერ ფაილი არ არის დამატებული</td></tr>`;

      return `
        <h3 style="margin:0 0 8px 0">ფაილები</h3>
        <div class="muted">ატვირთეთ მოთხოვნილი დოკუმენტები. რეკომენდირებული ზომა: 25MB-მდე.</div>

        <div class="card" style="margin-top:12px;box-shadow:none">
          <b>მოთხოვნილი ფაილები</b>
          <div class="small" style="margin-top:6px">სავალდებულო: ${required.length} ც.</div>
          ${reqHtml}
        </div>

        <div class="card" style="margin-top:12px;box-shadow:none">
          <b>სხვა ფაილები (optional)</b>

          <div class="field" style="margin-top:10px">
            <label>ფაილის დამატება</label>
            <input id="otherFiles" type="file" multiple>
            <div class="small" style="margin-top:6px">შეგიძლია დამატებით ატვირთო სხვა დოკუმენტებიც.</div>
          </div>

          <div class="tableScroll">
            <table>
              <thead><tr><th>ფაილი</th><th>ტიპი</th><th></th></tr></thead>
              <tbody>${otherList}</tbody>
            </table>
          </div>

          <div class="pill" style="margin-top:10px">
            არჩეული მოთხოვნები: ${Object.keys(state.reqUploads).length} • სხვა: ${state.otherUploads.length}
          </div>
        </div>
      `;
    }

    function validateRequirements(){
      const reqs = DATA.requirements || [];
      const required = reqs.filter(r => Number(r.is_required || 0) === 1);
      return required.every(r => !!state.reqUploads[String(r.id)]);
    }

    function validateField(f, budgetOpt, actionPlanOpt){
      const k = "field_" + f.id;
      const req = f.is_required === 1;
      const showFor = f.show_for || "all";

      if(showFor === "person" && state.applicantType !== "person") return true;
      if(showFor === "org" && state.applicantType !== "org") return true;
      if(!req) return true;

      if(f.type === "budget_table"){
        const budget = syncBudgetHiddenInput();
        return validateBudgetWithOptions(budget.rows, budgetOpt || budgetDefaultOptions());
      }

      if(f.type === "action_plan_table"){
        const plan = syncActionPlanHiddenInput(actionPlanOpt || actionPlanDefaultOptions());
        return validateActionPlanWithOptions(plan.rows, actionPlanOpt || actionPlanDefaultOptions());
      }

      if(f.type === "checkbox"){
        const v = state.form_data[k];
        return Array.isArray(v) ? v.length > 0 : !!String(v || "").trim();
      }

      if(f.type === "budget_table"){
        return `
          <div class="field">
            <div class="notice">ბიუჯეტის ცხრილი გამოჩნდება ამ ნაბიჯის სრულ ეკრანზე.</div>
          </div>
        `;
      }

      if(f.type === "action_plan_table"){
        return `
          <div class="field">
            <div class="notice">სამოქმედო გეგმის ცხრილი გამოჩნდება ამ ნაბიჯის სრულ ეკრანზე.</div>
          </div>
        `;
      }

      if(f.type === "file"){
        return !!state.fieldUploads[String(f.id)];
      }

      const v = state.form_data[k];
      return !!String(v ?? "").trim();
    }

    function validateStep(activeDbStep){
      const stepId = Number(activeDbStep?.id || 0);
      const fields = stepId ? getFieldsForStep(stepId) : [];

      const tf = findApplicantTypeField();
      if(tf){
        const kk = "field_" + tf.id;
        const t = normalizeApplicantType(state.form_data[kk]);
        if(t) state.applicantType = t;
      }

      const budgetField = stepId ? findBudgetTableFieldForStep(stepId) : null;
      const budgetOpt = budgetField ? readBudgetOptionsFromField(budgetField) : budgetDefaultOptions();
      const actionPlanField = stepId ? findActionPlanTableFieldForStep(stepId) : null;
      const actionPlanOpt = actionPlanField ? readActionPlanOptionsFromField(actionPlanField) : actionPlanDefaultOptions();

      if(isBudgetStep(activeDbStep)){
        const budget = applyBudgetToFormData();
        if(!validateBudgetWithOptions(budget.rows, budgetOpt)) return false;
      }

      if(isActionPlanStep(activeDbStep)){
        const plan = applyActionPlanToFormData();
        if(!validateActionPlanWithOptions(plan.rows, actionPlanOpt)) return false;
      }

      if(isFilesStep(activeDbStep, state.currentKey)){
        if(!validateRequirements()) return false;
      }

      for(const f of fields){
        if(!validateField(f, budgetOpt, actionPlanOpt)) return false;
      }
      return true;
    }

    function missingFieldsForStep(activeDbStep){
      const stepId = Number(activeDbStep?.id || 0);
      const fields = stepId ? getFieldsForStep(stepId) : [];
      const miss = [];

      const budgetField = stepId ? findBudgetTableFieldForStep(stepId) : null;
      const budgetOpt = budgetField ? readBudgetOptionsFromField(budgetField) : budgetDefaultOptions();
      const actionPlanField = stepId ? findActionPlanTableFieldForStep(stepId) : null;
      const actionPlanOpt = actionPlanField ? readActionPlanOptionsFromField(actionPlanField) : actionPlanDefaultOptions();

      if(isBudgetStep(activeDbStep)){
        const budget = applyBudgetToFormData();
        if(!validateBudgetWithOptions(budget.rows, budgetOpt)){
          miss.push(state.lastBudgetError || "ხარჯების ცხრილი (ბიუჯეტი)");
        }
      }

      if(isActionPlanStep(activeDbStep)){
        const plan = applyActionPlanToFormData();
        if(!validateActionPlanWithOptions(plan.rows, actionPlanOpt)){
          miss.push(state.lastActionPlanError || "პროექტის სამოქმედო გეგმა");
        }
      }

      if(isFilesStep(activeDbStep, state.currentKey) && !validateRequirements()){
        miss.push("სავალდებულო ფაილები");
      }

      for(const f of fields){
        const showFor = f.show_for || "all";
        if(showFor === "person" && state.applicantType !== "person") continue;
        if(showFor === "org" && state.applicantType !== "org") continue;
        if(f.is_required !== 1) continue;
        if(f.type === "budget_table" || f.type === "action_plan_table") continue;

        if(f.type === "file"){
          if(!state.fieldUploads[String(f.id)]) miss.push(f.label);
          continue;
        }

        if(f.type === "checkbox"){
          const v = state.form_data["field_" + f.id];
          const ok = Array.isArray(v) ? v.length > 0 : !!String(v || "").trim();
          if(!ok) miss.push(f.label);
          continue;
        }

        const v = state.form_data["field_" + f.id];
        if(!String(v ?? "").trim()) miss.push(f.label);
      }

      return Array.from(new Set(miss.map(x => String(x || "").trim()).filter(Boolean)));
    }

    function blockIfMissing(activeDbStep){
      const miss = missingFieldsForStep(activeDbStep);
      if(!miss.length) return true;
      alert("აკლია / შეცდომაა:\n- " + miss.join("\n- "));
      return false;
    }

    function renderSubmit(){
      const dbSteps = (DATA.steps || []).filter(s => Number(s.id || 0) > 0);
      let allOk = true;

      for(const s of dbSteps){
        const stepId = Number(s.id || 0);
        const fields = getFieldsForStep(stepId);
        const budField = findBudgetTableFieldForStep(stepId);
        const budOpt = budField ? readBudgetOptionsFromField(budField) : budgetDefaultOptions();
        const apField = findActionPlanTableFieldForStep(stepId);
        const apOpt = apField ? readActionPlanOptionsFromField(apField) : actionPlanDefaultOptions();

        if(isBudgetStep(s)){
          const budget = applyBudgetToFormData();
          if(!validateBudgetWithOptions(budget.rows, budOpt)){ allOk = false; break; }
        }

        if(isActionPlanStep(s)){
          const plan = applyActionPlanToFormData();
          if(!validateActionPlanWithOptions(plan.rows, apOpt)){ allOk = false; break; }
        }

        for(const f of fields){
          if(!validateField(f, budOpt, apOpt)) { allOk = false; break; }
        }
        if(!allOk) break;
      }

      if(!validateRequirements()) allOk = false;

      return `
        <h2 class="sectionTitle">გაგზავნა</h2>
        <div class="muted">სისტემა გადაამოწმებს სავალდებულო ველებს და შემდეგ გაგზავნის განაცხადს.</div>

        <div style="margin-top:12px">
          ${allOk
            ? `<div class="okbox">ყველა სავალდებულო ნაწილი შევსებულია ✅</div>`
            : `<div class="err">განაცხადი არ არის მზად. შეამოწმე სავალდებულო ნაბიჯები.</div>`
          }
        </div>

        <div class="row sp" style="margin-top:14px">
          <button class="btn btn-ghost" type="button" id="btnBackS">უკან</button>
          <button class="btn btn-ok" type="button" id="btnSubmit" ${allOk ? "" : "disabled"}>განაცხადის გაგზავნა</button>
        </div>

        <div id="finalBox" style="margin-top:12px"></div>
      `;
    }

    function pickApplicantMeta(){
      const all = getAllFieldsFlat();
      let name = "", email = "", phone = "";

      for(const f of all){
        const label = String(f.label || "").trim().toLowerCase();
        const normalizedLabel = label
          .replace(/\./g, "")
          .replace(/-/g, "")
          .replace(/\s+/g, "");

        const k = "field_" + f.id;
        const v = state.form_data[k];
        const val = (v == null) ? "" : String(v).trim();
        if(!val) continue;

        if(
          !name &&
          (
            label.includes("სახელი") ||
            normalizedLabel.includes("fullname") ||
            normalizedLabel.includes("contactname")
          )
        ){
          name = val;
        }

        if(
          !email &&
          (
            label.includes("ელ.ფოსტ") ||
            label.includes("ელ-ფოსტ") ||
            label.includes("ელ ფოსტ") ||
            normalizedLabel.includes("ელფოსტ") ||
            label.includes("იმეილ") ||
            normalizedLabel.includes("email") ||
            normalizedLabel.includes("mail")
          )
        ){
          email = val;
        }

        if(
          !phone &&
          (
            label.includes("ტელ") ||
            normalizedLabel.includes("phone") ||
            normalizedLabel.includes("mobile") ||
            normalizedLabel.includes("mob")
          )
        ){
          phone = val;
        }
      }

      return { name, email, phone };
    }

    function buildSubmissionMeta(applicant){
      const fields = getAllFieldsFlat();
      const reqs = DATA.requirements || [];
      const fieldMap = new Map(fields.map(f => [String(f.id), f]));
      const reqMap = new Map(reqs.map(r => [String(r.id), r]));

      const reqFiles = Object.keys(state.reqUploads).map(reqId => {
        const f = state.reqUploads[reqId];
        if (!f) return null;
        const req = reqMap.get(String(reqId));
        return {
          requirement_id: String(reqId),
          requirement_name: req ? String(req.name || '') : '',
          is_required: req ? Number(req.is_required || 0) === 1 : false,
          original_name: f.name,
          size_bytes: f.size,
          mime_type: f.type || ''
        };
      }).filter(Boolean);

      const fieldFiles = Object.keys(state.fieldUploads).map(fieldId => {
        const f = state.fieldUploads[fieldId];
        if (!f) return null;
        const field = fieldMap.get(String(fieldId));
        return {
          field_id: String(fieldId),
          field_label: field ? String(field.label || '') : '',
          original_name: f.name,
          size_bytes: f.size,
          mime_type: f.type || ''
        };
      }).filter(Boolean);

      const otherFiles = state.otherUploads.map(f => ({
        original_name: f.name,
        size_bytes: f.size,
        mime_type: f.type || ''
      }));

      const fieldLabels = {};
      const fieldTypes = {};
      fields.forEach(f => {
        const key = "field_" + f.id;
        fieldLabels[key] = String(f.label || '');
        fieldTypes[key] = String(f.type || 'text').toLowerCase();
      });

      return {
        submitted_at: new Date().toISOString(),
        grant_id: String(state.grant_id),
        grant_title: String(DATA.grant?.title || ''),
        applicant_type: state.applicantType,
        applicant_name: applicant.name || '',
        applicant_email: applicant.email || '',
        applicant_phone: applicant.phone || '',
        field_labels: fieldLabels,
        field_types: fieldTypes,
        requirements: reqs.map(r => ({
          id: String(r.id),
          name: String(r.name || ''),
          is_required: Number(r.is_required || 0) === 1
        })),
        files: {
          requirements: reqFiles,
          fields: fieldFiles,
          other: otherFiles,
          total_count: reqFiles.length + fieldFiles.length + otherFiles.length
        }
      };
    }

    function bindSubmit(){
      const back = document.getElementById("btnBackS");
      if(back){
        back.addEventListener("click", () => {
          const idx = steps.findIndex(s => s.key === state.currentKey);
          state.currentKey = steps[Math.max(0, idx - 1)].key;
          renderStep();
        });
      }

      const btn = document.getElementById("btnSubmit");
      if(!btn) return;

      btn.addEventListener("click", async () => {
        try{
          btn.disabled = true;
          syncVisibleDomToState();

          const anyBudgetField = findAnyBudgetTableField();
          const budgetOpt = anyBudgetField ? readBudgetOptionsFromField(anyBudgetField) : budgetDefaultOptions();
          if(anyBudgetField && !validateBudgetWithOptions(state.data.budget.rows, budgetOpt)){
            alert(state.lastBudgetError || "ბიუჯეტის შეცდომა");
            btn.disabled = false;
            return;
          }

          const anyActionPlanField = findAnyActionPlanTableField();
          const actionPlanOpt = anyActionPlanField ? readActionPlanOptionsFromField(anyActionPlanField) : actionPlanDefaultOptions();
          if(anyActionPlanField && !validateActionPlanWithOptions(state.data.actionPlan.rows, actionPlanOpt)){
            alert(state.lastActionPlanError || "სამოქმედო გეგმის შეცდომა");
            btn.disabled = false;
            return;
          }

          const tf = findApplicantTypeField();
          if(tf){
            const k = "field_" + tf.id;
            const t = normalizeApplicantType(state.form_data[k]);
            if(t) state.applicantType = t;
          }

          const all = getAllFieldsFlat();
          const budgetFields = all.filter(f => f.type === "budget_table");
          if(budgetFields.length){
            for(const bf of budgetFields){
              const k = "field_" + bf.id;
              state.form_data[k] = budgetPayloadForField(bf);
            }
          }

          const actionPlanFields = all.filter(f => f.type === "action_plan_table");
          if(actionPlanFields.length){
            for(const af of actionPlanFields){
              const k = "field_" + af.id;
              state.form_data[k] = actionPlanPayloadForField(af);
            }
          }

          const fd = new FormData();
          fd.append("csrf", DATA.csrf);
          fd.append("grant_id", String(state.grant_id));
          fd.append("applicant_type", state.applicantType);

          const budgetHidden = document.getElementById("budget_json");
          if(budgetHidden && budgetHidden.value){
            fd.append("budget_json", budgetHidden.value);
          }

          const actionPlanHidden = document.getElementById("action_plan_json");
          if(actionPlanHidden && actionPlanHidden.value){
            fd.append("action_plan_json", actionPlanHidden.value);
          }

          const meta = pickApplicantMeta();
          state.form_data.__meta = buildSubmissionMeta(meta);
          fd.append("form_data", JSON.stringify(state.form_data));

          if(meta.name)  fd.append("applicant_name", meta.name);
          if(meta.email) fd.append("email", meta.email);
          if(meta.phone) fd.append("phone", meta.phone);

          Object.keys(state.reqUploads).forEach(reqId => {
            const f = state.reqUploads[reqId];
            if(f) fd.append("req_file[" + reqId + "]", f);
          });

          state.otherUploads.forEach(f => {
            if(f) fd.append("other_files[]", f);
          });

          Object.keys(state.fieldUploads).forEach(fieldId => {
            const f = state.fieldUploads[fieldId];
            if(f) fd.append("field_file[" + fieldId + "]", f);
          });

          const res = await fetch(API + "?action=submit", { method: "POST", body: fd });
          const j = await res.json().catch(() => null);
          if(!res.ok || !j || !j.ok){
            throw new Error(j?.error || ("API შეცდომა: " + res.status));
          }

          const appId = (j && (j.id ?? j.app_id ?? j.application_id)) ?? "";

          document.getElementById("finalBox").innerHTML = `
            <div class="okbox">განაცხადი მიღებულია ✅</div>
            <div class="card" style="margin-top:12px;box-shadow:none">
              <b>Application ID:</b>
              <span style="color:#86efac;font-weight:1000">${esc(String(appId))}</span>
              <div class="muted" style="margin-top:6px">შეინახეთ ეს ნომერი შემდგომი კომუნიკაციისთვის.</div>
            </div>
          `;
        }catch(e){
          alert(e.message || "შეცდომა");
          btn.disabled = false;
        }
      });
    }

    function renderFieldInput(f){
      const fieldKey = "field_" + f.id;
      const isReq = f.is_required === 1;
      const showFor = f.show_for || "all";

      if(showFor === "person" && state.applicantType !== "person") return "";
      if(showFor === "org" && state.applicantType !== "org") return "";

      const label = `${esc(f.label)}${isReq ? " *" : ""}`;
      const v = state.form_data[fieldKey];

      if(f.type === "textarea"){
        return `
          <div class="field">
            <label>${label}</label>
            <textarea data-field="${fieldKey}">${esc(String(v ?? ""))}</textarea>
          </div>
        `;
      }

      if(f.type === "select" || f.type === "radio" || f.type === "checkbox"){
        const optsRaw = parseJsonMaybe(f.options_json) || [];
        const arr = Array.isArray(optsRaw) ? optsRaw.map(normalizeOptionItem) : [];
        const cur = (v == null) ? "" : String(v);

        if(f.type === "select"){
          const optionsHtml = arr.map(o => {
            return `<option value="${esc(o.value)}" ${cur === o.value ? "selected" : ""}>${esc(o.label)}</option>`;
          }).join("");

          return `
            <div class="field">
              <label>${label}</label>
              <select data-field="${fieldKey}">
                <option value="">—</option>
                ${optionsHtml}
              </select>
            </div>
          `;
        }

        const name = "n_" + fieldKey;
        const curSet = new Set(Array.isArray(v) ? v.map(String) : [cur].filter(Boolean));

        return `
          <div class="field">
            <label>${label}</label>
            <div class="card" style="background:#0a0f1c;box-shadow:none">
              ${arr.map(o => {
                const checked = curSet.has(o.value) ? "checked" : "";
                const t = f.type === "radio" ? "radio" : "checkbox";
                return `
                  <div class="row" style="gap:8px;align-items:center;margin-top:8px">
                    <input style="width:auto" type="${t}" name="${esc(name)}" value="${esc(o.value)}" ${checked} data-group="${fieldKey}">
                    <div style="font-weight:900">${esc(o.label)}</div>
                  </div>
                `;
              }).join("")}
            </div>
          </div>
        `;
      }

      if(f.type === "file"){
        const picked = state.fieldUploads[String(f.id)] ? state.fieldUploads[String(f.id)].name : "";
        return `
          <div class="field">
            <label>${label}</label>
            <input type="file" data-file-field="${f.id}">
            ${picked ? `<div class="small" style="margin-top:6px">არჩეულია: <b>${esc(picked)}</b></div>` : ``}
          </div>
        `;
      }

      const typeMap = new Set(["text","number","email","phone","date"]);
      const t = typeMap.has(f.type) ? f.type : "text";
      const inputType =
        (t === "phone") ? "text" :
        (t === "date") ? "date" :
        (t === "email") ? "email" :
        (t === "number") ? "number" : "text";

      const val = (v == null) ? "" : String(v);

      return `
        <div class="field">
          <label>${label}</label>
          <input type="${esc(inputType)}" data-field="${fieldKey}" value="${esc(val)}">
        </div>
      `;
    }

    function renderStep(){
      const key = state.currentKey;
      const idx = steps.findIndex(s => s.key === key);

      document.querySelectorAll(".stepItem").forEach((el, i) => {
        el.classList.remove("active","done","disabled");
        if(i < idx) el.classList.add("done");
        if(i === idx) el.classList.add("active");
        if(i > idx + 1) el.classList.add("disabled");
      });

      for(const s of steps){
        const sub = document.getElementById("sub_" + s.key);
        if(!sub) continue;
        sub.textContent = state.validByStep[s.key]
          ? "შევსებულია"
          : (s.key === "submit" ? "შემოწმება" : "შესავსებია");
      }

      const box = document.getElementById("stepContent");
      if(!box) return;

      if(key === "submit"){
        box.innerHTML = renderSubmit();
        bindSubmit();
        return;
      }

      const activeDbStep = resolveDbStepByKey(key);

      if(!activeDbStep){
        box.innerHTML = `
          <h2 class="sectionTitle">${esc(steps[idx]?.name || "ნაბიჯი")}</h2>
          <div class="notice">ბილდერში ნაბიჯები/ველები ჯერ არ არის აწყობილი.</div>
          <div class="row" style="margin-top:14px;justify-content:flex-end">
            <button class="btn btn-ac" type="button" onclick="state.currentKey='submit'; renderStep()">გაგრძელება</button>
          </div>
        `;
        return;
      }

      const stepId = Number(activeDbStep.id || 0);
      const fields = getFieldsForStep(stepId);

      const typeField = findApplicantTypeField();
      if(typeField){
        const tfKey = "field_" + typeField.id;
        const t = normalizeApplicantType(state.form_data[tfKey]);
        if(t) state.applicantType = t;
      }

      if(isBudgetStep(activeDbStep)){
        const budgetField = findBudgetTableFieldForStep(stepId) || findAnyBudgetTableField();
        const opt = budgetField ? readBudgetOptionsFromField(budgetField) : budgetDefaultOptions();

        let restoredRows = null;

        if(budgetField){
          const k = "field_" + budgetField.id;
          const cur = state.form_data[k];
          const curObj = (cur && typeof cur === "object") ? cur : parseJsonMaybe(cur);
          const rows = (curObj && Array.isArray(curObj.rows)) ? curObj.rows : null;
          if(rows && rows.length) restoredRows = rows;
        }

        if(!restoredRows){
          const topBudget = parseJsonMaybe(state.form_data.budget);
          const rows = (topBudget && Array.isArray(topBudget.rows)) ? topBudget.rows : null;
          if(rows && rows.length) restoredRows = rows;
        }

        if(restoredRows) state.data.budget.rows = restoredRows;

        box.innerHTML = `<div id="budgetStep"></div>`;

        renderBudget(
          state,
          opt,
          () => {
            state.currentKey = steps[Math.max(0, idx - 1)].key;
            renderStep();
          },
          () => {
            if(budgetField){
              const k = "field_" + budgetField.id;
              state.form_data[k] = budgetPayloadForField(budgetField);
            }
            state.validByStep[state.currentKey] = true;
            state.currentKey = steps[Math.min(steps.length - 1, idx + 1)].key;
            renderStep();
          }
        );
        return;
      }

      if(isActionPlanStep(activeDbStep)){
        const actionPlanField = findActionPlanTableFieldForStep(stepId) || findAnyActionPlanTableField();
        const opt = actionPlanField ? readActionPlanOptionsFromField(actionPlanField) : actionPlanDefaultOptions();

        let restoredRows = null;

        if(actionPlanField){
          const k = "field_" + actionPlanField.id;
          const cur = state.form_data[k];
          const curObj = (cur && typeof cur === "object") ? cur : parseJsonMaybe(cur);
          const rows = (curObj && Array.isArray(curObj.rows)) ? curObj.rows : null;
          if(rows && rows.length) restoredRows = rows;
        }

        if(!restoredRows){
          const topPlan = parseJsonMaybe(state.form_data.action_plan);
          const rows = (topPlan && Array.isArray(topPlan.rows)) ? topPlan.rows : null;
          if(rows && rows.length) restoredRows = rows;
        }

        if(restoredRows) state.data.actionPlan.rows = restoredRows;

        box.innerHTML = `<div id="actionPlanStep"></div>`;

        renderActionPlan(
          state,
          opt,
          () => {
            state.currentKey = steps[Math.max(0, idx - 1)].key;
            renderStep();
          },
          () => {
            if(actionPlanField){
              const k = "field_" + actionPlanField.id;
              state.form_data[k] = actionPlanPayloadForField(actionPlanField);
            }
            state.validByStep[state.currentKey] = true;
            state.currentKey = steps[Math.min(steps.length - 1, idx + 1)].key;
            renderStep();
          }
        );
        return;
      }

      const filesStep = isFilesStep(activeDbStep, key);
      const title = esc(activeDbStep.name || steps[idx]?.name || "ნაბიჯი");

      box.innerHTML = `
        <h2 class="sectionTitle">${title}</h2>
        <div class="pill" style="margin-bottom:8px">გთხოვ შეავსე სავალდებულო ველები</div>

        ${fields.map(renderFieldInput).join("") || `<div class="notice">ამ ნაბიჯში ველი ჯერ არ არის.</div>`}

        ${filesStep ? `<hr class="hr">` + renderFilesStep() : ""}

        <div class="row sp" style="margin-top:14px">
          <button class="btn btn-ghost" type="button" ${idx === 0 ? "disabled" : ""} id="btnBack">უკან</button>
          <button class="btn btn-ac" type="button" id="btnNext">შემდეგი</button>
        </div>
      `;

      bindFields(activeDbStep, fields, idx, filesStep);
    }

    function bindFields(activeDbStep, fields, idx, isFiles){
      document.querySelectorAll("[data-field]").forEach(el => {
        const k = el.getAttribute("data-field");

        const syncValue = () => {
          state.form_data[k] = el.value;
          const tf = findApplicantTypeField();
          if(tf && k === ("field_" + tf.id)){
            const t = normalizeApplicantType(el.value);
            state.applicantType = t || "person";
            renderStep();
          }
        };

        el.addEventListener("input", syncValue);
        el.addEventListener("change", syncValue);

        if(state.form_data[k] == null) state.form_data[k] = el.value ?? "";
      });

      document.querySelectorAll("[data-group]").forEach(el => {
        const k = el.getAttribute("data-group");

        const syncGroup = () => {
          const list = Array.from(document.querySelectorAll(`input[data-group="${k}"]`));
          const isRadio = list.some(x => x.type === "radio");
          if(isRadio){
            const chosen = list.find(x => x.checked);
            state.form_data[k] = chosen ? chosen.value : "";
          } else {
            state.form_data[k] = list.filter(x => x.checked).map(x => x.value);
          }
        };

        el.addEventListener("change", syncGroup);
        if(state.form_data[k] == null) syncGroup();
      });

      document.querySelectorAll("[data-file-field]").forEach(inp => {
        inp.addEventListener("change", () => {
          const fieldId = String(inp.getAttribute("data-file-field"));
          const f = inp.files && inp.files[0] ? inp.files[0] : null;
          if(f && validateClientFile(f)) state.fieldUploads[fieldId] = f;
          renderStep();
        });
      });

      if(isFiles){
        document.querySelectorAll("[data-req-file]").forEach(inp => {
          inp.addEventListener("change", () => {
            const reqId = String(inp.getAttribute("data-req-file"));
            const f = inp.files && inp.files[0] ? inp.files[0] : null;
            if(f && validateClientFile(f)) state.reqUploads[reqId] = f;
            renderStep();
          });
        });

        const other = document.getElementById("otherFiles");
        if(other){
          other.addEventListener("change", () => {
            const files = Array.from(other.files || []);
            const okFiles = files.filter(f => validateClientFile(f));
            state.otherUploads.push(...okFiles);
            other.value = "";
            renderStep();
          });
        }

        document.querySelectorAll("[data-rm-other]").forEach(btn => {
          btn.addEventListener("click", () => {
            const i = Number(btn.getAttribute("data-rm-other"));
            state.otherUploads.splice(i, 1);
            renderStep();
          });
        });
      }

      const back = document.getElementById("btnBack");
      const next = document.getElementById("btnNext");

      if(back){
        back.addEventListener("click", () => {
          state.currentKey = steps[Math.max(0, idx - 1)].key;
          renderStep();
        });
      }

      if(next){
        next.addEventListener("click", () => {
          syncVisibleDomToState();
          const ok = validateStep(activeDbStep);
          state.validByStep[state.currentKey] = ok;

          if(!ok){
            blockIfMissing(activeDbStep);
            renderStep();
            return;
          }

          state.currentKey = steps[Math.min(steps.length - 1, idx + 1)].key;
          renderStep();
        });
      }
    }

    (function init(){
      if(!DATA.isOpen) return;

      const list = document.getElementById("stepsList");
      list.innerHTML = steps.map((s,i) => `
        <div class="stepItem" data-step="${esc(s.key)}">
          <div class="dot">${i + 1}</div>
          <div>
            <div style="font-weight:1000">${esc(s.name)}</div>
            <div class="small" id="sub_${esc(s.key)}">შესავსებია</div>
          </div>
        </div>
      `).join("");

      list.querySelectorAll(".stepItem").forEach(el => {
        el.addEventListener("click", () => {
          const key = el.getAttribute("data-step");
          const idx = steps.findIndex(x => x.key === key);
          const cur = steps.findIndex(x => x.key === state.currentKey);

          if(idx <= cur){
            state.currentKey = key;
            renderStep();
            return;
          }

          if(idx === cur + 1){
            const activeDbStep = resolveDbStepByKey(state.currentKey);
            if(activeDbStep){
              const ok = validateStep(activeDbStep);
              state.validByStep[state.currentKey] = ok;
              if(!ok){
                blockIfMissing(activeDbStep);
                renderStep();
                return;
              }
            }
            state.currentKey = key;
            renderStep();
          }
        });
      });

      renderStep();
    })();
  </script>

  <script>
    async function inject(id, file) {
      const el = document.getElementById(id);
      if (!el) return;
      const res = await fetch(file + (file.includes('?') ? '&' : '?') + 'v=2');
      if (!res.ok) return;
      el.innerHTML = await res.text();
    }

    async function loadScript(src) {
      return new Promise((resolve) => {
        const s = document.createElement('script');
        s.src = src + (src.includes('?') ? '&' : '?') + 'v=2';
        s.onload = resolve;
        s.onerror = resolve;
        document.body.appendChild(s);
      });
    }

    (async () => {
      await inject('siteHeaderMount', '/header.php');
      await loadScript('/app.js');
      if (typeof window.initHeader === 'function') window.initHeader();
      await inject('siteFooterMount', '/footer.php');
    })();
  </script>
</body>
</html>
