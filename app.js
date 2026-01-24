// app.js (FULL FIXED)

(function () {
  console.log('[app.js] loaded ✅');

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
      'contact.error': 'გთხოვთ სწორად შეავსოთ აუცილებელი ველები.'
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
      'contact.emailInfo': 'info@youth.ge'
    }
  };

  function getStoredLanguage() {
    return localStorage.getItem('language') || 'ka';
  }

  function applyTranslations(lang) {
    const dict = translations[lang] || translations.ka;
    document.documentElement.lang = lang;

    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) {
        el.textContent = dict[key];
      }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (dict[key]) {
        el.setAttribute('placeholder', dict[key]);
      }
    });

    document.querySelectorAll('[data-i18n-aria]').forEach((el) => {
      const key = el.getAttribute('data-i18n-aria');
      if (dict[key]) {
        el.setAttribute('aria-label', dict[key]);
      }
    });

    document.querySelectorAll('.lang-btn').forEach((btn) => {
      const isActive = btn.getAttribute('data-lang') === lang;
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  }

  function setLanguage(lang) {
    const nextLang = translations[lang] ? lang : 'ka';
    localStorage.setItem('language', nextLang);
    applyTranslations(nextLang);
  }

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

    const savedLang = getStoredLanguage();
    applyTranslations(savedLang);

    headerRoot.querySelectorAll('[data-lang]').forEach((btn) => {
      btn.addEventListener('click', () => {
        setLanguage(btn.getAttribute('data-lang'));
      });
    });

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
