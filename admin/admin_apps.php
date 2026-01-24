<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$pdo = db();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$csrf  = $_SESSION['csrf'];
$title = 'ადმინი — განაცხადები (გრანტების პორტალი)';

ob_start();
?>

<style>
:root{
  --bg:#0f1426; --panel:#141b33; --card:#182041;
  --line:rgba(255,255,255,.12); --text:#f3f5fa; --muted:rgba(243,245,250,.7);
  --ok:#2ecc71; --bad:#e74c3c; --ac:#3498db; --warn:#f1c40f; --chip:#0b1022;
  --r:14px;
  --shadow: 0 14px 40px rgba(0,0,0,.28);
}

*{box-sizing:border-box}
html,body{height:100%}
body{color:var(--text); background:radial-gradient(1200px 700px at 20% -10%, rgba(52,152,219,.16), transparent 55%),
                     radial-gradient(1100px 650px at 110% 0%, rgba(46,204,113,.12), transparent 55%),
                     var(--bg);}

h1{margin:0;font-size:20px;font-weight:900}
h2{margin:0;font-size:16px;font-weight:900}
.small{color:var(--muted);font-size:12px;font-weight:700}
.mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace}

.container{max-width:1200px;margin:0 auto;padding:14px}
@media(max-width:560px){ .container{padding:10px} }

.topbar{
  display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;
  margin-bottom:12px
}
.titleBlock{display:flex;flex-direction:column;gap:4px}
.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}

.btn{
  padding:10px 12px;border-radius:12px;border:1px solid var(--line);
  background:rgba(255,255,255,.06);color:#fff;cursor:pointer;font-weight:900;
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  text-decoration:none;
  transition:transform .06s ease, opacity .15s ease, border-color .15s ease;
}
.btn:hover{opacity:.95}
.btn:active{transform:translateY(1px)}
.btn[disabled]{opacity:.55;cursor:not-allowed}
.btn.ok{border-color:rgba(46,204,113,.45);background:rgba(46,204,113,.16)}
.btn.bad{border-color:rgba(231,76,60,.45);background:rgba(231,76,60,.16)}
.btn.ac{border-color:rgba(52,152,219,.45);background:rgba(52,152,219,.16)}
.btn.warn{border-color:rgba(241,196,15,.45);background:rgba(241,196,15,.14)}
.btn.ghost{background:rgba(255,255,255,.04)}
.btn.active{border-color:rgba(46,204,113,.55); box-shadow:0 0 0 2px rgba(46,204,113,.16) inset}

.nav{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px 0}

.card{
  background:rgba(24,32,65,.88);
  border:1px solid var(--line);
  border-radius:var(--r);
  padding:12px;
  box-shadow:0 10px 28px rgba(0,0,0,.20);
  backdrop-filter: blur(8px);
}

.alert{
  display:none;margin:10px 0;padding:10px 12px;border-radius:12px;
  border:1px solid rgba(231,76,60,.35); background:rgba(231,76,60,.14);
  font-weight:800;
}

input,select,textarea{
  width:100%; padding:10px 12px; border-radius:12px;
  border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text);
  outline:none;
}
input:focus,select:focus,textarea:focus{box-shadow:0 0 0 2px rgba(52,152,219,.22) inset}
textarea{min-height:120px;resize:vertical}

.filters{
  position:sticky; top:10px; z-index:5;
  margin-bottom:12px;
}
.filtersGrid{
  display:grid;
  grid-template-columns: 280px 1fr 240px;
  gap:10px;
  align-items:end;
}
@media(max-width:980px){
  .filtersGrid{grid-template-columns:1fr 1fr}
}
@media(max-width:560px){
  .filtersGrid{grid-template-columns:1fr}
}
label{display:block;margin:0 0 6px 0}

.chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.chip{
  display:inline-flex;align-items:center;gap:8px;
  padding:7px 10px;border-radius:999px;
  background:rgba(11,16,34,.6);
  border:1px solid rgba(255,255,255,.12);
  font-weight:900;font-size:12px;
}
.chip .count{font-weight:900;color:#fff}
.dot{width:8px;height:8px;border-radius:999px;background:rgba(255,255,255,.35)}
.dot.ok{background:rgba(46,204,113,.95)}
.dot.warn{background:rgba(241,196,15,.95)}
.dot.bad{background:rgba(231,76,60,.95)}
.dot.ac{background:rgba(52,152,219,.95)}
.dot.muted{background:rgba(255,255,255,.25)}

.tableWrap{overflow:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid var(--line);vertical-align:top;text-align:left}
th{color:rgba(207,233,255,.92);font-size:12px;font-weight:900}
.subtle{color:var(--muted);font-size:12px;font-weight:800}
.stack{display:flex;flex-direction:column;gap:4px}
.contactRow{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.tag{
  display:inline-flex;align-items:center;gap:6px;
  padding:4px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.06);font-size:11px;font-weight:900;color:#fff
}
.tag.muted{color:var(--muted)}

.statusPill{
  display:inline-flex;align-items:center;gap:8px;
  padding:7px 10px;border-radius:999px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.06);
  font-weight:900;font-size:12px;
}
.statusPill.submitted{border-color:rgba(52,152,219,.45); box-shadow:0 0 0 2px rgba(52,152,219,.12) inset}
.statusPill.in_review{border-color:rgba(241,196,15,.45); box-shadow:0 0 0 2px rgba(241,196,15,.12) inset}
.statusPill.need_clarification{border-color:rgba(241,196,15,.45); box-shadow:0 0 0 2px rgba(241,196,15,.12) inset}
.statusPill.approved{border-color:rgba(46,204,113,.45); box-shadow:0 0 0 2px rgba(46,204,113,.12) inset}
.statusPill.rejected{border-color:rgba(231,76,60,.45); box-shadow:0 0 0 2px rgba(231,76,60,.12) inset}

.gridCards{display:none;gap:10px}
@media(max-width:840px){
  .tableWrap{display:none}
  .gridCards{display:grid}
}
.appCard{
  display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;
}
.appCardLeft{min-width:240px;flex:1}
.appCardRight{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.kvmini{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
.kvmini .k{color:rgba(207,233,255,.92);font-weight:900;font-size:12px}
.kvmini .v{font-weight:900}

.toast{
  position:fixed;left:16px;bottom:16px;z-index:60;
  background:rgba(20,27,51,.92);border:1px solid var(--line);
  border-radius:14px;padding:10px 12px;display:none;max-width:min(520px, calc(100% - 32px));
  box-shadow:var(--shadow);
}
.toast.show{display:block}
.toast .t{font-weight:900}
.toast .d{margin-top:2px;color:var(--muted);font-weight:700;font-size:12px}

.modal{position:fixed;inset:0;background:rgba(0,0,0,.62);display:none;align-items:center;justify-content:center;padding:12px;z-index:50}
.modal.show{display:flex}
.box{width:min(1180px,100%);background:rgba(20,27,51,.92);border:1px solid var(--line);border-radius:16px;padding:14px;box-shadow:var(--shadow)}
.head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.close{width:44px;height:44px;border-radius:14px}

.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:980px){.grid2{grid-template-columns:1fr}}

hr{border:0;border-top:1px solid var(--line);margin:12px 0}

.pill{
  display:inline-flex;gap:8px;align-items:center;padding:6px 10px;border-radius:999px;
  border:1px solid var(--line);background:rgba(255,255,255,.06);
  font-weight:900;font-size:12px;color:#fff
}
.pill.open{border-color:rgba(46,204,113,.55);box-shadow:0 0 0 2px rgba(46,204,113,.12) inset}
.pill.person{border-color:rgba(52,152,219,.55);box-shadow:0 0 0 2px rgba(52,152,219,.12) inset}
.pill.org{border-color:rgba(46,204,113,.55);box-shadow:0 0 0 2px rgba(46,204,113,.12) inset}
.pill.warn{border-color:rgba(241,196,15,.55);box-shadow:0 0 0 2px rgba(241,196,15,.12) inset}

.kv{display:grid;grid-template-columns:260px 1fr;gap:8px;align-items:start}
@media(max-width:980px){.kv{grid-template-columns:1fr}}
.kv .k{color:rgba(207,233,255,.92);font-weight:900;font-size:12px}
.kv .v{font-weight:800;word-break:break-word}

.fileRow{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between}
.fileMeta{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.mini{font-size:12px;color:var(--muted);font-weight:800}
a.dl{color:#fff;text-decoration:none;border-bottom:1px dashed rgba(255,255,255,.35)}
a.dl:hover{opacity:.9}

.spin{
  width:16px;height:16px;border-radius:999px;
  border:2px solid rgba(255,255,255,.25);border-top-color:#fff;
  animation:sp 0.7s linear infinite;
}
@keyframes sp{to{transform:rotate(360deg)}}
</style>

<div class="container">

  <div class="topbar">
    <div class="titleBlock">
      <h1>ადმინი — განაცხადები</h1>
      <div class="small">გრანტების პორტალი • განაცხადების სია • გახსნა → სრული ფორმა + ფაილები + ბიუჯეტი</div>
    </div>
    <div class="actions">
      <button class="btn warn" type="button" onclick="quickShowAll()">ყველა გრანტი (Quick)</button>
      <button class="btn ac" id="btnReload" type="button" onclick="reloadAll()">განახლება</button>
    </div>
  </div>

  <div class="nav">
    <a class="btn" href="admin_grants.php">გრანტები</a>
    <a class="btn" href="admin_builder.php">ფორმის ბილდერი</a>
    <a class="btn active" href="admin_apps.php">განაცხადები</a>
  </div>

  <div id="errBox" class="alert"></div>

  <div class="filters">
    <div class="card">
      <div class="filtersGrid">
        <div>
          <label class="small">გრანტი</label>
          <select id="aGrant" onchange="syncUrlFromFilters(); loadApps(true)"></select>
        </div>
        <div>
          <label class="small">ძიება</label>
          <input id="aq" placeholder="სახელი/ელფოსტა/ტელეფონი/ID..." oninput="debounce('apps_q', ()=>{syncUrlFromFilters(); loadApps(true);}, 240)">
        </div>
        <div>
          <label class="small">სტატუსი</label>
          <select id="aStatus" onchange="syncUrlFromFilters(); loadApps(true)">
            <option value="all">ყველა</option>
            <option value="submitted">გაგზავნილი</option>
            <option value="in_review">გადახედვაში</option>
            <option value="need_clarification">დაზუსტება სჭირდება</option>
            <option value="approved">დამტკიცებული</option>
            <option value="rejected">უარყოფილი</option>
          </select>
        </div>
      </div>

      <div class="chips">
        <span class="chip"><span class="dot muted"></span> სულ: <span class="count" id="kpi_total">0</span></span>
        <span class="chip"><span class="dot ac"></span> გაგზავნილი: <span class="count" id="kpi_submitted">0</span></span>
        <span class="chip"><span class="dot warn"></span> გადახედვაში: <span class="count" id="kpi_review">0</span></span>
        <span class="chip"><span class="dot warn"></span> დაზუსტება: <span class="count" id="kpi_clarify">0</span></span>
        <span class="chip"><span class="dot ok"></span> დამტკიცებული: <span class="count" id="kpi_approved">0</span></span>
        <span class="chip"><span class="dot bad"></span> უარყოფილი: <span class="count" id="kpi_rejected">0</span></span>
      </div>
    </div>
  </div>

  <section id="apps">
    <div class="card">
      <div class="small">სულ: <b id="countApps">0</b></div>

      <!-- Desktop table -->
      <div class="tableWrap">
        <table>
          <thead>
            <tr>
              <th style="width:90px">ID</th>
              <th>გრანტი</th>
              <th>განმცხადებელი</th>
              <th>კონტაქტი</th>
              <th style="width:160px">სტატუსი</th>
              <th style="width:90px">ქულა</th>
              <th style="width:320px">ქმედებები</th>
            </tr>
          </thead>
          <tbody id="aBody"></tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div class="gridCards" id="cards"></div>

      <div class="small" style="margin-top:10px">
        თუ სია ცარიელია, სცადე: <b>„ყველა გრანტი (Quick)”</b>.
      </div>
    </div>
  </section>

</div>

<!-- ===================== APP MODAL ===================== -->
<div class="modal" id="appModal">
  <div class="box">
    <div class="head">
      <div>
        <b id="amTitle">განაცხადი</b>
        <div class="small" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <span id="amMeta"></span>
          <span id="amTypePill" class="pill" style="display:none"></span>
          <span id="amFilesPill" class="pill warn" style="display:none"></span>
        </div>
      </div>
      <button class="btn close" onclick="closeApp()" type="button">✕</button>
    </div>

    <div class="grid2">
      <div class="card">
        <b>მიღებული ინფორმაცია (Label-ებით)</b>
        <hr>

        <div id="amPretty" class="kv"></div>

        <div id="amUploadsWrap" style="display:none;margin-top:14px">
          <hr>
          <h2>ატვირთული ფაილები</h2>
          <div class="mini">uploads + ასევე form_data-ში აღმოჩენილი ფაილები.</div>
          <div id="amUploads"></div>
        </div>

        <div id="amBudgetWrap" style="display:none;margin-top:14px">
          <hr>
          <h2 style="margin:0 0 6px 0">ბიუჯეტი</h2>
          <div class="muted small">დამატებული ხარჯები. ჯამი ითვლება ავტომატურად.</div>

          <div class="tableScroll" style="overflow:auto;margin-top:10px">
            <table>
              <thead>
                <tr>
                  <th>კატეგორია *</th>
                  <th>აღწერა *</th>
                  <th style="width:160px">თანხა (₾) *</th>
                </tr>
              </thead>
              <tbody id="amBudgetBody"></tbody>
            </table>
          </div>

          <div class="row" style="margin-top:10px;justify-content:space-between;align-items:center">
            <div></div>
            <div class="pill open">ჯამი: <b id="amBudgetTotal">0</b> ₾</div>
          </div>
        </div>

        <div style="margin-top:14px">
          <hr>
          <b>RAW JSON (სრული)</b>
          <pre id="amData" class="small mono" style="white-space:pre-wrap;margin:8px 0 0 0"></pre>
        </div>
      </div>

      <div class="card">
        <b>მეტა (სტატუსი / ქულა / შენიშვნა)</b>

        <div class="row" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
          <div style="flex:1;min-width:240px">
            <label class="small">სტატუსი</label>
            <select id="amStatus" onchange="saveAppMetaSoon()">
              <option value="submitted">გაგზავნილი</option>
              <option value="in_review">გადახედვაში</option>
              <option value="need_clarification">დაზუსტება სჭირდება</option>
              <option value="approved">დამტკიცებული</option>
              <option value="rejected">უარყოფილი</option>
            </select>
          </div>
          <div style="flex:0 0 220px;min-width:220px">
            <label class="small">ქულა (0-100)</label>
            <input id="amRating" type="number" min="0" max="100" oninput="saveAppMetaSoon()">
          </div>
        </div>

        <div style="height:10px"></div>
        <label class="small">ადმინის შენიშვნა</label>
        <textarea id="amNote" oninput="saveAppMetaSoon()"></textarea>

        <hr>
        <div class="small">
          ⚠️ წაშლა არის <b>soft-delete</b> (ფაილები არ იშლება).
        </div>

        <div class="actions" style="margin-top:12px">
          <button class="btn bad" type="button" onclick="deleteActiveApp()">წაშლა</button>
          <button class="btn ok" type="button" onclick="closeApp()">დახურვა</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast">
  <div class="t" id="toastT">OK</div>
  <div class="d" id="toastD"></div>
</div>

<script>
const CSRF = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;
const API  = "api/grants_portal_api.php";

/* ---------- small utils ---------- */
function resolveFileUrl(path){
  if(!path) return "";
  const p = String(path).trim();
  if(!p) return "";
  if(p.startsWith("http://") || p.startsWith("https://")) return p;
  if(p.startsWith("/")) return p;
  return "../" + p.replace(/^\.?\//, "");
}
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
  console.error("ADMIN ERROR:", msg);
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
function setBtnLoading(btn, on){
  if(!btn) return;
  if(on){
    btn.dataset._txt = btn.textContent;
    btn.innerHTML = `<span class="spin"></span> იტვირთება...`;
    btn.disabled = true;
  }else{
    const old = btn.dataset._txt || btn.textContent;
    btn.textContent = old;
    btn.disabled = false;
  }
}
const __deb = new Map();
function debounce(key, fn, ms=220){
  clearTimeout(__deb.get(key));
  __deb.set(key, setTimeout(fn, ms));
}

/* ESC closes modal */
document.addEventListener("keydown", (e)=>{
  if(e.key !== "Escape") return;
  const am = document.getElementById("appModal");
  if(am?.classList.contains("show")) closeApp();
});

/* ---------- API with abort ---------- */
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
  }).catch(err=>{
    if(err?.name === "AbortError") throw err;
    throw new Error("Network error: " + (err?.message || "unknown"));
  });

  const rawText = await res.text();
  let j = null;
  try { j = JSON.parse(rawText); } catch(_){}

  if(!res.ok){
    throw new Error((j?.error) ? j.error : ("HTTP " + res.status + " — " + rawText.slice(0,180)));
  }
  if(!j || !j.ok){
    throw new Error(j?.error || "API error (bad JSON)");
  }
  return j;
}

/* ---------- URL <-> filters sync ---------- */
function getQS(){
  try{ return new URLSearchParams(location.search); }catch(_){ return new URLSearchParams(); }
}
function syncFiltersFromUrl(){
  const qs = getQS();
  const gid = Number(qs.get("grant_id") || 0);
  const st  = (qs.get("status") || "").trim();
  const q   = (qs.get("q") || "").trim();

  const aq = document.getElementById("aq");
  if(aq) aq.value = q;

  const as = document.getElementById("aStatus");
  if(as && st) as.value = st;

  return gid;
}
function syncUrlFromFilters(){
  const gid = Number(document.getElementById('aGrant').value || 0);
  const st  = document.getElementById('aStatus').value || "all";
  const q   = (document.getElementById('aq').value || "").trim();

  const qs = new URLSearchParams(location.search);
  if(gid) qs.set("grant_id", String(gid)); else qs.delete("grant_id");
  if(st && st !== "all") qs.set("status", st); else qs.delete("status");
  if(q) qs.set("q", q); else qs.delete("q");

  const url = location.pathname + (qs.toString() ? ("?" + qs.toString()) : "");
  history.replaceState(null, "", url);
}

/* ---------- Grants ---------- */
let GRANTS = [];
async function loadGrantsForApps(){
  const btn = document.getElementById("btnReload");
  try{
    setBtnLoading(btn, true);
    const j = await api("grants_list", {q:"", status:"all", sort:"new"});
    GRANTS = j.items || [];
    renderGrantSelect();
  }catch(e){
    if(e?.name !== "AbortError") showError(e.message);
  }finally{
    setBtnLoading(btn, false);
  }
}
function renderGrantSelect(){
  const sel = document.getElementById("aGrant");
  const allOpt = `<option value="0">ყველა გრანტი</option>`;
  const opts = (GRANTS || []).map(g => `<option value="${Number(g.id)}">${esc(g.title)}</option>`).join("");
  sel.innerHTML = allOpt + (opts || '');

  const gidFromUrl = syncFiltersFromUrl();
  if(gidFromUrl && GRANTS.some(x => Number(x.id) === gidFromUrl)){
    sel.value = String(gidFromUrl);
  }else{
    if(!sel.value) sel.value = "0";
  }
}

/* ---------- Applications list ---------- */
let APPS = [];
let ACTIVE_APP_ID = 0;

/* NEW: cache field labels per grant */
const FIELD_LABELS = new Map(); // grant_id -> { field_89: "სახელი", ... }
function looksLikeFieldKey(k){
  const s = String(k || "");
  return /^field_\d+$/i.test(s) || /^f_\d+$/i.test(s) || /^\d+$/.test(s);
}
function labelForKey(grantId, key){
  const map = FIELD_LABELS.get(Number(grantId));
  if(map && key in map) return map[key];
  return key; // fallback
}
async function ensureFieldLabels(grantId){
  grantId = Number(grantId || 0);
  if(!grantId) return;
  if(FIELD_LABELS.has(grantId)) return;

  try{
    const j = await api("grant_fields_map", {grant_id: grantId});
    FIELD_LABELS.set(grantId, j.map || {});
  }catch(e){
    // no hard fail; just fallback to raw keys
    console.warn("No fields map:", e?.message);
    FIELD_LABELS.set(grantId, {});
  }
}

function statusPill(status){
  const s = (status || "submitted");
  const cls = "statusPill " + escAttr(s);
  const dot =
    (s === "approved") ? "ok" :
    (s === "rejected") ? "bad" :
    (s === "in_review" || s === "need_clarification") ? "warn" :
    "ac";
  const txt =
    (s === "approved") ? "დამტკიცებული" :
    (s === "rejected") ? "უარყოფილი" :
    (s === "in_review") ? "გადახედვაში" :
    (s === "need_clarification") ? "დაზუსტება სჭირდება" :
    "გაგზავნილი";
  return `<span class="${cls}"><span class="dot ${dot}"></span>${esc(txt)}</span>`;
}

async function loadApps(withLoading){
  const grant_id = Number(document.getElementById('aGrant').value || 0); // 0 => ALL
  const q = (document.getElementById('aq').value || '').trim();
  const status = document.getElementById('aStatus').value;

  try{
    const j = await api("apps_list", {grant_id, q, status});
    APPS = j.items || [];
    document.getElementById("countApps").textContent = String(APPS.length);

    renderApps();

    // If specific grant selected, preload labels for better modal view
    if(grant_id) ensureFieldLabels(grant_id);
  }catch(e){
    if(e?.name !== "AbortError") showError(e.message);
  }
}

function renderApps(){
  const tb = document.getElementById('aBody');
  const cards = document.getElementById('cards');
  const kpi = {
    total: APPS.length,
    submitted: 0,
    in_review: 0,
    need_clarification: 0,
    approved: 0,
    rejected: 0
  };

  (APPS || []).forEach(a => {
    const st = (a.status || 'submitted');
    if (st in kpi) kpi[st] += 1;
  });

  document.getElementById('kpi_total').textContent = String(kpi.total);
  document.getElementById('kpi_submitted').textContent = String(kpi.submitted);
  document.getElementById('kpi_review').textContent = String(kpi.in_review);
  document.getElementById('kpi_clarify').textContent = String(kpi.need_clarification);
  document.getElementById('kpi_approved').textContent = String(kpi.approved);
  document.getElementById('kpi_rejected').textContent = String(kpi.rejected);

  const rows = (APPS || []).map(a=>`
    <tr>
      <td><b>${Number(a.id)}</b><div class="small">${esc(a.created_at || '')}</div></td>
      <td>
        <div class="stack">
          <b>${esc(a.grant_title || ('#'+(a.grant_id ?? '')) || '-')}</b>
          <span class="subtle">grant_id: ${esc(String(a.grant_id ?? ''))}</span>
        </div>
      </td>
      <td>
        <div class="stack">
          <b>${esc(a.applicant_name || '-')}</b>
          <span class="subtle">${esc(a.email || '—')}</span>
        </div>
      </td>
      <td>
        <div class="stack">
          <span class="tag">📧 ${esc(a.email || '—')}</span>
          <span class="tag">📞 ${esc(a.phone || '—')}</span>
        </div>
      </td>
      <td>${statusPill(a.status)}</td>
      <td><b>${Number(a.rating || 0)}</b></td>
      <td>
        <div class="actions">
          <button class="btn ghost" type="button" onclick="openApp(${Number(a.id)}, ${Number(a.grant_id||0)})">გახსნა</button>
          <button class="btn bad" type="button" onclick="deleteApp(${Number(a.id)})">წაშლა</button>
        </div>
      </td>
    </tr>
  `).join('');

  tb.innerHTML = rows || `<tr><td colspan="7" class="small">განაცხადი არ არის. სცადე „ყველა გრანტი“ + სტატუსი „ყველა“.</td></tr>`;

  // mobile cards
  const cardsHtml = (APPS || []).map(a=>`
    <div class="card">
      <div class="appCard">
        <div class="appCardLeft">
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <b>#${Number(a.id)}</b>
            ${statusPill(a.status)}
          </div>
          <div class="small" style="margin-top:6px">${esc(a.created_at || '')}</div>
          <div style="margin-top:10px">
            <div><b>${esc(a.grant_title || ('#'+(a.grant_id ?? '')) || '-')}</b></div>
            <div class="small">grant_id: ${esc(String(a.grant_id ?? ''))}</div>
          </div>

            <div class="kvmini">
              <div><span class="k">განმცხადებელი:</span> <span class="v">${esc(a.applicant_name || '-')}</span></div>
              <div><span class="k">ქულა:</span> <span class="v">${Number(a.rating || 0)}</span></div>
              <div><span class="k">ელ.ფოსტა:</span> <span class="v">${esc(a.email || '—')}</span></div>
              <div><span class="k">ტელეფონი:</span> <span class="v">${esc(a.phone || '—')}</span></div>
            </div>
        </div>
        <div class="appCardRight">
          <button class="btn ghost" type="button" onclick="openApp(${Number(a.id)}, ${Number(a.grant_id||0)})">გახსნა</button>
          <button class="btn bad" type="button" onclick="deleteApp(${Number(a.id)})">წაშლა</button>
        </div>
      </div>
    </div>
  `).join("");

  cards.innerHTML = cardsHtml || `<div class="card small">განაცხადი არ არის. სცადე „ყველა გრანტი“ + სტატუსი „ყველა“.</div>`;
}

/* Applicant type */
function normalizeApplicantType(v){
  if(v == null) return null;
  const s = String(v).trim().toLowerCase();
  if(s === "person" || s === "individual" || s === "physical") return "person";
  if(s === "org" || s === "organization" || s === "organisation" || s === "company") return "org";
  if(s.includes("ფიზ") || s.includes("პირ")) return "person";
  if(s.includes("ორგ") || s.includes("კომპ") || s.includes("იურიდ")) return "org";
  if(s === "1") return "person";
  if(s === "2") return "org";
  return null;
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
function findApplicantTypeDeep(obj, depth=0){
  if(depth > 6) return null;
  obj = parseJsonMaybe(obj);
  if(!obj || typeof obj !== "object") return null;

  const keys = ["applicant_type","applicantType","type","person_type","org_type","applicant","applicanttype"];
  for(const k of keys){
    if(k in obj){
      const t = normalizeApplicantType(obj[k]);
      if(t) return t;
    }
  }
  for(const [k,v] of Object.entries(obj)){
    const kk = String(k).toLowerCase();
    if(kk.includes("განმც") || kk.includes("ტიპ") || kk.includes("type")){
      const t = normalizeApplicantType(v);
      if(t) return t;
    }
  }
  for(const v of Object.values(obj)){
    if(v && typeof v === "object"){
      const t = findApplicantTypeDeep(v, depth+1);
      if(t) return t;
    }else{
      const t = normalizeApplicantType(v);
      if(t) return t;
    }
  }
  return null;
}
function renderApplicantTypePill(formData){
  const pill = document.getElementById("amTypePill");
  if(!pill) return;
  const t = findApplicantTypeDeep(formData);
  if(!t){
    pill.style.display = "none";
    pill.textContent = "";
    pill.classList.remove("person","org");
    return;
  }
  pill.style.display = "inline-flex";
  pill.classList.remove("person","org");
  pill.classList.add(t);
  pill.textContent = (t === "org") ? "ორგანიზაცია" : "ფიზიკური პირი";
}

/* Budget */
function fmtMoney(n){
  const x = Number(n || 0);
  try{ return x.toLocaleString("ka-GE"); }catch(_){ return String(x); }
}
function looksLikeBudgetRow(r){
  if(!r || typeof r !== "object") return false;
  const a = Number(r.amount ?? r.sum ?? r.total ?? 0);
  const cat = (r.cat ?? r.category ?? "").toString().trim();
  const desc = (r.desc ?? r.description ?? "").toString().trim();
  return (cat || desc) && !Number.isNaN(a);
}
function deepFindBudgetRows(obj, depth=0){
  if(depth > 6) return null;
  obj = parseJsonMaybe(obj);
  if(!obj) return null;

  if(Array.isArray(obj)){
    if(obj.some(x => looksLikeBudgetRow(parseJsonMaybe(x)))) return obj;
    for(const v of obj){
      const r = deepFindBudgetRows(v, depth+1);
      if(r) return r;
    }
    return null;
  }
  if(typeof obj !== "object") return null;

  const b = parseJsonMaybe(obj.budget);
  if(b && typeof b === "object" && Array.isArray(b.rows)) return b.rows;

  const bt = parseJsonMaybe(obj.budget_table);
  if(bt && typeof bt === "object" && Array.isArray(bt.rows)) return bt.rows;

  if(Array.isArray(obj.rows) && obj.rows.some(x => looksLikeBudgetRow(parseJsonMaybe(x)))) return obj.rows;

  for(const [k,v] of Object.entries(obj)){
    const kk = String(k).toLowerCase();
    if(kk.includes("budget") || kk.includes("ბიუჯ")){
      const r = deepFindBudgetRows(v, depth+1);
      if(r) return r;
    }
  }
  for(const v of Object.values(obj)){
    const r = deepFindBudgetRows(v, depth+1);
    if(r) return r;
  }
  return null;
}
function showBudgetInModal(formData){
  const wrap = document.getElementById("amBudgetWrap");
  const body = document.getElementById("amBudgetBody");
  const totalEl = document.getElementById("amBudgetTotal");
  if(!wrap || !body || !totalEl) return;

  const rows = deepFindBudgetRows(formData);
  if(!rows || !rows.length){
    wrap.style.display = "none";
    body.innerHTML = "";
    totalEl.textContent = "0";
    return;
  }

  const norm = rows.map(r=>{
    r = parseJsonMaybe(r) || {};
    return {
      cat: (r.cat ?? r.category ?? "").toString(),
      desc: (r.desc ?? r.description ?? "").toString(),
      amount: Number(r.amount ?? r.sum ?? r.total ?? 0)
    };
  }).filter(r => (r.cat || r.desc || Number(r.amount||0) > 0));

  const total = norm.reduce((s,r)=>s + Number(r.amount||0), 0);

  body.innerHTML = norm.map(r=>`
    <tr>
      <td><b>${esc(r.cat || "-")}</b></td>
      <td>${esc(r.desc || "-")}</td>
      <td><b>${fmtMoney(r.amount)}</b></td>
    </tr>
  `).join("") || `<tr><td colspan="3" class="small">ბიუჯეტის ჩანაწერი არ არის.</td></tr>`;

  totalEl.textContent = fmtMoney(total);
  wrap.style.display = "block";
}

/* Pretty view with label mapping */
function valToText(v){
  if(v === null || v === undefined) return "";
  if(typeof v === "string") return v;
  if(typeof v === "number" || typeof v === "boolean") return String(v);
  try{ return JSON.stringify(v); }catch(_){ return String(v); }
}
function flattenObject(obj, prefix="", out=[]){
  obj = parseJsonMaybe(obj);
  if(obj === null || obj === undefined) return out;

  if(Array.isArray(obj)){
    out.push([prefix || "items", `(${obj.length})`]);
    obj.forEach((v,i)=> flattenObject(v, (prefix ? prefix+"." : "") + (i+1), out));
    return out;
  }
  if(typeof obj === "object"){
    for(const [k,v] of Object.entries(obj)){
      const key = prefix ? `${prefix}.${k}` : k;
      if(v && typeof v === "object"){
        if(String(k).toLowerCase().includes("budget") || String(k).includes("ბიუჯ")){
          out.push([key, "[object]"]);
        }else{
          flattenObject(v, key, out);
        }
      }else{
        out.push([key, valToText(v)]);
      }
    }
    return out;
  }
  out.push([prefix || "value", valToText(obj)]);
  return out;
}
function prettifyKey(grantId, key){
  // If key is nested (a.b.c), try map last token if it matches field_x
  const k = String(key || "");
  const parts = k.split(".");
  const last = parts[parts.length - 1] || k;

  if(looksLikeFieldKey(last)){
    const label = labelForKey(grantId, last);
    // show label + original key so admin can debug
    if(label && label !== last){
      return `${label}  ·  (${last})`;
    }
  }
  // also map direct keys if exists
  const label2 = labelForKey(grantId, k);
  if(label2 && label2 !== k) return `${label2}  ·  (${k})`;

  return k;
}
function renderPretty(formData, app){
  const box = document.getElementById("amPretty");
  if(!box) return;

  const grantId = Number(app?.grant_id || 0);

  const rows = [];
  if(app){
    rows.push(["ID", String(app.id)]);
    rows.push(["გრანტი", (app.grant_title ? app.grant_title : ("#" + app.grant_id))]);
    if(app.applicant_name) rows.push(["განმცხადებელი", app.applicant_name]);
    if(app.email) rows.push(["ელ.ფოსტა", app.email]);
    if(app.phone) rows.push(["ტელეფონი", app.phone]);
  }

  const flat = flattenObject(formData, "", []);
  const seen = new Set();
  for(const [k,v] of flat){
    const kk = String(k||"").trim();
    if(!kk) continue;
    if(seen.has(kk)) continue;
    seen.add(kk);

    rows.push([prettifyKey(grantId, kk), v]);
  }

  box.innerHTML = rows.map(([k,v])=>`
    <div class="k">${esc(k)}</div>
    <div class="v">${esc(v || "—")}</div>
  `).join("");
}

/* Files */
function bytesToSize(bytes){
  const b = Number(bytes||0);
  if(!b) return "0 B";
  const units = ["B","KB","MB","GB","TB"];
  let i=0, n=b;
  while(n>=1024 && i<units.length-1){ n/=1024; i++; }
  return (i===0? n.toFixed(0) : n.toFixed(2)) + " " + units[i];
}
function looksLikeFileValue(v){
  if(v == null) return false;
  if(typeof v === "string"){
    const s = v.toLowerCase();
    return s.includes("uploads/") || s.includes("upload/") || s.endsWith(".pdf") || s.endsWith(".doc") || s.endsWith(".docx") ||
           s.endsWith(".jpg") || s.endsWith(".jpeg") || s.endsWith(".png") || s.endsWith(".zip");
  }
  if(typeof v === "object"){
    const keys = Object.keys(v).map(x=>x.toLowerCase());
    const hasPath = keys.some(k => k.includes("path") || k.includes("file") || k.includes("url"));
    return hasPath;
  }
  return false;
}
function extractFilesDeep(obj, acc=[], depth=0, keyPath=""){
  if(depth > 7) return acc;
  obj = parseJsonMaybe(obj);
  if(obj == null) return acc;

  if(Array.isArray(obj)){
    obj.forEach((v,i)=> extractFilesDeep(v, acc, depth+1, keyPath ? (keyPath+"."+String(i+1)) : String(i+1)));
    return acc;
  }
  if(typeof obj === "object"){
    for(const [k,v] of Object.entries(obj)){
      const kp = keyPath ? (keyPath+"."+k) : k;
      const kk = String(k).toLowerCase();
      const keyHints = (kk.includes("file") || kk.includes("upload") || kk.includes("attachment") || kk.includes("doc") || kk.includes("ფაი") || kk.includes("ატვირთ"));
      if(keyHints && looksLikeFileValue(v)){
        if(typeof v === "string"){
          acc.push({file_path: v, field_label: kp, original_name: v.split("/").pop()});
        }else if(v && typeof v === "object"){
          const file_path =
            v.file_path ?? v.path ?? v.url ?? v.href ?? v.file ?? v.filename ?? v.name ?? v.location ?? "";
          acc.push({
            file_path,
            field_label: kp,
            original_name: v.original_name ?? v.name ?? v.filename ?? (String(file_path||"").split("/").pop()),
            size_bytes: v.size_bytes ?? v.size ?? null,
            mime_type: v.mime_type ?? v.mime ?? null
          });
        }
      }else{
        extractFilesDeep(v, acc, depth+1, kp);
      }
    }
    return acc;
  }
  return acc;
}
function uniqUploads(arr){
  const out = [];
  const seen = new Set();
  for(const u of (arr||[])){
    const p = String(u.file_path || u.url || "").trim();
    const n = String(u.original_name || u.file_name || u.stored_name || "").trim();
    const key = (p ? p : n) + "||" + (u.field_label || "") + "||" + (u.requirement_name || "");
    if(!key.trim()) continue;
    if(seen.has(key)) continue;
    seen.add(key);
    out.push(u);
  }
  return out;
}
function renderUploads(uploads, formData){
  const wrap = document.getElementById("amUploadsWrap");
  const box  = document.getElementById("amUploads");
  const pill = document.getElementById("amFilesPill");
  if(!wrap || !box) return;

  const fromApi = Array.isArray(uploads) ? uploads : [];
  const fromForm = extractFilesDeep(formData, []);

  const merged = uniqUploads([...fromApi, ...fromForm]);

  if(pill){
    if(merged.length){
      pill.style.display = "inline-flex";
      pill.textContent = `ფაილები: ${merged.length}`;
    }else{
      pill.style.display = "none";
      pill.textContent = "";
    }
  }

  if(!merged.length){
    wrap.style.display = "none";
    box.innerHTML = "";
    return;
  }

  box.innerHTML = merged.map(u=>{
    const rawPath = (u.file_path || u.url || "").toString();
    const url = resolveFileUrl(rawPath);
    const name = u.original_name || u.file_name || u.stored_name || (rawPath ? rawPath.split("/").pop() : "file");
    const req = u.requirement_name ? ` • მოთხოვნა: ${u.requirement_name}` : "";
    const fld = u.field_label ? ` • field: ${u.field_label}` : "";
    const sz  = bytesToSize(u.size_bytes || u.size || 0);
    const mime= (u.mime_type || u.mime || "").toString();

    return `
      <div class="card" style="margin-top:10px">
        <div class="fileRow">
          <div class="fileMeta">
            <b>${esc(name)}</b>
            <span class="mini">${esc(sz)}${mime ? " • " + esc(mime) : ""}${esc(req)}${esc(fld)}</span>
          </div>
          <div class="actions">
            ${url ? `<a class="dl" href="${escAttr(url)}" target="_blank" rel="noopener">გახსნა/ჩამოტვირთვა</a>` : `<span class="mini">ბმული არ მოიძებნა</span>`}
          </div>
        </div>
        <div class="mini mono" style="margin-top:6px;opacity:.85">path: ${esc(rawPath || "-")}</div>
      </div>
    `;
  }).join("");

  wrap.style.display = "block";
}

/* ---------- open/close/save/delete ---------- */
async function openApp(id, grantIdHint=0){
  try{
    // preload labels (important!)
    const gid = Number(grantIdHint || 0);
    if(gid) await ensureFieldLabels(gid);

    const j = await api("app_get", {id, include_uploads: 1, include_files: 1});
    const a = j.app;

    // if we didn't know grant_id before
    await ensureFieldLabels(Number(a.grant_id || 0));

    ACTIVE_APP_ID = Number(a.id);

    document.getElementById('amTitle').textContent = "განაცხადი — " + a.id;
    document.getElementById('amMeta').textContent =
      `გრანტი: ${(a.grant_title || ("#" + a.grant_id))} • სტატუსი: ${a.status} • ქულა: ${a.rating} • შექმნა: ${a.created_at} • განახლდა: ${a.updated_at || "-"}`;

    document.getElementById('amStatus').value = a.status || 'submitted';
    document.getElementById('amRating').value = Number(a.rating || 0);
    document.getElementById('amNote').value = a.admin_note || '';

    const fd = a.form_data || {};
    document.getElementById('amData').textContent = JSON.stringify(fd, null, 2);

    renderApplicantTypePill(fd);
    renderPretty(fd, a);
    renderUploads(a.uploads || [], fd);
    showBudgetInModal(fd);

    document.getElementById('appModal').classList.add('show');
  }catch(e){
    if(e?.name !== "AbortError") showError(e.message);
  }
}

function closeApp(){
  document.getElementById('appModal').classList.remove('show');
  ACTIVE_APP_ID = 0;

  const pill = document.getElementById("amTypePill");
  if(pill){
    pill.style.display = "none";
    pill.textContent = "";
    pill.classList.remove("person","org");
  }
  const filesP = document.getElementById("amFilesPill");
  if(filesP){
    filesP.style.display = "none";
    filesP.textContent = "";
  }

  const wrap = document.getElementById("amBudgetWrap");
  const body = document.getElementById("amBudgetBody");
  const totalEl = document.getElementById("amBudgetTotal");
  if(wrap) wrap.style.display = "none";
  if(body) body.innerHTML = "";
  if(totalEl) totalEl.textContent = "0";

  const upWrap = document.getElementById("amUploadsWrap");
  const upBox  = document.getElementById("amUploads");
  if(upWrap) upWrap.style.display = "none";
  if(upBox) upBox.innerHTML = "";

  const pretty = document.getElementById("amPretty");
  if(pretty) pretty.innerHTML = "";
}

/* save meta throttled */
let __saveMetaT = null;
function saveAppMetaSoon(){
  clearTimeout(__saveMetaT);
  __saveMetaT = setTimeout(saveAppMeta, 350);
}
async function saveAppMeta(){
  if(!ACTIVE_APP_ID) return;
  try{
    await api("app_update_meta", {
      id: ACTIVE_APP_ID,
      status: document.getElementById('amStatus').value,
      rating: Number(document.getElementById('amRating').value || 0),
      admin_note: document.getElementById('amNote').value
    });
    debounce("apps_reload", ()=>loadApps(), 250);
  }catch(e){
    if(e?.name !== "AbortError") showError(e.message);
  }
}

async function deleteApp(id){
  if(!confirm("წაიშალოს განაცხადი? (soft-delete, ფაილები არ იშლება)")) return;
  try{ await api("app_delete", {id}); toast("წაშლილია ✅"); await loadApps(); }
  catch(e){ if(e?.name !== "AbortError") showError(e.message); }
}
async function deleteActiveApp(){
  if(!ACTIVE_APP_ID) return;
  await deleteApp(ACTIVE_APP_ID);
  closeApp();
}

/* ---------- reload/quick ---------- */
async function reloadAll(){
  await loadGrantsForApps();
  await loadApps();
}
function quickShowAll(){
  document.getElementById("aGrant").value = "0";
  document.getElementById("aStatus").value = "all";
  document.getElementById("aq").value = "";
  syncUrlFromFilters();
  loadApps();
  toast("OK", "ყველა გრანტი ჩართულია");
}

/* init */
(async function init(){
  syncFiltersFromUrl();
  await loadGrantsForApps();
  await loadApps();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
