<?php
/**
 * ==========================================================
 * FILE: grants/grants_apply.php   (USER SIDE)
 * ==========================================================
 * - Dynamic multi-step application form from DB
 * - Submits to: ../admin/api/grants_portal_api.php?action=submit (multipart/form-data)
 *
 * ✅ FIXED: Requirements loaded from grant_file_requirements (admin compatible)
 * ✅ Uses is_enabled column if exists (safe check)
 * ✅ Budget step detection: based on budget_table field (not only step_key)
 * ✅ Budget options_json support: currency/min_rows/columns/max_total
 * ✅ Max budget validation: shows "მაქსიმალური ბიუჯეტი არის X"
 * ✅ Prevent step jump / next if missing required; shows missing list
 * ✅ submit fills ALL budget_table fields
 * ✅ sends req_file + other_files + field_file correctly
 */

declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$pdo = db();

/* ---------- helpers ---------- */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

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

/**
 * safe column existence check (no placeholders for identifiers)
 */
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

/* ---------- input ---------- */
$grantId = (int)($_GET['id'] ?? 0);
if ($grantId <= 0) { http_response_code(404); echo "Grant not found"; exit; }

/* ---------- load grant (with optional max_budget if exists) ---------- */
$hasMaxBudget = has_col($pdo, 'grants', 'max_budget');
$hasTitleEn = has_col($pdo, 'grants', 'title_en');
$hasDescEn = has_col($pdo, 'grants', 'description_en');
$hasBodyEn = has_col($pdo, 'grants', 'body_en');

$sql = "SELECT id,title," . ($hasTitleEn ? "title_en" : "'' AS title_en") . ",
        slug,description," . ($hasDescEn ? "description_en" : "'' AS description_en") . ",
        body," . ($hasBodyEn ? "body_en" : "'' AS body_en") . ",
        deadline,status,is_active,image_path,apply_url";
if ($hasMaxBudget) $sql .= ", max_budget";
$sql .= " FROM grants WHERE id=? LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([$grantId]);
$grant = $st->fetch(PDO::FETCH_ASSOC);
if (!$grant) { http_response_code(404); echo "Grant not found"; exit; }

/* ---------- load builder: steps, fields ---------- */
$steps = [];
$fieldsByStep = [];

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
  $stepIds = array_map(fn($r)=> (int)$r['id'], $steps);
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
  }
}

/* ---------- load requirements (FIXED TABLE) ---------- */
$reqs = [];
$reqTable = 'grant_file_requirements';
$reqHasEnabled = has_col($pdo, $reqTable, 'is_enabled');

$reqSql = "SELECT id,grant_id,name,is_required FROM {$reqTable} WHERE grant_id=? ";
if ($reqHasEnabled) $reqSql .= "AND is_enabled=1 ";
$reqSql .= "ORDER BY id ASC";

$st = $pdo->prepare($reqSql);
$st->execute([$grantId]);
$reqs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ---------- fallback if builder empty ---------- */
if (!$steps) {
  $steps = [
    ['id'=>0,'step_key'=>'applicant','name'=>'განმცხადებელი','sort_order'=>0,'is_enabled'=>1],
    ['id'=>0,'step_key'=>'project','name'=>'პროექტი','sort_order'=>1,'is_enabled'=>1],
    ['id'=>0,'step_key'=>'budget','name'=>'ბიუჯეტი','sort_order'=>2,'is_enabled'=>1],
    ['id'=>0,'step_key'=>'files','name'=>'ფაილები','sort_order'=>3,'is_enabled'=>1],
  ];
}

$open = is_open_grant($grant);

/* ---------- payload to JS ---------- */
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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h((string)$grant['title']) ?> • Grant Portal</title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/youthagency/assets.css?v=1">

  <style>
    :root{
      --bg:#0b1220; --panel:#0f172a; --card:#111827; --line:#22314a;
      --text:#e5e7eb; --muted:#94a3b8; --accent:#2563eb; --ok:#16a34a;
      --warn:#f59e0b; --bad:#dc2626; --radius:14px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:'Noto Sans Georgian',system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--text)}
    a{color:inherit;text-decoration:none}
    .wrap{max-width:1160px;margin:0 auto;padding:18px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:14px}
    .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:900;cursor:pointer}
    .btn-ac{background:var(--accent);color:#fff}
    .btn-ok{background:var(--ok);color:#fff}
    .btn-ghost{background:#0a0f1c;color:#fff;border:1px solid var(--line)}
    .btn-bad{background:var(--bad);color:#fff}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .pill{display:inline-flex;gap:8px;align-items:center;padding:6px 10px;border-radius:999px;border:1px solid var(--line);
      background:#0a0f1c;font-weight:900;font-size:12px;color:#cbd5e1}
    .pill.open{border-color:rgba(22,163,74,.35);color:#86efac}
    .pill.closed{border-color:rgba(245,158,11,.35);color:#fde68a}

    .banner{
      border:1px solid var(--line);
      border-radius:18px;
      overflow:hidden;
      background:
        radial-gradient(1200px 500px at 20% 0%, rgba(37,99,235,.35), transparent 55%),
        radial-gradient(900px 500px at 70% 30%, rgba(245,158,11,.20), transparent 55%),
        linear-gradient(135deg, #0a0f1c 0%, #0f172a 60%, #0a0f1c 100%);
      padding:22px;
      display:grid;
      grid-template-columns: 1.2fr .8fr;
      gap:18px;
      align-items:center;
    }
    .banner h1{margin:0 0 8px 0;font-size:26px}
    .banner p{margin:0 0 14px 0;color:var(--muted);font-weight:800;line-height:1.5}
    .banner .art{
      height:180px;border-radius:16px;border:1px solid var(--line);
      background:
        radial-gradient(160px 120px at 30% 40%, rgba(37,99,235,.55), transparent 70%),
        radial-gradient(180px 120px at 70% 60%, rgba(16,185,129,.35), transparent 70%),
        radial-gradient(220px 140px at 60% 20%, rgba(245,158,11,.35), transparent 70%),
        linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    }
    @media(max-width:900px){ .banner{grid-template-columns:1fr} .banner .art{height:140px} }

    .portal{display:grid; grid-template-columns:260px 1fr; gap:14px; margin-top:14px}
    @media(max-width:960px){ .portal{grid-template-columns:1fr} }
    .steps{position:sticky;top:14px;align-self:start}
    @media(max-width:960px){ .steps{position:relative;top:0} }

    .stepItem{
      display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:14px;
      border:1px solid var(--line); background:#0a0f1c; margin-top:10px;
      cursor:pointer;
    }
    .dot{width:26px;height:26px;border-radius:999px;border:1px solid var(--line);
      display:flex;align-items:center;justify-content:center;font-weight:1000}
    .stepItem.done{border-color:rgba(22,163,74,.35)}
    .stepItem.active{border-color:rgba(37,99,235,.45); box-shadow:0 0 0 2px rgba(37,99,235,.15) inset}
    .stepItem.disabled{opacity:.6; cursor:not-allowed}

    input,select,textarea{
      width:100%;background:#0a0f1c;color:var(--text);border:1px solid var(--line);
      border-radius:12px;padding:10px 12px;outline:none;font-weight:900
    }
    textarea{min-height:110px;resize:vertical}
    label{display:block;color:#93c5fd;font-weight:1000;font-size:12px;margin-bottom:6px}
    .field{margin-top:10px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid var(--line);text-align:left;font-size:14px;vertical-align:top}
    th{color:#93c5fd}
    .notice{padding:12px;border-radius:14px;border:1px solid rgba(245,158,11,.35);background:rgba(245,158,11,.08);color:#fde68a;font-weight:900}
    .okbox{padding:12px;border-radius:14px;border:1px solid rgba(22,163,74,.35);background:rgba(22,163,74,.08);color:#86efac;font-weight:900}
    .err{padding:12px;border-radius:14px;border:1px solid rgba(220,38,38,.35);background:rgba(220,38,38,.08);color:#fecaca;font-weight:900}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .sp{justify-content:space-between}
    .tableScroll{overflow:auto;margin-top:10px}
    .muted{color:var(--muted);font-weight:900}
    .small{font-size:12px;color:var(--muted);font-weight:800}
    .hr{border:0;border-top:1px solid var(--line);margin:14px 0}
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
            <span data-i18n="<?= $open ? 'grantsApply.statusOpen' : 'grantsApply.statusClosed' ?>"><?= $open ? 'მიმდინარე' : 'დახურული' ?></span>
          </span>
          <span class="pill"><span data-i18n="grantsApply.deadlineLabel">ვადა:</span> <b><?= h((string)($grant['deadline'] ?: '—')) ?></b></span>
        </div>
      </div>
      <div class="art" aria-hidden="true"></div>
    </div>

    <?php if(!$open): ?>
      <div class="card" style="margin-top:14px">
        <div class="notice" data-i18n="grantsApply.closedNotice">ამ საგრანტო პროგრამაზე განაცხადების მიღება დასრულებულია ან გამორთულია.</div>
      </div>
    <?php else: ?>
      <div class="portal">
        <div class="card steps">
          <b data-i18n="grantsApply.stepsTitle">ნაბიჯები</b>
          <div class="pill" style="margin-top:8px" data-i18n="grantsApply.stepsHint">შეავსეთ ნაბიჯობრივად</div>
          <div id="stepsList"></div>
        </div>

        <div class="card" id="stepContent"></div>
      </div>
    <?php endif; ?>
  </div>

  <div id="siteFooterMount"></div>

<script>
const DATA = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
// grants/grants_apply.php -> ../admin/api/...
const API  = "../admin/api/grants_portal_api.php";

/* ========= Utils ========= */
function formatMoney(n){ n=Number(n||0); try{return n.toLocaleString("ka-GE");}catch(_){return String(n);} }
function esc(s){
  return (s??"").toString()
    .replaceAll("&","&amp;").replaceAll("<","&lt;")
    .replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;");
}
function escAttr(s){ return esc(s).replaceAll("\n"," "); }
function parseJsonMaybe(v){
  if(v == null) return null;
  if(typeof v === "string"){
    const s = v.trim();
    if(!s) return null;
    try{ return JSON.parse(s); }catch(e){ return null; }
  }
  return v;
}
function bytesToMB(b){ return Math.round((Number(b||0)/1024/1024)*10)/10; }

/* ========= build step model ========= */
const steps = (DATA.steps || []).map((s, idx)=>(Object.freeze({
  id: Number(s.id||0),
  key: (s.step_key && String(s.step_key).trim()) ? String(s.step_key) : ("step_"+idx),
  name: String(s.name || ("ნაბიჯი " + (idx+1))),
})));
if(!steps.some(s => s.key === "submit")) steps.push({id:0, key:"submit", name:"გაგზავნა"});

/* ========= state ========= */
const state = {
  grant_id: Number(DATA.grant.id),
  currentKey: steps[0]?.key || "submit",
  validByStep: {},
  form_data: {},
  applicantType: "person",

  reqUploads: {},   // { reqId: File }
  otherUploads: [], // File[]
  fieldUploads: {}, // { fieldId: File }

  data: { budget: { rows: [{}] } },
  lastBudgetError: "",

  budgetTotal(){
    return (this.data.budget.rows||[]).reduce((s,r)=> s + Number((r && r.amount) ? r.amount : 0), 0);
  },
};

/* ========= builder helpers ========= */
function getFieldsForStep(stepId){
  const fb = DATA.fieldsByStep || {};
  const list = (fb[String(stepId)] || fb[stepId] || []);
  return (list || []).map(f=>(Object.freeze({
    id: Number(f.id),
    label: String(f.label||""),
    type: String(f.type||"text"),
    is_required: Number(f.is_required||0),
    show_for: String(f.show_for||"all"),
    options_json: f.options_json ?? null,
  })));
}
function getAllFieldsFlat(){
  const out = [];
  for(const s of DATA.steps || []){
    const sid = Number(s.id||0);
    for(const f of getFieldsForStep(sid)) out.push(f);
  }
  return out;
}
function normalizeApplicantType(v){
  const s = String(v||"").trim().toLowerCase();
  if(s === "org" || s === "organization" || s.includes("ორგ")) return "org";
  if(s === "person" || s.includes("ფიზ")) return "person";
  return "";
}
function findApplicantTypeField(){
  const all = getAllFieldsFlat();
  return all.find(f => f.type === "select" && String(f.label).toLowerCase().includes("ტიპ")) || null;
}
function findBudgetTableFieldForStep(stepId){
  const fields = getFieldsForStep(stepId);
  return fields.find(f => f.type === "budget_table") || null;
}
function findAnyBudgetTableField(){
  return getAllFieldsFlat().find(f => f.type === "budget_table") || null;
}

/* ✅ Budget step detection */
function isBudgetStep(dbStep){
  if(!dbStep) return false;

  const key = String(dbStep?.step_key || "").toLowerCase();
  const name = String(dbStep?.name || "").toLowerCase();
  if(key.includes("budget") || name.includes("ბიუჯ")) return true;

  const sid = Number(dbStep.id || 0);
  if(sid && findBudgetTableFieldForStep(sid)) return true;

  // fallback: if local step key is "budget"
  if(state.currentKey === "budget") return true;

  return false;
}

/* ✅ Files step detection */
function isFilesStep(dbStep, localKey){
  const nm = String(dbStep?.name || "").toLowerCase();
  const ky = String(dbStep?.step_key || "").toLowerCase();
  return localKey === "files" || nm.includes("ფაილ") || ky.includes("file");
}

/* resolve active DB step by key */
function resolveDbStepByKey(key){
  const dbSteps = (DATA.steps || []).filter(s => Number(s.id||0) > 0);
  if(!dbSteps.length) return null;

  let s = dbSteps.find(x => String(x.step_key||"") === key) || null;
  if(s) return s;

  const nonSubmit = steps.filter(x=>x.key!=="submit");
  const localIdx = nonSubmit.findIndex(x=>x.key===key);
  return dbSteps[localIdx] || dbSteps[0] || null;
}

/* =========================
     BUDGET TABLE OPTIONS
========================= */
function budgetDefaultOptions(){
  return {
    currency: "₾",
    min_rows: 1,
    max_total: 0, // 0 => no limit
    columns: [
      {key:"cat",   label:"კატეგორია *", type:"text",   required:true, placeholder:"მაგ: აღჭურვილობა"},
      {key:"desc",  label:"აღწერა *",    type:"text",   required:true, placeholder:"დანიშნულება"},
      {key:"amount",label:"თანხა (₾) *", type:"number", required:true, min:0},
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
  if(!maxTotal && Number(DATA.grant?.max_budget||0) > 0) maxTotal = Number(DATA.grant.max_budget||0);
  maxTotal = Math.max(0, Number(maxTotal||0));

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
    if(c.key === "amount" && !c.label.includes("(")) c.label = `თანხა (${currency}) *`;
    return c;
  });

  return { currency, min_rows:minRows, max_total:maxTotal, columns: cols };
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

  const total = state.budgetTotal();
  if(maxTotal > 0 && total > maxTotal){
    state.lastBudgetError = `მაქსიმალური ბიუჯეტი არის ${formatMoney(maxTotal)} ${currency}`;
    return false;
  }
  return true;
}

/* =========================
     BUDGET STEP (VIEW)
========================= */
function viewBudget(state, opt){
  const rows = state.data.budget.rows || [];
  const total = state.budgetTotal();
  const currency = String(opt.currency || "₾");
  const cols = opt.columns || [];
  const maxTotal = Math.max(0, Number(opt.max_total || 0));

  return `
    <h2 style="margin:0 0 6px 0">ბიუჯეტი</h2>
    <div class="muted">დაამატეთ ხარჯები. ჯამი ითვლება ავტომატურად.</div>

    <div class="row" style="margin-top:10px;gap:10px">
      <div class="pill open">ჯამი: <b id="budgetTotal">${formatMoney(total)}</b> ${esc(currency)}</div>
      ${maxTotal>0 ? `<div class="pill closed">მაქსიმუმი: <b>${formatMoney(maxTotal)}</b> ${esc(currency)}</div>` : ``}
    </div>

    <div style="overflow:auto;margin-top:10px">
      <table>
        <thead>
          <tr>
            ${cols.map(c=>`<th>${esc(c.label || c.key)}</th>`).join("")}
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((r,i)=>`
            <tr>
              ${cols.map(c=>{
                const k = c.key;
                const t = (c.type === "number") ? "number" : "text";
                const ph = c.placeholder ? `placeholder="${escAttr(c.placeholder)}"` : "";
                const min = (t==="number" && c.min != null) ? `min="${Number(c.min)}"` : (t==="number" ? `min="0"` : "");
                const val = (t==="number") ? Number(r?.[k]||0) : escAttr(r?.[k] ?? "");
                return `<td><input data-row data-i="${i}" data-k="${escAttr(k)}" type="${t}" ${min} ${ph} value="${val}"></td>`;
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
      <button class="btn btn-ghost" id="btnBackB">უკან</button>
      <button class="btn btn-ac" id="btnNextB">შემდეგი</button>
    </div>
  `;
}
function bindBudget(state, opt, onBack, onNext){
  document.getElementById("btnBackB").onclick = () => onBack?.();

  document.getElementById("btnAddRow").onclick = () => {
    state.data.budget.rows.push({});
    renderBudget(state, opt, onBack, onNext);
  };

  document.querySelectorAll("[data-row]").forEach(inp=>{
    inp.oninput = () => {
      const i = Number(inp.getAttribute("data-i"));
      const k = inp.getAttribute("data-k");
      state.data.budget.rows[i] = state.data.budget.rows[i] || {};

      const col = (opt.columns||[]).find(c => c.key === k);
      if(col && col.type === "number"){
        state.data.budget.rows[i][k] = Number(inp.value||0);
      }else{
        state.data.budget.rows[i][k] = inp.value;
      }
      document.getElementById("budgetTotal").textContent = formatMoney(state.budgetTotal());
    };
  });

  document.querySelectorAll("[data-del]").forEach(btn=>{
    btn.onclick = () => {
      const i = Number(btn.getAttribute("data-del"));
      state.data.budget.rows.splice(i,1);
      const minRows = Math.max(1, Number(opt.min_rows||1));
      while(state.data.budget.rows.length < minRows) state.data.budget.rows.push({});
      renderBudget(state, opt, onBack, onNext);
    };
  });

  document.getElementById("btnNextB").onclick = () => {
    if(validateBudgetWithOptions(state.data.budget.rows, opt)){
      onNext?.();
    } else {
      alert(state.lastBudgetError || "ბიუჯეტის შეცდომა");
    }
  };
}
function renderBudget(state, opt, onBack, onNext){
  const wrap = document.getElementById("budgetStep");
  if(!wrap) return;

  const minRows = Math.max(1, Number(opt.min_rows||1));
  state.data.budget.rows = Array.isArray(state.data.budget.rows) ? state.data.budget.rows : [];
  while(state.data.budget.rows.length < minRows) state.data.budget.rows.push({});
  wrap.innerHTML = viewBudget(state, opt);
  bindBudget(state, opt, onBack, onNext);
}

/* ========== init ========== */
(function init(){
  if(!DATA.isOpen) return;

  const list = document.getElementById("stepsList");
  list.innerHTML = steps.map((s,i)=>`
    <div class="stepItem" data-step="${esc(s.key)}">
      <div class="dot">${i+1}</div>
      <div>
        <div style="font-weight:1000">${esc(s.name)}</div>
        <div class="small" id="sub_${esc(s.key)}">შესავსებია</div>
      </div>
    </div>
  `).join("");

  list.querySelectorAll(".stepItem").forEach(el=>{
    el.addEventListener("click", ()=>{
      const key = el.getAttribute("data-step");
      const idx = steps.findIndex(x=>x.key===key);
      const cur = steps.findIndex(x=>x.key===state.currentKey);

      // always allow back
      if(idx <= cur){
        state.currentKey = key;
        renderStep();
        return;
      }

      // allow only next step, if current validates
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

/* ========== Files step UI ========== */
const MAX_FILE_MB = 25; // match server (suggested)
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
  const required = reqs.filter(r => Number(r.is_required||0) === 1);

  const reqHtml = reqs.map(r=>{
    const rid = String(r.id);
    const must = Number(r.is_required||0) === 1;
    const f = state.reqUploads[rid];
    return `
      <div class="card" style="margin-top:10px;background:#0a0f1c">
        <div class="row sp">
          <div>
            <b>${esc(String(r.name||""))}</b>
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

  const otherList = state.otherUploads.map((f,i)=>`
    <tr>
      <td><b>${esc(f.name)}</b><div class="small">${bytesToMB(f.size)} MB</div></td>
      <td>${esc(f.type||"-")}</td>
      <td><button class="btn btn-bad" type="button" data-rm-other="${i}">Remove</button></td>
    </tr>
  `).join("") || `<tr><td colspan="3" class="muted">ჯერ ფაილი არ არის დამატებული</td></tr>`;

  return `
    <h3 style="margin:0 0 8px 0">ფაილები</h3>
    <div class="muted">ატვირთეთ მოთხოვნილი დოკუმენტები. (რეკომენდაცია: 25MB-მდე)</div>

    <div class="card" style="margin-top:12px">
      <b>მოთხოვნილი ფაილები</b>
      <div class="small" style="margin-top:6px">სავალდებულო: ${required.length} ც.</div>
      ${reqHtml}
    </div>

    <div class="card" style="margin-top:12px">
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

/* ========== validation ========= */
function validateRequirements(){
  const reqs = DATA.requirements || [];
  const required = reqs.filter(r => Number(r.is_required||0) === 1);
  return required.every(r => !!state.reqUploads[String(r.id)]);
}

function validateField(f, budgetOpt){
  const k = "field_" + f.id;
  const req = f.is_required === 1;
  const showFor = f.show_for || "all";

  if(showFor === "person" && state.applicantType !== "person") return true;
  if(showFor === "org" && state.applicantType !== "org") return true;
  if(!req) return true;

  if(f.type === "budget_table"){
    return validateBudgetWithOptions(state.data.budget.rows, budgetOpt || budgetDefaultOptions());
  }
  if(f.type === "checkbox"){
    const v = state.form_data[k];
    return Array.isArray(v) ? v.length > 0 : !!String(v||"").trim();
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

  // applicant type sync
  const tf = findApplicantTypeField();
  if(tf){
    const kk = "field_" + tf.id;
    const t = normalizeApplicantType(state.form_data[kk]);
    if(t) state.applicantType = t;
  }

  // budget guard
  const budgetField = stepId ? findBudgetTableFieldForStep(stepId) : null;
  const budgetOpt = budgetField ? readBudgetOptionsFromField(budgetField) : budgetDefaultOptions();
  if(isBudgetStep(activeDbStep)){
    if(!validateBudgetWithOptions(state.data.budget.rows, budgetOpt)) return false;
  }

  // files guard
  if(isFilesStep(activeDbStep, state.currentKey)){
    if(!validateRequirements()) return false;
  }

  for(const f of fields){
    if(!validateField(f, budgetOpt)) return false;
  }
  return true;
}

function missingFieldsForStep(activeDbStep){
  const stepId = Number(activeDbStep?.id || 0);
  const fields = stepId ? getFieldsForStep(stepId) : [];
  const miss = [];

  const budgetField = stepId ? findBudgetTableFieldForStep(stepId) : null;
  const budgetOpt = budgetField ? readBudgetOptionsFromField(budgetField) : budgetDefaultOptions();

  if(isBudgetStep(activeDbStep)){
    if(!validateBudgetWithOptions(state.data.budget.rows, budgetOpt)){
      miss.push(state.lastBudgetError || "ხარჯების ცხრილი (ბიუჯეტი)");
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
    if(f.type === "budget_table") continue;

    if(f.type === "file"){
      if(!state.fieldUploads[String(f.id)]) miss.push(f.label);
      continue;
    }
    if(f.type === "checkbox"){
      const v = state.form_data["field_" + f.id];
      const ok = Array.isArray(v) ? v.length > 0 : !!String(v||"").trim();
      if(!ok) miss.push(f.label);
      continue;
    }

    const v = state.form_data["field_" + f.id];
    if(!String(v ?? "").trim()) miss.push(f.label);
  }

  return Array.from(new Set(miss.map(x => String(x||"").trim()).filter(Boolean)));
}
function blockIfMissing(activeDbStep){
  const miss = missingFieldsForStep(activeDbStep);
  if(!miss.length) return true;
  alert("აკლია / შეცდომაა:\n- " + miss.join("\n- "));
  return false;
}

/* ========== submit step ========= */
function renderSubmit(){
  const dbSteps = (DATA.steps || []).filter(s => Number(s.id||0) > 0);
  let allOk = true;

  for(const s of dbSteps){
    const stepId = Number(s.id||0);
    const fields = getFieldsForStep(stepId);

    const budField = findBudgetTableFieldForStep(stepId);
    const budOpt = budField ? readBudgetOptionsFromField(budField) : budgetDefaultOptions();

    if(isBudgetStep(s) && !validateBudgetWithOptions(state.data.budget.rows, budOpt)){ allOk = false; break; }
    for(const f of fields){
      if(!validateField(f, budOpt)) { allOk = false; break; }
    }
    if(!allOk) break;
  }
  if(!validateRequirements()) allOk = false;

  return `
    <h2 style="margin:0 0 6px 0">გაგზავნა</h2>
    <div class="muted">სისტემა გადაამოწმებს სავალდებულო ველებს და შემდეგ გაგზავნის განაცხადს.</div>

    <div style="margin-top:12px">
      ${allOk ? `<div class="okbox">ყველა სავალდებულო ნაწილი შევსებულია ✅</div>`
              : `<div class="err">განაცხადი არ არის მზად. შეამოწმე სავალდებულო ნაბიჯები.</div>`}
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
    const label = String(f.label||"").toLowerCase();
    const k = "field_" + f.id;
    const v = state.form_data[k];
    const val = (v == null) ? "" : String(v).trim();
    if(!val) continue;

    if(!name && (label.includes("სახელი") || label.includes("fullname") || label.includes("full name") || label.includes("contact name"))) name = val;
    if(!email && (label.includes("ელ-ფოსტ") || label.includes("იმეილ") || label.includes("email"))) email = val;
    if(!phone && (label.includes("ტელ") || label.includes("phone") || label.includes("მობ"))) phone = val;
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

function bindSubmit(){
  const back = document.getElementById("btnBackS");
  if(back){
    back.addEventListener("click", ()=>{
      const idx = steps.findIndex(s=>s.key===state.currentKey);
      state.currentKey = steps[Math.max(0, idx-1)].key;
      renderStep();
    });
  }

  const btn = document.getElementById("btnSubmit");
  if(!btn) return;

  btn.addEventListener("click", async ()=>{
    try{
      btn.disabled = true;

      // final budget guard
      const anyBudgetField = findAnyBudgetTableField();
      const budgetOpt = anyBudgetField ? readBudgetOptionsFromField(anyBudgetField) : budgetDefaultOptions();
      if(anyBudgetField && !validateBudgetWithOptions(state.data.budget.rows, budgetOpt)){
        alert(state.lastBudgetError || "ბიუჯეტის შეცდომა");
        btn.disabled = false;
        return;
      }

      // applicant type sync
      const tf = findApplicantTypeField();
      if(tf){
        const k = "field_" + tf.id;
        const t = normalizeApplicantType(state.form_data[k]);
        if(t) state.applicantType = t;
      }

      // sync budget rows/columns into ALL budget_table fields
      const all = getAllFieldsFlat();
      const budgetFields = all.filter(f => f.type === "budget_table");
      if(budgetFields.length){
        for(const bf of budgetFields){
          const k = "field_" + bf.id;
          state.form_data[k] = budgetPayloadForField(bf);
        }
      }

      // build form-data for API
      const fd = new FormData();
      fd.append("csrf", DATA.csrf);
      fd.append("grant_id", String(state.grant_id));
      fd.append("applicant_type", state.applicantType);
      const meta = pickApplicantMeta();
      state.form_data.__meta = buildSubmissionMeta(meta);
      fd.append("form_data", JSON.stringify(state.form_data));

      if(meta.name) fd.append("applicant_name", meta.name);
      if(meta.email) fd.append("email", meta.email);
      if(meta.phone) fd.append("phone", meta.phone);

      // required requirement files
      Object.keys(state.reqUploads).forEach(reqId=>{
        const f = state.reqUploads[reqId];
        if(f) fd.append("req_file["+reqId+"]", f);
      });

      // other files
      state.otherUploads.forEach(f => { if(f) fd.append("other_files[]", f); });

      // field files
      Object.keys(state.fieldUploads).forEach(fieldId=>{
        const f = state.fieldUploads[fieldId];
        if(f) fd.append("field_file["+fieldId+"]", f);
      });

      const res = await fetch(API + "?action=submit", { method: "POST", body: fd });
      const j = await res.json().catch(()=>null);
      if(!res.ok || !j || !j.ok) throw new Error(j?.error || ("API შეცდომა: " + res.status));

      const appId = (j && (j.id ?? j.app_id ?? j.application_id)) ?? "";

      document.getElementById("finalBox").innerHTML = `
        <div class="okbox">განაცხადი მიღებულია ✅</div>
        <div class="card" style="margin-top:12px">
          <b>Application ID:</b> <span style="color:#86efac;font-weight:1000">${esc(String(appId))}</span>
          <div class="muted" style="margin-top:6px">შეინახეთ ეს ნომერი შემდგომი კომუნიკაციისთვის.</div>
        </div>
      `;
    }catch(e){
      alert(e.message || "შეცდომა");
      btn.disabled = false;
    }
  });
}

/* ========== render step ========= */
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
    const opts = parseJsonMaybe(f.options_json) || [];
    const arr = Array.isArray(opts) ? opts : [];
    const cur = (v == null) ? "" : String(v);

    if(f.type === "select"){
      const optionsHtml = arr.map(o=>{
        const s = String(o);
        return `<option value="${esc(s)}" ${cur===s ? "selected":""}>${esc(s)}</option>`;
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
        <div class="card" style="background:#0a0f1c">
          ${arr.map(o=>{
            const s = String(o);
            const checked = curSet.has(s) ? "checked" : "";
            const t = f.type === "radio" ? "radio" : "checkbox";
            return `
              <div class="row" style="gap:8px;align-items:center;margin-top:8px">
                <input style="width:auto" type="${t}" name="${esc(name)}" value="${esc(s)}" ${checked} data-group="${fieldKey}">
                <div style="font-weight:900">${esc(s)}</div>
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
    (t==="phone") ? "text" :
    (t==="date") ? "date" :
    (t==="email") ? "email" :
    (t==="number") ? "number" : "text";

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
  const idx = steps.findIndex(s=>s.key===key);

  document.querySelectorAll(".stepItem").forEach((el, i)=>{
    el.classList.remove("active","done","disabled");
    if(i < idx) el.classList.add("done");
    if(i === idx) el.classList.add("active");
    if(i > idx + 1) el.classList.add("disabled");
  });

  for(const s of steps){
    const sub = document.getElementById("sub_" + s.key);
    if(!sub) continue;
    sub.textContent = (state.validByStep[s.key] ? "შევსებულია" : (s.key==="submit" ? "შემოწმება" : "შესავსებია"));
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
      <h2 style="margin:0 0 6px 0">${esc(steps[idx]?.name || "ნაბიჯი")}</h2>
      <div class="notice">ბილდერში ნაბიჯები/ველები ჯერ არ არის აწყობილი.</div>
      <div class="row" style="margin-top:14px;justify-content:flex-end">
        <button class="btn btn-ac" type="button" onclick="state.currentKey='submit'; renderStep()">გაგრძელება</button>
      </div>
    `;
    return;
  }

  const stepId = Number(activeDbStep.id||0);
  const fields = getFieldsForStep(stepId);

  // applicant type sync
  const typeField = findApplicantTypeField();
  if(typeField){
    const tfKey = "field_" + typeField.id;
    const t = normalizeApplicantType(state.form_data[tfKey]);
    if(t) state.applicantType = t;
  }

  // budget step
  if(isBudgetStep(activeDbStep)){
    const budgetField = findBudgetTableFieldForStep(stepId) || findAnyBudgetTableField();
    const opt = budgetField ? readBudgetOptionsFromField(budgetField) : budgetDefaultOptions();

    // load existing rows if saved
    if(budgetField){
      const k = "field_" + budgetField.id;
      const cur = state.form_data[k];
      const curObj = (cur && typeof cur==="object") ? cur : parseJsonMaybe(cur);
      const rows = (curObj && Array.isArray(curObj.rows)) ? curObj.rows : null;
      if(rows && rows.length) state.data.budget.rows = rows;
    }

    box.innerHTML = `<div id="budgetStep"></div>`;

    renderBudget(state, opt,
      () => {
        state.currentKey = steps[Math.max(0, idx-1)].key;
        renderStep();
      },
      () => {
        // store rows/columns into budget field if exists
        if(budgetField){
          const k = "field_" + budgetField.id;
          state.form_data[k] = budgetPayloadForField(budgetField);
        }
        state.validByStep[state.currentKey] = true;
        state.currentKey = steps[Math.min(steps.length-1, idx+1)].key;
        renderStep();
      }
    );
    return;
  }

  const filesStep = isFilesStep(activeDbStep, key);

  const title = esc(activeDbStep.name || steps[idx]?.name || "ნაბიჯი");
  box.innerHTML = `
    <h2 style="margin:0 0 6px 0">${title}</h2>
    <div class="pill" style="margin-bottom:8px">გთხოვ შეავსე სავალდებულო ველები</div>

    ${fields.map(renderFieldInput).join("") || `<div class="notice">ამ ნაბიჯში ველი ჯერ არ არის.</div>`}

    ${filesStep ? `<hr class="hr">` + renderFilesStep() : ""}

    <div class="row sp" style="margin-top:14px">
      <button class="btn btn-ghost" type="button" ${idx===0 ? "disabled":""} id="btnBack">უკან</button>
      <button class="btn btn-ac" type="button" id="btnNext">შემდეგი</button>
    </div>
  `;

  bindFields(activeDbStep, fields, idx, filesStep);
}

function bindFields(activeDbStep, fields, idx, isFiles){
  document.querySelectorAll("[data-field]").forEach(el=>{
    const k = el.getAttribute("data-field");
    const syncValue = ()=>{
      state.form_data[k] = el.value;

      const tf = findApplicantTypeField();
      if(tf && k === ("field_"+tf.id)){
        const t = normalizeApplicantType(el.value);
        state.applicantType = t || "person";
        renderStep();
      }
    };

    el.addEventListener("input", syncValue);
    el.addEventListener("change", syncValue);

    if(state.form_data[k] == null) state.form_data[k] = el.value ?? "";
  });

  document.querySelectorAll("[data-group]").forEach(el=>{
    const k = el.getAttribute("data-group");
    const syncGroup = ()=>{
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

  document.querySelectorAll("[data-file-field]").forEach(inp=>{
    inp.addEventListener("change", ()=>{
      const fieldId = String(inp.getAttribute("data-file-field"));
      const f = inp.files && inp.files[0] ? inp.files[0] : null;
      if(f && validateClientFile(f)) state.fieldUploads[fieldId] = f;
      renderStep();
    });
  });

  if(isFiles){
    document.querySelectorAll("[data-req-file]").forEach(inp=>{
      inp.addEventListener("change", ()=>{
        const reqId = String(inp.getAttribute("data-req-file"));
        const f = inp.files && inp.files[0] ? inp.files[0] : null;
        if(f && validateClientFile(f)) state.reqUploads[reqId] = f;
        renderStep();
      });
    });

    const other = document.getElementById("otherFiles");
    if(other){
      other.addEventListener("change", ()=>{
        const files = Array.from(other.files || []);
        const okFiles = files.filter(f => validateClientFile(f));
        state.otherUploads.push(...okFiles);
        other.value = "";
        renderStep();
      });
    }

    document.querySelectorAll("[data-rm-other]").forEach(btn=>{
      btn.addEventListener("click", ()=>{
        const i = Number(btn.getAttribute("data-rm-other"));
        state.otherUploads.splice(i,1);
        renderStep();
      });
    });
  }

  const back = document.getElementById("btnBack");
  const next = document.getElementById("btnNext");

  if(back){
    back.addEventListener("click", ()=>{
      state.currentKey = steps[Math.max(0, idx-1)].key;
      renderStep();
    });
  }

  if(next){
    next.addEventListener("click", ()=>{
      const ok = validateStep(activeDbStep);
      state.validByStep[state.currentKey] = ok;

      if(!ok){
        blockIfMissing(activeDbStep);
        renderStep();
        return;
      }

      state.currentKey = steps[Math.min(steps.length-1, idx+1)].key;
      renderStep();
    });
  }
}
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
      await inject('siteHeaderMount', '/youthagency/header.html');
      await loadScript('/youthagency/app.js');
      if (typeof window.initHeader === 'function') window.initHeader();
      await inject('siteFooterMount', '/youthagency/footer.html');
    })();
  </script>
</body>
</html>
