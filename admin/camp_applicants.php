<?php
// admin/camp_applicants.php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (empty($_SESSION['admin_logged_in']) || (int)$_SESSION['admin_logged_in'] !== 1) {
  header("Location: login.php");
  exit;
}

$title = "Camp Applicants";

ob_start();
?>
<style>
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  input,select,textarea{
    padding:10px;border-radius:12px;border:1px solid var(--line);
    background:#0f172a;color:var(--txt);font-weight:900
  }
  textarea{min-height:44px}
  .btn{
    padding:10px 12px;border-radius:12px;border:1px solid var(--line);
    background:rgba(17,28,51,.55);color:var(--txt);cursor:pointer;font-weight:1000;
    transition: transform .12s ease, border-color .12s ease, opacity .12s ease;
  }
  .btn:hover{transform:translateY(-1px);border-color:rgba(96,165,250,.55)}
  .btn:disabled{opacity:.55;cursor:not-allowed;transform:none}
  .btn.ac{background:rgba(37,99,235,.18);border-color:rgba(37,99,235,.4)}
  .btn.ok{background:rgba(22,163,74,.12);border-color:rgba(22,163,74,.35)}
  .btn.bad{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.35)}
  .btn.ghost{background:transparent}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  td,th{border-bottom:1px solid var(--line);padding:8px;vertical-align:top}
  th{text-align:left;position:sticky;top:0;background:rgba(2,6,23,.85);backdrop-filter: blur(6px)}
  .muted{color:var(--muted);font-weight:900}
  .pill{display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid var(--line);font-weight:1000}
  .pill.pending{background:rgba(148,163,184,.12)}
  .pill.approved{background:rgba(22,163,74,.12);border-color:rgba(22,163,74,.35)}
  .pill.rejected{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.35)}
  .note{width:240px}
  .tableWrap{overflow:auto;border-radius:14px;border:1px solid var(--line)}
  .tools{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-top:8px}
  .leftTools,.rightTools{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .kpi{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .kpi .chip{padding:6px 10px;border-radius:999px;border:1px solid var(--line);background:rgba(148,163,184,.10);font-weight:1000}
  .small{font-size:12px}
  .nowrap{white-space:nowrap}
  .link{color:#93c5fd;text-decoration:none;font-weight:900}
  .link:hover{text-decoration:underline}
  .valCell{min-width:160px;max-width:320px}
  .valBox{display:flex;flex-direction:column;gap:6px}
  .valItem{padding:6px 8px;border-radius:10px;border:1px solid var(--line);background:rgba(15,23,42,.45);word-break:break-word}
  .valItem.file{border-color:rgba(37,99,235,.35);background:rgba(37,99,235,.10)}
  .valItem.empty{opacity:.65}
</style>

<div class="card">
  <div class="row">
    <select id="campId" style="min-width:240px"></select>

    <select id="status" style="min-width:180px">
      <option value="">All statuses</option>
      <option value="pending">Pending</option>
      <option value="approved">დადასტურებული</option>
      <option value="rejected">უარყოფილი</option>
    </select>

    <input id="q" placeholder="Search unique key / values..." style="min-width:260px;flex:1">
    <button class="btn ac" id="loadBtn" onclick="loadApplicants()">Load</button>
    <button class="btn ghost" onclick="resetFilters()">Reset</button>
    <span class="muted" id="msg"></span>
  </div>

  <div class="tools">
    <div class="leftTools kpi">
      <span class="chip">ჯამი: <span id="count">0</span></span>
      <span class="chip">NEW: <span id="kpi_pending">0</span></span>
      <span class="chip">დადასტურებული: <span id="kpi_approved">0</span></span>
      <span class="chip">უარყოფილი: <span id="kpi_rejected">0</span></span>
    </div>

    <div class="rightTools">
      <button class="btn" onclick="exportXLSX()" title="Export table to Excel">Export Excel</button>

      <span class="muted small" id="hint">Tip: type in search and press Enter</span>
    </div>
  </div>
</div>

<!-- PID BLOCK MANAGER -->
<div class="card">
  <div class="row">
    <input id="block_pid" placeholder="PID to block (01010101010)" style="min-width:260px">
    <input id="block_reason" placeholder="Reason (optional)" style="min-width:260px;flex:1">
    <select id="block_scope" style="min-width:220px">
      <option value="camp">Block for selected camp</option>
      <option value="all">Block for ALL camps</option>
    </select>
    <button class="btn bad" onclick="blockPid()">დაბლოკვა</button>
    <button class="btn" onclick="loadBlocked()">დაბლოკილების ნახვა</button>
    <span class="muted" id="blkMsg"></span>
  </div>
  <div id="blockedBox" class="muted" style="margin-top:10px"></div>
</div>

<div class="card">
  <div class="row" style="justify-content:space-between">
    <b>Applicants</b>
    <span class="muted small" id="renderInfo"></span>
  </div>
  <div id="tableBox" class="muted" style="margin-top:10px">Choose a camp and click Load</div>
</div>

<script>
/* -------------------- API -------------------- */
async function apiJson(action, payload = {}) {
  const res = await fetch("/youthagency/admin/api/camps.php?action=" + encodeURIComponent(action), {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify(payload)
  });

  const j = await res.json().catch(()=>({ok:false,error:"Bad JSON"}));
  if (!res.ok || !j.ok) throw new Error(j.error || ("HTTP " + res.status));
  return j;
}

/* -------------------- HELPERS -------------------- */
function esc(s){
  return (s??"").toString()
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;");
}

function isFileLink(s){
  s = (s??"").toString();
  return s.startsWith("/youthagency/uploads/") || s.startsWith("http://") || s.startsWith("https://");
}

/**
 * ✅ FIX for: (r.values||[]).map is not a function
 * r.values may be:
 * - array: ["a","b"]
 * - object: {"1":"a","2":"b"}
 * - JSON string: '["a","b"]' OR '{"1":"a"}'
 */
function asArrayValues(values){
  if (!values) return [];
  if (typeof values === "string") {
    try { values = JSON.parse(values); } catch { return [values]; }
  }
  if (Array.isArray(values)) return values;
  if (typeof values === "object") return Object.values(values);
  return [values];
}

function setText(id, txt){
  const el = document.getElementById(id);
  if (el) el.textContent = txt;
}

function debounce(fn, ms=350){
  let t=null;
  return (...args)=>{
    clearTimeout(t);
    t=setTimeout(()=>fn(...args), ms);
  };
}

function downloadText(filename, content){
  const blob = new Blob([content], {type:"text/csv;charset=utf-8"});
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}
function exportXLSX(){
  const campId = document.getElementById("campId").value;
  if(!campId){ alert("Select camp"); return; }

  const status = document.getElementById("status").value;
  const q = document.getElementById("q").value.trim();

  const url =
    "/youthagency/admin/api/export_applicants_xlsx.php" +
    "?campId=" + encodeURIComponent(campId) +
    "&status=" + encodeURIComponent(status) +
    "&q=" + encodeURIComponent(q);

  window.open(url, "_blank");
}

/* -------------------- STATE -------------------- */
let fields = [];
let rows = [];
let lastCampId = "";

/* -------------------- CAMPS DROPDOWN -------------------- */
async function loadCampsDrop(){
  try{
    const j = await apiJson("list",{q:""});
    const sel = document.getElementById("campId");
    sel.innerHTML =
      `<option value="">Select camp...</option>` +
      (j.camps||[]).map(c=>`<option value="${c.id}">${esc(c.name)} (#${c.id})</option>`).join("");

    // keep last selected camp on refresh of this page
    const saved = localStorage.getItem("campApplicants_lastCampId") || "";
    if (saved) sel.value = saved;
  }catch(e){
    setText("msg", "❌ "+e.message);
  }
}
loadCampsDrop();

/* -------------------- FILTER UX -------------------- */
function resetFilters(){
  document.getElementById("status").value = "";
  document.getElementById("q").value = "";
  setText("msg","");
  if (document.getElementById("campId").value) loadApplicants();
}

document.getElementById("q").addEventListener("keydown", (e)=>{
  if (e.key === "Enter") loadApplicants();
});

document.getElementById("status").addEventListener("change", ()=>{
  if (document.getElementById("campId").value) loadApplicants();
});

/* -------------------- LOAD APPLICANTS -------------------- */
async function loadApplicants(){
  const campId = document.getElementById("campId").value;
  if(!campId){ setText("msg","❌ Select camp"); return; }

  lastCampId = campId;
  localStorage.setItem("campApplicants_lastCampId", campId);

  const loadBtn = document.getElementById("loadBtn");
  loadBtn.disabled = true;

  setText("msg","Loading...");
  setText("renderInfo","");
  document.getElementById("tableBox").innerHTML = `<div class="muted">Loading...</div>`;

  try{
    const j = await apiJson("applicants", {
      campId,
      status: document.getElementById("status").value,
      q: document.getElementById("q").value.trim()
    });

    fields = j.fields || [];
    rows = j.rows || [];

    // Normalize row.values so render is stable
    rows.forEach(r=>{
      r.values = asArrayValues(r.values);
    });

    renderTable();
    setText("msg","");
  }catch(e){
    setText("msg","❌ "+e.message);
    document.getElementById("tableBox").innerHTML = `<div class="muted">❌ ${esc(e.message)}</div>`;
  }finally{
    loadBtn.disabled = false;
  }
}

/* -------------------- RENDER -------------------- */
function renderKPIs(){
  const total = rows.length;
  let p=0,a=0,r=0;
  rows.forEach(x=>{
    if(x.status==="pending") p++;
    else if(x.status==="approved") a++;
    else if(x.status==="უარყოფილი") r++;
  });
  setText("count", String(total));
  setText("kpi_pending", String(p));
  setText("kpi_approved", String(a));
  setText("kpi_rejected", String(r));
}

function renderTable(){
  renderKPIs();

  if(!rows.length){
    document.getElementById("tableBox").innerHTML = `<div class="muted">No applicants found.</div>`;
    setText("renderInfo","");
    return;
  }

  // header columns from fields
  const headExtra = fields.map(f=>`<th class="valCell">${esc(f.label)}</th>`).join("");

  const body = rows.map(r=>{
    const pill = `<span class="pill ${esc(r.status)}">${esc(r.status)}</span>`;

    // ✅ FIXED: r.values always array now
    const valuesTds = (r.values || []).map(v=>{
      const s = (v ?? "").toString().trim();
      if (!s) return `<td class="valCell"><div class="valBox"><div class="valItem empty">—</div></div></td>`;

      if (isFileLink(s)) {
        return `<td class="valCell">
          <div class="valBox">
            <div class="valItem file">
              <a class="link" href="${esc(s)}" target="_blank" rel="noopener">Open file</a>
              <div class="muted small">${esc(s)}</div>
            </div>
          </div>
        </td>`;
      }

      return `<td class="valCell"><div class="valBox"><div class="valItem">${esc(s)}</div></div></td>`;
    }).join("");

    return `
      <tr>
        <td class="nowrap">${esc(r.id)}</td>
        <td class="muted nowrap">${esc(r.created_at || "")}</td>
        <td class="muted">${esc(r.unique_key || "")}</td>
        <td class="nowrap">${pill}</td>

        <td>
          <textarea class="note" id="note_${esc(r.id)}" placeholder="admin note...">${esc(r.admin_note||"")}</textarea>
        </td>

        <td class="nowrap">
          <button class="btn ok" onclick="setStatus(${Number(r.id)},'approved')">დადასტურება</button>
          <button class="btn bad" onclick="setStatus(${Number(r.id)},'rejected')">უარყოფა</button>
          <button class="btn" onclick="setStatus(${Number(r.id)},'pending')">NEW</button>
        </td>

        ${valuesTds}
      </tr>
    `;
  }).join("");

  document.getElementById("tableBox").innerHTML = `
    <div class="tableWrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Created</th><th>Unique</th><th>Status</th><th>Note</th><th>Actions</th>
            ${headExtra}
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    </div>
  `;

  setText("renderInfo", `Rendered ${rows.length} rows • ${fields.length} fields`);
}

/* -------------------- STATUS UPDATE -------------------- */
async function setStatus(id, status){
  const note = document.getElementById("note_"+id)?.value || "";
  try{
    await apiJson("applicantStatus", {id:String(id), status, note});
    const r = rows.find(x=>Number(x.id)===Number(id));
    if(r){ r.status=status; r.admin_note=note; }
    renderTable();
  }catch(e){
    alert("❌ "+e.message);
  }
}

/* -------------------- EXPORT CSV -------------------- */
function exportCSV(){
  if(!rows.length){
    alert("No rows to export.");
    return;
  }
  const headers = ["ID","Created","Unique","Status","Note"].concat(fields.map(f=>f.label || ""));
  const lines = [];
  lines.push(headers.map(x=>`"${String(x).replaceAll('"','""')}"`).join(","));

  rows.forEach(r=>{
    const vals = asArrayValues(r.values);
    const base = [
      r.id ?? "",
      r.created_at ?? "",
      r.unique_key ?? "",
      r.status ?? "",
      r.admin_note ?? ""
    ];

    // pad/truncate to fields length
    const extra = fields.map((_, idx)=> (vals[idx] ?? ""));
    const row = base.concat(extra).map(x=>`"${String(x).replaceAll('"','""')}"`).join(",");
    lines.push(row);
  });

  const campId = document.getElementById("campId").value || "camp";
  downloadText(`applicants_${campId}.csv`, lines.join("\n"));
}

/* -------------------- PID BLOCK -------------------- */
async function blockPid(){
  const pid = document.getElementById("block_pid").value.trim().replace(/\s+/g,'');
  if(!pid){ setText("blkMsg","❌ PID required"); return; }

  const reason = document.getElementById("block_reason").value.trim();
  const scope = document.getElementById("block_scope").value;

  const campIdSel = document.getElementById("campId").value;
  const campId = (scope === "all") ? "" : campIdSel;

  if(scope === "camp" && !campId){
    setText("blkMsg","❌ Select camp first (or choose ALL)");
    return;
  }

  try{
    await apiJson("pidBlockAdd", { campId, pid, reason });
    setText("blkMsg","✅ Blocked");
    document.getElementById("block_pid").value="";
    document.getElementById("block_reason").value="";
    loadBlocked();
  }catch(e){
    setText("blkMsg","❌ "+e.message);
  }
}

async function loadBlocked(){
  const scope = document.getElementById("block_scope").value;
  const campIdSel = document.getElementById("campId").value;
  const campId = (scope === "all") ? "" : campIdSel;

  try{
    const j = await apiJson("pidBlockList", { campId });
    const b = j.rows || [];

    if(!b.length){
      document.getElementById("blockedBox").innerHTML = `<div class="muted">No blocked PIDs.</div>`;
      return;
    }

    document.getElementById("blockedBox").innerHTML = `
      <div class="tableWrap">
        <table>
          <thead>
            <tr><th>ID</th><th>Scope</th><th>PID</th><th>Reason</th><th>Created</th><th></th></tr>
          </thead>
          <tbody>
            ${b.map(r=>`
              <tr>
                <td>${esc(r.id)}</td>
                <td class="muted">${(r.camp_id===null || r.camp_id==="") ? "ALL" : esc(r.camp_id)}</td>
                <td><b>${esc(r.pid)}</b></td>
                <td class="muted">${esc(r.reason||"")}</td>
                <td class="muted">${esc(r.created_at||"")}</td>
                <td><button class="btn" onclick="unblockPid(${Number(r.id)})">განბლოკვა</button></td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    `;
  }catch(e){
    document.getElementById("blockedBox").innerHTML = `<div class="muted">❌ ${esc(e.message)}</div>`;
  }
}

async function unblockPid(id){
  if(!confirm("Unblock this PID?")) return;
  await apiJson("pidBlockRemove", { id:String(id) });
  loadBlocked();
}

/* -------------------- NICE: Auto-load applicants when camp selected -------------------- */
document.getElementById("campId").addEventListener("change", ()=>{
  const v = document.getElementById("campId").value;
  if (!v) return;
  loadApplicants();
  // also refresh blocked list quickly if user wants
  setText("blkMsg","");
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
