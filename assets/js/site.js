// Language switching and the enquiry form. Content stays readable without JavaScript.
(() => {
  const dict = JSON.parse(document.getElementById('i18n').textContent);
  let lang = document.documentElement.lang === 'id' ? 'id' : 'en';
  const T = (key) => dict[lang][key] ?? key;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const heroTitle = document.querySelector('.hero h1');
  let heroEntranceTimer;
  function finishHeroEntrance() {
    clearTimeout(heroEntranceTimer);
    heroTitle?.classList.remove('hero-entrance');
    heroTitle?.classList.add('hero-underlined');
  }
  if (heroTitle && !reduce) {
    let wordIndex = 0;
    heroTitle.querySelectorAll('[data-i18n]').forEach(line => {
      const words = line.textContent.trim().split(/\s+/);
      line.replaceChildren();
      words.forEach((word, index) => {
        const span = document.createElement('span');
        span.className = 'hero-word';
        span.style.setProperty('--hero-word', wordIndex++);
        span.textContent = word;
        line.append(span);
        if (index < words.length - 1) line.append(' ');
      });
    });
    heroTitle.classList.add('hero-entrance');
    heroEntranceTimer = setTimeout(() => heroTitle.classList.add('hero-underlined'), (wordIndex - 1) * 55 + 700);
  }
  const highlights = [...document.querySelectorAll('.scroll-highlight')];
  const intro = document.querySelector('[data-word-reveal]');
  function layoutIntroUnderline() {
    if (!intro || reduce) return;
    intro.querySelector('.intro-ink')?.remove();
    const words = [...intro.querySelectorAll('.intro-finish .reveal-word')];
    if (!words.length) return;
    const lines = [];
    const fontSize = Number.parseFloat(getComputedStyle(intro).fontSize);
    words.forEach(word => {
      const top = word.offsetTop;
      let line = lines.find(row => Math.abs(row.top - top) < 3);
      if (!line) { line = {top, left: word.offsetLeft, right: 0, bottom: top + word.offsetHeight}; lines.push(line); }
      line.left = Math.min(line.left, word.offsetLeft);
      line.right = Math.max(line.right, word.offsetLeft + word.offsetWidth);
    });
    const ink = document.createElement('span');
    ink.className = 'intro-ink';
    ink.setAttribute('aria-hidden', 'true');
    const totalWidth = lines.reduce((sum, line) => sum + line.right - line.left, 0);
    const textReady = Math.max(0, intro.querySelectorAll('.reveal-word').length - 1) * 38 + 650;
    let travelled = 0;
    lines.forEach(line => {
      const width = line.right - line.left;
      const bar = document.createElement('span');
      bar.className = 'intro-ink-line';
      Object.assign(bar.style, {
        left: `${line.left}px`, top: `${line.bottom - fontSize * .39}px`,
        width: `${width}px`, height: `${fontSize * .17}px`,
        animationDelay: `${textReady + travelled / totalWidth * 1200}ms`,
        animationDuration: `${width / totalWidth * 1200}ms`
      });
      ink.append(bar);
      travelled += width;
    });
    intro.append(ink);
  }
  function splitIntro() {
    if (!intro) return;
    let index = 0;
    intro.querySelectorAll('[data-i18n]').forEach((leaf) => {
      const words = leaf.textContent.trim().split(/\s+/);
      leaf.replaceChildren();
      words.forEach((word, n) => {
        const span = document.createElement('span');
        span.className = 'reveal-word';
        span.style.setProperty('--word', index++);
        span.textContent = word;
        leaf.append(span);
        if (n < words.length - 1) leaf.append(' ');
      });
    });
    requestAnimationFrame(layoutIntroUnderline);
  }
  if (intro && !reduce) {
    new ResizeObserver(layoutIntroUnderline).observe(intro);
    document.fonts.ready.then(layoutIntroUnderline);
  }
  // Animate native details while retaining their keyboard and no-script behavior.
  const faqItems = [...document.querySelectorAll('.faq-item')];
  if (!reduce) {
    const faqState = new WeakMap();
    function animateAnswer(item, open) {
      const previous = faqState.get(item);
      const from = item.getBoundingClientRect().height;
      previous?.animation?.cancel();
      const state = {open, animation: null};
      faqState.set(item, state);
      item.style.height = '';
      item.open = true;
      const border = item.offsetHeight - item.clientHeight;
      const to = open ? item.getBoundingClientRect().height : item.querySelector('summary').offsetHeight + border;
      item.style.height = `${from}px`;
      item.classList.add('is-animating');
      const animation = item.animate([{height: `${from}px`}, {height: `${to}px`}], {
        duration: 360, easing: 'cubic-bezier(.22,1,.36,1)'
      });
      state.animation = animation;
      animation.onfinish = () => {
        if (faqState.get(item) !== state) return;
        item.open = open;
        item.style.height = '';
        item.classList.remove('is-animating');
        state.animation = null;
      };
    }
    faqItems.forEach(item => {
      item.removeAttribute('name');
      item.classList.add('faq-smooth');
      faqState.set(item, {open: item.open, animation: null});
      item.querySelector('summary').addEventListener('click', event => {
        event.preventDefault();
        const open = !faqState.get(item).open;
        if (open) faqItems.forEach(other => {
          if (other !== item && faqState.get(other).open) animateAnswer(other, false);
        });
        animateAnswer(item, open);
      });
    });
  }
  const projectTabs = [...document.querySelectorAll('[data-project-tab]')];
  const projectPanels = [...document.querySelectorAll('[data-project]')];
  if (projectTabs.length) {
    document.querySelector('[data-project-tabs]').hidden = false;
    const workSection = document.querySelector('#work');
    const playback = document.querySelector('[data-project-playback]');
    const pauseButton = document.querySelector('[data-project-pause]');
    let currentProject = 0;
    let elapsed = 0;
    let lastFrame = null;
    let paused = false;
    let inView = false;
    playback.hidden = false;
    pauseButton.addEventListener('click', () => {
      paused = !paused;
      pauseButton.setAttribute('aria-pressed', String(paused));
      pauseButton.dataset.i18nAria = paused ? 'work.play' : 'work.pause';
      pauseButton.setAttribute('aria-label', T(pauseButton.dataset.i18nAria));
    });
    new IntersectionObserver(entries => { inView = entries[0].isIntersecting; }, {threshold: .1}).observe(workSection);
    function selectProject(tab) {
      currentProject = projectTabs.indexOf(tab);
      elapsed = 0;
      projectTabs.forEach(button => button.style.setProperty('--project-progress', '0'));
      projectTabs.forEach(button => {
        const selected = button === tab;
        button.setAttribute('aria-selected', String(selected));
        button.tabIndex = selected ? 0 : -1;
      });
      projectPanels.forEach(panel => {
        const selected = panel.dataset.project === tab.dataset.projectTab;
        panel.hidden = !selected;
        panel.classList.toggle('is-active', selected);
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', `project-tab-${panel.dataset.project}`);
        panel.tabIndex = 0;
      });
    }
    projectTabs.forEach((tab, index) => {
      tab.addEventListener('click', () => selectProject(tab));
      tab.addEventListener('keydown', event => {
        let next;
        if (event.key === 'ArrowRight') next = (index + 1) % projectTabs.length;
        if (event.key === 'ArrowLeft') next = (index - 1 + projectTabs.length) % projectTabs.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = projectTabs.length - 1;
        if (next === undefined) return;
        event.preventDefault();
        projectTabs[next].focus();
        selectProject(projectTabs[next]);
      });
    });
    selectProject(projectTabs[0]);
    // Links like #project-dimas (the service cards) open that project's tab, then scroll to the work section.
    document.querySelectorAll('a[href^="#project-"]').forEach(link => link.addEventListener('click', event => {
      const tab = projectTabs.find(t => `#project-${t.dataset.projectTab}` === link.getAttribute('href'));
      if (!tab) return;
      event.preventDefault();
      selectProject(tab);
      workSection.scrollIntoView({behavior: reduce ? 'auto' : 'smooth'});
    }));
    function advanceProjects(now) {
      const delta = lastFrame === null ? 0 : now - lastFrame;
      lastFrame = now;
      if (!paused && inView && !document.hidden) {
        elapsed += delta;
        if (elapsed >= 5000) selectProject(projectTabs[(currentProject + 1) % projectTabs.length]);
        projectTabs[currentProject].style.setProperty('--project-progress', String(elapsed / 5000));
      }
      requestAnimationFrame(advanceProjects);
    }
    requestAnimationFrame(advanceProjects);
  }
  if (!reduce && 'IntersectionObserver' in window) {
    splitIntro();
    const reveals = [...document.querySelectorAll('.prod,.step,.offering,.section-head')];
    reveals.forEach(el => el.setAttribute('data-reveal', ''));
    const io = new IntersectionObserver((entries) => {
      // Process steps arriving together rise one after another; a timer keeps hover free of delay.
      let stepOrder = 0;
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        setTimeout(() => el.classList.add('is-visible'), el.matches('.step') ? stepOrder++ * 160 : 0);
        io.unobserve(el);
      });
    }, {threshold:0.18});
    [...highlights, ...reveals, ...(intro ? [intro] : [])].forEach(el => io.observe(el));
    const aiCards = [...document.querySelectorAll('.ai-path li')];
    const aiCardObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-ai-visible');
        aiCardObserver.unobserve(entry.target);
      });
    }, {threshold: .25, rootMargin: '0px 0px -12% 0px'});
    aiCards.forEach((card, index) => {
      card.classList.add('ai-scroll-card');
      card.style.setProperty('--card-delay', `${index * 120}ms`);
      aiCardObserver.observe(card);
    });
    document.documentElement.classList.add('motion-ready');
  }
  // One restrained word reveal system for editorial text throughout the page.
  const textBlocks = [...document.querySelectorAll('main h2, main h3, main p, main dd, main .step-output strong, .footer-about>p')]
    .filter(block => !block.closest('.hero h1, [data-word-reveal], form, summary, .project-playback') && !block.matches('.eyebrow, .project-file, .field-error, .field-help'));
  const textObserver = !reduce && 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-text-visible');
      textObserver.unobserve(entry.target);
    });
  }, {threshold: .12, rootMargin: '0px 0px -20px 0px'}) : null;
  function prepareTextReveals() {
    if (!textObserver) return;
    textBlocks.forEach(block => {
      const leaves = block.querySelectorAll('[data-i18n]');
      const targets = leaves.length ? [...leaves] : (block.children.length && !block.classList.contains('word-motion') ? [] : [block]);
      let index = 0;
      targets.forEach(leaf => {
        const words = leaf.textContent.trim().split(/(\s+)/);
        leaf.replaceChildren();
        words.forEach(word => {
          if (!word.trim()) { leaf.append(word); return; }
          const span = document.createElement('span');
          span.className = 'soft-word';
          span.style.setProperty('--text-delay', `${Math.min(index++ * 28, 320)}ms`);
          span.textContent = word;
          leaf.append(span);
        });
      });
      if (!index) return;
      block.classList.add('word-motion');
      if (!block.classList.contains('is-text-visible')) textObserver.observe(block);
    });
  }
  prepareTextReveals();
  const receivedBoard = document.querySelector('[data-received-board]');
  function setSeg(seg, text) {
    const chars = text.toUpperCase().padEnd(seg.children.length);
    [...seg.children].forEach((cell, n) => { cell.firstElementChild.textContent = chars[n]; });
  }
  // One shared frame loop keeps every translated word in sync, including off-screen copy.
  let languageFrame = 0;
  let languageEntries = [];
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  function restoreLanguageLabels() {
    languageEntries.forEach(({el, label}) => {
      if (label === null) el.removeAttribute('aria-label'); else el.setAttribute('aria-label', label);
      el.classList.remove('is-scrambling');
    });
  }
  function translateWithScramble() {
    finishHeroEntrance();
    cancelAnimationFrame(languageFrame);
    restoreLanguageLabels();
    languageEntries = [...document.querySelectorAll('body [data-i18n]')].map(el => {
      const key = el.dataset.i18n;
      const target = T(key);
      const entry = {el, key, target, label:el.getAttribute('aria-label')};
      el.textContent = target;
      // Keep assistive labels and live form feedback readable during the visual effect.
      if (!reduce && !el.closest('[aria-live], [role="status"]')) {
        el.setAttribute('aria-label', target);
        el.classList.add('is-scrambling');
      }
      return entry;
    });
    function finish() {
      languageEntries.forEach(({el, key, target}) => {
        if (el.dataset.i18n === key) el.textContent = target;
      });
      restoreLanguageLabels();
      languageEntries = [];
      delete document.documentElement.dataset.languageTransition;
      if (!reduce) { splitIntro(); prepareTextReveals(); }
    }
    if (reduce) { finish(); return; }
    document.documentElement.dataset.languageTransition = lang;
    const start = performance.now();
    let last = -Infinity;
    function tick(now) {
      const progress = Math.min((now - start) / 680, 1);
      if (progress === 1) { finish(); return; }
      if (now - last >= 30) {
        last = now;
        languageEntries.forEach(({el, key, target}) => {
          if (!el.classList.contains('is-scrambling') || el.dataset.i18n !== key) return;
          const settled = Math.floor(progress * (target.length + 4));
          el.textContent = [...target].map((char, n) => {
            if (n < settled || !/[a-z]/i.test(char)) return char;
            const random = alphabet[Math.floor(Math.random() * alphabet.length)];
            return char === char.toLowerCase() ? random.toLowerCase() : random;
          }).join('');
        });
      }
      languageFrame = requestAnimationFrame(tick);
    }
    tick(start);
  }
  document.querySelectorAll('[data-lang]').forEach((a) => a.addEventListener('click', (ev) => {
    ev.preventDefault();
    if (a.dataset.lang === lang) return;
    lang = a.dataset.lang;
    document.documentElement.lang = lang;
    document.title = T('meta.title');
    const url = new URL(location.href);
    url.searchParams.set('lang', lang);
    history.replaceState(null, '', url);
    document.querySelector('meta[name="description"]').content = T('meta.desc');
    document.querySelector('meta[property="og:title"]').content = T('meta.title');
    document.querySelector('meta[property="og:description"]').content = T('meta.desc');
    document.querySelectorAll('[data-lang]').forEach((el) => el.setAttribute('aria-current', String(el.dataset.lang === lang)));
    document.querySelectorAll('[data-lang-field]').forEach((el) => { el.value = lang; });
    translateWithScramble();
    document.querySelectorAll('[data-i18n-alt]').forEach((el) => { el.alt = T(el.dataset.i18nAlt); });
    document.querySelectorAll('[data-i18n-aria]').forEach((el) => { el.setAttribute('aria-label', T(el.dataset.i18nAria)); });
    document.querySelectorAll('[data-i18n-ph]').forEach((el) => { el.placeholder = T(el.dataset.i18nPh); });
    document.querySelectorAll('.wa-float').forEach((el) => { el.href = el.href.split('?')[0] + '?text=' + encodeURIComponent(T('wa.hello')); });
    if (receivedBoard) setSeg(receivedBoard, T('form.received.board'));
  }));
  /* ---------- Enquiry form ---------- */
  const form = document.querySelector('[data-form]');
  if (!form) return;
  const btn = form.querySelector('[data-submit]');
  const btnLabel = btn.querySelector('[data-i18n]');
  const status = form.querySelector('.form-status');
  const received = form.querySelector('[data-received]');
  const story = form.elements.message;

  function setMsg(el, key) {
    if (key) { el.dataset.i18n = key; el.textContent = T(key); } else { delete el.dataset.i18n; el.textContent = ''; }
  }

  // The sentence has no room for per-field messages: mark the blanks, explain the first problem once.
  function showErrors(errors) {
    ['name', 'business', 'area', 'message'].forEach((f) => {
      if (errors[f]) form.elements[f].setAttribute('aria-invalid', 'true'); else form.elements[f].removeAttribute('aria-invalid');
    });
    const first = Object.keys(errors)[0];
    setMsg(status, first ? errors[first] : null);
    if (first && form.elements[first]) form.elements[first].focus();
  }

  function checkLocally() {
    const v = (f) => form.elements[f].value.trim();
    const errors = {};
    if (!v('name')) errors.name = 'err.required';
    if (!v('message')) errors.message = 'err.required';
    else if (v('message').length < 10) errors.message = 'err.short';
    return errors;
  }

  // Grow the last blank with the story, for browsers without field-sizing.
  story.addEventListener('input', () => { story.style.height = 'auto'; story.style.height = story.scrollHeight + 'px'; });

  function setLabel(key) { btnLabel.dataset.i18n = key; btnLabel.textContent = T(key); }

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const local = checkLocally();
    showErrors(local);
    if (Object.keys(local).length) return;

    btn.disabled = true;
    setLabel('form.sending');
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const data = await res.json();
      if (data.ok) {
        form.classList.add('is-received');
        received.hidden = false;
        [...form.elements].forEach((el) => { el.disabled = true; });
        if (!reduce) {
          [...receivedBoard.children].forEach((c) => { c.firstElementChild.textContent = ' '; });
          setTimeout(() => setSeg(receivedBoard, T('form.received.board')), 150);
        }
        setLabel('cta.wa');
        received.setAttribute('tabindex', '-1');
        received.focus();
        if (data.wa) {
          received.querySelector('[data-wa-open]').href = data.wa;
          setTimeout(() => { location.href = data.wa; }, 900);
        }
        return;
      }
      if (data.errors) showErrors(data.errors); else setMsg(status, data.error || 'err.generic');
    } catch {
      setMsg(status, 'err.generic');
    }
    btn.disabled = false;
    setLabel('cta.wa');
  });
})();

// Hero: each paper part flies from its messy spot to its tidy spot, one after another.
// Scrolling drives it.
(() => {
  const stage = document.querySelector('[data-hero-fix]');
  if (!stage) return;
  const parts = [...stage.querySelectorAll('.part')].map((el) => ({ el, delay: +el.dataset.delay || 0 }));
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return; // parts stay in their messy spots, no motion
  const pin = stage.querySelector('.hero-pin');
  const wide = matchMedia('(min-width: 801px)');
  stage.classList.add('can-pin');
  const clamp = (n) => Math.min(1, Math.max(0, n));
  const SPAN = 0.42; // share of the total progress each part takes to travel
  // ease out with a small overshoot, so each part settles into place like it was set down
  const settle = (t) => { const c = 1.4; return 1 + (c + 1) * (t - 1) ** 3 + c * (t - 1) ** 2; };
  let p = 0, shown = -1, raf = 0;
  function render() {
    raf = 0;
    if (p === shown) return;
    shown = p;
    for (const { el, delay } of parts) {
      const t = clamp(+((p - delay * (1 - SPAN) / 0.56) / SPAN).toFixed(4)); // rounding avoids 0.9999 at the end
      el.style.setProperty('--ip', (t === 0 || t === 1 ? t : settle(t)).toFixed(4));
    }
  }
  const schedule = () => { if (!raf) raf = requestAnimationFrame(render); };
  function measure() {
    const r = stage.getBoundingClientRect();
    // Desktop: progress over the stretch where the hero holds in place. Phone: over the first half screen of scrolling.
    p = wide.matches ? clamp(-r.top / (stage.offsetHeight - pin.offsetHeight)) : clamp(scrollY / (innerHeight * 0.5));
    schedule();
  }
  addEventListener('scroll', measure, { passive: true });
  addEventListener('resize', measure);
  measure();
})();

// "Why not just use AI?" route: draw the line and bring in the stops once it scrolls into view.
(() => {
  const route = document.querySelector('[data-ai-route]');
  if (!route || matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
  route.classList.add('will-draw');
  const io = new IntersectionObserver(([e]) => { if (e.isIntersecting) { route.classList.add('is-drawn'); io.disconnect(); } }, { threshold: 0.35 });
  io.observe(route);
})();
