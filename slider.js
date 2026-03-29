document.addEventListener("DOMContentLoaded", async () => {

  const track = document.getElementById("slidesTrack");
  const dots = document.getElementById("dots");
  const prev = document.getElementById("prevBtn");
  const next = document.getElementById("nextBtn");

  if (!track || !dots) {
    console.error("❌ Slider HTML not found");
    return;
  }

  const API_URLS = ["/data/slides_api.php"];

  async function fetchSlidesData() {
    for (const url of API_URLS) {
      try {
        const res = await fetch(url, { headers: { "Accept": "application/json" } });
        if (!res.ok) continue;

        const raw = await res.text();
        const parsed = JSON.parse(raw);
        return parsed;
      } catch (e) {
        console.warn("API candidate failed:", url, e);
      }
    }
    return null;
  }

  const data = await fetchSlidesData();
  if (!data) {
    console.error("API fetch failed for all candidates", API_URLS);
    return;
  }


  let slides = data.slides || [];
  const autoplay = Number(data.settings?.autoplay_ms || 4500);

  slides = slides.filter(s => s.image);

  if (!slides.length) {
    track.innerHTML = "<p>No slides</p>";
    return;
  }

  let index = 0;
  let timer;

  function img(path) {
    if (path.startsWith("/")) return path;
    return "/" + path;
  }

  function render() {
    track.innerHTML = slides.map((s, i) => `
      <div class="slide">
        <img src="${img(s.image)}" alt="${s.title ? String(s.title).replace(/"/g, '&quot;') : ''}" ${i === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy" fetchpriority="auto"'} decoding="async">
        <div class="slide-content">
          ${s.title ? `<h3 class="slide-title">${s.title}</h3>` : ""}
        </div>
      </div>
    `).join("");

    dots.innerHTML = slides.map((_, i) => {
      const current = i === index;
      return `<button class="dot ${current ? "active" : ""}" data-i="${i}" aria-label="Slide ${i + 1}" aria-current="${current ? 'true' : 'false'}"></button>`;
    }).join("");

    move();
  }

  function move() {
    track.style.transform = `translateX(${-index * 100}%)`;
    dots.querySelectorAll(".dot").forEach((d, i) => {
      const active = i === index;
      d.classList.toggle("active", active);
      d.setAttribute("aria-current", active ? "true" : "false");
    });
  }

  function start() {
    timer = setInterval(() => {
      index = (index + 1) % slides.length;
      move();
    }, autoplay);
  }

  function stop() {
    clearInterval(timer);
  }

  dots.onclick = e => {
    if (!e.target.dataset.i) return;
    stop();
    index = Number(e.target.dataset.i);
    move();
    start();
  };

  prev.onclick = () => {
    stop();
    index = (index - 1 + slides.length) % slides.length;
    move();
    start();
  };

  next.onclick = () => {
    stop();
    index = (index + 1) % slides.length;
    move();
    start();
  };

  let touchStartX = 0;
  let touchEndX = 0;
  const SWIPE_THRESHOLD = 35;

  track.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0]?.clientX || 0;
  }, { passive: true });

  track.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0]?.clientX || 0;
    const delta = touchEndX - touchStartX;
    if (Math.abs(delta) < SWIPE_THRESHOLD) return;

    stop();
    if (delta < 0) index = (index + 1) % slides.length;
    else index = (index - 1 + slides.length) % slides.length;
    move();
    start();
  }, { passive: true });

  render();
  start();

});
