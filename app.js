// app.js (FULL FIXED)

(function () {
  console.log('[app.js] loaded ✅');

  function normalizePath(path) {
    // ensure trailing slash for matching
    if (!path) return '/';
    return path.endsWith('/') ? path : (path + '/');
  }

  function setActiveLinks(root) {
    const current = normalizePath(window.location.pathname);

    const links = root.querySelectorAll('.header-nav a, .mobile-panel a');
    if (!links.length) return;

    // clear all
    links.forEach(a => a.classList.remove('active'));

    // choose best match: longest prefix match
    let best = null;
    let bestLen = -1;

    links.forEach(a => {
      const base = a.getAttribute('data-active') || a.getAttribute('href') || '';
      const baseNorm = normalizePath(base);

      // only match if current starts with baseNorm
      if (baseNorm !== '/' && current.startsWith(baseNorm)) {
        if (baseNorm.length > bestLen) {
          best = a;
          bestLen = baseNorm.length;
        }
      }
    });

    // special case: home
    if (!best) {
      // if you are exactly /youthagency/ then highlight home
      const home = Array.from(links).find(a => normalizePath(a.getAttribute('data-active')) === '/youthagency/');
      if (home && normalizePath(current) === '/youthagency/') best = home;
    }

    if (best) best.classList.add('active');

    // also: if "camps" is active, you may want "activities" visual state (optional)
    // (CSS could style .nav-item.open etc. if you want)
  }

  function initHeader() {
    const headerRoot = document.getElementById('siteHeader') || document;

    // ✅ set active underline based on URL
    setActiveLinks(headerRoot);

    // ✅ clicking links updates underline immediately (nice UX)
    headerRoot.querySelectorAll('.header-nav a, .mobile-panel a').forEach(a => {
      a.addEventListener('click', () => {
        headerRoot.querySelectorAll('.header-nav a, .mobile-panel a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
      });
    });

    // Burger menu
    const burgerBtn = document.getElementById('burgerBtn');
    const mobilePanel = document.getElementById('mobilePanel');

    if (burgerBtn && mobilePanel) {
      burgerBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        mobilePanel.classList.toggle('is-open');
        burgerBtn.setAttribute('aria-expanded', mobilePanel.classList.contains('is-open') ? 'true' : 'false');
      });

      document.addEventListener('click', (e) => {
        if (!mobilePanel.classList.contains('is-open')) return;
        if (mobilePanel.contains(e.target) || burgerBtn.contains(e.target)) return;
        mobilePanel.classList.remove('is-open');
        burgerBtn.setAttribute('aria-expanded', 'false');
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          mobilePanel.classList.remove('is-open');
          burgerBtn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // Activities dropdown
    const activitiesBtn = document.getElementById('activitiesBtn');
    const activitiesMenu = document.getElementById('activitiesMenu');

    if (activitiesBtn && activitiesMenu) {
      function closeDropdown() {
        activitiesMenu.classList.remove('open');
        activitiesBtn.setAttribute('aria-expanded', 'false');
      }

      activitiesBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = activitiesMenu.classList.toggle('open');
        activitiesBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      // close only if click outside menu/button
      document.addEventListener('click', (e) => {
        if (activitiesMenu.contains(e.target) || activitiesBtn.contains(e.target)) return;
        closeDropdown();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDropdown();
      });
    }
  }

  window.initHeader = initHeader;
})();
