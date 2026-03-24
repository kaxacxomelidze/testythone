console.log("✅ slider.js loaded");

document.addEventListener("DOMContentLoaded", async () => {
  console.log("✅ DOM ready");

  const track = document.getElementById("slidesTrack");
  const dots = document.getElementById("dots");
  const prev = document.getElementById("prevBtn");
  const next = document.getElementById("nextBtn");

  if (!track || !dots) {
    console.error("❌ Slider HTML not found");
    return;
  }

  console.log("✅ Slider HTML found");

  // Try root path first, then legacy subpath fallback.
  const API_URLS = ["/data/slides_api.php", "/youthagency/data/slides_api.php"];
  console.log("API candidates:", API_URLS);

  async function fetchSlidesData() {
    for (const url of API_URLS) {
      try {
        const res = await fetch(url, { headers: { "Accept": "application/json" } });
        console.log("API status:", url, res.status);
        if (!res.ok) continue;

        const raw = await res.text();
        const parsed = JSON.parse(raw);
        return parsed;
      } catch (e) {
        console.warn("⚠️ API candidate failed:", url, e);
      }
    }
    return null;
  }

  const data = await fetchSlidesData();
  if (!data) {
    console.error("❌ API fetch failed for all candidates", API_URLS);
    return;
  }

  console.log("API DATA:", data);

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
    track.innerHTML = slides.map(s => `
      <div class="slide">
        <img src="${img(s.image)}">
        <div class="slide-content">
          ${s.title ? `<h3>${s.title}</h3>` : ""}
        </div>
      </div>
    `).join("");

    dots.innerHTML = slides.map((_, i) =>
      `<button class="dot ${i === index ? "active" : ""}" data-i="${i}"></button>`
    ).join("");

    move();
  }

  function move() {
    track.style.transform = `translateX(${-index * 100}%)`;
    dots.querySelectorAll(".dot").forEach((d, i) =>
      d.classList.toggle("active", i === index)
    );
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

  render();
  start();

  console.log("✅ Slider initialized");
});
