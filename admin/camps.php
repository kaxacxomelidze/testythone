<?php
// admin/camps.php
declare(strict_types=1);
require __DIR__ . '/config.php';

// if your config has h() keep it. If not, define safe:
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Auth (same logic as your system)
if (empty($_SESSION['admin_logged_in']) || (int)$_SESSION['admin_logged_in'] !== 1) {
  header("Location: login.php");
  exit;
}

$title = "Camps";

// build page content for layout
ob_start();
?>
<style>
  /* page-only styles (keeps layout clean) */
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  input,select,textarea{
    padding:10px;border-radius:12px;border:1px solid var(--line);
    background:#0f172a;color:var(--txt);font-weight:900
  }
  textarea{min-height:130px;width:100%}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;margin-top:14px}
  .camp{border:1px solid var(--line);border-radius:16px;background:rgba(15,23,42,.65);padding:12px}
  .muted{color:var(--muted);font-weight:900}
  .hr{height:1px;background:var(--line);margin:12px 0}
  img.preview{max-width:240px;border-radius:12px;border:1px solid var(--line);margin-top:8px}
  .pill{padding:6px 10px;border-radius:999px;border:1px solid var(--line);font-weight:1000;display:inline-block}
  .modalBack{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;padding:18px;z-index:9999}
  .modal{max-width:980px;width:100%;background:var(--bg);border:1px solid var(--line);border-radius:18px;overflow:hidden}
  .modalHead{padding:12px 14px;background:rgba(17,28,51,.75);border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center}
  .modalBody{padding:14px;max-height:75vh;overflow:auto}
  .thumbRow{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
  .thumb{position:relative;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#0f172a}
  .thumb img{width:140px;height:90px;object-fit:cover;display:block}
  .thumb button{position:absolute;top:6px;right:6px;padding:6px 8px;border-radius:10px}
  table{width:100%;border-collapse:collapse}
  td,th{border-bottom:1px solid var(--line);padding:8px;vertical-align:top}
  th{text-align:left}
</style>

<div class="card">
  <div class="row">
    <input id="q" placeholder="Search camps..." style="min-width:260px">
    <button class="btn ac" onclick="loadCamps()">Search</button>
    <button class="btn" onclick="openCampEditor()">+ Add Camp</button>
    <span class="muted" id="status"></span>
  </div>
</div>

<div class="grid" id="grid"></div>

<!-- Camp Editor Modal -->
<div class="modalBack" id="campModalBack">
  <div class="modal">
    <div class="modalHead">
      <b id="campModalTitle">Camp</b>
      <button class="btn" onclick="closeCampEditor()">Close</button>
    </div>
    <div class="modalBody">
      <div class="row">
        <div style="flex:1;min-width:240px">
          <div class="muted">Name *</div>
          <input id="c_name" style="width:100%">
        </div>

        <div style="flex:1;min-width:240px">
          <div class="muted">Cover Image (upload)</div>
          <input id="c_cover_file" type="file" accept="image/*" style="width:100%">
          <div id="c_cover_preview" class="muted" style="margin-top:6px"></div>
        </div>
      </div>

      <div class="row" style="margin-top:10px">
        <div style="flex:1;min-width:240px">
          <div class="muted">Card text</div>
          <input id="c_cardText" style="width:100%">
        </div>
        <div style="min-width:160px">
          <div class="muted">windowDays</div>
          <input id="c_windowDays" type="number" style="width:160px" value="365">
        </div>
        <div style="min-width:160px">
          <div class="muted">Manual closed</div>
          <select id="c_closed" style="width:160px">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
      </div>

      <div class="row" style="margin-top:10px">
        <div style="min-width:220px">
          <div class="muted">Start *</div>
          <input id="c_start" type="date" style="width:220px">
        </div>
        <div style="min-width:220px">
          <div class="muted">End *</div>
          <input id="c_end" type="date" style="width:220px">
        </div>
      </div>

      <div class="hr"></div>

      <div class="row" style="justify-content:space-between">
        <b>Registration Form Fields</b>
        <button class="btn" onclick="addField()">+ Add Field</button>
      </div>

      <div id="fieldsBox" style="margin-top:10px"></div>

      <div class="hr"></div>

      <div class="row" style="gap:8px">
        <button class="btn ac" onclick="saveCamp()">Save Camp</button>
        <button class="btn" onclick="saveForm()">Save Form</button>
        <button class="btn" onclick="openPosts()">Camp Posts</button>
        <button class="btn" onclick="openRegs()">Registrations</button>
        <button class="btn bad" onclick="deleteCamp()" id="btnDelete" style="display:none">Delete</button>
        <span class="muted" id="campMsg"></span>
      </div>
    </div>
  </div>
</div>

<!-- Posts Modal -->
<div class="modalBack" id="postsModalBack">
  <div class="modal">
    <div class="modalHead">
      <b>Camp Posts</b>
      <button class="btn" onclick="closePosts()">Close</button>
    </div>
    <div class="modalBody">

      <div class="row">
        <div style="flex:1;min-width:240px">
          <div class="muted">Post title *</div>
          <input id="p_title" placeholder="Post title" style="width:100%">
        </div>

        <div style="flex:1;min-width:240px">
          <div class="muted">Post Cover (upload)</div>
          <input id="p_cover_file" type="file" accept="image/*" style="width:100%">
          <div class="muted" id="p_cover_preview" style="margin-top:6px"></div>
        </div>

        <div style="flex:1;min-width:240px">
          <div class="muted">Gallery (multiple images)</div>
          <input id="p_gallery_files" type="file" accept="image/*" multiple style="width:100%">
          <div class="muted" style="margin-top:6px">Select many images.</div>
        </div>
      </div>

      <div style="margin-top:10px">
        <textarea id="p_body" placeholder="Post body..."></textarea>
      </div>

      <div class="row" style="margin-top:10px">
        <button class="btn ac" onclick="savePost()">Save Post</button>
        <button class="btn" onclick="resetPost()">Reset</button>
        <span class="muted" id="postMsg"></span>
      </div>

      <div class="hr"></div>
      <div id="postsList"></div>
    </div>
  </div>
</div>

<!-- Registrations Modal -->
<div class="modalBack" id="regsModalBack">
  <div class="modal">
    <div class="modalHead">
      <b>Registrations</b>
      <button class="btn" onclick="closeRegs()">Close</button>
    </div>
    <div class="modalBody">
      <div id="regsBox" class="muted">Loading...</div>
    </div>
  </div>
</div>

<script>
let camps = [];
let currentCamp = null;
let editingPostId = 0;

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

function esc(s){return (s??"").toString().replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;");}

async function loadCamps(){
  const q = document.getElementById("q").value.trim();
  document.getElementById("status").textContent = "Loading...";
  try{
    const j = await apiJson("list",{q});
    camps = j.camps || [];
    renderCamps();
    document.getElementById("status").textContent = "Loaded: " + camps.length;
  }catch(e){
    document.getElementById("status").textContent = "❌ " + e.message;
  }
}

function renderCamps(){
  const g = document.getElementById("grid");
  g.innerHTML = camps.map(c=>{
    const open = !c.closed;
    return `
      <div class="camp">
        <div class="row" style="justify-content:space-between">
          <b>${esc(c.name)}</b>
          <span class="pill">${open ? "Active" : "Closed"}</span>
        </div>
        ${c.cover ? `<img src="${esc(c.cover)}" class="preview" alt="">` : ``}
        <div class="muted">${esc(c.cardText||"")}</div>
        <div class="muted">${c.start} → ${c.end}</div>
        <div class="muted">Fields: ${c.fieldsCount ?? 0}</div>
        <div class="row" style="margin-top:10px">
          <button class="btn ac" onclick="editCamp(${c.id})">Edit</button>
          <a class="pill" href="/youthagency/camps/${c.id}/${encodeURIComponent(c.slug)}" target="_blank">Open user page</a>
        </div>
      </div>
    `;
  }).join("");
}

function openCampEditor(){
  currentCamp = { id:"", name:"", cover:"", cardText:"", start:"", end:"", closed:false, windowDays:365, form:[], posts:[] };
  fillCampEditor();
  document.getElementById("btnDelete").style.display = "none";
  document.getElementById("campModalTitle").textContent = "Add Camp";
  document.getElementById("campModalBack").style.display = "flex";
}
function closeCampEditor(){ document.getElementById("campModalBack").style.display = "none"; }

async function editCamp(id){
  document.getElementById("status").textContent = "Loading camp...";
  try{
    const j = await apiJson("get",{id:String(id)});
    currentCamp = j.camp;
    fillCampEditor();
    document.getElementById("btnDelete").style.display = "";
    document.getElementById("campModalTitle").textContent = "Edit Camp #" + id;
    document.getElementById("campModalBack").style.display = "flex";
    document.getElementById("status").textContent = "";
  }catch(e){
    document.getElementById("status").textContent = "❌ " + e.message;
  }
}

function fillCampEditor(){
  document.getElementById("c_name").value = currentCamp.name || "";
  document.getElementById("c_cardText").value = currentCamp.cardText || "";
  document.getElementById("c_start").value = currentCamp.start || "";
  document.getElementById("c_end").value = currentCamp.end || "";
  document.getElementById("c_closed").value = currentCamp.closed ? "1" : "0";
  document.getElementById("c_windowDays").value = currentCamp.windowDays ?? 365;
  document.getElementById("campMsg").textContent = "";

  const prev = document.getElementById("c_cover_preview");
  document.getElementById("c_cover_file").value = "";
  if (currentCamp.cover) {
    prev.innerHTML = `Current: <a href="${esc(currentCamp.cover)}" target="_blank">${esc(currentCamp.cover)}</a><br>
      <img src="${esc(currentCamp.cover)}" class="preview" alt="">`;
  } else {
    prev.textContent = "No cover uploaded";
  }
  renderFields();
}

function renderFields(){
  const box = document.getElementById("fieldsBox");
  const form = currentCamp.form || [];
  if(!form.length){
    box.innerHTML = `<div class="muted">No fields yet.</div>`;
    return;
  }
  box.innerHTML = form.map((f,idx)=>`
    <div class="card">
      <div class="row" style="justify-content:space-between">
        <b>Field #${idx+1}</b>
        <div class="row">
          <button class="btn" onclick="moveField(${idx},-1)">↑</button>
          <button class="btn" onclick="moveField(${idx},1)">↓</button>
          <button class="btn bad" onclick="removeField(${idx})">Remove</button>
        </div>
      </div>
      <div class="row" style="margin-top:10px">
        <div style="flex:1;min-width:220px">
          <div class="muted">Label *</div>
          <input value="${esc(f.label||"")}" oninput="currentCamp.form[${idx}].label=this.value" style="width:100%">
        </div>
        <div style="min-width:200px">
          <div class="muted">Type</div>
          <select onchange="currentCamp.form[${idx}].type=this.value;renderFields()" style="width:200px">
            ${["text","pid","phone","email","date","select","file"].map(t=>`<option ${f.type===t?'selected':''} value="${t}">${t}</option>`).join("")}
          </select>
        </div>
        <div style="min-width:140px">
          <div class="muted">Required</div>
          <select onchange="currentCamp.form[${idx}].req=this.value==='1'" style="width:140px">
            <option value="0" ${!f.req?'selected':''}>No</option>
            <option value="1" ${f.req?'selected':''}>Yes</option>
          </select>
        </div>
      </div>
      ${f.type==="select" ? `
        <div style="margin-top:10px">
          <div class="muted">Options (comma separated)</div>
          <input value="${esc(f.options||"")}" oninput="currentCamp.form[${idx}].options=this.value" style="width:100%" placeholder="Option1, Option2">
        </div>
      ` : ``}
    </div>
  `).join("");
}

function addField(){ (currentCamp.form ||= []).push({id:"",label:"",type:"text",req:false,options:""}); renderFields(); }
function removeField(i){ currentCamp.form.splice(i,1); renderFields(); }
function moveField(i,dir){
  const a = currentCamp.form; const j = i+dir;
  if(j<0||j>=a.length) return;
  [a[i],a[j]]=[a[j],a[i]];
  renderFields();
}

async function saveCamp(){
  const fd = new FormData();
  fd.append("id", currentCamp.id || "");
  fd.append("name", document.getElementById("c_name").value.trim());
  fd.append("cardText", document.getElementById("c_cardText").value.trim());
  fd.append("start", document.getElementById("c_start").value);
  fd.append("end", document.getElementById("c_end").value);
  fd.append("closed", document.getElementById("c_closed").value);
  fd.append("windowDays", document.getElementById("c_windowDays").value);

  const file = document.getElementById("c_cover_file").files[0];
  if (file) fd.append("cover_file", file);

  try{
    const res = await fetch("/youthagency/admin/api/camps.php?action=save", { method:"POST", body: fd });
    const j = await res.json().catch(()=>({ok:false,error:"Bad JSON"}));
    if(!res.ok || !j.ok) throw new Error(j.error || ("HTTP " + res.status));

    currentCamp.id = String(j.id);
    document.getElementById("campMsg").textContent = "✅ Saved (ID: " + j.id + ")";
    document.getElementById("btnDelete").style.display = "";

    await loadCamps();
    const gg = await apiJson("get",{id:currentCamp.id});
    currentCamp = gg.camp;
    fillCampEditor();
  }catch(e){
    document.getElementById("campMsg").textContent = "❌ " + e.message;
  }
}

async function saveForm(){
  if(!currentCamp.id){ document.getElementById("campMsg").textContent = "❌ Save camp first"; return; }
  const fields = (currentCamp.form||[]).map(f=>({
    label:(f.label||"").trim(),
    type:f.type||"text",
    req:!!f.req,
    options:(f.options||"").trim()
  }));
  const hasKey = fields.some(x=>["pid","email","phone"].includes(x.type));
  if(!hasKey){ document.getElementById("campMsg").textContent = "❌ Add PID or Email or Phone field"; return; }
  try{
    await apiJson("saveForm",{campId: currentCamp.id, fields});
    document.getElementById("campMsg").textContent = "✅ Form saved";
    const j = await apiJson("get",{id:currentCamp.id});
    currentCamp = j.camp;
    fillCampEditor();
    await loadCamps();
  }catch(e){
    document.getElementById("campMsg").textContent = "❌ " + e.message;
  }
}

async function deleteCamp(){
  if(!currentCamp.id) return;
  if(!confirm("Delete this camp?")) return;
  try{
    await apiJson("delete",{id:currentCamp.id});
    closeCampEditor();
    await loadCamps();
  }catch(e){
    document.getElementById("campMsg").textContent = "❌ " + e.message;
  }
}

/* POSTS */
function openPosts(){
  if(!currentCamp.id){ document.getElementById("campMsg").textContent="❌ Save camp first"; return; }
  resetPost(); renderPostsList();
  document.getElementById("postsModalBack").style.display="flex";
}
function closePosts(){ document.getElementById("postsModalBack").style.display="none"; }

function resetPost(){
  editingPostId=0;
  document.getElementById("p_title").value="";
  document.getElementById("p_body").value="";
  document.getElementById("p_cover_file").value="";
  document.getElementById("p_gallery_files").value="";
  document.getElementById("p_cover_preview").textContent="No cover selected";
  document.getElementById("postMsg").textContent="";
}

async function savePost(){
  const title = document.getElementById("p_title").value.trim();
  const body  = document.getElementById("p_body").value.trim();
  if(!title || !body){ document.getElementById("postMsg").textContent="❌ Title/body required"; return; }

  const fd = new FormData();
  fd.append("campId", currentCamp.id);
  if(editingPostId) fd.append("id", String(editingPostId));
  fd.append("title", title);
  fd.append("body", body);

  const cover = document.getElementById("p_cover_file").files[0];
  if(cover) fd.append("cover_file", cover);

  const galleryFiles = document.getElementById("p_gallery_files").files;
  for(const f of galleryFiles) fd.append("gallery_files[]", f);

  try{
    const res = await fetch("/youthagency/admin/api/camps.php?action=postSave", { method:"POST", body: fd });
    const j = await res.json().catch(()=>({ok:false,error:"Bad JSON"}));
    if(!res.ok || !j.ok) throw new Error(j.error || ("HTTP "+res.status));

    const gg = await apiJson("get",{id:currentCamp.id});
    currentCamp = gg.camp;
    renderPostsList();
    resetPost();
    document.getElementById("postMsg").textContent="✅ Saved";
  }catch(e){
    document.getElementById("postMsg").textContent="❌ "+e.message;
  }
}

function renderPostsList(){
  const list = document.getElementById("postsList");
  const posts = currentCamp.posts || [];
  if(!posts.length){ list.innerHTML = `<div class="muted">No posts yet.</div>`; return; }

  list.innerHTML = posts.map(p=>{
    const media = p.media || [];
    return `
      <div class="card">
        <div class="row" style="justify-content:space-between">
          <div>
            <b>${esc(p.title)}</b>
            <div class="muted">${esc(p.created_at||"")}</div>
          </div>
          <div class="row">
            <button class="btn" onclick="editPost(${p.id})">Edit</button>
            <button class="btn bad" onclick="delPost(${p.id})">Delete</button>
          </div>
        </div>

        ${p.cover ? `<div class="muted">Cover:</div><img src="${esc(p.cover)}" class="preview">` : `<div class="muted">No cover</div>`}
        <div style="white-space:pre-wrap;margin-top:10px">${esc(p.body)}</div>

        <div class="hr"></div>
        <b>Gallery</b>
        ${!media.length ? `<div class="muted">No gallery images.</div>` : `
          <div class="thumbRow">
            ${media.map(m=>`
              <div class="thumb">
                <img src="${esc(m.path)}">
                <button class="btn bad" onclick="deleteMedia(${m.id})">X</button>
              </div>
            `).join("")}
          </div>
        `}
      </div>
    `;
  }).join("");
}

function editPost(id){
  const p = (currentCamp.posts||[]).find(x=>Number(x.id)===Number(id));
  if(!p) return;
  editingPostId=Number(id);
  document.getElementById("p_title").value=p.title||"";
  document.getElementById("p_body").value=p.body||"";
  document.getElementById("p_cover_file").value="";
  document.getElementById("p_gallery_files").value="";
  document.getElementById("p_cover_preview").innerHTML = p.cover
    ? `Current:<br><img src="${esc(p.cover)}" class="preview">`
    : "No cover";
  document.getElementById("postMsg").textContent="Editing post #"+id;
}

async function delPost(id){
  if(!confirm("Delete post?")) return;
  try{
    await apiJson("postDelete",{campId: currentCamp.id, id:String(id)});
    const gg = await apiJson("get",{id:currentCamp.id});
    currentCamp = gg.camp;
    renderPostsList();
  }catch(e){
    document.getElementById("postMsg").textContent="❌ "+e.message;
  }
}

async function deleteMedia(mediaId){
  if(!confirm("Delete this image from gallery?")) return;
  try{
    await apiJson("postMediaDelete",{id:String(mediaId)});
    const gg = await apiJson("get",{id:currentCamp.id});
    currentCamp = gg.camp;
    renderPostsList();
  }catch(e){ alert("❌ "+e.message); }
}

/* REGISTRATIONS */
function openRegs(){
  if(!currentCamp.id){ document.getElementById("campMsg").textContent="❌ Save camp first"; return; }
  document.getElementById("regsModalBack").style.display="flex";
  loadRegs();
}
function closeRegs(){ document.getElementById("regsModalBack").style.display="none"; }

async function loadRegs(){
  const box = document.getElementById("regsBox");
  box.textContent = "Loading...";
  try{
    const j = await apiJson("registrations",{campId: currentCamp.id});
    const fields = j.fields || [];
    const regs = j.regs || [];
    if(!regs.length){ box.innerHTML = `<div class="muted">No registrations yet.</div>`; return; }

    box.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th><th>Created</th><th>Unique Key</th><th>IP</th>
            ${fields.map(f=>`<th>${esc(f.label)}</th>`).join("")}
          </tr>
        </thead>
        <tbody>
          ${regs.map(r=>`
            <tr>
              <td>${r.id}</td>
              <td class="muted">${esc(r.created_at)}</td>
              <td class="muted">${esc(r.unique_key)}</td>
              <td class="muted">${esc(r.ip||"")}</td>
              ${(r.values||[]).map(v=>{
                const s=(v||"").toString();
                const isLink=s.startsWith("/youthagency/uploads/");
                return `<td>${isLink ? `<a href="${esc(s)}" target="_blank">${esc(s)}</a>` : esc(s)}</td>`;
              }).join("")}
            </tr>
          `).join("")}
        </tbody>
      </table>
    `;
  }catch(e){
    box.textContent = "❌ " + e.message;
  }
}

loadCamps();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
