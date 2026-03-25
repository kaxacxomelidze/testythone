// /app.js (FULL FIXED + IMPROVED)
(function () {
  'use strict';

  if (window.__appJsLoaded) return;
  window.__appJsLoaded = true;

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

      'footer.searchPlaceholder': 'მოძებნე სიახლე, პროგრამა ან გვერდი...',
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
      'footer.copySuffix': '© 2026. ყველა უფლება დაცულია.',

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
      'contact.mapTitle': 'მდებარეობა რუკაზე',
      'contact.mapNote': 'ოფისი შეგიძლიათ მარტივად ნახოთ რუკაზე და დაგეგმოთ ვიზიტი წინასწარ.',

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
      'newsIndex.paginationAria': 'გვერდები',

      'grants.title': 'საგრანტო პროგრამები',
      'grants.subtitle': 'ახალგაზრდებისთვის განკუთვნილი საგრანტო შესაძლებლობები იდეებისა და პროექტების მხარდასაჭერად',
      'grants.rulesCta': 'წესები და პირობები',
      'grants.listTitle': 'საგრანტო პროგრამების ჩამონათვალი',
      'grants.recordsLabel': 'ჩანაწერები:',
      'grants.empty': 'ამჟამად საგრანტო პროგრამები არ მოიძებნა.',
      'grants.statusClosed': 'დახურულია',
      'grants.statusOpen': 'მიმდინარე',
      'grants.deadlineLabel': 'ვადა:',
      'grants.details': 'დეტალურად',
      'grants.paginationAria': 'გვერდები',

      'grantsView.back': 'უკან',
      'grantsView.statusClosed': 'დახურული',
      'grantsView.statusOpen': 'მიმდინარე',
      'grantsView.deadlineLabel': 'ვადა:',
      'grantsView.apply': 'განაცხადის შევსება',
      'grantsView.all': 'ყველა საგრანტო',
      'grantsView.detailsTitle': 'დეტალური აღწერა',

      'grantsApply.statusOpen': 'მიმდინარე',
      'grantsApply.statusClosed': 'დახურული',
      'grantsApply.deadlineLabel': 'ვადა:',
      'grantsApply.closedNotice': 'ამ საგრანტო პროგრამაზე განაცხადების მიღება დასრულებულია ან გამორთულია.',
      'grantsApply.stepsTitle': 'ნაბიჯები',
      'grantsApply.stepsHint': 'შეავსეთ ნაბიჯობრივად',

      'camps.heroTitle': 'ბანაკები',
      'camps.heroSubtitle': 'აღმოაჩინე ახალგაზრდული ბანაკები, თარიღები და რეგისტრაციის დეტალები.',
      'camps.searchPlaceholder': 'ძიება ბანაკებში...',
      'camps.filtersAria': 'ფილტრები',
      'camps.filterAll': 'ყველა',
      'camps.filterOpen': 'ღია',
      'camps.filterUpcoming': 'მალე',
      'camps.filterClosed': 'დახურული',
      'camps.statusOpen': 'ღია',
      'camps.statusUpcoming': 'მალე',
      'camps.statusClosed': 'დახურულია',
      'camps.emptyTitle': 'ჯერ ბანაკები არ დამატებულა.',
      'camps.emptySubtitle': 'ადმინისტრატორის პანელიდან დაამატე პირველი ბანაკი და აქ გამოჩნდება.',
      'camps.noResultsTitle': 'შედეგი ვერ მოიძებნა.',
      'camps.noResultsSubtitle': 'სცადე სხვა სიტყვა ან შეცვალე ფილტრი.',

      'campsView.back': 'ბანაკებზე დაბრუნება',
      'campsView.registrationOpen': 'რეგისტრაცია ღიაა',
      'campsView.registrationClosed': 'რეგისტრაცია დახურულია',
      'campsView.reasonManual': 'ადმინისტრატორმა დახურა',
      'campsView.reasonSoon': 'მალე დაიწყება',
      'campsView.reasonEnded': 'ვადა გავიდა',
      'campsView.noCover': 'ქავერი არ არის',
      'campsView.idLabel': 'ID:',
      'campsView.manualClosed': 'დახურულია (manual)',
      'campsView.posts': 'პოსტები',
      'campsView.postsEmpty': 'პოსტები ჯერ არ დამატებულა.',
      'campsView.registration': 'რეგისტრაცია',
      'campsView.selectPlaceholder': '-- აირჩიე --',
      'campsView.pidPlaceholder': 'პირადი ნომერი',
      'campsView.requiredNote': 'ვარსკვლავით (*) მონიშნული ველები სავალდებულოა.',
      'campsView.submitted': 'გაიგზავნა ✅',
      'campsView.submit': 'გაგზავნა',
      'campsView.errorRequired': 'გთხოვ შეავსო სავალდებულო ველები (*)',
      'campsView.submitting': 'იგზავნება...',
      'newsIndex.paginationAria': 'გვერდები',
      'notFound.title': 'გვერდი ვერ მოიძებნა',
      'notFound.body': 'შესაძლოა მისამართი არასწორად აკრიფეთ ან მოთხოვნილი გვერდი გადატანილია.',
      'notFound.home': 'მთავარზე დაბრუნება',
      'notFound.contact': 'დაგვიკავშირდით'
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

      'footer.searchPlaceholder': 'Search news, programs, or pages...',
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
      'footer.copySuffix': '© 2026. All rights reserved.',

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
      'contact.mapTitle': 'Find us on the map',
      'contact.mapNote': 'Use the map to locate our office and plan your visit in advance.',

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
      'newsIndex.paginationAria': 'Pagination',

      'grants.title': 'Grant programs',
      'grants.subtitle': 'Grant opportunities for young people to support ideas and projects',
      'grants.rulesCta': 'Rules and terms',
      'grants.listTitle': 'Grant program list',
      'grants.recordsLabel': 'Records:',
      'grants.empty': 'No grant programs found at the moment.',
      'grants.statusClosed': 'Closed',
      'grants.statusOpen': 'Open',
      'grants.deadlineLabel': 'Deadline:',
      'grants.details': 'Details',
      'grants.paginationAria': 'Pagination',

      'grantsView.back': 'Back',
      'grantsView.statusClosed': 'Closed',
      'grantsView.statusOpen': 'Open',
      'grantsView.deadlineLabel': 'Deadline:',
      'grantsView.apply': 'Apply',
      'grantsView.all': 'All grants',
      'grantsView.detailsTitle': 'Detailed description',

      'grantsApply.statusOpen': 'Open',
      'grantsApply.statusClosed': 'Closed',
      'grantsApply.deadlineLabel': 'Deadline:',
      'grantsApply.closedNotice': 'Applications for this grant program are closed or disabled.',
      'grantsApply.stepsTitle': 'Steps',
      'grantsApply.stepsHint': 'Fill out step by step',

      'camps.heroTitle': 'Camps',
      'camps.heroSubtitle': 'Discover youth camp programs, dates, and registration details.',
      'camps.searchPlaceholder': 'Search camps...',
      'camps.filtersAria': 'Filters',
      'camps.filterAll': 'All',
      'camps.filterOpen': 'Open',
      'camps.filterUpcoming': 'Upcoming',
      'camps.filterClosed': 'Closed',
      'camps.statusOpen': 'Open',
      'camps.statusUpcoming': 'Upcoming',
      'camps.statusClosed': 'Closed',
      'camps.emptyTitle': 'No camps have been added yet.',
      'camps.emptySubtitle': 'Add the first camp from the admin panel and it will appear here.',
      'camps.noResultsTitle': 'No results found.',
      'camps.noResultsSubtitle': 'Try another word or change the filter.',

      'campsView.back': 'Back to camps',
      'campsView.registrationOpen': 'Registration open',
      'campsView.registrationClosed': 'Registration closed',
      'campsView.reasonManual': 'Closed by admin',
      'campsView.reasonSoon': 'Starts soon',
      'campsView.reasonEnded': 'Deadline passed',
      'campsView.noCover': 'No cover',
      'campsView.idLabel': 'ID:',
      'campsView.manualClosed': 'Closed (manual)',
      'campsView.posts': 'Posts',
      'campsView.postsEmpty': 'No posts yet.',
      'campsView.registration': 'Registration',
      'campsView.selectPlaceholder': '-- Select --',
      'campsView.pidPlaceholder': 'Personal ID',
      'campsView.requiredNote': 'Fields marked with (*) are required.',
      'campsView.submitted': 'Submitted ✅',
      'campsView.submit': 'Submit',
      'campsView.errorRequired': 'Please fill the required fields (*)',
      'campsView.submitting': 'Submitting...',
      'newsIndex.paginationAria': 'Pagination',
      'notFound.title': 'Page not found',
      'notFound.body': 'The address may be incorrect or the page may have been moved.',
      'notFound.home': 'Back to home',
      'notFound.contact': 'Contact us'
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
    const decode = (() => {
      const el = document.createElement('textarea');
      return (value) => {
        if (value === null || value === undefined) return '';
        el.innerHTML = String(value);
        return el.value;
      };
    })();

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

    // dynamic text content (db-driven)
    document.querySelectorAll('[data-i18n-text]').forEach((el) => {
      const primary = el.getAttribute(lang === 'en' ? 'data-text-en' : 'data-text-ka');
      const fallback = el.getAttribute(lang === 'en' ? 'data-text-ka' : 'data-text-en');
      const value = (primary !== null && primary !== '') ? primary : (fallback ?? '');
      if (value !== null) el.textContent = decode(value);
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

  function initGlobalLanguageHandlers() {
    const savedLang = getStoredLanguage();
    applyTranslations(savedLang);
    initTranslationObserver();
    initPreventDummyNavigation();

    document.addEventListener('click', (event) => {
      const btn = event.target.closest('.lang-btn[data-lang]');
      if (!btn) return;
      event.preventDefault();
      setLanguage(btn.getAttribute('data-lang') || 'ka');
    });
  }

  function initPreventDummyNavigation() {
    if (window.__preventDummyNavigationInitialized) return;
    window.__preventDummyNavigationInitialized = true;

    document.addEventListener('click', (event) => {
      const link = event.target.closest('a');
      if (!link) return;
      const href = (link.getAttribute('href') || '').trim();
      if (href === '' || href === '#') {
        event.preventDefault();
      }
    });

    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      const action = (form.getAttribute('action') || '').trim();
      if (action === '#') {
        event.preventDefault();
      }
    });
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

    // home exact match: / highlight home
    if (!best) {
      const home = Array.from(links).find(a => normalizePath(a.getAttribute('data-active') || '') === '/');
      if (home && current === '/') best = home;
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
  function initFooterAccordion() {
    const footer = document.querySelector('.site-footer');
    if (!footer || footer.dataset.accordionBound === 'true') return;

    const sections = Array.from(footer.querySelectorAll('[data-footer-section]'));
    if (!sections.length) return;

    const mobileQuery = window.matchMedia('(max-width: 640px)');

    function setSectionState(section, shouldOpen, isMobile) {
      const button = section.querySelector('.footer-section-toggle');
      const body = section.querySelector('.footer-section-body');
      if (!button || !body) return;

      section.classList.toggle('is-open', shouldOpen);
      button.setAttribute('aria-expanded', String(isMobile ? shouldOpen : true));
      body.hidden = isMobile ? !shouldOpen : false;
    }

    function syncFooterState() {
      const isMobile = mobileQuery.matches;
      sections.forEach((section) => {
        const shouldOpen = !isMobile ? true : section.dataset.userToggled === 'true';
        setSectionState(section, shouldOpen, isMobile);
      });
    }

    sections.forEach((section) => {
      const button = section.querySelector('.footer-section-toggle');
      if (!button) return;

      button.addEventListener('click', () => {
        if (!mobileQuery.matches) return;
        const willOpen = !section.classList.contains('is-open');

        sections.forEach((otherSection) => {
          otherSection.dataset.userToggled = 'false';
          setSectionState(otherSection, false, true);
        });

        if (willOpen) {
          section.dataset.userToggled = 'true';
          setSectionState(section, true, true);
        }
      });
    });

    if (typeof mobileQuery.addEventListener === 'function') {
      mobileQuery.addEventListener('change', syncFooterState);
    } else if (typeof mobileQuery.addListener === 'function') {
      mobileQuery.addListener(syncFooterState);
    }

    footer.dataset.accordionBound = 'true';
    syncFooterState();
  }

  function initActivitiesDropdown() {
    const activitiesBtn = document.getElementById('activitiesBtn');
    const activitiesMenu = document.getElementById('activitiesMenu');
    const navItem = activitiesBtn ? activitiesBtn.closest('.nav-item') : null;
    if (!activitiesBtn || !activitiesMenu || !navItem) return;
    if (navItem.dataset.dropdownBound === 'true') return;

    const hoverQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
    let closeTimer = null;

    function setOpen(open) {
      activitiesMenu.classList.toggle('open', open);
      activitiesBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function openDropdown() {
      if (closeTimer) window.clearTimeout(closeTimer);
      setOpen(true);
    }

    function closeDropdown() {
      if (closeTimer) window.clearTimeout(closeTimer);
      setOpen(false);
    }

    function queueClose() {
      if (closeTimer) window.clearTimeout(closeTimer);
      closeTimer = window.setTimeout(() => setOpen(false), 120);
    }

    activitiesBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const isOpen = !activitiesMenu.classList.contains('open');
      setOpen(isOpen);
    });

    navItem.addEventListener('mouseenter', () => {
      if (!hoverQuery.matches) return;
      openDropdown();
    });

    navItem.addEventListener('mouseleave', () => {
      if (!hoverQuery.matches) return;
      queueClose();
    });

    navItem.addEventListener('focusin', () => {
      openDropdown();
    });

    navItem.addEventListener('focusout', () => {
      window.setTimeout(() => {
        if (!navItem.contains(document.activeElement)) closeDropdown();
      }, 0);
    });

    document.addEventListener('click', (e) => {
      if (navItem.contains(e.target)) return;
      closeDropdown();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeDropdown();
    });

    navItem.dataset.dropdownBound = 'true';
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
    initFooterAccordion();
  }

  function observeDynamicFooter() {
    // Avoid watching the full subtree forever (can cause heavy callback churn on dynamic pages).
    // We only need to initialize once when footer exists, then disconnect.
    if (document.querySelector('.site-footer')) {
      initFooterAccordion();
      return;
    }

    const observer = new MutationObserver(() => {
      if (!document.querySelector('.site-footer')) return;
      initFooterAccordion();
      observer.disconnect();
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  // expose for injected header/footer usage
  window.initHeader = initHeader;
  window.initFooterAccordion = initFooterAccordion;

  initGlobalLanguageHandlers();
  observeDynamicFooter();
})();
