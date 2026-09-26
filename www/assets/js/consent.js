/*
 * Cookies et mesure d'audience.
 *
 * - Sans consentement : un simple compteur anonyme de pages vues (aucun cookie,
 *   aucun identifiant), comme le permet la CNIL pour la mesure d'audience.
 * - Avec consentement : un identifiant aléatoire (cookie de 13 mois maximum)
 *   permet de compter les visiteurs uniques, la durée des visites et les clics.
 * Le choix est gardé 6 mois, puis la question est reposée.
 */
(() => {
  'use strict';

  const CHOICE = 'miam_choix';
  const VISITOR = 'miam_id';
  const SIX_MONTHS = 182 * 86400;
  const THIRTEEN_MONTHS = 395 * 86400;

  const base = document.body.dataset.base || '';
  const endpoint = `${base}/api/track.php`;
  const banner = document.getElementById('cookie-banner');

  const read = (name) => document.cookie.split('; ').find((c) => c.startsWith(`${name}=`))?.split('=')[1];
  const write = (name, value, maxAge) => {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${name}=${value}; Max-Age=${maxAge}; Path=${base}/; SameSite=Lax${secure}`;
  };
  const hex = (bytes) => [...crypto.getRandomValues(new Uint8Array(bytes))].map((b) => b.toString(16).padStart(2, '0')).join('');
  const consented = () => read(CHOICE) === 'oui';
  const excluded = read('miam_exclude') === '1'; // visites du propriétaire, non comptées

  function visitorId() {
    let id = read(VISITOR);
    if (!/^[a-f0-9]{32}$/.test(id || '')) {
      id = hex(16);
      write(VISITOR, id, THIRTEEN_MONTHS); // jamais prolongé : il expire 13 mois après sa création
    }
    return id;
  }
  function sessionId() {
    try {
      let id = sessionStorage.getItem('miam_session');
      if (!/^[a-f0-9]{16}$/.test(id || '')) {
        id = hex(8);
        sessionStorage.setItem('miam_session', id);
      }
      return id;
    } catch {
      return hex(8);
    }
  }

  function send(payload) {
    if (excluded) return;
    const body = JSON.stringify(payload);
    try {
      if (navigator.sendBeacon && navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }))) return;
    } catch { /* on tente fetch */ }
    fetch(endpoint, { method: 'POST', body, keepalive: true, headers: { 'Content-Type': 'application/json' } }).catch(() => {});
  }

  const params = new URLSearchParams(location.search);
  const utm = {
    source: params.get('utm_source') || '',
    medium: params.get('utm_medium') || '',
    campaign: params.get('utm_campaign') || '',
  };

  /* ---------- Page vue ---------- */
  let viewId = null;
  let visibleSince = document.visibilityState === 'visible' ? performance.now() : null;
  let visibleMs = 0;
  let maxScroll = 0;

  function pageview(countHit) {
    // La source (utm_source, ex. « qr ») fait partie de l'adresse : elle est comptée même sans consentement.
    const payload = { t: 'view', hit: countHit, path: location.pathname, ref: document.referrer, utm };
    if (consented()) {
      viewId = hex(12);
      Object.assign(payload, {
        id: viewId,
        v: visitorId(),
        s: sessionId(),
        w: Math.round(screen.width) || 0,
        lang: navigator.language || '',
      });
    }
    send(payload);
  }

  const measureScroll = () => {
    const doc = document.documentElement;
    const ratio = (window.scrollY + window.innerHeight) / Math.max(doc.scrollHeight, 1);
    maxScroll = Math.max(maxScroll, Math.min(100, Math.round(ratio * 100)));
  };
  addEventListener('scroll', measureScroll, { passive: true });
  measureScroll();

  function leave() {
    if (visibleSince !== null) {
      visibleMs += performance.now() - visibleSince;
      visibleSince = null;
    }
    if (viewId && consented()) {
      send({ t: 'leave', id: viewId, v: visitorId(), d: Math.round(visibleMs / 1000), sc: maxScroll });
    }
  }
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') leave();
    else visibleSince = performance.now();
  });
  addEventListener('pagehide', leave);

  /* ---------- Événements (clics), uniquement avec consentement ---------- */
  window.miamTrack = (name, label = '') => {
    if (!consented()) return;
    send({ t: 'event', n: String(name).slice(0, 40), l: String(label).slice(0, 80), path: location.pathname, v: visitorId(), s: sessionId() });
  };

  /* ---------- Bandeau ---------- */
  function showBanner(takeFocus = false) {
    if (!banner) return;
    banner.classList.remove('is-leaving');
    banner.hidden = false;
    // On ne vole pas le focus d'un visiteur déjà en train de saisir quelque chose
    if (takeFocus || document.activeElement === document.body) banner.focus({ preventScroll: true });
  }
  function hideBanner() {
    if (!banner || banner.hidden) return;
    if (document.documentElement.classList.contains('calm')) { banner.hidden = true; return; }
    banner.classList.add('is-leaving');
    banner.addEventListener('animationend', () => { banner.hidden = true; banner.classList.remove('is-leaving'); }, { once: true });
  }

  function choose(value) {
    const before = read(CHOICE);
    write(CHOICE, value, SIX_MONTHS);
    if (value === 'oui') {
      if (before !== 'oui') {
        send({ t: 'consent', v: 'oui' });
        pageview(false); // la page en cours, déjà comptée anonymement
      }
    } else {
      write(VISITOR, '', 0); // retire l'identifiant s'il existait
      viewId = null;
      if (before !== 'non') send({ t: 'consent', v: 'non' });
    }
    hideBanner();
  }

  banner?.querySelectorAll('[data-consent]').forEach((button) => {
    button.addEventListener('click', () => choose(button.dataset.consent));
  });
  document.querySelectorAll('[data-cookie-manage]').forEach((button) => button.addEventListener('click', () => showBanner(true)));

  const choice = read(CHOICE);
  pageview(true);
  if (choice !== 'oui' && choice !== 'non') {
    // Petit délai : le bandeau arrive après la première impression de la page.
    setTimeout(showBanner, 900);
  }
})();
