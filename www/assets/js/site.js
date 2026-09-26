/* Site vitrine : menu, fiches des formules, démo interactive, formulaire de contact. */
(() => {
  'use strict';

  const root = document.documentElement;
  root.classList.add('js');
  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const track = (name, label = '') => window.miamTrack?.(name, label);
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const norm = (s) => String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  const euro = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

  /* ---------- Mesure des clics (seulement si le visiteur a accepté) ---------- */
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-track]');
    if (el) track(el.dataset.track, el.dataset.label || '');
  });

  /* ---------- Barre du haut ---------- */
  const topbar = document.querySelector('[data-topbar]');
  const hero = document.querySelector('[data-hero]');
  const updateTopbar = () => {
    const limit = hero ? hero.offsetHeight - 110 : 40;
    topbar?.classList.toggle('is-scrolled', window.scrollY > limit);
  };
  updateTopbar();
  addEventListener('scroll', updateTopbar, { passive: true });
  addEventListener('resize', updateTopbar, { passive: true });

  /* ---------- Fenêtres (menu, fiches) avec animation de fermeture ---------- */
  function openDialog(dialog) {
    if (dialog.open) return;
    dialog.classList.remove('is-closing');
    dialog.showModal();
    root.classList.add('has-dialog');
  }
  function closeDialog(dialog, then) {
    if (!dialog.open) { then?.(); return; }
    const finish = () => {
      dialog.classList.remove('is-closing');
      dialog.close();
      root.classList.remove('has-dialog');
      then?.();
    };
    if (reduceMotion) { finish(); return; }
    dialog.classList.add('is-closing');
    dialog.addEventListener('animationend', finish, { once: true });
  }
  document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(dialog); });
    dialog.addEventListener('click', (e) => { if (e.target === dialog) closeDialog(dialog); });
  });
  const scrollToHash = (hash) => {
    const target = document.querySelector(hash);
    if (!target) return;
    target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
    history.pushState(null, '', hash);
  };

  /* ---------- Menu ---------- */
  const menu = document.getElementById('menu');
  if (menu) {
    document.querySelectorAll('[data-menu-open]').forEach((b) => b.addEventListener('click', () => { openDialog(menu); track('menu', 'ouverture'); }));
    menu.querySelectorAll('[data-menu-close]').forEach((b) => b.addEventListener('click', () => closeDialog(menu)));
    menu.querySelectorAll('[data-menu-link]').forEach((link) => link.addEventListener('click', (e) => {
      if (link.pathname !== location.pathname || !link.hash) return; // lien vers une autre page
      e.preventDefault();
      closeDialog(menu, () => scrollToHash(link.hash));
    }));
  }

  /* ---------- Parallaxe du hero ---------- */
  if (hero && !reduceMotion && matchMedia('(pointer: fine)').matches) {
    let frame = 0;
    hero.addEventListener('pointermove', (e) => {
      const r = hero.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 2 - 1;
      const y = ((e.clientY - r.top) / r.height) * 2 - 1;
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        hero.style.setProperty('--mx', x.toFixed(3));
        hero.style.setProperty('--my', y.toFixed(3));
      });
    });
    hero.addEventListener('pointerleave', () => {
      hero.style.setProperty('--mx', '0');
      hero.style.setProperty('--my', '0');
    });
  }

  /* ---------- Apparition au défilement, défilement des captures ---------- */
  if ('IntersectionObserver' in window) {
    const reveal = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        reveal.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    document.querySelectorAll('[data-reveal]').forEach((el) => reveal.observe(el));

    const play = new IntersectionObserver((entries) => {
      entries.forEach((entry) => entry.target.classList.toggle('is-playing', entry.isIntersecting));
    }, { threshold: 0.2 });
    document.querySelectorAll('.project').forEach((el) => play.observe(el));
  } else {
    document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-in'));
  }

  /* ---------- La gamme : fiches détaillées ---------- */
  document.querySelectorAll('[data-offer-open]').forEach((button) => {
    button.addEventListener('click', (e) => {
      const key = button.dataset.offerOpen;
      const sheet = document.getElementById(`offre-${key}`);
      if (!sheet) return;
      const card = button.closest('.bottle').getBoundingClientRect();
      const x = e.clientX || card.left + card.width / 2;
      const y = e.clientY || card.top + card.height / 2;
      sheet.style.setProperty('--ox', `${x}px`);
      sheet.style.setProperty('--oy', `${y}px`);
      openDialog(sheet);
      sheet.scrollTop = 0;
      track('offre', key);
    });
  });
  document.querySelectorAll('[data-sheet-close]').forEach((b) => b.addEventListener('click', () => closeDialog(b.closest('dialog'))));
  document.querySelectorAll('[data-choose-offer]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const form = document.querySelector('[data-contact-form]');
      const radio = form?.querySelector(`input[name="offer"][value="${link.dataset.chooseOffer}"]`);
      if (radio) radio.checked = true;
      closeDialog(link.closest('dialog'), () => {
        scrollToHash('#contact');
        setTimeout(() => form?.elements.name?.focus({ preventScroll: true }), reduceMotion ? 0 : 700);
      });
    });
  });

  /* ---------- FAQ ---------- */
  document.querySelectorAll('details[data-track-open]').forEach((d) => {
    d.addEventListener('toggle', () => { if (d.open) track(d.dataset.trackOpen, d.dataset.label || ''); });
  });

  /* ---------- Démo interactive ---------- */
  const demo = document.querySelector('[data-demo]');
  if (demo) initDemo(demo);

  function initDemo(section) {
    const data = JSON.parse(document.getElementById('demo-data').textContent);
    const screen = section.querySelector('[data-demo-screen]');
    const words = section.querySelectorAll('[data-demo-word]');
    const flavors = [...section.querySelectorAll('[data-cuisine]')];
    const tabs = [...section.querySelectorAll('[data-demo-tab]')];
    const pointLists = section.querySelectorAll('[data-points]');
    const state = { cuisine: Object.keys(data)[0], tab: 'client', query: '', tag: null, menus: {}, list: {}, changed: new Set() };
    let toastTimer = 0;

    const menuOf = (key) => {
      if (!state.menus[key]) {
        state.menus[key] = Object.entries(data[key].menu).map(([cat, items], ci) => ({
          cat,
          items: items.map(([name, desc, price, tags], i) => ({ id: `${key}-${ci}-${i}`, name, desc, price, tags, out: false })),
        }));
      }
      return state.menus[key];
    };
    const allItems = () => menuOf(state.cuisine).flatMap((c) => c.items);
    const listOf = () => (state.list[state.cuisine] ||= new Map());

    function head(title, line, badge, admin = false) {
      const c = data[state.cuisine];
      return `<div class="app__head${admin ? ' app__head--admin' : ''}">
        <div class="app__top"><span class="app__logo" aria-hidden="true">${esc(c.name.charAt(0))}</span><span class="app__status">${esc(badge)}</span></div>
        <p class="app__name">${esc(title)}</p><p class="app__line">${esc(line)}</p></div>`;
    }

    function dishesHTML() {
      const q = norm(state.query.trim());
      const groups = menuOf(state.cuisine).map((group) => {
        const items = group.items.filter((it) => {
          if (state.tag && !it.tags.includes(state.tag)) return false;
          return !q || norm(`${it.name} ${it.desc} ${it.tags.join(' ')} ${group.cat}`).includes(q);
        });
        if (!items.length) return '';
        const list = listOf();
        return `<p class="app__cat">${esc(group.cat)}</p>` + items.map((it) => `
          <div class="dish${it.out ? ' is-out' : ''}${state.changed.has(it.id) ? ' is-flash' : ''}">
            <div><p class="dish__name">${esc(it.name)}</p><p class="dish__desc">${esc(it.desc)}</p>
              ${it.tags.length ? `<div class="dish__tags">${it.tags.map((t) => `<span class="dish__tag">${esc(t)}</span>`).join('')}</div>` : ''}</div>
            <div class="dish__side"><p class="dish__price">${it.out ? 'Épuisé' : euro.format(it.price / 100)}</p>
              ${it.out ? '' : `<button type="button" class="dish__add${list.has(it.id) ? ' is-added' : ''}" data-add="${it.id}" aria-pressed="${list.has(it.id)}" aria-label="${list.has(it.id) ? 'Retirer' : 'Ajouter'} ${esc(it.name)} ${list.has(it.id) ? 'de' : 'à'} ma liste"><svg aria-hidden="true"><use href="#${list.has(it.id) ? 'i-check' : 'i-plus'}"/></svg></button>`}</div>
          </div>`).join('');
      }).join('');
      state.changed.clear();
      return groups || `<p class="app__empty">Aucun plat ne correspond à « ${esc(state.query)} ».</p>`;
    }

    function renderClient() {
      const c = data[state.cuisine];
      const tags = [...new Set(allItems().flatMap((it) => it.tags))];
      screen.innerHTML = `<div class="app">
        ${head(c.name, c.line, "Ouvert · jusqu'à 22h")}
        <label class="app__search"><svg aria-hidden="true"><use href="#i-search"/></svg><span class="sr-only">Rechercher un plat</span>
          <input type="search" placeholder="Rechercher un plat…" value="${esc(state.query)}" data-app-search autocomplete="off"></label>
        <div class="app__chips">${tags.map((t) => `<button type="button" class="app__chip" aria-pressed="${state.tag === t}" data-app-tag="${esc(t)}">${esc(t)}</button>`).join('')}</div>
        <div class="app__list" data-app-list>${dishesHTML()}</div>
        <div class="app__toast" data-app-toast role="status"></div>
        <div class="app__bar">
          <button type="button" class="app__btn app__btn--main" data-app-call><svg aria-hidden="true"><use href="#i-phone"/></svg>Appeler</button>
          <button type="button" class="app__btn app__btn--ghost" data-app-mylist><svg aria-hidden="true"><use href="#i-list"/></svg>Ma liste <span class="app__count" data-app-count>${listOf().size}</span></button>
        </div></div>`;
    }

    function renderGestion() {
      const c = data[state.cuisine];
      const count = allItems().length;
      screen.innerHTML = `<div class="app">
        ${head('Gestion', `${c.name} · ${count} plats`, 'Connecté', true)}
        <div class="app__list">
          <p class="app__hint">Changez un prix ou coupez un plat, puis revenez « Côté client » pour voir le résultat.</p>
          ${menuOf(state.cuisine).map((group) => `<p class="app__cat">${esc(group.cat)}</p>` + group.items.map((it) => `
            <div class="admin-row">
              <div><p class="admin-row__name">${esc(it.name)}</p><p class="admin-row__state${it.out ? ' is-out' : ''}" data-state="${it.id}">${it.out ? 'Épuisé' : 'En vente'}</p></div>
              <label class="price-input"><span class="sr-only">Prix de ${esc(it.name)}</span><input inputmode="decimal" value="${(it.price / 100).toFixed(2).replace('.', ',')}" data-price="${it.id}" aria-label="Prix de ${esc(it.name)} en euros"><span aria-hidden="true">€</span></label>
              <button type="button" class="switch" role="switch" aria-checked="${!it.out}" aria-label="${esc(it.name)} disponible" data-switch="${it.id}"></button>
            </div>`).join('')).join('')}
        </div>
        <div class="app__toast" data-app-toast role="status"></div></div>`;
    }

    function renderStats() {
      const c = data[state.cuisine];
      // Chiffres fictifs mais stables : dérivés du nom de la cuisine
      let seed = [...state.cuisine].reduce((a, ch) => (a * 31 + ch.charCodeAt(0)) >>> 0, 7);
      const rnd = () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
      const days = [0.55, 0.6, 0.62, 0.7, 0.92, 1, 0.78].map((d) => Math.round((d * (0.85 + rnd() * 0.3)) * 100) / 100);
      const max = Math.max(...days);
      const views = Math.round(900 + rnd() * 900);
      const visitors = Math.round(views * (0.55 + rnd() * 0.1));
      const mobile = Math.round(78 + rnd() * 10);
      const peaks = { pizzeria: '19h – 20h', sushi: '19h – 20h', braise: '20h – 21h', creperie: '12h – 13h', burger: '12h – 13h', brunch: '10h – 11h' };
      const top = allItems().slice(0, 3);
      const fmt = new Intl.NumberFormat('fr-FR');
      screen.innerHTML = `<div class="app">
        ${head('Statistiques', `${c.name} · 7 derniers jours`, 'Cette semaine', true)}
        <div class="app__list">
          <div class="kpis">
            <div class="kpi"><p class="kpi__label">Vues de la carte</p><p class="kpi__value">${fmt.format(views)}</p><p class="kpi__delta">+${Math.round(6 + rnd() * 14)} % sur 7 jours</p></div>
            <div class="kpi"><p class="kpi__label">Visiteurs</p><p class="kpi__value">${fmt.format(visitors)}</p><p class="kpi__delta">+${Math.round(4 + rnd() * 10)} % sur 7 jours</p></div>
            <div class="kpi"><p class="kpi__label">Sur téléphone</p><p class="kpi__value">${mobile} %</p></div>
            <div class="kpi"><p class="kpi__label">Heure de pointe</p><p class="kpi__value" style="font-size:17px">${peaks[state.cuisine] || '12h – 13h'}</p></div>
          </div>
          <div class="mini-chart"><p class="mini-chart__title">Vues par jour</p>
            <div class="mini-bars" role="img" aria-label="Vues par jour, le samedi est le jour le plus fort">${days.map((d, i) => `<span class="${d === max ? 'is-max' : ''}" style="--h:${Math.round((d / max) * 100)}%;animation-delay:${i * 50}ms"></span>`).join('')}</div>
            <div class="mini-days" aria-hidden="true"><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span></div>
          </div>
          <div class="mini-chart"><p class="mini-chart__title">Plats les plus consultés</p>
            <ul class="top-list">${top.map((it, i) => { const p = [100, 68, 43][i]; return `<li><span>${esc(it.name)}</span><span>${Math.round((views * p) / 380)}</span><i><b style="--p:${p}%"></b></i></li>`; }).join('')}</ul>
          </div>
        </div></div>`;
    }

    function render() {
      ({ client: renderClient, gestion: renderGestion, stats: renderStats })[state.tab]();
    }

    function toast(text) {
      const el = screen.querySelector('[data-app-toast]');
      if (!el) return;
      el.textContent = text;
      el.classList.add('is-on');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => el.classList.remove('is-on'), 2200);
    }

    function setCuisine(key, focus = false) {
      if (!data[key]) return;
      state.cuisine = key;
      state.query = '';
      state.tag = null;
      const [d1, d2, d3] = data[key].colors;
      section.style.setProperty('--d1', d1);
      section.style.setProperty('--d2', d2);
      section.style.setProperty('--d3', d3);
      words.forEach((w) => { w.textContent = `${data[key].label} `.repeat(8); });
      flavors.forEach((f) => {
        const on = f.dataset.cuisine === key;
        f.setAttribute('aria-checked', String(on));
        f.tabIndex = on ? 0 : -1;
        if (on && focus) f.focus();
      });
      render();
      screen.scrollTop = 0;
    }

    function setTab(tab, focus = false) {
      state.tab = tab;
      tabs.forEach((t) => {
        const on = t.dataset.demoTab === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        if (on && focus) t.focus();
      });
      screen.setAttribute('aria-labelledby', `tab-${tab}`);
      section.querySelector('.phone--demo').dataset.tab = tab;
      pointLists.forEach((list) => { list.hidden = list.dataset.points !== tab; });
      render();
      screen.scrollTop = 0;
    }

    // Clavier : flèches pour les onglets et les cuisines
    const arrows = (items, current, go) => (e) => {
      const keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
      if (!(e.key in keys)) return;
      e.preventDefault();
      const i = items.indexOf(current());
      go(items[(i + keys[e.key] + items.length) % items.length]);
    };
    flavors.forEach((f) => {
      f.tabIndex = f.getAttribute('aria-checked') === 'true' ? 0 : -1;
      f.addEventListener('click', () => { setCuisine(f.dataset.cuisine); track('demo-cuisine', f.dataset.cuisine); });
      f.addEventListener('keydown', arrows(flavors, () => flavors.find((x) => x.dataset.cuisine === state.cuisine), (n) => setCuisine(n.dataset.cuisine, true)));
    });
    tabs.forEach((t) => {
      t.addEventListener('click', () => { setTab(t.dataset.demoTab); track('demo-vue', t.dataset.demoTab); });
      t.addEventListener('keydown', arrows(tabs, () => tabs.find((x) => x.dataset.demoTab === state.tab), (n) => setTab(n.dataset.demoTab, true)));
    });

    // Interactions dans le téléphone
    screen.addEventListener('input', (e) => {
      if (!e.target.matches('[data-app-search]')) return;
      state.query = e.target.value;
      screen.querySelector('[data-app-list]').innerHTML = dishesHTML();
    });
    screen.addEventListener('click', (e) => {
      const tag = e.target.closest('[data-app-tag]');
      if (tag) {
        state.tag = state.tag === tag.dataset.appTag ? null : tag.dataset.appTag;
        screen.querySelectorAll('[data-app-tag]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.appTag === state.tag)));
        screen.querySelector('[data-app-list]').innerHTML = dishesHTML();
        return;
      }
      const add = e.target.closest('[data-add]');
      if (add) {
        const list = listOf();
        const id = add.dataset.add;
        list.has(id) ? list.delete(id) : list.set(id, 1);
        const scroll = screen.scrollTop;
        screen.querySelector('[data-app-list]').innerHTML = dishesHTML();
        screen.scrollTop = scroll;
        screen.querySelector(`[data-add="${id}"]`)?.focus({ preventScroll: true });
        const count = screen.querySelector('[data-app-count]');
        count.textContent = list.size;
        count.classList.remove('is-bump');
        void count.offsetWidth;
        count.classList.add('is-bump');
        return;
      }
      if (e.target.closest('[data-app-call]')) {
        toast('Sur votre carte, ce bouton appelle le restaurant');
        return;
      }
      if (e.target.closest('[data-app-mylist]')) {
        const items = allItems().filter((it) => listOf().has(it.id) && !it.out);
        const total = items.reduce((s, it) => s + it.price, 0);
        toast(items.length ? `${items.length} plat${items.length > 1 ? 's' : ''} noté${items.length > 1 ? 's' : ''} · ${euro.format(total / 100)}` : 'Ajoutez des plats avec le bouton +');
        return;
      }
      const sw = e.target.closest('[data-switch]');
      if (sw) {
        const item = allItems().find((it) => it.id === sw.dataset.switch);
        item.out = !item.out;
        state.changed.add(item.id);
        sw.setAttribute('aria-checked', String(!item.out));
        const label = screen.querySelector(`[data-state="${item.id}"]`);
        label.textContent = item.out ? 'Épuisé' : 'En vente';
        label.classList.toggle('is-out', item.out);
        toast(item.out ? `${item.name} : marqué épuisé` : `${item.name} : de nouveau en vente`);
        track('demo', item.out ? 'epuise' : 'remis');
      }
    });
    screen.addEventListener('change', (e) => {
      if (!e.target.matches('[data-price]')) return;
      const item = allItems().find((it) => it.id === e.target.dataset.price);
      const value = parseFloat(e.target.value.replace(/\s/g, '').replace(',', '.'));
      if (!Number.isFinite(value) || value < 0 || value > 9999) {
        e.target.value = (item.price / 100).toFixed(2).replace('.', ',');
        toast('Ce prix ne semble pas valide');
        return;
      }
      item.price = Math.round(value * 100);
      state.changed.add(item.id);
      e.target.value = (item.price / 100).toFixed(2).replace('.', ',');
      toast(`Enregistré : ${item.name} à ${euro.format(item.price / 100)}`);
      track('demo', 'prix');
    });
    screen.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && e.target.matches('[data-price]')) e.target.blur();
    });

    render();
  }

  /* ---------- Formulaire de contact ---------- */
  const form = document.querySelector('[data-contact-form]');
  if (form) initForm(form);

  function initForm(form) {
    form.noValidate = true; // nos messages d'erreur remplacent ceux du navigateur
    const thanks = document.querySelector('[data-thanks]');
    const status = form.querySelector('[data-form-status]');
    const submit = form.querySelector('[data-submit]');
    const label = submit.querySelector('.pill__label');
    const counter = form.querySelector('[data-count]');
    const message = form.elements.message;
    let started = false;
    let retried = false;

    const rules = {
      name: (v) => v.trim().length >= 2 || 'Indiquez votre nom.',
      email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) || 'Cette adresse email ne semble pas valide.',
      phone: (v) => !v.trim() || /^[+0-9 ().\-]{6,30}$/.test(v.trim()) || 'Ce numéro ne semble pas valide.',
      message: (v) => v.trim().length >= 10 || "Dites-m'en un peu plus (10 caractères minimum).",
    };

    function showError(name, text) {
      const field = form.elements[name];
      const error = document.getElementById(`${field.id}-error`);
      if (text) {
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', error.id);
        error.textContent = text;
        error.hidden = false;
      } else {
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
        error.hidden = true;
      }
    }
    function check(name) {
      const result = rules[name](form.elements[name].value);
      showError(name, result === true ? '' : result);
      return result === true;
    }

    Object.keys(rules).forEach((name) => {
      const field = form.elements[name];
      field.addEventListener('blur', () => { if (field.value.trim() || field.hasAttribute('aria-invalid')) check(name); });
      field.addEventListener('input', () => { if (field.hasAttribute('aria-invalid')) check(name); });
    });
    message.addEventListener('input', () => { counter.textContent = `${message.value.length} / 5000`; });
    form.addEventListener('focusin', () => { if (!started) { started = true; track('formulaire', 'debut'); } });

    const setLoading = (on) => {
      submit.classList.toggle('is-loading', on);
      submit.setAttribute('aria-busy', String(on));
      label.textContent = on ? 'Envoi en cours…' : 'Envoyer ma demande';
    };

    async function send() {
      setLoading(true);
      status.textContent = '';
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
        const result = await response.json().catch(() => ({}));
        if (response.ok && result.ok) {
          track('formulaire', 'envoye');
          form.reset();
          counter.textContent = '0 / 5000';
          form.hidden = true;
          thanks.hidden = false;
          thanks.focus();
          thanks.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
          return;
        }
        if (result.token) form.elements.token.value = result.token;
        if (result.error === 'rapide' && !retried) { retried = true; setTimeout(send, 3000); return; }
        if (result.error === 'jeton' && !retried) { retried = true; send(); return; }
        if (result.errors) {
          Object.entries(result.errors).forEach(([name, text]) => form.elements[name] && showError(name, text));
          form.querySelector('[aria-invalid="true"]')?.focus();
        }
        status.textContent = result.message || "L'envoi n'a pas abouti. Réessayez dans un instant.";
      } catch {
        status.textContent = 'Connexion impossible. Vérifiez votre réseau, puis réessayez.';
      }
      setLoading(false);
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const valid = Object.keys(rules).map(check).every(Boolean);
      if (!valid) {
        form.querySelector('[aria-invalid="true"]')?.focus();
        status.textContent = 'Quelques champs sont à compléter.';
        return;
      }
      retried = false;
      send();
    });

    document.querySelector('[data-thanks-reset]')?.addEventListener('click', () => {
      thanks.hidden = true;
      form.hidden = false;
      setLoading(false);
      form.elements.name.focus();
    });
  }
})();
