/*
 * Page Menus imprimés.
 * 1. Le dépliant d'Aux Saveurs Braisées s'ouvre au fil du défilement, volet par volet, puis se retourne.
 * 2. Dans la galerie, chaque carte se retourne (extérieur / intérieur).
 * 3. La visionneuse agrandit la carte pour lire chaque ligne (clic pour zoomer, glisser pour se déplacer).
 * Sans GSAP, le dépliant reste simplement ouvert ; en mode doux, il s'ouvre sans pencher.
 */
(() => {
  'use strict';

  const root = document.documentElement;
  const calm = root.classList.contains('calm');
  const { gsap, ScrollTrigger } = window;

  /* ---------- 1. Le dépliant ---------- */
  const unfold = document.querySelector('[data-unfold]');
  if (unfold && gsap && ScrollTrigger) initFold(unfold);

  function initFold(section) {
    gsap.registerPlugin(ScrollTrigger);
    ScrollTrigger.config({ ignoreMobileResize: true });
    root.classList.add('has-scroll-scenes');

    const book = section.querySelector('[data-fold]');
    const scene = book.parentElement;
    const left = book.querySelector('[data-fold-panel="left"]');
    const right = book.querySelector('[data-fold-panel="right"]');
    const shadow = section.querySelector('[data-fold-shadow]');
    const meter = section.querySelector('[data-fold-meter]');
    const notes = [...section.querySelectorAll('[data-fold-note]')];
    const mid = book.querySelector('[data-fold-panel="mid"]');
    const faces = { left: [...left.querySelectorAll('img')], mid: [...mid.querySelectorAll('img')], right: [...right.querySelectorAll('img')] };
    book.querySelectorAll('img').forEach((img) => { img.loading = 'eager'; }); // la scène arrive vite : on n'attend pas le dernier moment

    const clamp = gsap.utils.clamp(0, 1);
    const ease = gsap.parseEase('power2.inOut');
    const lerp = (a, b, t) => a + (b - a) * t;

    // La carte fermée (un volet) peut être affichée plus grande que la carte ouverte (trois volets)
    let dims;
    const measure = () => {
      const pw = book.offsetWidth / 3;
      const ph = book.offsetHeight;
      const sw = scene.clientWidth;
      const sh = scene.clientHeight;
      dims = {
        pw,
        fold: Math.max(1, Math.min(1.7, (0.94 * sh) / ph, (0.9 * sw) / pw)),
        half: Math.max(1, Math.min(1.35, (0.94 * sh) / ph, (0.94 * sw) / (2 * pw))),
      };
    };
    measure();

    // Les poses de la carte, dans l'ordre du défilement (0 → 1)
    const tilt = calm ? 0 : 1;
    const poses = () => [
      [0, { rx: 16 * tilt, ry: -28 * tilt, rz: -3 * tilt, s: dims.fold, x: 0, l: 0, r: 0 }],
      [0.12, { rx: 8 * tilt, ry: -14 * tilt, rz: 0, s: dims.fold, x: 0, l: 0, r: 0 }],
      [0.14, { rx: 8 * tilt, ry: -14 * tilt, rz: 0, s: dims.fold, x: 0, l: 0, r: 0 }],
      [0.36, { rx: 6 * tilt, ry: -6 * tilt, rz: 0, s: dims.half, x: 0.5 * dims.pw * dims.half, l: 1, r: 0 }],
      [0.4, { rx: 6 * tilt, ry: -6 * tilt, rz: 0, s: dims.half, x: 0.5 * dims.pw * dims.half, l: 1, r: 0 }],
      [0.62, { rx: 4 * tilt, ry: 0, rz: 0, s: 1, x: 0, l: 1, r: 1 }],
      [0.72, { rx: 4 * tilt, ry: 0, rz: 0, s: 1, x: 0, l: 1, r: 1 }],
      [0.94, { rx: 4 * tilt, ry: -180, rz: 0, s: 1, x: 0, l: 1, r: 1 }],
      [1, { rx: 4 * tilt, ry: -180, rz: 0, s: 1, x: 0, l: 1, r: 1 }],
    ];
    let keys = poses();
    const noteSteps = [0, 0.13, 0.38, 0.7];

    let lastNote = -1;
    const setNote = (p) => {
      let index = 0;
      noteSteps.forEach((step, i) => { if (p >= step) index = i; });
      if (index === lastNote) return;
      lastNote = index;
      notes.forEach((note, i) => {
        note.classList.toggle('is-active', i === index);
        note.classList.toggle('is-done', i < index);
      });
    };

    const shade = (imgs, value) => {
      const filter = value > 0.995 ? '' : `brightness(${value.toFixed(3)})`;
      imgs.forEach((img) => { if (img.style.filter !== filter) img.style.filter = filter; });
    };

    function render(p) {
      let i = 0;
      while (i < keys.length - 2 && p > keys[i + 1][0]) i++;
      const [t0, a] = keys[i];
      const [t1, b] = keys[i + 1];
      const t = ease(clamp((p - t0) / (t1 - t0 || 1)));
      const pose = {};
      Object.keys(a).forEach((k) => { pose[k] = lerp(a[k], b[k], t); });

      // Pendant qu'elle se retourne, la carte s'éloigne un peu
      const flip = clamp((p - 0.72) / 0.22);
      const scale = pose.s * (1 - 0.12 * Math.sin(Math.PI * ease(flip)));

      const leftAngle = 180 * (1 - pose.l);
      const rightAngle = -180 * (1 - pose.r);
      book.style.transform = `translate3d(${pose.x.toFixed(1)}px, 0, 0) rotateX(${pose.rx.toFixed(2)}deg) rotateY(${pose.ry.toFixed(2)}deg) rotateZ(${pose.rz.toFixed(2)}deg) scale(${scale.toFixed(4)})`;
      left.style.transform = `translateZ(${(3 * (1 - pose.l)).toFixed(2)}px) rotateY(${leftAngle.toFixed(2)}deg)`;
      right.style.transform = `translateZ(${(1.5 * (1 - pose.r)).toFixed(2)}px) rotateY(${rightAngle.toFixed(2)}deg)`;

      // Lumière : un volet de biais est plus sombre, la carte aussi quand on la voit par la tranche
      const edge = Math.abs(Math.cos((pose.ry * Math.PI) / 180));
      const light = 0.72 + 0.28 * edge;
      shade(faces.mid, light);
      shade(faces.left, light * (0.55 + 0.45 * Math.abs(Math.cos((leftAngle * Math.PI) / 180))));
      shade(faces.right, light * (0.55 + 0.45 * Math.abs(Math.cos((rightAngle * Math.PI) / 180))));

      // L'ombre au sol suit la largeur visible de la carte
      const visible = ((1 + pose.l + pose.r) / 3) * scale * Math.max(0.3, edge);
      shadow.style.transform = `translate3d(${pose.x.toFixed(1)}px, 0, 0) scaleX(${visible.toFixed(4)})`;

      if (meter) meter.style.transform = `scaleX(${p.toFixed(4)})`;
      setNote(p);
    }

    const state = { p: 0 };
    render(0);
    gsap.to(state, {
      p: 1,
      ease: 'none',
      onUpdate: () => render(state.p),
      scrollTrigger: {
        trigger: section,
        start: 'top top',
        end: () => '+=' + Math.round(window.innerHeight * 4),
        pin: true,
        scrub: 0.9,
        anticipatePin: 1,
        invalidateOnRefresh: true,
      },
    });
    ScrollTrigger.addEventListener('refresh', () => { measure(); keys = poses(); render(state.p); });
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
    addEventListener('load', () => ScrollTrigger.refresh());
  }

  /* ---------- 2. Retourner une carte de la galerie ---------- */
  const zoom = document.getElementById('zoom');
  document.querySelectorAll('[data-print]').forEach((card) => {
    const buttons = [...card.querySelectorAll('.flipseg [data-side]')];
    card.dataset.side = 'exterieur';
    buttons.forEach((button) => button.addEventListener('click', () => {
      card.dataset.side = button.dataset.side;
      buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
    }));
    if (zoom) card.querySelectorAll('[data-print-zoom]').forEach((button) => button.addEventListener('click', () => openZoom(card)));
  });

  /* ---------- 3. La visionneuse ---------- */
  if (!zoom) return;
  const view = zoom.querySelector('[data-zoom-view]');
  const img = zoom.querySelector('[data-zoom-img]');
  const title = zoom.querySelector('[data-zoom-title]');
  const hint = zoom.querySelector('[data-zoom-hint]');
  const toggle = zoom.querySelector('[data-zoom-toggle]');
  const sideButtons = [...zoom.querySelectorAll('[data-zoom-sides] [data-side]')];
  const touch = matchMedia('(pointer: coarse)').matches;
  let data = null;
  let card = null;
  let zoomed = false;
  let loadToken = 0;

  const setHint = () => {
    hint.textContent = zoomed
      ? (touch ? 'Faites glisser pour lire toute la carte. Touchez-la pour revenir à la vue d\'ensemble.' : 'Faites glisser la carte pour vous déplacer. Cliquez pour revenir à la vue d\'ensemble.')
      : (touch ? 'Touchez la carte pour zoomer.' : 'Cliquez sur la carte pour zoomer à cet endroit.');
  };

  function showSide(side) {
    const entry = data.sides.find((s) => s.key === side) || data.sides[0];
    sideButtons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.side === entry.key)));
    img.alt = entry.alt;
    img.src = entry.src; // l'image de la galerie, déjà chargée, en attendant la grande
    const token = ++loadToken;
    const big = new Image();
    big.src = entry.zoom;
    big.decode().then(() => { if (token === loadToken) img.src = entry.zoom; }).catch(() => {});
    return entry;
  }

  function setZoom(on, point) {
    // point : position visée, en fraction de l'image (0 à 1), et position à l'écran où la garder
    const rect = img.getBoundingClientRect();
    zoomed = on;
    zoom.classList.toggle('is-zoomed', on);
    toggle.setAttribute('aria-pressed', String(on));
    if (on) {
      const width = Math.round(Math.min(2800, Math.max(1600, rect.width * 2.5)));
      zoom.style.setProperty('--zw', `${width}px`);
      const target = point || { fx: 0.5, fy: 0.5, cx: view.clientWidth / 2, cy: view.clientHeight / 2 };
      const height = (width * img.naturalHeight) / (img.naturalWidth || 1);
      view.scrollLeft = img.offsetLeft + target.fx * width - target.cx;
      view.scrollTop = img.offsetTop + target.fy * height - target.cy;
    }
    setHint();
  }

  function openZoom(from) {
    card = from;
    data = JSON.parse(card.dataset.print);
    title.textContent = data.title;
    showSide(card.dataset.side || 'exterieur');
    setZoom(false);
    window.miamDialog?.open(zoom);
  }

  sideButtons.forEach((button) => button.addEventListener('click', () => {
    showSide(button.dataset.side);
    // La galerie suit la face choisie dans la visionneuse
    card?.querySelector(`.flipseg [data-side="${button.dataset.side}"]`)?.click();
    setZoom(false);
  }));
  toggle.addEventListener('click', () => setZoom(!zoomed));

  // Clic : zoom à l'endroit visé ; glisser (souris) : déplacement
  let drag = null;
  let dragged = false;
  view.addEventListener('pointerdown', (e) => {
    if (!zoomed || e.pointerType !== 'mouse' || e.button !== 0) return;
    drag = { x: e.clientX, y: e.clientY, left: view.scrollLeft, top: view.scrollTop, moved: false };
    view.setPointerCapture(e.pointerId);
    zoom.classList.add('is-dragging');
  });
  view.addEventListener('pointermove', (e) => {
    if (!drag) return;
    const dx = e.clientX - drag.x;
    const dy = e.clientY - drag.y;
    if (Math.abs(dx) + Math.abs(dy) > 5) drag.moved = true;
    view.scrollLeft = drag.left - dx;
    view.scrollTop = drag.top - dy;
  });
  const endDrag = () => {
    if (!drag) return;
    dragged = drag.moved;
    drag = null;
    zoom.classList.remove('is-dragging');
  };
  view.addEventListener('pointerup', endDrag);
  view.addEventListener('pointercancel', endDrag);
  view.addEventListener('click', (e) => {
    if (dragged) { dragged = false; return; }
    if (zoomed) { setZoom(false); return; }
    if (e.target !== img) return;
    const rect = img.getBoundingClientRect();
    const box = view.getBoundingClientRect();
    setZoom(true, {
      fx: (e.clientX - rect.left) / rect.width,
      fy: (e.clientY - rect.top) / rect.height,
      cx: e.clientX - box.left,
      cy: e.clientY - box.top,
    });
  });
  view.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setZoom(!zoomed); }
  });

  zoom.addEventListener('close', () => {
    setZoom(false);
    loadToken++;
  });
})();
