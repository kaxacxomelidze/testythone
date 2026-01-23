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

  // FIXED API PATH (NO MAGIC)
  const API_URL = "/youthagency/data/slides_api.php";
  console.log("API:", API_URL);

  let data;
  try {
    const res = await fetch(API_URL);
    console.log("API status:", res.status);
    data = await res.json();
  } catch (e) {
    console.error("❌ API fetch failed", e);
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
    return "/youthagency/" + path;
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
