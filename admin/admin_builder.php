<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$pdo = db();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$csrf  = $_SESSION['csrf'];
$title = 'ადმინი — გრანტების პორტალი (ბილდერი)';

ob_start();
?>

<style>
:root{
  --bg:#0f1426; --panel:#141b33; --card:#182041;
  --line:rgba(255,255,255,.12); --text:#f3f5fa; --muted:rgba(243,245,250,.7);
  --ok:#2ecc71; --bad:#e74c3c; --ac:#3498db; --warn:#f1c40f;
  --r:14px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{color:var(--text); background:var(--bg)}
h1{margin:0;font-size:20px;font-weight:900}
h2{margin:0;font-size:16px;font-weight:900}
.small{color:var(--muted);font-size:12px;font-weight:700}
.wrap{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:12px}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:12px}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:end}
.row > *{flex:1}
.tight{flex:0 0 240px}
label{display:block;margin:0 0 6px 0}
input,select,textarea{
  width:100%; padding:10px 12px; border-radius:12px;
  border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text);
  outline:none;
}
input:focus,select:focus,textarea:focus{box-shadow:0 0 0 2px rgba(52,152,219,.22) inset}
textarea{min-height:120px;resize:vertical}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid var(--line);vertical-align:top;text-align:left}
th{color:rgba(207,233,255,.92);font-size:12px;font-weight:900}
.btn{
  padding:10px 12px;border-radius:12px;border:1px solid var(--line);
  background:rgba(255,255,255,.06);color:#fff;cursor:pointer;font-weight:900;
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  text-decoration:none;
}
.btn[disabled]{opacity:.55;cursor:not-allowed}
.btn.ok{border-color:rgba(46,204,113,.45);background:rgba(46,204,113,.16)}
.btn.bad{border-color:rgba(231,76,60,.45);background:rgba(231,76,60,.16)}
.btn.ac{border-color:rgba(52,152,219,.45);background:rgba(52,152,219,.16)}
.btn.warn{border-color:rgba(241,196,15,.45);background:rgba(241,196,15,.14)}
.btn.ghost{background:rgba(255,255,255,.04)}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.alert{
  display:none;margin:10px 0;padding:10px 12px;border-radius:12px;
  border:1px solid rgba(231,76,60,.35); background:rgba(231,76,60,.14);
  font-weight:800;
}
.toast{
  position:fixed;left:16px;bottom:16px;z-index:60;
  background:rgba(20,27,51,.92);border:1px solid var(--line);
  border-radius:14px;padding:10px 12px;display:none;max-width:min(520px, calc(100% - 32px));
  box-shadow:0 10px 30px rgba(0,0,0,.35);
}
.toast.show{display:block}
.toast .t{font-weight:900}
.toast .d{margin-top:2px;color:var(--muted);font-weight:700;font-size:12px}
.box{width:min(1100px,100%);background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:14px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:980px){.grid2{grid-template-columns:1fr}}
hr{border:0;border-top:1px solid var(--line);margin:12px 0}
.tableScroll{overflow:auto;margin-top:10px}
.pill{
  display:inline-flex;gap:8px;align-items:center;padding:6px 10px;border-radius:999px;
  border:1px solid var(--line);background:rgba(255,255,255,.06);
  font-weight:900;font-size:12px;color:#fff
}
.pill.open{border-color:rgba(46,204,113,.55);box-shadow:0 0 0 2px rgba(46,204,113,.12) inset}
.budgetPreview{margin-top:10px}
.spin{
  width:16px;height:16px;border-radius:999px;
  border:2px solid rgba(255,255,255,.25);border-top-color:#fff;
  animation:sp 0.7s linear infinite;
}
@keyframes sp{to{transform:rotate(360deg)}}
.nav{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px 0}
.nav .btn.active{border-color:rgba(46,204,113,.55); box-shadow:0 0 0 2px rgba(46,204,113,.16) inset}
</style>

<div class="wrap">
  <div>
    <h1>ადმინი — გრანტების პორტალი</h1>
    <div class="small">გრანტები • ფორმის ბილდერი • განაცხადები</div>
  </div>
  <div class="actions">
    <a class="btn ghost" href="admin_grants.php">← გრანტები</a>
  </div>
</div>

<div class="nav">
  <a class="btn" href="admin_grants.php">გრანტები</a>
  <a class="btn active" href="admin_builder.php">ფორმის ბილდერი</a>
  <a class="btn" href="admin_apps.php">განაცხადები</a>
</div>

<div id="errBox" class="alert"></div>

<section id="builder">
  <div class="card">
    <div class="row">
      <div class="tight">
        <label class="small">აირჩიე გრანტი</label>
        <select id="bGrant" onchange="syncUrl(); loadBuilder()"></select>
      </div>
      <div>
        <div class="small">ნაბიჯები → ველები → ფაილების მოთხოვნები</div>
      </div>
      <div class="tight" style="flex:0 0 260px">
        <label class="small">შაბლონი (სტანდარტული)</label>
        <button class="btn ac" type="button" onclick="insertTemplate()">+ ჩასვი Applicant/Project/Budget/Files</button>
      </div>
    </div>

    <div class="small" style="margin-top:10px">
      შაბლონი ავტომატურად დაამატებს ნაბიჯებს და ველებს (მათ შორის <b>ბიუჯეტის ცხრილს</b> — type: <b>budget_table</b>).
      თუ უკვე არსებობს იგივე ველები, დუბლიკატებს არ შექმნის.
    </div>

    <div class="grid2" style="margin-top:12px">
      <div class="card">
        <b>ნაბიჯები</b>
        <div class="row" style="margin-top:10px">
          <div>
            <label class="small">ნაბიჯის დასახელება</label>
            <input id="stepName" placeholder="მაგ: განმცხადებელი">
          </div>
          <div class="tight">
            <button class="btn ok" type="button" onclick="addStep()">დამატება</button>
          </div>
        </div>
        <div id="stepsList"></div>
      </div>

      <div class="card">
        <div class="row">
          <div class="tight">
            <label class="small">აქტიური ნაბიჯი</label>
            <select id="activeStep" onchange="renderFields()"></select>
          </div>
          <div>
            <div class="small" id="activeStepLabel"></div>
          </div>
        </div>

        <div class="card" style="margin-top:10px">
          <b>ველის დამატება</b>

          <div class="row" style="margin-top:10px">
            <div>
              <label class="small">label</label>
              <input id="fLabel" placeholder="მაგ: სახელი">
            </div>
            <div class="tight">
              <label class="small">ტიპი</label>
              <select id="fType" onchange="toggleOptionsRow()">
                <option value="text">text</option>
                <option value="textarea">textarea</option>
                <option value="number">number</option>
                <option value="email">email</option>
                <option value="phone">phone</option>
                <option value="date">date</option>
                <option value="file">file</option>
                <option value="select">select</option>
                <option value="radio">radio</option>
                <option value="checkbox">checkbox</option>
                <option value="budget_table">budget_table</option>
              </select>
            </div>
          </div>

          <div class="row" id="optRow" style="display:none">
            <div>
              <label class="small">options (comma separated)</label>
              <input id="fOptions" placeholder="მაგ: სტუდენტი,დასაქმებული,უმუშევარი">
            </div>
          </div>

          <div class="row" id="budgetRow" style="display:none">
            <div class="tight">
              <label class="small">ვალუტა</label>
              <input id="bCurrency" value="₾" placeholder="₾">
            </div>
            <div class="tight">
              <label class="small">min rows</label>
              <input id="bMinRows" type="number" min="1" value="1">
            </div>
            <div>
              <div class="small" style="margin-top:26px">
                budget_table ავტომატურად ქმნის 3 სვეტს: <b>კატეგორია</b>, <b>აღწერა</b>, <b>თანხა</b>.
              </div>
            </div>
          </div>

          <div class="row">
            <div class="tight">
              <label class="small">show_for</label>
              <select id="fShowFor">
                <option value="all">all</option>
                <option value="person">person</option>
                <option value="org">org</option>
              </select>
            </div>

            <div class="tight">
              <label class="small">required</label>
              <select id="fReq">
                <option value="1">კი</option>
                <option value="0">არა</option>
              </select>
            </div>

            <div class="tight">
              <button class="btn ok" type="button" onclick="addField()">დამატება</button>
            </div>
          </div>

          <div class="small" style="margin-top:8px">
            TIP: გააკეთე ველი „განმცხადებლის ტიპი“ (select) და options-ში გამოიყენე მნიშვნელობები <b>person</b>,<b>org</b>.
          </div>
        </div>

        <div id="fieldsList"></div>

        <div id="budgetPreviewBox" class="card budgetPreview" style="display:none">
          <b>budget_table პრევიუ</b>
          <div class="small">ეს არის მხოლოდ პრევიუ (როგორც მომხმარებელი დაინახავს).</div>
          <div class="tableScroll">
            <table>
              <thead id="bpHead"></thead>
              <tbody id="bpBody"></tbody>
            </table>
          </div>
          <div class="row" style="margin-top:10px;justify-content:space-between;align-items:center">
            <div class="small" id="bpMeta"></div>
            <div class="pill open">ჯამი: <b id="bpTotal">0</b></div>
          </div>
        </div>
      </div>
    </div>

    <hr>

    <div class="card">
      <b>ფაილების მოთხოვნები</b>
      <div class="row" style="margin-top:10px">
        <div><input id="reqName" placeholder="მაგ: პროექტის ბიუჯეტი (PDF)"></div>
        <div>
          <input id="reqTemplateFile" type="file">
          <div class="small" style="margin-top:6px">სურვილის შემთხვევაში ატვირთე ფაილი, რომელსაც მომხმარებელი ჩამოტვირთავს.</div>
        </div>
        <div class="tight">
          <select id="reqReq">
            <option value="1">სავალდებულო</option>
            <option value="0">არასავალდებულო</option>
          </select>
        </div>
        <div class="tight">
          <button class="btn ok" type="button" onclick="addRequirement()">დამატება</button>
        </div>
      </div>
      <div id="reqList"></div>
    </div>
  </div>
</section>

<div class="toast" id="toast">
  <div class="t" id="toastT">OK</div>
  <div class="d" id="toastD"></div>
</div>

<script>
const CSRF = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;
const API  = "api/grants_portal_api.php";

/* helpers */
function esc(s){
  return (s ?? '').toString()
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}
function escAttr(s){ return esc(s).replaceAll("\n"," "); }

function showError(msg){
  const box = document.getElementById('errBox');
  box.style.display = 'block';
  box.textContent = msg || 'შეცდომა';
  console.error(msg);
}
function clearError(){
  const box = document.getElementById('errBox');
  box.style.display = 'none';
  box.textContent = '';
}
function toast(title, desc=""){
  const t = document.getElementById("toast");
  document.getElementById("toastT").textContent = title || "OK";
  document.getElementById("toastD").textContent = desc || "";
  t.classList.add("show");
  clearTimeout(window.__toastT);
  window.__toastT = setTimeout(()=>t.classList.remove("show"), 2600);
}

/* per-key debounce */
const __deb = new Map();
function debounce(key, fn, ms=220){
  clearTimeout(__deb.get(key));
  __deb.set(key, setTimeout(fn, ms));
}

/* API with abort */
let API_CTRL = null;
async function api(action, payload = {}){
  clearError();

  const isForm = (payload instanceof FormData);
  if(API_CTRL) API_CTRL.abort();
  API_CTRL = new AbortController();

  const res = await fetch(API + "?action=" + encodeURIComponent(action), {
    method: "POST",
    signal: API_CTRL.signal,
    headers: isForm
      ? {"X-CSRF": CSRF}
      : {"Content-Type":"application/json", "X-CSRF": CSRF},
    body: isForm ? payload : JSON.stringify(payload)
  });

  const raw = await res.text();
  let j = null;
  try{ j = JSON.parse(raw); }catch(_){}

  if(!res.ok) throw new Error(j?.error || ("HTTP " + res.status + " — " + raw.slice(0,180)));
  if(!j || !j.ok) throw new Error(j?.error || ("API bad JSON"));
  return j;
}

/* URL sync */
function syncUrl(){
  const gid = Number(document.getElementById('bGrant').value || 0);
  const qs = new URLSearchParams(location.search);
  if(gid) qs.set("grant_id", String(gid)); else qs.delete("grant_id");
  history.replaceState(null, "", location.pathname + (qs.toString() ? ("?" + qs.toString()) : ""));
}

/* ===== Grants for select ===== */
let GRANTS = [];
async function loadGrantsForSelect(){
  const j = await api("grants_list", {q:"", status:"all", sort:"new"});
  GRANTS = j.items || [];
  const b = document.getElementById('bGrant');
  b.innerHTML = GRANTS.map(g=>`<option value="${Number(g.id)}">${esc(g.title)}</option>`).join('') || `<option value="">—</option>`;

  const qp = new URLSearchParams(location.search);
  const gidFromUrl = Number(qp.get("grant_id") || 0);

  if(gidFromUrl && GRANTS.some(x=>Number(x.id)===gidFromUrl)){
    b.value = String(gidFromUrl);
  }else if(!b.value && GRANTS[0]){
    b.value = String(GRANTS[0].id);
  }

  syncUrl();
}

/* ===================== BUILDER ===================== */
let BUILDER = {steps:[], fieldsByStep:{}, reqs:[]};

/* ✅ IMPORTANT: normalize builder so label always exists */
function normalizeBuilder(b){
  b = b || {};
  b.steps = Array.isArray(b.steps) ? b.steps : [];
  b.reqs  = Array.isArray(b.reqs) ? b.reqs : [];

  // If API returns flat fields array instead of fieldsByStep
  if ((!b.fieldsByStep || typeof b.fieldsByStep !== "object") && Array.isArray(b.fields)) {
    const grouped = {};
    for (const f of b.fields) {
      const sid = Number(f.step_id || 0);
      if (!grouped[sid]) grouped[sid] = [];
      grouped[sid].push(f);
    }
    b.fieldsByStep = grouped;
  }

  b.fieldsByStep = (b.fieldsByStep && typeof b.fieldsByStep === "object") ? b.fieldsByStep : {};

  // Ensure every field has .label (fallbacks)
  for (const sid of Object.keys(b.fieldsByStep)) {
    b.fieldsByStep[sid] = (b.fieldsByStep[sid] || []).map(f => ({
      ...f,
      label: normalizeFieldLabel(f)
    }));
  }

  return b;
}

function normalizeFieldLabel(f){
  const raw = (f?.label ?? f?.field_label ?? f?.name ?? f?.title ?? "").toString().trim();
  const asLower = raw.toLowerCase();
  if (!raw || /^field[_\s]?\d+$/i.test(raw) || /^f[_\s]?\d+$/i.test(raw)) {
    return `ველი #${Number(f?.id || 0) || ''}`.trim();
  }
  return raw;
}

async function loadBuilder(){
  const gid = Number(document.getElementById('bGrant').value || 0);
  if(!gid) return;

  try{
    const j = await api("builder_load", {grant_id: gid});
    BUILDER = normalizeBuilder(j.builder || {steps:[], fieldsByStep:{}, reqs:[]});

    renderSteps();
    renderReqs();
    renderActiveStepSelect();
    renderFields();
  }catch(e){
    if(e?.name !== "AbortError") showError(e.message);
  }
}

function renderSteps(){
  const box = document.getElementById('stepsList');
  const list = (BUILDER.steps || []).slice().sort((a,b)=>Number(a.sort_order)-Number(b.sort_order));

  box.innerHTML = list.map(s=>`
    <div class="card" style="margin-top:10px">
      <div class="row" style="align-items:center">
        <div>
          <b>${esc(s.name)}</b>
          <div class="small">key: ${esc(s.step_key)} • რიგი: ${Number(s.sort_order||0)}</div>
        </div>
        <div class="actions">
          <button class="btn ghost" type="button" onclick="setActiveStep(${Number(s.id)})">ველები</button>
          <button class="btn ghost" type="button" onclick="stepMove(${Number(s.id)},-1)">↑</button>
          <button class="btn ghost" type="button" onclick="stepMove(${Number(s.id)},+1)">↓</button>
          <button class="btn ghost" type="button" onclick="stepToggle(${Number(s.id)})">${String(s.is_enabled)==='1'?'გამორთე':'ჩართე'}</button>
          <button class="btn ac" type="button" onclick="stepRename(${Number(s.id)})">გადარქმევა</button>
          <button class="btn bad" type="button" onclick="stepDelete(${Number(s.id)})">წაშლა</button>
        </div>
      </div>
    </div>
  `).join('') || `<div class="small" style="margin-top:10px">ნაბიჯები ჯერ არ არის.</div>`;
}

function renderActiveStepSelect(){
  const sel = document.getElementById('activeStep');
  const list = (BUILDER.steps || []).slice().sort((a,b)=>Number(a.sort_order)-Number(b.sort_order));

  sel.innerHTML = list.map(s=>`<option value="${Number(s.id)}">${esc(s.name)}</option>`).join('') || `<option value="">—</option>`;
  if(!sel.value && list[0]) sel.value = String(list[0].id);
}

function setActiveStep(step_id){
  document.getElementById('activeStep').value = String(step_id);
  renderFields();
}

async function addStep(){
  const gid = Number(document.getElementById('bGrant').value || 0);
  const name = (document.getElementById('stepName').value || '').trim();
  if(!gid) return;
  if(!name) return alert("ჩაწერე ნაბიჯის სახელი");

  try{
    await api("step_add", {grant_id: gid, name});
    document.getElementById('stepName').value = '';
    toast("დამატებულია ✅", "ნაბიჯი შეიქმნა");
    await loadBuilder();
  }catch(e){ showError(e.message); }
}

async function stepRename(step_id){
  const s = (BUILDER.steps || []).find(x => Number(x.id) === Number(step_id));
  const oldName = s ? (s.name || "") : "";
  const name = (prompt("ახალი სახელი:", oldName) ?? "").trim();
  if(!name) return;

  try{
    await api("step_rename", {step_id, name});
    toast("განახლდა ✅");
    await loadBuilder();
  }catch(e){
    try{
      await api("step_update", {step_id, name});
      toast("განახლდა ✅");
      await loadBuilder();
    }catch(e2){ showError(e2.message); }
  }
}
async function stepDelete(step_id){
  if(!confirm("წაიშალოს ნაბიჯი? (შიგნით არსებული ველებიც წაიშლება)")) return;
  try{ await api("step_delete", {step_id}); toast("წაშლილია ✅"); await loadBuilder(); }
  catch(e){ showError(e.message); }
}
async function stepToggle(step_id){
  try{ await api("step_toggle", {step_id}); toast("განახლდა ✅"); await loadBuilder(); }
  catch(e){ showError(e.message); }
}
async function stepMove(step_id, dir){
  try{ await api("step_move", {step_id, dir}); await loadBuilder(); }
  catch(e){ showError(e.message); }
}

/* fields */
function toggleOptionsRow(){
  const t = document.getElementById('fType').value;
  const isOpt = (t==='select' || t==='radio' || t==='checkbox');
  document.getElementById('optRow').style.display = isOpt ? 'flex' : 'none';
  if(!isOpt) document.getElementById('fOptions').value = '';

  const isBudget = (t === 'budget_table');
  document.getElementById('budgetRow').style.display = isBudget ? 'flex' : 'none';
  if(!isBudget){
    document.getElementById('bCurrency').value = '₾';
    document.getElementById('bMinRows').value = '1';
  }
}

function renderFields(){
  const step_id = Number(document.getElementById('activeStep').value || 0);
  const list = (BUILDER.fieldsByStep?.[step_id] || []);
  const box  = document.getElementById('fieldsList');

  const step = (BUILDER.steps || []).find(x=>Number(x.id) === step_id);
  document.getElementById('activeStepLabel').textContent =
    step ? `აქტიური ნაბიჯი: ${step.name} • ველები: ${list.length}` : '';

  box.innerHTML = list.map(f=>{
    const lbl = normalizeFieldLabel(f);
    return `
      <div class="card" style="margin-top:10px">
        <div class="row" style="align-items:center">
          <div>
            <b>${esc(lbl)}</b>
            <div class="small">type: ${esc(f.type)} • required: ${String(f.is_required)==='1'?'კი':'არა'} • show_for: ${esc(f.show_for || 'all')} • id: ${Number(f.id || 0)}</div>
          </div>
          <div class="actions">
            <button class="btn ac" type="button" onclick="fieldEdit(${Number(f.id)})">რედაქტირება</button>
            <button class="btn ghost" type="button" onclick="fieldToggle(${Number(f.id)})">${String(f.is_enabled)==='1'?'გამორთე':'ჩართე'}</button>
            <button class="btn bad" type="button" onclick="fieldDelete(${Number(f.id)})">წაშლა</button>
          </div>
        </div>
      </div>
    `;
  }).join('') || `<div class="small" style="margin-top:10px">ამ ნაბიჯში ველი ჯერ არ არის.</div>`;

  maybeShowBudgetPreviewForActiveStep();
}

function defaultBudgetOptions(currency="₾", minRows=1){
  return {
    currency: currency || "₾",
    min_rows: Math.max(1, Number(minRows || 1)),
    columns: [
      {key:"cat",   label:"კატეგორია", type:"text",   required:true, placeholder:"მაგ: აღჭურვილობა"},
      {key:"desc",  label:"აღწერა",    type:"text",   required:true, placeholder:"დანიშნულება"},
      {key:"amount",label:`თანხა (${currency || "₾"})`, type:"number", required:true, min:0}
    ]
  };
}

async function addField(){
  const gid = Number(document.getElementById('bGrant').value || 0);
  const step_id = Number(document.getElementById('activeStep').value || 0);
  if(!gid || !step_id) return;

  const label = (document.getElementById('fLabel').value || '').trim();
  const type  = document.getElementById('fType').value;
  const is_required = Number(document.getElementById('fReq').value || 0);
  const show_for = document.getElementById('fShowFor').value;

  if(!label) return alert("ჩაწერე ველის სახელი");

  let options = [];
  let options_json = null;

  if(type==='select' || type==='radio' || type==='checkbox'){
    const raw = (document.getElementById('fOptions').value || '').trim();
    options = raw.split(",").map(s=>s.trim()).filter(Boolean);
    if(!options.length) return alert("ამ ტიპს სჭირდება options (მძიმით)");
    options_json = JSON.stringify(options);
  }

  if(type === 'budget_table'){
    const currency = (document.getElementById('bCurrency').value || '₾').trim() || '₾';
    const minRows  = Number(document.getElementById('bMinRows').value || 1);
    options = [];
    options_json = JSON.stringify(defaultBudgetOptions(currency, minRows));
  }

  try{
    await api("field_add", { grant_id: gid, step_id, label, type, is_required, show_for, options, options_json });
    document.getElementById('fLabel').value = '';
    document.getElementById('fOptions').value = '';
    toast("დამატებულია ✅", "ველი შეიქმნა");
    await loadBuilder();
  }catch(e){ showError(e.message); }
}

async function fieldToggle(field_id){
  try{ await api("field_toggle", {field_id}); toast("განახლდა ✅"); await loadBuilder(); }
  catch(e){ showError(e.message); }
}
async function fieldDelete(field_id){
  if(!confirm("წაიშალოს ველი?")) return;
  try{ await api("field_delete", {field_id}); toast("წაშლილია ✅"); await loadBuilder(); }
  catch(e){ showError(e.message); }
}

function parseJsonMaybe(v){
  if(v == null) return null;
  if(typeof v === "string"){
    const s = v.trim();
    if(!s) return null;
    try{ return JSON.parse(s); }catch(_){ return null; }
  }
  return v;
}
function findFieldById(field_id){
  field_id = Number(field_id);
  for(const list of Object.values(BUILDER.fieldsByStep || {})){
    const f = (list || []).find(x => Number(x.id) === field_id);
    if(f) return f;
  }
  return null;
}

async function fieldEdit(field_id){
  const f = findFieldById(field_id);
  if(!f) return showError("ველი ვერ მოიძებნა: " + field_id);

  const currentLabel = (f.label ?? f.field_label ?? f.name ?? f.title ?? "").toString();

  const label = (prompt("label:", currentLabel) ?? currentLabel).trim();
  const type  = (prompt("type:", f.type) ?? f.type).trim();
  const required = confirm("required? (OK=კი, Cancel=არა)");
  const show_for = (prompt("show_for (all/person/org):", f.show_for || "all") ?? (f.show_for || "all")).trim();

  let options = [];
  let options_json = null;

  if(type==="select" || type==="radio" || type==="checkbox"){
    const cur = parseJsonMaybe(f.options_json) || f.options || [];
    const raw = prompt("options (comma):", Array.isArray(cur) ? cur.join(",") : "") || "";
    options = raw.split(",").map(s=>s.trim()).filter(Boolean);
    options_json = options.length ? JSON.stringify(options) : null;
  }

  if(type === "budget_table"){
    const curOpt = parseJsonMaybe(f.options_json) || null;
    const currCurrency = (curOpt && curOpt.currency) ? String(curOpt.currency) : "₾";
    const currMinRows  = (curOpt && curOpt.min_rows) ? Number(curOpt.min_rows) : 1;

    const currency = (prompt("currency (₾/$/€):", currCurrency) ?? currCurrency).trim() || "₾";
    const minRowsRaw = (prompt("min_rows:", String(currMinRows)) ?? String(currMinRows)).trim();
    const minRows = Math.max(1, Number(minRowsRaw || 1));

    options = [];
    options_json = JSON.stringify(defaultBudgetOptions(currency, minRows));
  }

  try{
    await api("field_update", { field_id, label, type, is_required: required ? 1 : 0, show_for, options, options_json });
    toast("შენახულია ✅", "ველი განახლდა");
    await loadBuilder();
  }catch(e){ showError(e.message); }
}

/* ===== Template insert ===== */
function normName(s){ return (s||"").toString().trim().toLowerCase(); }
function stepFindByAny(names){
  const nset = names.map(normName);
  return (BUILDER.steps || []).find(s=>{
    const a = normName(s.name);
    const k = normName(s.step_key);
    return nset.some(n => a.includes(n) || k.includes(n));
  }) || null;
}
function fieldExists(step_id, label){
  const list = (BUILDER.fieldsByStep?.[step_id] || []);
  const L = normName(label);
  return list.some(f => normName((f.label ?? f.field_label ?? f.name ?? f.title ?? "")) === L);
}
async function safeAddField(grant_id, step_id, label, type, is_required=1, show_for="all", options=[], options_json=null){
  if(fieldExists(step_id, label)) return;
  await api("field_add", {grant_id, step_id, label, type, is_required, show_for, options, options_json});
}

async function insertTemplate(){
  const gid = Number(document.getElementById('bGrant').value || 0);
  if(!gid) return;

  if(!confirm("დავამატო სტანდარტული ნაბიჯები/ველები (Applicant/Project/Budget/Files) + ბიუჯეტის ცხრილი?")) return;

  try{
    await loadBuilder();

    const wantSteps = [
      {name:"განმცხადებელი", aliases:["განმც", "applicant"]},
      {name:"პროექტი", aliases:["პრო", "project"]},
      {name:"ბიუჯეტი", aliases:["ბიუჯ", "budget"]},
      {name:"ფაილები", aliases:["ფაილ", "files"]},
    ];

    for(const s of wantSteps){
      const ex = stepFindByAny([s.name, ...s.aliases]);
      if(!ex) await api("step_add", {grant_id: gid, name: s.name});
    }

    await loadBuilder();

    const stepApplicant = stepFindByAny(["განმც", "applicant"]);
    const stepProject   = stepFindByAny(["პრო", "project"]);
    const stepBudget    = stepFindByAny(["ბიუჯ", "budget"]);
    const stepFiles     = stepFindByAny(["ფაილ", "files"]);

    if(!stepApplicant || !stepProject || !stepBudget || !stepFiles){
      throw new Error("ვერ მოიძებნა ყველა საჭირო ნაბიჯი (შეამოწმე step_add / builder_load API).");
    }

    await safeAddField(gid, Number(stepApplicant.id), "განმცხადებლის ტიპი", "select", 1, "all", ["person","org"], JSON.stringify(["person","org"]));
    await safeAddField(gid, Number(stepApplicant.id), "სახელი", "text", 1, "person");
    await safeAddField(gid, Number(stepApplicant.id), "გვარი", "text", 1, "person");
    await safeAddField(gid, Number(stepApplicant.id), "პირადი ნომერი", "text", 1, "person");
    await safeAddField(gid, Number(stepApplicant.id), "ტელეფონი", "phone", 1, "all");
    await safeAddField(gid, Number(stepApplicant.id), "ელ.ფოსტა", "email", 1, "all");
    await safeAddField(gid, Number(stepApplicant.id), "მისამართი", "text", 0, "all");

    await safeAddField(gid, Number(stepApplicant.id), "ორგანიზაციის დასახელება", "text", 1, "org");
    await safeAddField(gid, Number(stepApplicant.id), "საიდენტიფიკაციო კოდი", "text", 1, "org");
    await safeAddField(gid, Number(stepApplicant.id), "წარმომადგენელი (სახელი/გვარი)", "text", 1, "org");

    await safeAddField(gid, Number(stepProject.id), "პროექტის დასახელება", "text", 1, "all");
    await safeAddField(gid, Number(stepProject.id), "მოკლე აღწერა", "text", 1, "all");
    await safeAddField(gid, Number(stepProject.id), "სრული აღწერა", "textarea", 1, "all");
    await safeAddField(gid, Number(stepProject.id), "მიზნები", "textarea", 1, "all");
    await safeAddField(gid, Number(stepProject.id), "მოსალოდნელი შედეგები", "textarea", 1, "all");
    await safeAddField(gid, Number(stepProject.id), "დასაწყისი", "date", 1, "all");
    await safeAddField(gid, Number(stepProject.id), "დასრულება", "date", 1, "all");

    const bud = JSON.stringify(defaultBudgetOptions("₾", 1));
    await safeAddField(gid, Number(stepBudget.id), "ბიუჯეტი", "budget_table", 1, "all", [], bud);

    await safeAddField(gid, Number(stepFiles.id), "ფაილების ატვირთვა", "file", 0, "all");

    toast("შაბლონი ჩაიმატა ✅", "ნაბიჯები/ველები/ბიუჯეტის ცხრილი");
    await loadBuilder();
  }catch(e){ showError(e.message); }
}

/* requirements */
function renderReqs(){
  const box = document.getElementById('reqList');
  const list = (BUILDER.reqs || []);

  box.innerHTML = list.map(r=>`
    <div class="card" style="margin-top:10px">
      <div class="row" style="align-items:center">
        <div>
          <b>${esc(r.name)}</b>
          <div class="small">${String(r.is_required)==='1'?'სავალდებულო':'არასავალდებულო'}</div>
          ${r.template_file_path ? `<div class="small" style="margin-top:6px">ფაილი: <a href="/admin/${escAttr(String(r.template_file_path).replace(/^\/+/,''))}" target="_blank" rel="noopener">${esc(r.template_file_name || 'ჩამოტვირთვა')}</a></div>` : `<div class="small" style="margin-top:6px">ფაილი: —</div>`}
        </div>
        <div class="actions">
          <button class="btn bad" type="button" onclick="reqDelete(${Number(r.id)})">წაშლა</button>
        </div>
      </div>
    </div>
  `).join('') || `<div class="small" style="margin-top:10px">მოთხოვნები ჯერ არ არის.</div>`;
}

async function addRequirement(){
  const gid = Number(document.getElementById('bGrant').value || 0);
  const name = document.getElementById('reqName').value.trim();
  const is_required = Number(document.getElementById('reqReq').value || 1);
  const templateFile = document.getElementById('reqTemplateFile').files?.[0] || null;

  if(!name) return alert("ჩაწერე მოთხოვნა");

  try{
    const fd = new FormData();
    fd.append("grant_id", String(gid));
    fd.append("name", name);
    fd.append("is_required", String(is_required));
    if(templateFile) fd.append("template_file", templateFile);

    await api("req_add", fd);
    document.getElementById('reqName').value = '';
    document.getElementById('reqTemplateFile').value = '';
    toast("დამატებულია ✅", "მოთხოვნა შეიქმნა");
    await loadBuilder();
  }catch(e){ showError(e.message); }
}
async function reqDelete(id){
  if(!confirm("წაიშალოს მოთხოვნა?")) return;
  try{ await api("req_delete", {id}); toast("წაშლილია ✅"); await loadBuilder(); }
  catch(e){ showError(e.message); }
}

/* ===== Budget preview ===== */
function fmtMoney(n){
  const x = Number(n || 0);
  try{ return x.toLocaleString("ka-GE"); }catch(_){ return String(x); }
}
function budgetDefaultRows(minRows=1){
  const n = Math.max(1, Number(minRows||1));
  const rows = [];
  for(let i=0;i<n;i++) rows.push({cat:"", desc:"", amount:0});
  return rows;
}

function renderBudgetPreviewFromOptions(opt){
  const box = document.getElementById("budgetPreviewBox");
  const thead = document.getElementById("bpHead");
  const tbody = document.getElementById("bpBody");
  const totalEl = document.getElementById("bpTotal");
  const metaEl = document.getElementById("bpMeta");

  if(!box || !thead || !tbody || !totalEl || !metaEl) return;

  opt = parseJsonMaybe(opt) || opt;

  if(!opt || typeof opt !== "object" || !Array.isArray(opt.columns)){
    box.style.display = "none";
    thead.innerHTML = "";
    tbody.innerHTML = "";
    totalEl.textContent = "0";
    metaEl.textContent = "";
    return;
  }

  const currency = String(opt.currency || "₾");
  const minRows = Math.max(1, Number(opt.min_rows || 1));
  const cols = opt.columns;

  thead.innerHTML = `
    <tr>
      ${cols.map(c=>`<th>${esc(c.label || c.key || "")}</th>`).join("")}
      <th style="width:90px"></th>
    </tr>
  `;

  const rows = budgetDefaultRows(minRows);
  tbody.innerHTML = rows.map((r,i)=>`
    <tr>
      ${cols.map(c=>{
        const k = c.key;
        const type = (c.type || "text").toLowerCase();
        const ph = c.placeholder ? `placeholder="${escAttr(c.placeholder)}"` : "";
        const min = (type==="number" && c.min != null) ? `min="${Number(c.min)}"` : "";
        const val = (k==="amount") ? Number(r[k]||0) : escAttr(r[k]||"");
        return `<td><input data-bp-row data-i="${i}" data-k="${escAttr(k)}" type="${type==="number"?"number":"text"}" ${min} ${ph} value="${val}"></td>`;
      }).join("")}
      <td><button class="btn bad" type="button" data-bp-del="${i}">X</button></td>
    </tr>
  `).join("");

  metaEl.textContent = `ვალუტა: ${currency} • მინ. ჩანაწერები: ${minRows}`;

  function recalc(){
    let sum = 0;
    document.querySelectorAll("[data-bp-row][data-k='amount']").forEach(inp=>{
      sum += Number(inp.value || 0);
    });
    totalEl.textContent = fmtMoney(sum) + " " + currency;
  }

  document.querySelectorAll("[data-bp-row]").forEach(inp=> inp.oninput = recalc);
  document.querySelectorAll("[data-bp-del]").forEach(btn=>{
    btn.onclick = ()=>{
      const i = Number(btn.getAttribute("data-bp-del"));
      document.querySelectorAll(`[data-bp-row][data-i="${i}"]`).forEach(el=>{
        el.value = (el.getAttribute("data-k")==="amount") ? 0 : "";
      });
      recalc();
    };
  });

  recalc();
  box.style.display = "block";
}

function maybeShowBudgetPreviewForActiveStep(){
  const step_id = Number(document.getElementById('activeStep').value || 0);
  const list = (BUILDER.fieldsByStep?.[step_id] || []);
  const f = list.find(x => String(x.type) === "budget_table");
  const box = document.getElementById("budgetPreviewBox");

  if(!f){
    if(box) box.style.display = "none";
    return;
  }
  const opt = parseJsonMaybe(f.options_json) || null;
  renderBudgetPreviewFromOptions(opt);
}

/* init */
toggleOptionsRow();
(async function init(){
  try{
    await loadGrantsForSelect();
    await loadBuilder();
  }catch(e){
    showError(e.message);
  }
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
