<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = (string)$_SESSION['csrf'];

$title = 'ადმინი — სპეციალური გვერდები';
ob_start();
?>
<style>
.sp-wrap{display:grid;grid-template-columns:320px 1fr;gap:14px}
@media(max-width:980px){.sp-wrap{grid-template-columns:1fr}}
.cardx{margin-top:14px;background:rgba(17,28,51,.55);border:1px solid #1e2a45;border-radius:16px;padding:14px}
.row{display:flex;gap:10px;flex-wrap:wrap}
.row>*{flex:1}
input,textarea,select{width:100%;padding:10px;border-radius:10px;border:1px solid #2b3a5d;background:#0d1528;color:#e5e7eb}
.btn{padding:10px 12px;border-radius:10px;border:1px solid #2b3a5d;background:#1a2440;color:#fff;font-weight:800;cursor:pointer}
.btn.ac{background:#1f3f7a;border-color:#2d5fba}
.btn.bad{background:#4a1f2a;border-color:#8c3245}
.small{color:#9fb0d3;font-size:12px;font-weight:700}
.linkRow{border:1px solid #2b3a5d;border-radius:12px;padding:10px;margin-top:10px}
.item{padding:10px;border:1px solid #2b3a5d;border-radius:10px;margin-top:8px;cursor:pointer}
.item.active{border-color:#2d5fba;background:#10213f}
</style>

<div class="cardx">
  <h2 style="margin:0">სპეციალური გვერდების ბილდერი</h2>
  <div class="small">შექმენი / შეცვალე გვერდები Mokhalise_fest-ის მსგავსი დიზაინით (ლოგო + ტექსტი + ლინკების სია).</div>
</div>

<div class="sp-wrap">
  <div class="cardx">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <b>გვერდები</b>
      <button class="btn ac" type="button" onclick="newPage()">+ ახალი</button>
    </div>
    <div id="pagesList" style="margin-top:8px"></div>
  </div>

  <div class="cardx">
    <div class="row">
      <div>
        <label class="small">Slug (მისამართი: /slug/)</label>
        <input id="slug" placeholder="მაგ: Mokhalise_fest">
      </div>
      <div>
        <label class="small">სათაური</label>
        <input id="titleInput" placeholder="გვერდის სათაური">
      </div>
    </div>

    <div class="row" style="margin-top:10px">
      <div>
        <label class="small">აღწერა</label>
        <textarea id="descInput" rows="2" placeholder="მოკლე ტექსტი"></textarea>
      </div>
    </div>

    <div class="row" style="margin-top:10px">
      <div>
        <label class="small">Facebook Pixel ID (ავტომატურად ყველა სპეციალურ გვერდზე)</label>
        <input id="pixelIdInput" placeholder="მაგ: 123456789012345">
      </div>
    </div>

    <div class="row" style="margin-top:10px">
      <div>
        <label class="small">ლოგოს URL</label>
        <input id="logoInput" placeholder="/imgs/logo.png ან https://...">
      </div>
      <div>
        <label class="small">ან ატვირთე Logo</label>
        <input id="logoFile" type="file" accept="image/*">
      </div>
    </div>

    <hr style="border-color:#2b3a5d;opacity:.5;margin:14px 0">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <b>ტექსტი + ლინკები</b>
      <button class="btn" type="button" onclick="addLinkRow()">+ ლინკის დამატება</button>
    </div>
    <div id="linksBox"></div>

    <div class="row" style="margin-top:14px">
      <div><button class="btn ac" type="button" onclick="savePage()">გვერდის შენახვა</button></div>
      <div><button class="btn bad" type="button" onclick="deletePage()">გვერდის წაშლა</button></div>
    </div>
    <div class="small" id="status" style="margin-top:8px"></div>
  </div>
</div>

<script>
const CSRF = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;
const API = 'api/special_pages_api.php';
let pages = [];
let current = null;
let pixelId = '';

function esc(s){return (s??'').toString().replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');}

async function apiGet(action){
  const r = await fetch(API + '?action=' + encodeURIComponent(action), {headers:{'X-CSRF': CSRF}});
  const raw = await r.text();
  let j = null;
  try{ j = JSON.parse(raw); }catch(_){}
  if(!r.ok) throw new Error((j && j.error) ? j.error : ('HTTP ' + r.status));
  if(!j || !j.ok) throw new Error((j && j.error) ? j.error : 'API შეცდომა');
  return j;
}

async function apiSave(fd){
  const r = await fetch(API + '?action=save', {method:'POST', headers:{'X-CSRF': CSRF}, body: fd});
  const raw = await r.text();
  let j = null;
  try{ j = JSON.parse(raw); }catch(_){}
  if(!r.ok) throw new Error((j && j.error) ? j.error : ('HTTP ' + r.status));
  if(!j || !j.ok) throw new Error((j && j.error) ? j.error : 'API შეცდომა');
  return j;
}

async function apiDelete(slug){
  const r = await fetch(API + '?action=delete', {method:'POST', headers:{'X-CSRF': CSRF,'Content-Type':'application/json'}, body: JSON.stringify({slug})});
  const raw = await r.text();
  let j = null;
  try{ j = JSON.parse(raw); }catch(_){}
  if(!r.ok) throw new Error((j && j.error) ? j.error : ('HTTP ' + r.status));
  if(!j || !j.ok) throw new Error((j && j.error) ? j.error : 'API შეცდომა');
  return j;
}

function linkRowHtml(link={}, idx=0){
  return `<div class="linkRow" data-link-row="${idx}">
    <div class="row">
      <div><label class="small">ტექსტი</label><input data-k="label" value="${esc(link.label||'')}" placeholder="ლინკის ტექსტი"></div>
      <div>
        <label class="small">ლინკის ტიპი</label>
        <select data-k="link_type">
          <option value="open" ${(link.link_type||'open')==='open'?'selected':''}>გახსნა</option>
          <option value="download" ${(link.link_type||'open')==='download'?'selected':''}>ჩამოტვირთვა</option>
        </select>
      </div>
    </div>
    <div class="row" style="margin-top:8px">
      <div><label class="small">URL / ფაილის მისამართი</label><input data-k="url" value="${esc(link.url||'')}" placeholder="/downloads/file.pdf ან https://..."></div>
      <div><label class="small">ან ატვირთე ფაილი</label><input data-k="file" type="file"></div>
    </div>
    <div style="margin-top:8px"><button class="btn bad" type="button" onclick="removeLinkRow(${idx})">წაშლა</button></div>
  </div>`;
}

function renderLinks(links=[]){
  const box = document.getElementById('linksBox');
  box.innerHTML = links.map((l,i)=>linkRowHtml(l,i)).join('') || '<div class="small" style="margin-top:8px">ლინკები ჯერ არ არის.</div>';
}

function collectLinks(){
  const rows = [...document.querySelectorAll('[data-link-row]')];
  return rows.map((row, i)=>{
    const label = row.querySelector('[data-k="label"]').value.trim();
    const link_type = row.querySelector('[data-k="link_type"]').value;
    const url = row.querySelector('[data-k="url"]').value.trim();
    const file = row.querySelector('[data-k="file"]').files?.[0] || null;
    return {label, link_type, url, file, idx: i};
  }).filter(x=>x.label || x.url || x.file);
}

function renderPages(){
  const box = document.getElementById('pagesList');
  box.innerHTML = pages.map(p=>`<div class="item ${current===p.slug?'active':''}" onclick="selectPage('${esc(p.slug)}')"><b>${esc(p.title||p.slug)}</b><div class="small">/${esc(p.slug)}/</div></div>`).join('') || '<div class="small">გვერდები ჯერ არ არის.</div>';
}

function selectPage(slug){
  const p = pages.find(x=>x.slug===slug); if(!p) return;
  current = slug;
  document.getElementById('slug').value = p.slug || '';
  document.getElementById('titleInput').value = p.title || '';
  document.getElementById('descInput').value = p.description || '';
  document.getElementById('logoInput').value = p.logo || '';
  document.getElementById('logoFile').value = '';
  renderLinks(Array.isArray(p.links)?p.links:[]);
  renderPages();
}

function addLinkRow(){
  const links = collectLinks();
  links.push({label:'', url:'', link_type:'open'});
  renderLinks(links);
}

function removeLinkRow(idx){
  const links = collectLinks().filter((_,i)=>i!==idx);
  renderLinks(links);
}

function newPage(){
  current = null;
  document.getElementById('slug').value = '';
  document.getElementById('titleInput').value = '';
  document.getElementById('descInput').value = '';
  document.getElementById('logoInput').value = '';
  document.getElementById('logoFile').value = '';
  renderLinks([]);
  renderPages();
}

async function savePage(){
  const slug = document.getElementById('slug').value.trim();
  const title = document.getElementById('titleInput').value.trim();
  const description = document.getElementById('descInput').value.trim();
  const logo = document.getElementById('logoInput').value.trim();
  const facebookPixelId = document.getElementById('pixelIdInput').value.trim();
  if(!slug || !title) return alert('Slug და სათაური სავალდებულოა');

  const links = collectLinks();
  const fd = new FormData();
  fd.append('slug', slug);
  fd.append('title', title);
  fd.append('description', description);
  fd.append('logo', logo);
  fd.append('facebook_pixel_id', facebookPixelId);
  fd.append('links_json', JSON.stringify(links.map(x=>({label:x.label, url:x.url, link_type:x.link_type}))));

  const logoFile = document.getElementById('logoFile').files?.[0] || null;
  if(logoFile) fd.append('logo_file', logoFile);
  links.forEach(l => { if(l.file) fd.append('link_file_' + l.idx, l.file); });

  try{
    const res = await apiSave(fd);
    document.getElementById('status').textContent = 'შენახულია ✅ მისამართი: ' + (res.url || ('/' + slug + '/'));
    await load();
    selectPage(res.slug || slug);
  }catch(e){ alert(e.message); }
}

async function deletePage(){
  const slug = document.getElementById('slug').value.trim();
  if(!slug) return;
  if(!confirm('წავშალო გვერდის კონფიგურაცია?')) return;
  try{
    await apiDelete(slug);
    document.getElementById('status').textContent = 'წაიშალა';
    await load();
    newPage();
  }catch(e){ alert(e.message); }
}

async function load(){
  const j = await apiGet('list');
  pages = j.items || [];
  pixelId = j.facebook_pixel_id || '';
  document.getElementById('pixelIdInput').value = pixelId;
  renderPages();
}

load().then(()=>{
  if(pages[0]) selectPage(pages[0].slug);
  else newPage();
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
