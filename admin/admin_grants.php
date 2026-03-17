<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$pdo = db();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$csrf  = $_SESSION['csrf'];
$title = 'ადმინი — გრანტების პორტალი (გრანტები)';

ob_start();
?>

<style>
:root{
  --bg:#0f1426; --panel:#141b33; --card:#182041;
  --line:rgba(255,255,255,.12); --text:#f3f5fa; --muted:rgba(243,245,250,.7);
  --ok:#2ecc71; --bad:#e74c3c; --ac:#3498db; --warn:#f1c40f; --chip:#0b1022;
  --r:14px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{color:var(--text); background:var(--bg)}
h1{margin:0;font-size:20px;font-weight:900}
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
textarea{min-height:140px;resize:vertical}

table{width:100%;border-collapse:collapse}
th,td{padding:12px 10px;border-bottom:1px solid var(--line);vertical-align:top;text-align:left}
th{color:rgba(207,233,255,.92);font-size:12px;font-weight:900}
tr:hover td{background:rgba(255,255,255,.02)}

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

.modal{position:fixed;inset:0;background:rgba(0,0,0,.62);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
.modal.show{display:flex}
.box{width:min(1100px,100%);background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:14px}
.head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.close{width:44px;height:44px;border-radius:14px}

.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:980px){.grid2{grid-template-columns:1fr}}

.spin{
  width:16px;height:16px;border-radius:999px;
  border:2px solid rgba(255,255,255,.25);border-top-color:#fff;
  animation:sp 0.7s linear infinite;
}
@keyframes sp{to{transform:rotate(360deg)}}

.nav{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px 0}
.nav .btn.active{border-color:rgba(46,204,113,.55); box-shadow:0 0 0 2px rgba(46,204,113,.16) inset}

.badge{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 10px;border-radius:999px;
  border:1px solid var(--line); background:rgba(255,255,255,.04);
  font-weight:900;font-size:12px;
}
.badge.ok{border-color:rgba(46,204,113,.45); background:rgba(46,204,113,.12)}
.badge.bad{border-color:rgba(231,76,60,.45); background:rgba(231,76,60,.12)}
.badge.warn{border-color:rgba(241,196,15,.45); background:rgba(241,196,15,.12)}

.thumb{
  width:54px;height:44px;border-radius:10px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  object-fit:cover;
}
.mini{
  display:flex;gap:10px;align-items:flex-start
}
.mini .meta{min-width:0}
.meta b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:420px}
.meta .small{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px}
.skel{
  background:linear-gradient(90deg, rgba(255,255,255,.05), rgba(255,255,255,.09), rgba(255,255,255,.05));
  background-size:200% 100%;
  animation:sh 1.1s ease-in-out infinite;
  border-radius:10px; height:14px;
}
@keyframes sh{0%{background-position:0%}100%{background-position:200%}}
</style>

<div class="wrap">
  <div>
    <h1>ადმინი — გრანტების პორტალი</h1>
    <div class="small">გრანტები • ფორმის ბილდერი • განაცხადები</div>
  </div>
  <div class="actions">
    <button class="btn ok" id="btnAddGrant" onclick="openGrantModal()">+ გრანტის დამატება</button>
    <button class="btn ghost" id="btnReload" onclick="loadGrants()">განახლება</button>
  </div>
</div>

<div class="nav">
  <a class="btn active" href="admin_grants.php">გრანტები</a>
  <a class="btn" href="admin_builder.php">ფორმის ბილდერი</a>
  <a class="btn" href="admin_apps.php">განაცხადები</a>
</div>

<div id="errBox" class="alert"></div>

<section id="grants">
  <div class="card">
    <div class="row" style="margin-bottom:10px">
      <div>
        <label class="small">ძიება</label>
        <input id="gq" placeholder="ძებნა სათაურით/slug-ით..." oninput="debounced(loadGrants)">
      </div>
      <div class="tight">
        <label class="small">სტატუსი</label>
        <select id="gstatus" onchange="loadGrants()">
          <option value="all">ყველა</option>
          <option value="current">მიმდინარე</option>
          <option value="closed">დახურული</option>
        </select>
      </div>
      <div class="tight">
        <label class="small">დალაგება</label>
        <select id="gsort" onchange="loadGrants()">
          <option value="new">ახლიდან ძველისკენ</option>
          <option value="old">ძველიდან ახალისკენ</option>
        </select>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>პროგრამა</th>
          <th>ვადა</th>
          <th>სტატუსი</th>
          <th>აქტიური</th>
          <th style="width:390px">ქმედებები</th>
        </tr>
      </thead>
      <tbody id="gBody"></tbody>
    </table>
  </div>
</section>

<!-- ===================== GRANT MODAL ===================== -->
<div class="modal" id="grantModal" onclick="onBackdrop(event)">
  <div class="box">
    <div class="head">
      <div>
        <b id="gmTitle">გრანტის დამატება</b>
        <div class="small">შეინახე გრანტი → შემდეგ ააწყე ფორმა ბილდერიდან.</div>
      </div>
      <button class="btn close" onclick="closeGrantModal()" type="button">✕</button>
    </div>

    <form id="grantForm" onsubmit="return saveGrant(event)" enctype="multipart/form-data">
      <input type="hidden" id="gid" value="0">

      <div class="grid2">
        <div class="card">
          <label class="small">სათაური *</label>
          <input id="gTitle" required>

          <div style="height:10px"></div>
          <label class="small">სათაური (EN)</label>
          <input id="gTitleEn">

          <div style="height:10px"></div>
          <label class="small">Slug (optional)</label>
          <input id="gSlug" placeholder="ავტომატურადაც შეიძლება">
          <div class="small" style="margin-top:6px">TIP: თუ ცარიელია, backend თვითონ გააკეთებს slug-ს.</div>

          <div style="height:10px"></div>
          <label class="small">მოკლე აღწერა</label>
          <input id="gDesc">

          <div style="height:10px"></div>
          <label class="small">მოკლე აღწერა (EN)</label>
          <input id="gDescEn">

          <div style="height:10px"></div>
          <label class="small">ქავერის სურათი (upload)</label>
          <input id="gImgFile" type="file" accept="image/*" onchange="previewCover()">
          <input type="hidden" id="gImgPath" value="">

          <div class="mini" style="margin-top:10px">
            <img class="thumb" id="gCoverPrev" alt="preview" style="display:none">
            <div class="small" id="gCoverHint">Preview გამოჩნდება აქ.</div>
          </div>
        </div>

        <div class="card">
          <div class="row" style="margin:0">
            <div class="tight">
              <label class="small">ვადა</label>
              <input id="gDeadline" type="date">
            </div>
            <div class="tight">
              <label class="small">სტატუსი</label>
              <select id="gStatus">
                <option value="current">მიმდინარე</option>
                <option value="closed">დახურული</option>
              </select>
            </div>
          </div>

          <div style="height:10px"></div>
          <div class="row" style="margin:0">
            <div class="tight">
              <label class="small">მაქს. თანხა — ფიზიკური პირი</label>
              <input id="gMaxPerson" type="number" min="0" step="0.01" placeholder="მაგ: 5000">
            </div>
            <div class="tight">
              <label class="small">მაქს. თანხა — ორგანიზაცია</label>
              <input id="gMaxOrg" type="number" min="0" step="0.01" placeholder="მაგ: 15000">
            </div>
            <div class="tight">
              <label class="small">მაქს. ბიუჯეტი (საერთო)</label>
              <input id="gMaxBudget" type="number" min="0" step="0.01" placeholder="მაგ: 10000">
            </div>
          </div>

          <div style="height:10px"></div>
          <label class="small">Apply URL (optional)</label>
          <input id="gApplyUrl" placeholder="/youthagency/grants/grants_apply.php?id=1">

          <div style="height:10px"></div>
          <div class="row" style="margin:0">
            <div class="tight">
              <label class="small">sort_order</label>
              <input id="gSort" type="number" value="0">
            </div>
            <div class="tight">
              <label class="small">აქტიური</label>
              <select id="gActive">
                <option value="1">კი</option>
                <option value="0">არა</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:12px">
        <label class="small">სრული აღწერა *</label>
        <textarea id="gBodyText" required></textarea>

        <div style="height:10px"></div>
        <label class="small">სრული აღწერა (EN)</label>
        <textarea id="gBodyTextEn"></textarea>
      </div>

      <div class="actions" style="margin-top:12px">
        <button type="button" class="btn ghost" onclick="closeGrantModal()">გაუქმება</button>
        <button type="submit" class="btn ok" id="btnGrantSave">შენახვა</button>
      </div>
    </form>
  </div>
</div>

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
  const tt = document.getElementById("toastT");
  const td = document.getElementById("toastD");
  tt.textContent = title || "OK";
  td.textContent = desc || "";
  t.classList.add("show");
  clearTimeout(window.__toastT);
  window.__toastT = setTimeout(()=>t.classList.remove("show"), 2600);
}
function setBtnLoading(btn, on, txt="იტვირთება..."){
  if(!btn) return;
  if(on){
    btn.dataset._txt = btn.textContent;
    btn.innerHTML = `<span class="spin"></span> ${txt}`;
    btn.disabled = true;
  }else{
    const old = btn.dataset._txt || btn.textContent;
    btn.textContent = old;
    btn.disabled = false;
  }
}
let __t=null;
function debounced(fn, ms=220){
  clearTimeout(__t);
  __t=setTimeout(()=>fn(), ms);
}
function closeAnyModalOnEsc(e){
  if(e.key !== "Escape") return;
  const gm = document.getElementById("grantModal");
  if(gm?.classList.contains("show")) closeGrantModal();
}
document.addEventListener("keydown", closeAnyModalOnEsc);

function onBackdrop(ev){
  // click outside the box closes modal
  if(ev.target && ev.target.id === "grantModal") closeGrantModal();
}

/* API */
async function api(action, payload = {}){
  clearError();
  const isForm = (payload instanceof FormData);

  const res = await fetch(API + "?action=" + encodeURIComponent(action), {
    method: "POST",
    credentials: "same-origin",
    headers: isForm
      ? {"X-CSRF": CSRF}
      : {"Content-Type":"application/json", "X-CSRF": CSRF},
    body: isForm ? payload : JSON.stringify(payload)
  });

  const j = await res.json().catch(()=>null);
  if(!res.ok || !j || !j.ok) throw new Error(j?.error || ("API შეცდომა " + res.status));
  return j;
}

/* ===================== GRANTS ===================== */
let GRANTS = [];
let LOADING = false;

function renderSkeleton(){
  const tb = document.getElementById('gBody');
  tb.innerHTML = `
    ${Array.from({length:5}).map(()=>`
      <tr>
        <td><div class="skel" style="width:70%"></div><div style="height:8px"></div><div class="skel" style="width:50%"></div></td>
        <td><div class="skel" style="width:70px"></div></td>
        <td><div class="skel" style="width:100px"></div></td>
        <td><div class="skel" style="width:50px"></div></td>
        <td><div class="skel" style="width:320px"></div></td>
      </tr>
    `).join('')}
  `;
}

async function loadGrants(){
  if(LOADING) return;
  LOADING = true;

  const btn = document.getElementById("btnReload");
  try{
    setBtnLoading(btn, true, "მიმდინარეობს...");
    renderSkeleton();

    const q = document.getElementById('gq').value.trim();
    const status = document.getElementById('gstatus').value;
    const sort = document.getElementById('gsort').value;

    const j = await api("grants_list", {q, status, sort});
    GRANTS = j.items || [];
    renderGrants();

  }catch(e){
    showError(e.message);
    document.getElementById('gBody').innerHTML = `<tr><td colspan="5" class="small">შეცდომა</td></tr>`;
  }finally{
    setBtnLoading(btn, false);
    LOADING = false;
  }
}

function badgeStatus(s){
  if(s === 'closed') return `<span class="badge bad">დახურული</span>`;
  return `<span class="badge ok">მიმდინარე</span>`;
}
function badgeActive(v){
  return String(v)==='1'
    ? `<span class="badge ok">კი</span>`
    : `<span class="badge warn">არა</span>`;
}

function renderGrants(){
  const tb = document.getElementById('gBody');

  if(!GRANTS.length){
    tb.innerHTML = `<tr><td colspan="5" class="small">გრანტი არ არის დამატებული</td></tr>`;
    return;
  }

  const fmtMoney = (v)=>{
    if(v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if(Number.isNaN(n)) return esc(String(v));
    try{ return n.toLocaleString("ka-GE"); }catch(_){ return String(n); }
  };

  tb.innerHTML = GRANTS.map(g=>{
    const cover = (g.image_path ? esc(g.image_path) : "");
    const coverHtml = cover
      ? `<img class="thumb" src="${cover}" alt="cover">`
      : `<div class="thumb" style="display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.45);font-weight:900;">—</div>`;

    return `
      <tr>
        <td>
          <div class="mini">
            ${coverHtml}
            <div class="meta">
              <b title="${esc(g.title||'')}">${esc(g.title||'')}</b>
              <div class="small">slug: ${esc(g.slug || '')}</div>
              ${g.description ? `<div class="small" title="${esc(g.description)}">${esc(g.description)}</div>` : ``}
              <div class="small">
                მაქს. თანხა — ფიზ: <b>${fmtMoney(g.max_amount_person)}</b> • ორგ: <b>${fmtMoney(g.max_amount_org)}</b> • ბიუჯეტი: <b>${fmtMoney(g.max_budget)}</b>
              </div>
            </div>
          </div>
        </td>
        <td>${esc(g.deadline || '—')}</td>
        <td>${badgeStatus(g.status)}</td>
        <td>${badgeActive(g.is_active)}</td>
        <td>
          <div class="actions">
            <button class="btn ghost" onclick="editGrantById(${Number(g.id)})">რედაქტირება</button>
            <button class="btn warn" onclick="toggleActive(${Number(g.id)})">აქტიური/არა</button>
            <button class="btn bad" onclick="deleteGrant(${Number(g.id)})">წაშლა</button>
            <a class="btn ac" href="admin_builder.php?grant_id=${Number(g.id)}">ბილდერი</a>
            <a class="btn ghost" href="admin_apps.php?grant_id=${Number(g.id)}">განაცხადები</a>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function editGrantById(id){
  const g = GRANTS.find(x => Number(x.id) === Number(id));
  if(!g) return showError("გრანტი ვერ მოიძებნა: " + id);
  editGrant(g);
}

/* ===== Grant modal ===== */
function openGrantModal(){
  document.getElementById('gmTitle').textContent = "გრანტის დამატება";
  document.getElementById('gid').value = "0";
  document.getElementById('grantForm').reset();
  document.getElementById('gStatus').value = 'current';
  document.getElementById('gActive').value = '1';
  document.getElementById('gImgPath').value = '';
  document.getElementById('gImgFile').value = '';
  document.getElementById('gMaxPerson').value = '';
  document.getElementById('gMaxOrg').value = '';
  document.getElementById('gMaxBudget').value = '';
  setCoverPreview("");
  document.getElementById('grantModal').classList.add('show');

  setTimeout(()=>document.getElementById("gTitle")?.focus(), 50);
}

function closeGrantModal(){
  document.getElementById('grantModal').classList.remove('show');
}

function setCoverPreview(url){
  const img = document.getElementById("gCoverPrev");
  const hint = document.getElementById("gCoverHint");
  if(url){
    img.src = url;
    img.style.display = "block";
    hint.textContent = "";
  }else{
    img.style.display = "none";
    img.src = "";
    hint.textContent = "Preview გამოჩნდება აქ.";
  }
}

function previewCover(){
  const fi = document.getElementById('gImgFile');
  const file = fi && fi.files && fi.files[0] ? fi.files[0] : null;
  if(!file){ setCoverPreview(document.getElementById('gImgPath').value || ""); return; }
  const url = URL.createObjectURL(file);
  setCoverPreview(url);
}

function editGrant(g){
  document.getElementById('gmTitle').textContent = "გრანტის რედაქტირება";
  document.getElementById('gid').value = String(g.id || 0);
  document.getElementById('gTitle').value = g.title || '';
  document.getElementById('gTitleEn').value = g.title_en || '';
  document.getElementById('gSlug').value = g.slug || '';
  document.getElementById('gDesc').value = g.description || '';
  document.getElementById('gDescEn').value = g.description_en || '';
  document.getElementById('gDeadline').value = g.deadline || '';
  document.getElementById('gStatus').value = g.status || 'current';
  document.getElementById('gApplyUrl').value = g.apply_url || '';
  document.getElementById('gSort').value = String(Number(g.sort_order || 0));
  document.getElementById('gActive').value = String(g.is_active) === '1' ? '1' : '0';
  document.getElementById('gBodyText').value = g.body || '';
  document.getElementById('gBodyTextEn').value = g.body_en || '';
  document.getElementById('gImgPath').value = g.image_path || '';
  document.getElementById('gImgFile').value = '';
  document.getElementById('gMaxPerson').value = (g.max_amount_person ?? '') === null ? '' : String(g.max_amount_person ?? '');
  document.getElementById('gMaxOrg').value = (g.max_amount_org ?? '') === null ? '' : String(g.max_amount_org ?? '');
  document.getElementById('gMaxBudget').value = (g.max_budget ?? '') === null ? '' : String(g.max_budget ?? '');

  setCoverPreview(g.image_path || "");
  document.getElementById('grantModal').classList.add('show');

  setTimeout(()=>document.getElementById("gTitle")?.focus(), 50);
}

async function saveGrant(ev){
  ev.preventDefault();
  const btn = document.getElementById("btnGrantSave");
  try{
    setBtnLoading(btn, true, "შენახვა...");

    const id = Number(document.getElementById('gid').value || 0);

    const fd = new FormData();
    fd.append("id", String(id));
    fd.append("title", document.getElementById('gTitle').value.trim());
    fd.append("title_en", document.getElementById('gTitleEn').value.trim());
    fd.append("slug", document.getElementById('gSlug').value.trim());
    fd.append("description", document.getElementById('gDesc').value.trim());
    fd.append("description_en", document.getElementById('gDescEn').value.trim());
    fd.append("deadline", document.getElementById('gDeadline').value || '');
    fd.append("status", document.getElementById('gStatus').value);
    fd.append("apply_url", document.getElementById('gApplyUrl').value.trim());
    fd.append("sort_order", String(Number(document.getElementById('gSort').value || 0)));
    fd.append("is_active", document.getElementById('gActive').value === '1' ? "1" : "0");
    fd.append("body", document.getElementById('gBodyText').value.trim());
    fd.append("body_en", document.getElementById('gBodyTextEn').value.trim());
    fd.append("existing_image_path", document.getElementById('gImgPath').value.trim());
    fd.append("max_amount_person", (document.getElementById('gMaxPerson').value || '').trim());
    fd.append("max_amount_org", (document.getElementById('gMaxOrg').value || '').trim());
    fd.append("max_budget", (document.getElementById('gMaxBudget').value || '').trim());

    const fi = document.getElementById('gImgFile');
    const file = fi && fi.files && fi.files[0] ? fi.files[0] : null;
    if(file) fd.append("cover_image", file);

    await api("grants_save", fd);

    closeGrantModal();
    toast("შენახულია ✅", "გრანტი წარმატებით შეინახა");
    await loadGrants();
  }catch(e){
    showError(e.message);
  }finally{
    setBtnLoading(btn, false);
  }
  return false;
}

async function deleteGrant(id){
  if(!confirm("წაიშალოს ეს გრანტი?")) return;
  try{
    await api("grants_delete", {id});
    toast("წაშლილია ✅");
    await loadGrants();
  }catch(e){ showError(e.message); }
}

async function toggleActive(id){
  try{
    await api("grants_toggle_active", {id});
    toast("განახლდა ✅", "აქტიურობა შეიცვალა");
    await loadGrants();
  }catch(e){ showError(e.message); }
}

/* init */
loadGrants();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
