/*
 * Mouvement commun à toutes les pages :
 * défilement doux à la molette (Lenis), boutons magnétiques,
 * bandeaux qui accélèrent avec le défilement, en-têtes en parallaxe.
 * Tout est désactivé si le visiteur a demandé à réduire les animations.
 */
(() => {
  'use strict';

  const root = document.documentElement;
  const reduce = root.classList.contains('calm'); // mode doux, décidé par prefs.js
  const finePointer = matchMedia('(pointer: fine)').matches;

  /* ---------- Défilement doux ---------- */
  let lenis = null;
  if (!reduce && window.Lenis) {
    lenis = new window.Lenis({
      lerp: 0.085,
      smoothWheel: true,
      wheelMultiplier: 0.95,
      anchors: { offset: -80 },
      autoRaf: false,
    });
    window.lenis = lenis;
    if (window.gsap) {
      // Une seule horloge pour Lenis et GSAP : les scènes restent synchronisées
      window.gsap.ticker.add((time) => lenis.raf(time * 1000));
      window.gsap.ticker.lagSmoothing(0);
      if (window.ScrollTrigger) lenis.on('scroll', window.ScrollTrigger.update);
    } else {
      const raf = (time) => { lenis.raf(time); requestAnimationFrame(raf); };
      requestAnimationFrame(raf);
    }
  }

  /* ---------- Vitesse de défilement, lissée (sert aux bandeaux) ---------- */
  let velocity = 0;
  let lastY = window.scrollY;
  const tickVelocity = () => {
    const y = window.scrollY;
    velocity += ((y - lastY) - velocity) * 0.18;
    lastY = y;
  };

  /* ---------- Bandeaux qui défilent plus vite quand on défile ---------- */
  const marquees = [...document.querySelectorAll('[data-velocity-marquee]')].map((root) => {
    const track = root.querySelector('[data-marquee-track]');
    return { root, track, x: 0, half: 0, visible: false };
  });
  if (!reduce && marquees.length) {
    const measure = () => marquees.forEach((m) => { m.half = m.track.scrollWidth / 2; });
    measure();
    addEventListener('resize', measure, { passive: true });
    document.fonts?.ready.then(measure);
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      const m = marquees.find((item) => item.root === e.target);
      if (m) m.visible = e.isIntersecting;
    }));
    marquees.forEach((m) => {
      m.track.style.animation = 'none';
      io.observe(m.root);
    });
    const loop = () => {
      tickVelocity();
      marquees.forEach((m) => {
        if (!m.visible || !m.half) return;
        const speed = 0.8 + velocity * 0.45;
        m.x -= speed;
        if (m.x <= -m.half) m.x += m.half;
        if (m.x > 0) m.x -= m.half;
        const skew = Math.max(-14, Math.min(14, velocity * 0.7));
        m.track.style.transform = `translate3d(${m.x.toFixed(2)}px,0,0) skewX(${(-skew).toFixed(2)}deg)`;
      });
      requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);
  }

  /* ---------- Boutons magnétiques ---------- */
  if (!reduce && finePointer) {
    document.querySelectorAll('[data-magnetic]').forEach((el) => {
      el.addEventListener('pointermove', (e) => {
        const r = el.getBoundingClientRect();
        const x = (e.clientX - (r.left + r.width / 2)) * 0.28;
        const y = (e.clientY - (r.top + r.height / 2)) * 0.4;
        el.style.translate = `${x.toFixed(1)}px ${y.toFixed(1)}px`;
      });
      el.addEventListener('pointerleave', () => { el.style.translate = ''; });
    });
  }

  /* ---------- Choix du visiteur : animations complètes ou douces ---------- */
  document.querySelectorAll('[data-motion-pref]').forEach((box) => {
    const state = box.querySelector('[data-motion-state]');
    const button = box.querySelector('[data-motion-toggle]');
    const osReduce = root.classList.contains('os-reduce');
    state.textContent = reduce
      ? (osReduce ? 'Animations réduites (réglage de votre appareil)' : 'Animations réduites')
      : 'Animations activées';
    button.textContent = reduce ? 'Tout activer' : 'Réduire';
    button.addEventListener('click', () => {
      try {
        // Retour au réglage de l'appareil quand il correspond au choix, sinon on mémorise le choix
        const wanted = reduce ? 'full' : 'calm';
        if ((wanted === 'calm') === osReduce) localStorage.removeItem('miam_motion');
        else localStorage.setItem('miam_motion', wanted);
      } catch { /* stockage indisponible : le choix ne sera pas mémorisé */ }
      location.reload();
    });
    box.hidden = false;
  });

  /* ---------- En-tête des pages : le titre s'éloigne doucement au défilement ---------- */
  const hero = document.querySelector('[data-page-hero]');
  if (!reduce && hero) {
    const inner = hero.querySelector('.phero__inner');
    const marquee = hero.querySelector('.phero__marquee');
    let ticking = false;
    const update = () => {
      ticking = false;
      const h = hero.offsetHeight;
      const y = Math.min(window.scrollY, h);
      inner.style.transform = `translate3d(0, ${(y * 0.28).toFixed(1)}px, 0)`;
      inner.style.opacity = String(Math.max(0, 1 - y / (h * 0.85)).toFixed(3));
      if (marquee) marquee.style.transform = `translate3d(0, ${(y * 0.12).toFixed(1)}px, 0)`;
    };
    addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    update();
  }
})();
