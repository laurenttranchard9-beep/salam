/*
 * Accueil : les scènes pilotées par la molette.
 * 1. le hero s'écarte, 2. la carte défile dans le téléphone, 3. le texte se révèle mot à mot,
 * 4. la gamme passe à l'horizontale, 5. les étapes s'empilent, 6. les réalisations en parallaxe,
 * 7. les couvertures des menus imprimés s'ouvrent en éventail.
 * En mode doux (appareil réglé sur « moins d'animations »), seules les scènes que la molette
 * pilote directement sont gardées (2, 3, 4) ; les effets décoratifs sont coupés.
 * Sans GSAP, la page reste complète et lisible, sans scène.
 */
(() => {
  'use strict';

  const { gsap, ScrollTrigger } = window;
  if (!gsap || !ScrollTrigger) return;
  const calm = document.documentElement.classList.contains('calm');

  gsap.registerPlugin(ScrollTrigger);
  ScrollTrigger.config({ ignoreMobileResize: true });
  document.documentElement.classList.add('has-scroll-scenes');
  const clamp = gsap.utils.clamp(0, 1);
  const mm = gsap.matchMedia();

  /* ---------- 1. Le hero : les téléphones s'écartent, le titre s'envole ---------- */
  const hero = document.querySelector('[data-hero]');
  if (hero && !calm) {
    mm.add({ wide: '(min-width: 761px)', narrow: '(max-width: 760px)' }, (context) => {
      const { wide } = context.conditions;
      const tl = gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: 0.7 } });
      tl.to('[data-hero-content]', { yPercent: wide ? -30 : -12, opacity: 0 }, 0)
        .to('[data-hero-phone="left"]', wide ? { xPercent: -40, yPercent: -22, rotation: -12 } : { yPercent: -18, rotation: -5 }, 0)
        .to('[data-hero-phone="right"]', wide ? { xPercent: 40, yPercent: -34, rotation: 12 } : { yPercent: -28, rotation: 5 }, 0)
        .to('.scroll-cue--hero', { opacity: 0, duration: 0.15 }, 0);
    });

    // Les rubans glissent en sens contraire pendant qu'on descend
    gsap.set('.ribbon--a', { rotation: -2.6 });
    gsap.set('.ribbon--b', { rotation: 2 });
    gsap.to('.ribbon--a', { xPercent: -6, ease: 'none', scrollTrigger: { trigger: '.ribbons', start: 'top bottom', end: 'bottom top', scrub: 0.5 } });
    gsap.to('.ribbon--b', { xPercent: 6, ease: 'none', scrollTrigger: { trigger: '.ribbons', start: 'top bottom', end: 'bottom top', scrub: 0.5 } });
  }

  /* ---------- 2. La carte qui défile dans le téléphone ---------- */
  const scene = document.querySelector('[data-scroll-phone]');
  if (scene) {
    const view = scene.querySelector('.phone--scroll .phone__view');
    const shot = view.querySelector('img');
    const notes = [...scene.querySelectorAll('[data-note]')];
    const meter = scene.querySelector('[data-scroll-meter]');
    let current = -1;
    const setNote = (progress) => {
      const index = Math.min(notes.length - 1, Math.floor(progress * notes.length * 0.999));
      if (index === current) return;
      current = index;
      notes.forEach((note, i) => {
        note.classList.toggle('is-active', i === index);
        note.classList.toggle('is-done', i < index);
      });
    };
    setNote(0);

    const tl = gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: {
        trigger: scene,
        start: 'top top',
        end: () => '+=' + Math.round(window.innerHeight * 3.2),
        pin: true,
        scrub: 0.9,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onUpdate: (self) => setNote(self.progress),
      },
    });
    tl.fromTo(shot, { y: 0 }, { y: () => -Math.max(0, shot.offsetHeight - view.clientHeight), duration: 1 }, 0)
      .fromTo(meter, { scaleX: 0 }, { scaleX: 1, duration: 1 }, 0);
    if (!calm) {
      tl.fromTo('.phone--scroll', { rotation: -6, scale: 0.92 }, { rotation: 0, scale: 1, duration: 0.18, ease: 'power2.out' }, 0)
        .to('.phone--scroll', { rotation: 3, scale: 0.96, duration: 0.12, ease: 'power1.in' }, 0.88);
    }
  }

  /* ---------- 3. Le manifeste, mot à mot ---------- */
  const manifesto = document.querySelector('[data-words]');
  if (manifesto) {
    // On découpe les nœuds de texte sans toucher aux balises (em, liens…)
    const words = [];
    const walker = document.createTreeWalker(manifesto, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    while (walker.nextNode()) textNodes.push(walker.currentNode);
    textNodes.forEach((node) => {
      const fragment = document.createDocumentFragment();
      node.textContent.split(/(\s+)/).forEach((part) => {
        if (!part) return;
        if (/^\s+$/.test(part)) { fragment.append(part); return; }
        const span = document.createElement('span');
        span.className = 'mw';
        span.textContent = part;
        span.style.opacity = '0.14';
        words.push(span);
        fragment.append(span);
      });
      node.replaceWith(fragment);
    });
    ScrollTrigger.create({
      trigger: manifesto,
      start: 'top 82%',
      end: 'bottom 42%',
      scrub: true,
      onUpdate: (self) => {
        const x = self.progress * (words.length + 2);
        words.forEach((word, i) => { word.style.opacity = String(0.14 + 0.86 * clamp(x - i)); });
      },
    });
  }

  /* ---------- 4. La gamme à l'horizontale ---------- */
  const lineup = document.querySelector('[data-lineup]');
  if (lineup) {
    const track = lineup.querySelector('[data-lineup-track]');
    const cards = [...lineup.querySelectorAll('[data-lineup-card]')];
    const cream = '#fff6ea';
    const stops = [cream, ...cards.map((card) => gsap.utils.interpolate(cream, card.dataset.color, 0.38)), cream];
    const distance = () => Math.max(0, track.scrollWidth - window.innerWidth);

    const move = gsap.to(track, {
      x: () => -distance(),
      ease: 'none',
      scrollTrigger: {
        trigger: lineup,
        start: 'top top',
        end: () => '+=' + distance(),
        pin: true,
        scrub: 0.9,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onUpdate: (self) => {
          const p = self.progress * (stops.length - 1);
          const i = Math.min(stops.length - 2, Math.floor(p));
          lineup.style.setProperty('--lineup-bg', gsap.utils.interpolate(stops[i], stops[i + 1], p - i));
        },
      },
    });

    // Chaque carte se redresse en entrant dans l'écran
    if (!calm) {
    const tilt = window.innerWidth < 760 ? 3 : 7;
    cards.forEach((card, i) => {
      gsap.fromTo(card, { rotation: i % 2 ? -tilt : tilt, yPercent: tilt * 1.4 }, {
        rotation: 0, yPercent: 0, ease: 'none',
        scrollTrigger: { trigger: card, containerAnimation: move, start: 'left 95%', end: 'left 45%', scrub: true },
      });
    });
    }
  }

  /* ---------- 5. Les étapes qui s'empilent ---------- */
  const stackCards = calm ? [] : gsap.utils.toArray('[data-stack-card]');
  stackCards.forEach((card, i) => {
    const next = stackCards[i + 1];
    if (!next) return;
    gsap.to(card, {
      scale: 0.93,
      yPercent: -4,
      ease: 'none',
      scrollTrigger: { trigger: next, start: 'top bottom', end: 'top 30%', scrub: true },
    });
  });

  /* ---------- 6. Les réalisations, en parallaxe ---------- */
  (calm ? [] : gsap.utils.toArray('[data-parallax]')).forEach((img) => {
    gsap.fromTo(img, { yPercent: -6 }, {
      yPercent: 6,
      ease: 'none',
      scrollTrigger: { trigger: img.closest('.showcase__card'), start: 'top bottom', end: 'bottom top', scrub: true },
    });
  });

  /* ---------- 7. Les menus imprimés : les couvertures s'ouvrent en éventail ---------- */
  const covers = calm ? [] : gsap.utils.toArray('[data-cover]');
  if (covers.length) {
    gsap.from(covers, {
      '--x': '0%',
      '--r': (i) => ['-3deg', '0deg', '3deg'][i] || '0deg',
      yPercent: 12,
      ease: 'none',
      stagger: 0.04,
      scrollTrigger: { trigger: '[data-covers]', start: 'top 92%', end: 'center 55%', scrub: 0.6 },
    });
  }

  // Les positions dépendent des polices et des images : on recalcule une fois tout chargé
  document.fonts?.ready.then(() => ScrollTrigger.refresh());
  addEventListener('load', () => ScrollTrigger.refresh());
})();
