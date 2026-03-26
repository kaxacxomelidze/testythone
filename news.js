(async function () {
  const wrap = document.getElementById('newsGrid');
  if (!wrap) return;

  console.log('✅ news.js loaded');

  const url = 'data/news_api.php';

  try {
    const res = await fetch(url, { cache: 'default' });
    console.log('News API status:', res.status);

    const payload = res.ok ? await res.json() : { ok: false, news: [] };
    console.log('News payload:', payload);

    const items = Array.isArray(payload.news) ? payload.news : [];

    if (!items.length) {
      wrap.innerHTML = `<div class="news-empty">No news yet</div>`;
      return;
    }

    wrap.innerHTML = items.map((n, i) => {
      const link = n.id ? `/news/${encodeURIComponent(n.id)}/${encodeURIComponent(n.slug || 'news')}` : '#';
      const preview = (n.body || '').toString().slice(0, 180);

      return `
        <article class="news-card">
          ${n.image_path ? `<img class="news-img" src="${String(n.image_path).startsWith("/") ? escAttr(n.image_path) : "/" + escAttr(n.image_path)}" alt="" ${i < 2 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy" fetchpriority="auto"'} decoding="async">` : ``}
          <div class="news-body">
            <div class="news-title">${escHtml(n.title || '')}</div>
            ${n.published_at ? `<div class="news-date">${escHtml(n.published_at)}</div>` : ``}
            ${preview ? `<div class="news-text">${escHtml(preview)}${(n.body||'').length>180?'...':''}</div>` : ``}
            <a class="news-link" href="${link}">Read more</a>
          </div>
        </article>
      `;
    }).join('');

  } catch (err) {
    console.error('❌ News load error:', err);
    wrap.innerHTML = `<div class="news-empty">Failed to load news</div>`;
  }

  function escHtml(str) {
    return String(str).replace(/[&<>"']/g, m => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "\"": "&quot;",
      "'": "&#039;"
    }[m]));
  }
  function escAttr(str) { return escHtml(str); }
})();
