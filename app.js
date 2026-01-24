// /youthagency/app.js (FULL FIXED + IMPROVED)
(function () {
  'use strict';

  console.log('[app.js] loaded ✅');

  // ----------------------------
  // i18n dictionary
  // ----------------------------
  const translations = {
    ka: {
      'nav.home': 'მთავარი',
      'nav.news': 'სიახლეები',
      'nav.activities': 'აქტივობები',
      'nav.camps': 'ბანაკები',
      'nav.meetings': 'შეხვედრები',
      'nav.grants': 'საგრანტო',
      'nav.rules': 'წესები',
      'nav.about': 'ჩვენს შესახებ',
      'nav.contact': 'კონტაქტი',

      'footer.searchPlaceholder': 'მოძებნე საიტზე...',
      'footer.searchAria': 'ძიება',
      'footer.searchButton': 'ძიება',
      'footer.aboutTitle': 'ჩვენს შესახებ',
      'footer.aboutText': 'სააგენტო ხელს უწყობს ახალგაზრდების ჩართულობას, განათლებასა და ინიციატივებს — საგრანტო პროგრამებით, პარტნიორული პროექტებით და პრაქტიკული სერვისებით.',
      'footer.socialLabel': 'სოციალური ქსელები',
      'footer.navTitle': 'ნავიგაცია',
      'footer.navHome': 'მთავარი',
      'footer.navCamps': 'ბანაკები',
      'footer.navActivities': 'აქტივობები',
      'footer.navGrants': 'საგრანტო პროექტები',
      'footer.servicesTitle': 'სერვისები',
      'footer.servicesNews': 'სიახლეები',
      'footer.servicesAbout': 'ჩვენს შესახებ',
      'footer.servicesContact': 'კონტაქტი',
      'footer.docsTitle': 'დოკუმენტები',
      'footer.docsPrivacy': 'კონფიდენციალურობის პოლიტიკა',
      'footer.docsTerms': 'გამოყენების წესები',
      'footer.docsCopyright': 'საავტორო უფლებები',
      'footer.address': 'ვაჟა ფშაველას ქ. #76',
      'footer.phone': '032 230 51 65',
      'footer.email': 'info@youth.ge',
      'footer.copy': 'youth.ge © 2026. ყველა უფლება დაცულია.',

      'about.title': 'ჩვენ შესახებ',
      'about.body': 'სსიპ ახალგაზრდობის სააგენტო არის საჯარო სამართლის იურიდიული პირი, რომელიც შექმნილია სახელმწიფო ახალგაზრდული პოლიტიკის სტრატეგიის შემუშავების, განხორციელებისა და კოორდინაციის მიზნით. ახალგაზრდობა არის ქვეყნის მდგრადი განვითარების მამოძრავებელი ძალა და ადამიანური კაპიტალის მთავარი განახლებადი წყარო. სახელმწიფო ახალგაზრდებისთვის და ახალგაზრდებთან ერთად ქმნის მათი, როგორც ინდივიდებისა და საზოგადოების სრულფასოვანი წევრების განვითარების მხარდამჭერ გარემოს, რაც ხელს შეუწყობს თითოეულის პოტენციალის სრულად გამოყენებას, ეკონომიკურ გაძლიერებასა და ქვეყნის განვითარების პროცესში აქტიურ მონაწილეობას.',

      'contact.title': 'კონტაქტი',
      'contact.subtitle': 'გამოგვიგზავნეთ შეტყობინება და მალე დაგიკავშირდებით.',
      'contact.name': 'სახელი და გვარი',
      'contact.email': 'ელფოსტა',
      'contact.phone': 'ტელეფონი (არასავალდებულო)',
      'contact.message': 'შეტყობინება',
      'contact.submit': 'გაგზავნა',
      'contact.successTitle': 'გმადლობთ!',
      'contact.successText': 'თქვენი შეტყობინება მიღებულია. მალე დაგიკავშირდებით.',
      'contact.error': 'გთხოვთ სწორად შეავსოთ აუცილებელი ველები.',
      'contact.infoTitle': 'საკონტაქტო ინფორმაცია',
      'contact.address': 'ვაჟა ფშაველას ქ. #76',
      'contact.phoneInfo': '032 230 51 65',
      'contact.emailInfo': 'info@youth.ge',

      'home.sliderLabel': 'მთავარი სლაიდერი',
      'home.prevSlide': 'წინა სლაიდი',
      'home.nextSlide': 'შემდეგი სლაიდი',

      'news.kicker': 'Youth Agency',
      'news.title': 'სიახლეები',
      'news.all': 'ნახე მეტი ↗',
      'news.empty': 'სიახლეები ჯერ არ არის',
      'news.featured': 'გამორჩეული',
      'news.cta': 'გაიგე მეტი',
      'news.morePosts': 'მეტი პოსტი მალე გამოჩნდება.',

      'newsIndex.title': 'სიახლეები',
      'newsIndex.searchPlaceholder': 'მოძებნე სიახლეები...',
      'newsIndex.searchButton': 'ძიება',
      'newsIndex.resetButton': 'გასუფთავება',
      'newsIndex.resultsPrefix': 'ძებნის შედეგები:',
      'newsIndex.empty': 'სიახლეები ვერ მოიძებნა',
      'newsIndex.emptyFor': 'ძიების მიხედვით',
      'newsIndex.noBody': 'დააწკაპე სრულად წასაკითხად.',
      'newsIndex.readMore': 'ვრცლად →',
      'newsIndex.noPreview': 'პრევიუ ტექსტი არ არის',
      'newsIndex.paginationAria': 'გვერდები'
    },

    en: {
      'nav.home': 'Home',
      'nav.news': 'News',
      'nav.activities': 'Activities',
      'nav.camps': 'Camps',
      'nav.meetings': 'Meetings',
      'nav.grants': 'Grants',
      'nav.rules': 'Rules',
      'nav.about': 'About Us',
      'nav.contact': 'Contact',

      'footer.searchPlaceholder': 'Search the site...',
      'footer.searchAria': 'Search',
      'footer.searchButton': 'Search',
      'footer.aboutTitle': 'About Us',
      'footer.aboutText': 'The agency supports youth engagement, education, and initiatives through grant programs, partner projects, and practical services.',
      'footer.socialLabel': 'Social networks',
      'footer.navTitle': 'Navigation',
      'footer.navHome': 'Home',
      'footer.navCamps': 'Camps',
      'footer.navActivities': 'Activities',
      'footer.navGrants': 'Grant projects',
      'footer.servicesTitle': 'Services',
      'footer.servicesNews': 'News',
      'footer.servicesAbout': 'About Us',
      'footer.servicesContact': 'Contact',
      'footer.docsTitle': 'Documents',
      'footer.docsPrivacy': 'Privacy policy',
      'footer.docsTerms': 'Terms of use',
      'footer.docsCopyright': 'Copyright',
      'footer.address': '76 Vazha-Pshavela St.',
      'footer.phone': '032 230 51 65',
      'footer.email': 'info@youth.ge',
      'footer.copy': 'youth.ge © 2026. All rights reserved.',

      'about.title': 'About Us',
      'about.body': 'The LEPL Youth Agency is a legal entity of public law established to develop, implement, and coordinate the state youth policy strategy. Youth is the driving force of sustainable development and the main renewable source of human capital. The state, together with young people, creates a supportive environment for their development as full members of society, enabling each person to fully realize their potential, strengthen economically, and participate actively in the country’s development.',

      'contact.title': 'Contact',
      'contact.subtitle': 'Send us your message and we will respond as soon as possible.',
      'contact.name': 'Full name',
      'contact.email': 'Email',
      'contact.phone': 'Phone (optional)',
      'contact.message': 'Message',
      'contact.submit': 'Send message',
      'contact.successTitle': 'Thank you!',
      'contact.successText': 'Your message has been received. We will contact you shortly.',
      'contact.error': 'Please fill out the required fields correctly.',
      'contact.infoTitle': 'Contact information',
      'contact.address': '76 Vazha-Pshavela St.',
      'contact.phoneInfo': '032 230 51 65',
      'contact.emailInfo': 'info@youth.ge',

      'home.sliderLabel': 'Main slider',
      'home.prevSlide': 'Previous slide',
      'home.nextSlide': 'Next slide',

      'news.kicker': 'Youth Agency',
      'news.title': 'News',
      'news.all': 'View more ↗',
      'news.empty': 'No news yet',
      'news.featured': 'Featured',
      'news.cta': 'Learn more',
      'news.morePosts': 'More posts will appear here.',

      'newsIndex.title': 'News',
      'newsIndex.searchPlaceholder': 'Search news...',
      'newsIndex.searchButton': 'Search',
      'newsIndex.resetButton': 'Reset',
      'newsIndex.resultsPrefix': 'Showing results for:',
      'newsIndex.empty': 'No news found',
      'newsIndex.emptyFor': 'for',
      'newsIndex.noBody': 'Click to read the full article.',
      'newsIndex.readMore': 'Read more →',
      'newsIndex.noPreview': 'No preview text',
      'newsIndex.paginationAria': 'Pagination'
    }
  };

  // ----------------------------
  // Language
  // ----------------------------
  function getStoredLanguage() {
    const v = localStorage.getItem('language');
    return (v === 'en' || v === 'ka') ? v : 'ka';
  }

  function syncLangButtons(lang) {
    document.querySelectorAll('.lang-btn').forEach((btn) => {
      const isActive = btn.getAttribute('data-lang') === lang;
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      btn.classList.toggle('active', isActive);
    });
  }

  function applyTranslations(lang) {
    const dict = translations[lang] || translations.ka;
    document.documentElement.lang = lang;

    // textContent
    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      if (key && Object.prototype.hasOwnProperty.call(dict, key)) {
        el.textContent = dict[key];
      }
    });

    // placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (key && Object.prototype.hasOwnProperty.call(dict, key)) {
        el.setAttribute('placeholder', dict[key]);
      }
    });

    // aria labels
    document.querySelectorAll('[data-i18n-aria]').forEach((el) => {
      const key = el.getAttribute('data-i18n-aria');
      if (key && Object.prototype.hasOwnProperty.call(dict, key)) {
        el.setAttribute('aria-label', dict[key]);
      }
    });

    syncLangButtons(lang);
  }

  function initTranslationObserver() {
    if (window.__i18nObserverInitialized) return;
    window.__i18nObserverInitialized = true;
    let pending = false;
    const observer = new MutationObserver(() => {
      if (pending) return;
      pending = true;
      window.requestAnimationFrame(() => {
        pending = false;
        applyTranslations(getStoredLanguage());
      });
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  function setLanguage(lang) {
    const next = translations[lang] ? lang : 'ka';
    localStorage.setItem('language', next);
    applyTranslations(next);
  }

  // ----------------------------
  // Active links (best match)
  // ----------------------------
  function normalizePath(path) {
    try {
      const u = new URL(path, window.location.origin);
      path = u.pathname;
    } catch (_) {}

    path = String(path || '').trim();
    if (!path.startsWith('/')) path = '/' + path;

    // ensure trailing slash for prefix matching
    path = path.replace(/\/+$/, '/');
    return path;
  }

  function setActiveLinks(root) {
    const current = normalizePath(window.location.pathname);

    const links = root.querySelectorAll('.header-nav a, .mobile-panel a');
    if (!links || !links.length) return;

    links.forEach(a => {
      a.classList.remove('active');
      a.removeAttribute('aria-current');
    });

    let best = null;
    let bestLen = -1;

    links.forEach(a => {
      const base = a.getAttribute('data-active') || a.getAttribute('href') || '';
      const baseNorm = normalizePath(base);

      // home exact match is handled later
      if (baseNorm !== '/' && current.startsWith(baseNorm)) {
        if (baseNorm.length > bestLen) {
          best = a;
          bestLen = baseNorm.length;
        }
      }
    });

    // home exact match: /youthagency/ highlight home
    if (!best) {
      const home = Array.from(links).find(a => normalizePath(a.getAttribute('data-active') || '') === '/youthagency/');
      if (home && current === '/youthagency/') best = home;
    }

    if (best) {
      best.classList.add('active');
      best.setAttribute('aria-current', 'page');
    }
  }

  // ----------------------------
  // Burger menu
  // header.html has: #burgerBtn, #mobilePanel
  // We'll use class "is-open"
  // ----------------------------
  function initBurgerMenu() {
    const burgerBtn = document.getElementById('burgerBtn');
    const mobilePanel = document.getElementById('mobilePanel');
    if (!burgerBtn || !mobilePanel) return;

    function close() {
      mobilePanel.classList.remove('is-open');
      burgerBtn.setAttribute('aria-expanded', 'false');
    }

    function toggle() {
      const open = !mobilePanel.classList.contains('is-open');
      mobilePanel.classList.toggle('is-open', open);
      burgerBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    burgerBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggle();
    });

    // close when click outside
    document.addEventListener('click', (e) => {
      if (!mobilePanel.classList.contains('is-open')) return;
      if (mobilePanel.contains(e.target) || burgerBtn.contains(e.target)) return;
      close();
    });

    // close on ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });

    // close when click any link inside mobile panel
    mobilePanel.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => close());
    });
  }

  // ----------------------------
  // Activities dropdown
  // header.html has: #activitiesBtn, #activitiesMenu
  // We'll use class "open"
  // ----------------------------
  function initActivitiesDropdown() {
    const activitiesBtn = document.getElementById('activitiesBtn');
    const activitiesMenu = document.getElementById('activitiesMenu');
    if (!activitiesBtn || !activitiesMenu) return;

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

    document.addEventListener('click', (e) => {
      if (activitiesMenu.contains(e.target) || activitiesBtn.contains(e.target)) return;
      closeDropdown();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeDropdown();
    });
  }

  // ----------------------------
  // Main init (called after header injection)
  // ----------------------------
  function initHeader() {
    const headerRoot = document.getElementById('siteHeader') || document;

    // Active underline
    setActiveLinks(headerRoot);

    // Optional: instant underline on click
    headerRoot.querySelectorAll('.header-nav a, .mobile-panel a').forEach(a => {
      a.addEventListener('click', () => {
        headerRoot.querySelectorAll('.header-nav a, .mobile-panel a').forEach(x => {
          x.classList.remove('active');
          x.removeAttribute('aria-current');
        });
        a.classList.add('active');
        a.setAttribute('aria-current', 'page');
      });
    });

    // Language init + buttons
    const savedLang = getStoredLanguage();
    applyTranslations(savedLang);
    initTranslationObserver();

    headerRoot.querySelectorAll('.lang-btn[data-lang]').forEach((btn) => {
      btn.addEventListener('click', () => {
        setLanguage(btn.getAttribute('data-lang') || 'ka');
      });
    });

    // UI
    initBurgerMenu();
    initActivitiesDropdown();
  }

  // expose for injected header usage
  window.initHeader = initHeader;
})();
