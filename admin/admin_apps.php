<?php
declare(strict_types=1);

// ✅ Start session FIRST (require_login() often needs it)
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/config.php';
require_login();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pdo = db();

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

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
body{
  color:var(--text);
  background:
    radial-gradient(1200px 700px at 20% -10%, rgba(52,152,219,.16), transparent 55%),
    radial-gradient(1100px 650px at 110% 0%, rgba(46,204,113,.12), transparent 55%),
    var(--bg);
}

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
.appCard{display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;}
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

.box{
  width:min(1180px,100%);
  background:rgba(20,27,51,.92);
  border:1px solid var(--line);
  border-radius:16px;
  padding:14px;
  box-shadow:var(--shadow);
  max-height:92vh;
  display:flex;flex-direction:column;
  overflow:hidden;
}

.head{
  display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;
  padding-bottom:10px;
  border-bottom:1px solid var(--line);
  position:sticky; top:0;
  background:rgba(20,27,51,.92);
  z-index:5;
}
.close{width:44px;height:44px;border-radius:14px}

.modal-body{overflow:auto;padding-right:4px;flex:1;padding-top:10px}

.grid2{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:12px}
@media(max-width:1200px){.grid2{grid-template-columns:1fr}}

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

.summaryGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:8px 0 12px}
@media(max-width:1200px){.summaryGrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.summaryGrid{grid-template-columns:1fr}}
.summaryCard{background:rgba(24,32,65,.72);border:1px solid var(--line);border-radius:12px;padding:10px}
.summaryLabel{font-size:11px;color:var(--muted);font-weight:800}
.summaryValue{font-size:14px;font-weight:900;margin-top:4px}
.summaryMeta{margin-top:6px}
.summaryMeta .tag{margin-right:6px;margin-bottom:4px}

.answersHeader{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:10px}
.answersMeta{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.answersMeta input{max-width:260px}
.answerGrid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:10px;
  max-height:520px;
  overflow:auto;
  padding-right:4px
}
.answerCard{border:1px solid var(--line);border-radius:12px;padding:10px;background:rgba(11,16,34,.45)}
.answerLabel{font-size:12px;color:rgba(207,233,255,.92);font-weight:900}
.answerValue{font-weight:800;margin-top:6px;white-space:pre-wrap;word-break:break-word}
.answerMeta{font-size:11px;color:var(--muted);margin-top:6px}

.uploadsGrid{display:grid;gap:10px;max-height:320px;overflow:auto;padding-right:4px}
.uploadCard{border:1px solid var(--line);border-radius:12px;padding:10px;background:rgba(11,16,34,.45)}
.uploadCard .mini{margin-top:6px}
.uploadHeader{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between}
.uploadCard .fileMeta b{word-break:break-word}
.uploadActions{display:flex;gap:8px;flex-wrap:wrap}

.rawToggle{border:1px dashed var(--line);border-radius:12px;padding:8px}
.rawToggle summary{cursor:pointer;font-weight:900}

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
              <th style="width:420px">ქმედებები</th>
            </tr>
          </thead>
          <tbody id="aBody"></tbody>
        </table>
      </div>

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
          <span id="amBudgetPill" class="pill open" style="display:none"></span>
        </div>
      </div>
      <button class="btn close" onclick="closeApp()" type="button">✕</button>
    </div>

    <div class="modal-body">

      <div class="summaryGrid">
        <div class="summaryCard">
          <div class="summaryLabel">გრანტი</div>
          <div class="summaryValue" id="amGrantTitle">—</div>
          <div class="summaryMeta small" id="amGrantMeta"></div>
        </div>
        <div class="summaryCard">
          <div class="summaryLabel">განმცხადებელი</div>
          <div class="summaryValue" id="amApplicantName">—</div>
          <div class="summaryMeta small" id="amApplicantMeta"></div>
        </div>
        <div class="summaryCard">
          <div class="summaryLabel">სტატუსი</div>
          <div class="summaryValue" id="amStatusText">—</div>
          <div class="summaryMeta small" id="amTimeline"></div>
        </div>
        <div class="summaryCard">
          <div class="summaryLabel">ბიუჯეტი</div>
          <div class="summaryValue" id="amBudgetSummary">—</div>
          <div class="summaryMeta small" id="amBudgetSummaryMeta"></div>
        </div>
      </div>

      <div class="grid2">

        <!-- LEFT -->
        <div class="card">
          <div class="answersHeader">
            <b>ფორმის პასუხები</b>
            <div class="answersMeta">
              <input id="amSearch" placeholder="ძებნა პასუხებში..." oninput="filterPretty()">
              <span class="pill" id="amAnswerCount">0</span>
            </div>
          </div>

          <div id="amPretty" class="answerGrid"></div>

          <div id="amExactWrap" style="display:none;margin-top:14px">
            <hr>
            <h2 style="margin:0 0 6px 0">ზუსტი შენახული ველები (როგორც გაიგზავნა)</h2>
            <div class="mini">ყველა top-level ველი ნაჩვენებია ზუსტი მნიშვნელობით.</div>
            <div style="overflow:auto;margin-top:10px">
              <table>
                <thead>
                  <tr>
                    <th style="min-width:180px">ველი</th>
                    <th style="min-width:140px">key</th>
                    <th>მნიშვნელობა</th>
                  </tr>
                </thead>
                <tbody id="amExactBody"></tbody>
              </table>
            </div>
          </div>

          <div id="amUploadsWrap" style="display:none;margin-top:14px">
            <hr>
            <h2>ატვირთული ფაილები</h2>
            <div class="mini">uploads + ასევე form_data-ში აღმოჩენილი ფაილები.</div>
            <div id="amUploads" class="uploadsGrid"></div>
          </div>

          <div id="amBudgetWrap" style="display:none;margin-top:14px">
            <hr>
            <h2 style="margin:0 0 6px 0">ბიუჯეტი</h2>
            <div class="muted small">დამატებული ხარჯები. ჯამი ითვლება ავტომატურად.</div>

            <div style="overflow:auto;margin-top:10px">
              <table>
                <thead>
                  <tr>
                    <th>კატეგორია</th>
                    <th>აღწერა</th>
                    <th style="width:160px">თანხა (₾)</th>
                  </tr>
                </thead>
                <tbody id="amBudgetBody"></tbody>
              </table>
            </div>

            <div class="row" style="margin-top:10px;display:flex;justify-content:space-between;align-items:center">
              <div></div>
              <div class="pill open">ჯამი: <b id="amBudgetTotal">0</b> ₾</div>
            </div>
          </div>

          <div style="margin-top:14px">
            <hr>
            <details class="rawToggle">
              <summary>RAW JSON (სრული)</summary>
              <pre id="amData" class="small mono" style="white-space:pre-wrap;margin:8px 0 0 0"></pre>
            </details>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="card" style="position:sticky;top:0;align-self:start">
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
            <a class="btn ac" id="amWordBtn" href="#" target="_blank" rel="noopener">Word</a>
            <button class="btn bad" type="button" onclick="deleteActiveApp()">წაშლა</button>
            <button class="btn ok" type="button" onclick="closeApp()">დახურვა</button>
          </div>
        </div>

      </div><!-- /grid2 -->
    </div><!-- /modal-body -->
  </div><!-- /box -->
</div><!-- /modal -->

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
    btn.dataset._html = btn.innerHTML;
    btn.innerHTML = `<span class="spin"></span> იტვირთება...`;
    btn.disabled = true;
  }else{
    btn.innerHTML = btn.dataset._html || btn.innerHTML;
    btn.disabled = false;
    delete btn.dataset._html;
  }
}
const __deb = new Map();
function debounce(key, fn, ms=220){
  clearTimeout(__deb.get(key));
  __deb.set(key, setTimeout(fn, ms));
}

function exportWordUrl(id) {
  const appId = Number(id || 0);
  if (!appId) return "#";
  return "./export_application_word.php?id=" + encodeURIComponent(String(appId));
}

function extractBudgetSummary(formData, rowsHint = null, budgetPayloads = []) {
  const gid = Number(window.__activeGrantIdForBudget || 0);
  let rows = null;

  if (Array.isArray(rowsHint) && rowsHint.length) {
    rows = rowsHint;
  }

  if (!rows && Array.isArray(budgetPayloads) && budgetPayloads.length) {
    for (const item of budgetPayloads) {
      const value = parseJsonMaybe(item?.value);
      const found = rowsFromBudgetValue(value);
      if (Array.isArray(found) && found.length) {
        rows = found;
        break;
      }
    }
  }

  if (!rows) {
    const budgetValue = deepFindBudgetValue(formData, 0, gid);
    const found = rowsFromBudgetValue(budgetValue);
    if (Array.isArray(found) && found.length) {
      rows = found;
    }
  }

  if (!rows) {
    const found = deepFindBudgetRows(formData, 0, gid);
    if (Array.isArray(found) && found.length) {
      rows = found;
    }
  }

  if (!Array.isArray(rows) || !rows.length) {
    return {
      hasBudget: false,
      total: 0,
      rowsCount: 0,
      rows: []
    };
  }

  const norm = rows
    .map((r) => normalizeBudgetRow(r))
    .filter((r) => r && r.hasContent);

  const total = norm.reduce((sum, row) => {
    return sum + Number(row.amount || 0);
  }, 0);

  return {
    hasBudget: norm.length > 0,
    total: total,
    rowsCount: norm.length,
    rows: norm
  };
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

/* cache field labels per grant */
const FIELD_LABELS = new Map(); // grant_id -> { field_89: "სახელი", ... }
const FIELD_TYPES  = new Map(); // grant_id -> { field_89: "text"|"budget_table"|... }
function looksLikeFieldKey(k){
  const s = String(k || "");
  return /^field_\d+$/i.test(s) || /^f_\d+$/i.test(s) || /^\d+$/.test(s);
}
function normalizeFieldKey(k){
  const s = String(k || "").trim();
  if(/^\d+$/.test(s)) return `field_${s}`;
  if(/^f_\d+$/i.test(s)) return `field_${s.replace(/^\D+_/, "")}`;
  if(/^field_\d+$/i.test(s)) return s;
  return s;
}
function fallbackFieldLabel(k){
  const s = String(k || "");
  const m = s.match(/(\d+)/);
  if(m) return `ველი #${m[1]}`;
  return s || "ველი";
}
function labelForKey(grantId, key){
  const map = FIELD_LABELS.get(Number(grantId));
  const norm = normalizeFieldKey(key);
  if(map && norm in map){
    const lbl = String(map[norm] ?? "").trim();
    return lbl || fallbackFieldLabel(norm);
  }
  if(looksLikeFieldKey(norm)) return fallbackFieldLabel(norm);
  return norm;
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
function getSubmissionMeta(formData){
  if(!formData || typeof formData !== "object") return null;
  const meta = formData.__meta;
  if(!meta) return null;
  return (typeof meta === "string") ? parseJsonMaybe(meta) : meta;
}
async function ensureFieldLabels(grantId){
  grantId = Number(grantId || 0);
  if(!grantId) return;
  if(FIELD_LABELS.has(grantId) && FIELD_TYPES.has(grantId)) return;
  try{
    const j = await api("grant_fields_map", {grant_id: grantId});
    FIELD_LABELS.set(grantId, j.map || {});

    const types = {};
    const fields = (j && typeof j.fields === "object" && j.fields) ? j.fields : {};
    for(const [k,v] of Object.entries(fields)){
      const nk = normalizeFieldKey(k);
      types[nk] = String(v?.type || "").toLowerCase();
    }
    FIELD_TYPES.set(grantId, types);
  }catch(e){
    console.warn("No fields map:", e?.message);
    FIELD_LABELS.set(grantId, {});
    FIELD_TYPES.set(grantId, {});
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
function statusText(status){
  const s = (status || "submitted");
  return (s === "approved") ? "დამტკიცებული" :
    (s === "rejected") ? "უარყოფილი" :
    (s === "in_review") ? "გადახედვაში" :
    (s === "need_clarification") ? "დაზუსტება სჭირდება" :
    "გაგზავნილი";
}

async function loadApps(){
  const grant_id = Number(document.getElementById('aGrant').value || 0);
  const q = (document.getElementById('aq').value || '').trim();
  const status = document.getElementById('aStatus').value;

  try{
    const j = await api("apps_list", {grant_id, q, status});
    APPS = j.items || [];
    document.getElementById("countApps").textContent = String(APPS.length);

    renderApps();

    if(grant_id) ensureFieldLabels(grant_id);
  }catch(e){
    if(e?.name !== "AbortError") showError(e.message);
  }
}

function renderApps(){
  const tb = document.getElementById('aBody');
  const cards = document.getElementById('cards');
  const kpi = { total: APPS.length, submitted: 0, in_review: 0, need_clarification: 0, approved: 0, rejected: 0 };

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
          <a class="btn ac" href="${exportWordUrl(Number(a.id))}" target="_blank" rel="noopener">Word</a>
          <button class="btn bad" type="button" onclick="deleteApp(${Number(a.id)})">წაშლა</button>
        </div>
      </td>
    </tr>
  `).join('');

  tb.innerHTML = rows || `<tr><td colspan="7" class="small">განაცხადი არ არის. სცადე „ყველა გრანტი“ + სტატუსი „ყველა“.</td></tr>`;

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
          <a class="btn ac" href="${exportWordUrl(Number(a.id))}" target="_blank" rel="noopener">Word</a>
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
  const meta = getSubmissionMeta(formData);
  if(meta && meta.applicant_type){
    const t = normalizeApplicantType(meta.applicant_type);
    if(t){
      pill.style.display = "inline-flex";
      pill.classList.remove("person","org");
      pill.classList.add(t);
      pill.textContent = (t === "org") ? "ორგანიზაცია" : "ფიზიკური პირი";
      return;
    }
  }
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

  const entries = Object.entries(r)
    .filter(([k]) => String(k) !== "__meta")
    .map(([k,v]) => [String(k), parseJsonMaybe(v)]);

  if(!entries.length) return false;

  const hasScalar = entries.some(([,v]) => {
    if(v === null || v === undefined) return false;
    if(typeof v === "string") return v.trim() !== "";
    if(typeof v === "number") return !Number.isNaN(v);
    if(typeof v === "boolean") return true;
    return false;
  });

  return hasScalar;
}

function findBudgetAmountKey(rowObj){
  if(!rowObj || typeof rowObj !== "object") return "";
  const keys = Object.keys(rowObj);
  const byName = keys.find(k => /(^|_)(amount|sum|total|price|cost)(_|$)|თანხ/i.test(String(k)));
  if(byName) return byName;
  const byNumeric = keys.find(k => {
    const v = parseMoneyLikeNumber(rowObj[k]);
    return v !== null;
  });
  return byNumeric || "";
}

function parseMoneyLikeNumber(v){
  if(v === null || v === undefined) return null;
  if(typeof v === "number") return Number.isFinite(v) ? v : null;
  if(typeof v === "string"){
    const raw = v.trim();
    if(!raw) return null;
    const normalized = raw
      .replace(/\s+/g, "")
      .replace(/₾|₺|\$/g, "")
      .replace(/,/g, "")
      .replace(/[^0-9.\-]/g, "");
    if(!normalized || normalized === "-" || normalized === ".") return null;
    const n = Number(normalized);
    return Number.isFinite(n) ? n : null;
  }
  return null;
}

function normalizeBudgetRow(rowObj){
  rowObj = parseJsonMaybe(rowObj);
  if(!rowObj || typeof rowObj !== "object") return null;

  const amountKey = findBudgetAmountKey(rowObj);
  const amount = amountKey ? (parseMoneyLikeNumber(rowObj[amountKey]) ?? 0) : 0;

  const keys = Object.keys(rowObj).filter(k => String(k) !== "__meta");
  const catKey = keys.find(k => /(^|_)(cat|category|item|name|title)(_|$)|კატეგ|დასახელებ/i.test(String(k)));
  const descKey = keys.find(k => /(^|_)(desc|description|details|note)(_|$)|აღწერ|დეტალ/i.test(String(k)));

  const cat = catKey ? String(rowObj[catKey] ?? "").trim() : "";
  const desc = descKey ? String(rowObj[descKey] ?? "").trim() : "";

  const extras = [];
  let hasContent = false;
  for(const k of keys){
    if(k === amountKey || k === catKey || k === descKey) continue;
    const v = rowObj[k];
    if(v === null || v === undefined) continue;
    const txt = (typeof v === "string") ? v.trim() : (typeof v === "object" ? JSON.stringify(v) : String(v));
    if(!txt) continue;
    hasContent = true;
    extras.push(`${k}: ${txt}`);
  }

  if(cat) hasContent = true;
  if(desc) hasContent = true;
  if(amountKey) hasContent = true;

  return {
    cat,
    desc: desc || extras.join(" • "),
    amount: Number.isFinite(amount) ? amount : 0,
    hasContent,
  };
}

function looksLikeBudgetValue(v){
  v = parseJsonMaybe(v);
  if(!v) return false;

  if(typeof v === "object" && !Array.isArray(v) && Array.isArray(v.rows)){
    return v.rows.some(x => looksLikeBudgetRow(parseJsonMaybe(x)));
  }

  if(Array.isArray(v)){
    return v.some(x => looksLikeBudgetRow(parseJsonMaybe(x)));
  }

  return false;
}

function isBudgetFieldType(t){
  const s = String(t || "").toLowerCase();
  return s === "budget_table" || s === "budget" || s.includes("budget");
}

function fieldTypeForKey(grantId, key){
  const map = FIELD_TYPES.get(Number(grantId)) || {};
  return String(map[normalizeFieldKey(key)] || "").toLowerCase();
}

function isLikelyBudgetRowShape(rowObj){
  rowObj = parseJsonMaybe(rowObj);
  if(!rowObj || typeof rowObj !== "object") return false;

  const keys = Object.keys(rowObj).filter(k => String(k) !== "__meta");
  if(!keys.length) return false;

  const hasBudgetKeyHints = keys.some(k => /budget|ბიუჯ|amount|sum|total|price|cost|თანხ|კატეგ|desc|description|დასახელებ|აღწერ/i.test(String(k)));

  let scalarCount = 0;
  let numericCount = 0;
  for(const k of keys){
    const v = rowObj[k];
    if(v === null || v === undefined) continue;
    if(typeof v === "string"){
      const t = v.trim();
      if(!t) continue;
      scalarCount += 1;
      const n = Number(t.replace(/,/g, ""));
      if(Number.isFinite(n)) numericCount += 1;
      continue;
    }
    if(typeof v === "number"){
      if(!Number.isFinite(v)) continue;
      scalarCount += 1;
      numericCount += 1;
      continue;
    }
    if(typeof v === "boolean"){
      scalarCount += 1;
      continue;
    }
  }

  return hasBudgetKeyHints || (numericCount >= 1 && scalarCount >= 2);
}

function normalizeBudgetColumns(cols){
  const arr = Array.isArray(cols) ? cols : [];
  const out = [];
  for(const c of arr){
    if(!c) continue;
    if(typeof c === "string"){
      const key = c.trim();
      if(!key) continue;
      out.push({ key, label: key });
      continue;
    }
    if(typeof c === "object"){
      const key = String(c.key ?? c.name ?? c.field ?? c.id ?? "").trim();
      const label = String(c.label ?? c.title ?? key).trim();
      if(!key) continue;
      out.push({ key, label: label || key });
    }
  }
  return out;
}

function rowsFromColumnsAndRows(valueObj){
  if(!valueObj || typeof valueObj !== "object") return null;
  const cols = normalizeBudgetColumns(valueObj.columns);
  const rows = Array.isArray(valueObj.rows) ? valueObj.rows : null;
  if(!cols.length || !rows || !rows.length) return null;

  const mapped = rows.map(r=>{
    const rv = parseJsonMaybe(r);
    if(Array.isArray(rv)){
      const obj = {};
      cols.forEach((c,i)=>{ obj[c.key] = rv[i] ?? ""; });
      return obj;
    }
    return rv;
  }).filter(Boolean);

  return mapped.length ? mapped : null;
}

function columnsFromBudgetValue(v){
  const pv = parseJsonMaybe(v);
  if(!pv || typeof pv !== "object") return [];
  return normalizeBudgetColumns(pv.columns);
}

function budgetValueLooksUsable(v){
  return !!rowsFromBudgetValue(v);
}

function deepFindBudgetValue(obj, depth=0, grantId=0){
  if(depth > 7) return null;
  obj = parseJsonMaybe(obj);
  if(!obj) return null;

  if(Array.isArray(obj)){
    if(budgetValueLooksUsable(obj)) return obj;
    for(const v of obj){
      const r = deepFindBudgetValue(v, depth+1, grantId);
      if(r) return r;
    }
    return null;
  }

  if(typeof obj !== "object") return null;

  const candidates = [];
  if(obj.budget !== undefined) candidates.push(obj.budget);
  if(obj.budget_table !== undefined) candidates.push(obj.budget_table);
  if(obj.rows !== undefined) candidates.push(obj);

  for(const [k,v] of Object.entries(obj)){
    const kk = String(k).toLowerCase();
    const isFieldKey = kk.startsWith("field_") || kk.startsWith("f_") || /^\d+$/.test(kk);
    const typedBudget = isFieldKey && isBudgetFieldType(fieldTypeForKey(grantId, kk));
    if(typedBudget || kk.includes("budget") || kk.includes("ბიუჯ") || isFieldKey){
      candidates.push(v);
    }
  }

  for(const c of candidates){
    if(budgetValueLooksUsable(c)) return c;
  }

  for(const v of Object.values(obj)){
    const r = deepFindBudgetValue(v, depth+1, grantId);
    if(r) return r;
  }

  return null;
}

function rowsFromBudgetValue(v){
  const pv = parseJsonMaybe(v);
  if(!pv) return null;

  const pickRows = (rows)=>{
    if(!Array.isArray(rows)) return null;
    const filtered = rows.filter(r => {
      const nr = normalizeBudgetRow(r);
      return !!(nr && nr.hasContent && isLikelyBudgetRowShape(r));
    });
    return filtered.length ? filtered : null;
  };

  if(Array.isArray(pv)) return pickRows(pv);
  if(typeof pv === "object"){
    const fromSchema = rowsFromColumnsAndRows(pv);
    const fromSchemaRows = pickRows(fromSchema);
    if(fromSchemaRows) return fromSchemaRows;

    const fromRows = pickRows(pv.rows);
    if(fromRows) return fromRows;

    const one = normalizeBudgetRow(pv);
    if(one && one.hasContent && isLikelyBudgetRowShape(pv)) return [pv];
  }

  return null;
}

/* ✅ NEW: deep search that also checks "field_*" values JSON */
function deepFindBudgetRows(obj, depth=0, grantId=0){
  if(depth > 7) return null;
  obj = parseJsonMaybe(obj);
  if(!obj) return null;

  if(Array.isArray(obj)){
    const directRows = rowsFromBudgetValue(obj);
    if(directRows) return directRows;
    for(const v of obj){
      const r = deepFindBudgetRows(v, depth+1, grantId);
      if(r) return r;
    }
    return null;
  }

  if(typeof obj !== "object") return null;

  const b = parseJsonMaybe(obj.budget);
  if(b && typeof b === "object" && Array.isArray(b.rows) && b.rows.some(x=>looksLikeBudgetRow(parseJsonMaybe(x)))) return b.rows;

  const bt = parseJsonMaybe(obj.budget_table);
  if(bt && typeof bt === "object" && Array.isArray(bt.rows) && bt.rows.some(x=>looksLikeBudgetRow(parseJsonMaybe(x)))) return bt.rows;

  if(Array.isArray(obj.rows) && obj.rows.some(x => looksLikeBudgetRow(parseJsonMaybe(x)))) return obj.rows;

  // ✅ case: field_123 can be budget by type OR by key hints
  for(const [k,v] of Object.entries(obj)){
    const kk = String(k).toLowerCase();
    const isFieldKey = kk.startsWith("field_") || kk.startsWith("f_") || /^\d+$/.test(kk);
    const typedBudget = isFieldKey && isBudgetFieldType(fieldTypeForKey(grantId, kk));

    if(typedBudget || kk.includes("budget") || kk.includes("ბიუჯ") || isFieldKey){
      const rows = rowsFromBudgetValue(v);
      if(rows) return rows;
    }
  }

  for(const [k,v] of Object.entries(obj)){
    const kk = String(k).toLowerCase();
    if(kk.includes("budget") || kk.includes("ბიუჯ")){
      const r = deepFindBudgetRows(v, depth+1, grantId);
      if(r) return r;
    }
  }
  for(const v of Object.values(obj)){
    const r = deepFindBudgetRows(v, depth+1, grantId);
    if(r) return r;
  }

  return null;
}

function budgetFieldKeysForGrant(grantId){
  const types = FIELD_TYPES.get(Number(grantId)) || {};
  return Object.keys(types).filter(k => isBudgetFieldType(types[k]));
}

function collectRawBudgetPayloads(formData, grantId){
  const fd = parseJsonMaybe(formData);
  if(!fd || typeof fd !== "object") return [];

  const typedKeys = new Set(budgetFieldKeysForGrant(grantId));
  const out = [];
  const seen = new Set();

  const push = (path, k, v)=>{
    const key = String(k || "");
    const pth = String(path || key || "budget");
    const pv = parseJsonMaybe(v);
    if(!pv || typeof pv !== "object") return;
    const sig = pth + "::" + JSON.stringify(pv);
    if(seen.has(sig)) return;
    seen.add(sig);
    out.push({ key: pth, value: pv });
  };

  // 1) typed budget keys from builder map (top-level fast path)
  for(const k of typedKeys){
    if(k in fd) push(k, k, fd[k]);
  }

  const walk = (obj, depth=0, path="")=>{
    if(depth > 8) return;
    obj = parseJsonMaybe(obj);
    if(!obj || typeof obj !== "object") return;

    const entries = Array.isArray(obj)
      ? obj.map((v,i)=> [String(i+1), v])
      : Object.entries(obj);

    for(const [k,v] of entries){
      const kk = String(k).toLowerCase();
      const curPath = path ? `${path}.${k}` : String(k);
      const isFieldKey = kk.startsWith("field_") || kk.startsWith("f_") || /^\d+$/.test(kk);
      const budgetishKey = kk.includes("budget") || kk.includes("ბიუჯ") || typedKeys.has(String(k));

      const pv = parseJsonMaybe(v);
      const usable = !!rowsFromBudgetValue(pv) || (!!pv && typeof pv === "object" && (Array.isArray(pv.rows) || Array.isArray(pv.columns)));

      if((isFieldKey || budgetishKey) && usable) push(curPath, k, v);

      if(pv && typeof pv === "object") walk(pv, depth+1, curPath);
    }
  };

  walk(fd, 0, "");
  return out;
}

function showBudgetInModal(formData, rowsHint=null, budgetPayloads=[]){
  const wrap = document.getElementById("amBudgetWrap");
  const body = document.getElementById("amBudgetBody");
  const totalEl = document.getElementById("amBudgetTotal");
  const pill = document.getElementById("amBudgetPill");
  if(!wrap || !body || !totalEl) return;

  const gid = Number(window.__activeGrantIdForBudget || 0);
  const hintRows = Array.isArray(rowsHint) ? rowsHint : null;
  const payloadList = Array.isArray(budgetPayloads) ? budgetPayloads : [];
  const payloadValue = payloadList.length ? parseJsonMaybe(payloadList[0].value) : null;
  const budgetValue = payloadValue || deepFindBudgetValue(formData, 0, gid);
  const valueRows = rowsFromBudgetValue(budgetValue);
  const rows = valueRows || hintRows || deepFindBudgetRows(formData, 0, gid);

  if(!rows){
    const rawPayloads = payloadList.length ? payloadList.map(x=>({key:String(x.key||x.label||"budget"), value:parseJsonMaybe(x.value)})) : collectRawBudgetPayloads(formData, gid);
    if(!rawPayloads.length){
      wrap.style.display = "none";
      body.innerHTML = "";
      totalEl.textContent = "0";
      if(pill){ pill.style.display="none"; pill.textContent=""; }
      return;
    }

    const rawRows = rawPayloads.map(x=>`
      <tr>
        <td colspan="3">
          <div class="small" style="font-weight:900;margin-bottom:6px">${esc(x.key)}</div>
          <pre class="small mono" style="white-space:pre-wrap;margin:0">${esc(JSON.stringify(x.value, null, 2))}</pre>
        </td>
      </tr>
    `).join("");

    body.innerHTML = rawRows;
    totalEl.textContent = "0";
    wrap.style.display = "block";
    if(pill){
      pill.style.display="inline-flex";
      pill.textContent = `ბიუჯეტი: მონაცემი ნაპოვნია`;
    }
    return;
  }

  const norm = rows
    .map(r => normalizeBudgetRow(r))
    .filter(r => r && r.hasContent);

  const total = norm.reduce((s,r)=>s + Number(r.amount||0), 0);
  const dynCols = columnsFromBudgetValue(budgetValue);

  if(dynCols.length){
    const header = `<tr>${dynCols.map(c=>`<th>${esc(c.label || c.key)}</th>`).join("")}</tr>`;
    const rowsHtml = rows.map(rr=>{
      const rowObj = parseJsonMaybe(rr) || {};
      return `<tr>${dynCols.map(c=>`<td>${esc(String(rowObj?.[c.key] ?? "-"))}</td>`).join("")}</tr>`;
    }).join("") || `<tr><td colspan="${dynCols.length}" class="small">ბიუჯეტის ჩანაწერი არ არის.</td></tr>`;
    body.innerHTML = header + rowsHtml;
  } else {
    body.innerHTML = norm.map(r=>`
      <tr>
        <td><b>${esc(r.cat || "-")}</b></td>
        <td>${esc(r.desc || "-")}</td>
        <td><b>${fmtMoney(r.amount)}</b></td>
      </tr>
    `).join("") || `<tr><td colspan="3" class="small">ბიუჯეტის ჩანაწერი არ არის.</td></tr>`;
  }

  totalEl.textContent = fmtMoney(total);
  wrap.style.display = "block";

  if(pill){
    pill.style.display="inline-flex";
    pill.textContent = `ბიუჯეტი: ${fmtMoney(total)} ₾`;
  }

  const rawPayloads = payloadList.length ? payloadList.map(x=>({key:String(x.key||x.label||"budget"), value:parseJsonMaybe(x.value)})) : collectRawBudgetPayloads(formData, gid);
  if(rawPayloads.length){
    body.innerHTML += rawPayloads.map(x=>`
      <tr>
        <td colspan="${dynCols.length || 3}">
          <details class="rawToggle" style="margin-top:6px">
            <summary>RAW ${esc(x.key)}</summary>
            <pre class="small mono" style="white-space:pre-wrap;margin-top:8px">${esc(JSON.stringify(x.value, null, 2))}</pre>
          </details>
        </td>
      </tr>
    `).join("");
  }
}

function extractBudgetRowsFromResolved(resolved){
  if(!Array.isArray(resolved)) return null;
  for(const row of resolved){
    const rowType = String(row?.type || "").toLowerCase();
    const rowKey = String(row?.key || "").toLowerCase();
    const keyBudget = rowKey.startsWith("field_")
      ? isBudgetFieldType(fieldTypeForKey(Number(window.__activeGrantIdForBudget || 0), rowKey))
      : false;

    if(!row || (!rowType.includes("budget") && rowType !== "budget_table" && !keyBudget)) continue;
    const val = parseJsonMaybe(row.value);
    const rows = rowsFromBudgetValue(val);
    if(rows) return rows;
  }
  return null;
}

/* Pretty view with label mapping */
function valToText(v){
  if(v === null || v === undefined) return "";
  if(typeof v === "string") return v;
  if(typeof v === "number" || typeof v === "boolean") return String(v);
  try{ return JSON.stringify(v); }catch(_){ return String(v); }
}
function normalizeAnswerValue(label, value){
  if(value == null) return value;
  if(typeof value === "string"){
    const v = value.trim().toLowerCase();
    if(label && label.includes("ტიპ") && (v === "person" || v === "individual" || v === "physical")) return "ფიზიკური პირი";
    if(label && label.includes("ტიპ") && (v === "org" || v === "organization" || v === "organisation" || v === "company")) return "ორგანიზაცია";
  }
  return value;
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
      if(String(k) === "__meta") continue;
      const key = prefix ? `${prefix}.${k}` : k;
      const pv = parseJsonMaybe(v);
      if(pv && typeof pv === "object"){
        flattenObject(pv, key, out);
      }else{
        out.push([key, valToText(v)]);
      }
    }
    return out;
  }
  out.push([prefix || "value", valToText(obj)]);
  return out;
}
function resolveLabelForKey(grantId, key){
  const metaMap = window.__activeMetaFieldLabels || null;
  const k = String(key || "");
  const parts = k.split(".");
  const last = parts[parts.length - 1] || k;

  const normLast = normalizeFieldKey(last);
  const normKey = normalizeFieldKey(k);

  const isNumericOnly = /^\d+$/.test(last);
  if(looksLikeFieldKey(last) && (!isNumericOnly || !k.includes("."))){
    const label = (metaMap && metaMap[normLast]) ? metaMap[normLast] : labelForKey(grantId, normLast);
    return label || fallbackFieldLabel(normLast);
  }

  const label2 = (metaMap && metaMap[normKey]) ? metaMap[normKey] : labelForKey(grantId, normKey);
  if(label2 && label2 !== normKey) return label2;
  return k;
}

function valueToDisplayText(v){
  if(v === null || v === undefined) return "—";
  if(typeof v === "boolean") return v ? "true" : "false";
  if(typeof v === "number") return Number.isFinite(v) ? String(v) : "—";
  const txt = String(v);
  return txt.trim() === "" ? "—" : txt;
}

function valueToPrettyJson(v){
  if(v === null || v === undefined) return "";
  if(typeof v === "string"){
    const pv = parseJsonMaybe(v);
    if(pv && typeof pv === "object"){
      try{ return JSON.stringify(pv, null, 2); }catch(_){ return String(v); }
    }
    return v;
  }
  if(typeof v === "object"){
    try{ return JSON.stringify(v, null, 2); }catch(_){ return String(v); }
  }
  return String(v);
}

function renderExactSubmitted(formData, app){
  const wrap = document.getElementById("amExactWrap");
  const body = document.getElementById("amExactBody");
  if(!wrap || !body) return;

  const fd = parseJsonMaybe(formData);
  if(!fd || typeof fd !== "object"){
    wrap.style.display = "none";
    body.innerHTML = "";
    return;
  }

  const grantId = Number(app?.grant_id || 0);
  const rows = [];
  for(const [k,v] of Object.entries(fd)){
    if(String(k) === "__meta") continue;
    const label = resolveLabelForKey(grantId, k);
    rows.push({
      key: String(k),
      label: String(label || k),
      value: valueToPrettyJson(v)
    });
  }

  rows.sort((a,b)=>a.key.localeCompare(b.key));
  if(!rows.length){
    wrap.style.display = "none";
    body.innerHTML = "";
    return;
  }

  body.innerHTML = rows.map(r=>`
    <tr>
      <td><b>${esc(r.label)}</b></td>
      <td class="small mono">${esc(r.key)}</td>
      <td><pre class="small mono" style="white-space:pre-wrap;margin:0">${esc(r.value || "—")}</pre></td>
    </tr>
  `).join("");
  wrap.style.display = "block";
}

function renderPretty(formData, app){
  const box = document.getElementById("amPretty");
  if(!box) return;
  const countEl = document.getElementById("amAnswerCount");

  const grantId = Number(app?.grant_id || 0);
  const meta = getSubmissionMeta(formData) || {};
  window.__activeMetaFieldLabels = meta.field_labels || null;

  const rows = [];
  const flat = flattenObject(formData, "", []);
  const seen = new Set();

  for(const [k,v] of flat){
    const kk = String(k||"").trim();
    if(!kk) continue;
    if(seen.has(kk)) continue;
    seen.add(kk);

    const label = resolveLabelForKey(grantId, kk);
    const normalizedValue = normalizeAnswerValue(label, v);
    rows.push({label, value: valueToDisplayText(normalizedValue), rawKey: kk});
  }

  box.innerHTML = rows.map(row=>{
    const showMeta = row.rawKey && !looksLikeFieldKey(row.rawKey) && row.rawKey !== row.label;
    return `
      <div class="answerCard" data-label="${escAttr(row.label)}" data-key="${escAttr(row.rawKey)}">
        <div class="answerLabel">${esc(row.label)}</div>
        <div class="answerValue">${esc(row.value)}</div>
        ${showMeta ? `<div class="answerMeta">path: ${esc(row.rawKey)}</div>` : ""}
      </div>
    `;
  }).join("");

  if(countEl){
    countEl.dataset.total = String(rows.length);
    countEl.textContent = String(rows.length);
  }
  filterPretty();
}

function filterPretty(){
  const input = document.getElementById("amSearch");
  const q = (input?.value || "").trim().toLowerCase();
  const cards = document.querySelectorAll("#amPretty .answerCard");
  let shown = 0;
  cards.forEach(card=>{
    const hay = `${card.dataset.label || ""} ${card.dataset.key || ""} ${card.textContent || ""}`.toLowerCase();
    const match = !q || hay.includes(q);
    card.style.display = match ? "" : "none";
    if(match) shown += 1;
  });
  const countEl = document.getElementById("amAnswerCount");
  if(countEl){
    const total = Number(countEl.dataset.total || cards.length);
    countEl.textContent = q ? `${shown}/${total}` : String(total);
  }
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
function renderUploads(uploads, formData, grantId=0){
  const wrap = document.getElementById("amUploadsWrap");
  const box  = document.getElementById("amUploads");
  const pill = document.getElementById("amFilesPill");
  if(!wrap || !box) return;

  const fromApi = Array.isArray(uploads) ? uploads : [];
  const fromForm = extractFilesDeep(formData, []);
  const meta = getSubmissionMeta(formData) || {};
  const metaFiles = [];

  const metaReqs = Array.isArray(meta?.files?.requirements) ? meta.files.requirements : [];
  const metaFields = Array.isArray(meta?.files?.fields) ? meta.files.fields : [];
  const metaOther = Array.isArray(meta?.files?.other) ? meta.files.other : [];

  metaReqs.forEach(f=>{
    metaFiles.push({
      requirement_name: f.requirement_name || "",
      requirement_id: f.requirement_id || "",
      file_path: "",
      original_name: f.original_name || "",
      size_bytes: f.size_bytes || 0,
      mime_type: f.mime_type || ""
    });
  });
  metaFields.forEach(f=>{
    metaFiles.push({
      field_label: f.field_label || "",
      field_id: f.field_id || "",
      file_path: "",
      original_name: f.original_name || "",
      size_bytes: f.size_bytes || 0,
      mime_type: f.mime_type || ""
    });
  });
  metaOther.forEach(f=>{
    metaFiles.push({
      file_path: "",
      original_name: f.original_name || "",
      size_bytes: f.size_bytes || 0,
      mime_type: f.mime_type || ""
    });
  });

  const merged = uniqUploads([...fromApi, ...fromForm, ...metaFiles]);

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
    const fieldLabel = u.field_label ? resolveLabelForKey(grantId, u.field_label) : "";
    const req = u.requirement_name ? ` • მოთხოვნა: ${u.requirement_name}` : "";
    const fld = fieldLabel ? ` • ველი: ${fieldLabel}` : "";
    const sz  = bytesToSize(u.size_bytes || u.size || 0);
    const mime= (u.mime_type || u.mime || "").toString();

    return `
      <div class="uploadCard">
        <div class="uploadHeader">
          <div class="fileMeta">
            <b>${esc(name)}</b>
            <span class="mini">${esc(sz)}${mime ? " • " + esc(mime) : ""}${esc(req)}${esc(fld)}</span>
          </div>
          <div class="uploadActions">
            ${url ? `<a class="btn ghost" href="${escAttr(url)}" target="_blank" rel="noopener">გახსნა</a>` : `<span class="mini">ბმული არ მოიძებნა</span>`}
            ${url ? `<a class="btn ghost" href="${escAttr(url)}" download>ჩამოტვირთვა</a>` : ``}
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
    const gid = Number(grantIdHint || 0);
    if(gid) await ensureFieldLabels(gid);

    const j = await api("app_get", {id, include_uploads: 1, include_files: 1});
    const a = j.app;

    await ensureFieldLabels(Number(a.grant_id || 0));

    ACTIVE_APP_ID = Number(a.id);

    const fd = a.form_data || {};
    const meta = getSubmissionMeta(fd) || {};

    document.getElementById('amTitle').textContent = "განაცხადი — " + a.id;
    document.getElementById('amMeta').textContent =
      `სტატუსი: ${statusText(a.status)} • ქულა: ${a.rating} • შექმნა: ${a.created_at} • განახლდა: ${a.updated_at || "-"}`;

    const grantTitle = (a.grant_title || ("#" + a.grant_id));
    const applicantName  = a.applicant_name || meta.applicant_name || "—";
    const applicantEmail = a.email || meta.applicant_email || "—";
    const applicantPhone = a.phone || meta.applicant_phone || "—";

    document.getElementById("amGrantTitle").textContent = grantTitle;
    document.getElementById("amGrantMeta").innerHTML = `<span class="tag muted">grant_id: ${esc(a.grant_id)}</span>`;

    document.getElementById("amApplicantName").textContent = applicantName;
    document.getElementById("amApplicantMeta").innerHTML = `
      <span class="tag">📧 ${esc(applicantEmail)}</span>
      <span class="tag">📞 ${esc(applicantPhone)}</span>
    `;

    document.getElementById("amStatusText").textContent = statusText(a.status);
    document.getElementById("amTimeline").innerHTML = `
      <span class="tag muted">ქულა: ${Number(a.rating || 0)}</span>
      <span class="tag muted">შექმნა: ${esc(a.created_at)}</span>
      <span class="tag muted">განახლდა: ${esc(a.updated_at || "-")}</span>
    `;

    document.getElementById('amStatus').value = a.status || 'submitted';
    document.getElementById('amRating').value = Number(a.rating || 0);
    document.getElementById('amNote').value   = a.admin_note || '';

    document.getElementById('amData').textContent = JSON.stringify(fd, null, 2);

    if(meta && meta.field_labels){
      FIELD_LABELS.set(Number(a.grant_id || 0), meta.field_labels);
    }
    if(meta && meta.field_types && typeof meta.field_types === "object"){
      FIELD_TYPES.set(Number(a.grant_id || 0), meta.field_types);
    }

    window.__activeGrantIdForBudget = Number(a.grant_id || 0);
    renderApplicantTypePill(fd);
    renderPretty(fd, a);
    renderExactSubmitted(fd, a);
    renderUploads(a.uploads || [], fd, Number(a.grant_id || 0));

    const budgetRowsHint = extractBudgetRowsFromResolved(a.form_data_resolved || []);
    showBudgetInModal(fd, budgetRowsHint, a.budget_payloads || []);

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
  const budP = document.getElementById("amBudgetPill");
  if(budP){
    budP.style.display = "none";
    budP.textContent = "";
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
  const search = document.getElementById("amSearch");
  if(search) search.value = "";
  const countEl = document.getElementById("amAnswerCount");
  if(countEl){
    countEl.textContent = "0";
    delete countEl.dataset.total;
  }

  const budgetSummary = document.getElementById("amBudgetSummary");
  const budgetSummaryMeta = document.getElementById("amBudgetSummaryMeta");
  if (budgetSummary) budgetSummary.textContent = "—";
  if (budgetSummaryMeta) budgetSummaryMeta.innerHTML = "";

  const wordBtn = document.getElementById("amWordBtn");
  if (wordBtn) wordBtn.href = "#";
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
