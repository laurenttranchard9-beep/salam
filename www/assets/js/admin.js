/* Espace gestion : courbes, info-bulles, confirmations, petits confort. */
(() => {
  'use strict';

  document.documentElement.classList.add('js');
  const fmt = new Intl.NumberFormat('fr-FR');
  const NS = 'http://www.w3.org/2000/svg';
  const svgEl = (name, attrs = {}) => {
    const el = document.createElementNS(NS, name);
    Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
    return el;
  };

  /* ---------- Messages flash ---------- */
  document.querySelectorAll('[data-dismiss]').forEach((b) => b.addEventListener('click', () => b.closest('.flash')?.remove()));

  /* ---------- Confirmations, envoi automatique ---------- */
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  });
  document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
    form.addEventListener('change', () => form.requestSubmit ? form.requestSubmit() : form.submit());
  });

  /* ---------- Copier ---------- */
  document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const label = button.querySelector('span');
      try {
        await navigator.clipboard.writeText(button.dataset.copy);
        const before = label.textContent;
        label.textContent = 'Copié !';
        setTimeout(() => { label.textContent = before; }, 1600);
      } catch {
        prompt('Copiez l\'adresse :', button.dataset.copy);
      }
    });
  });

  /* ---------- Info-bulle partagée ---------- */
  const tip = document.getElementById('tip');
  function showTip(content, x, y) {
    if (!tip) return;
    tip.replaceChildren(...(Array.isArray(content) ? content : [document.createTextNode(content)]));
    tip.hidden = false;
    const r = tip.getBoundingClientRect();
    const left = Math.min(Math.max(8, x + 14), innerWidth - r.width - 8);
    const top = y - r.height - 12 < 8 ? y + 16 : y - r.height - 12;
    tip.style.left = `${left}px`;
    tip.style.top = `${top}px`;
  }
  const hideTip = () => { if (tip) tip.hidden = true; };

  document.querySelectorAll('[data-tip]').forEach((el) => {
    el.addEventListener('pointermove', (e) => showTip(el.dataset.tip, e.clientX, e.clientY));
    el.addEventListener('pointerleave', hideTip);
    el.addEventListener('focus', () => { const r = el.getBoundingClientRect(); showTip(el.dataset.tip, r.left + r.width / 2, r.top); });
    el.addEventListener('blur', hideTip);
  });
  addEventListener('scroll', hideTip, { passive: true });

  /* ---------- Courbes ---------- */
  // Graduations rondes : pas de 1, 2 ou 5 × 10ⁿ, cinq repères au plus
  function niceScale(value) {
    for (let pow = 1; ; pow *= 10) {
      for (const s of [1, 2, 5]) {
        const step = s * pow;
        const count = Math.max(1, Math.ceil(value / step));
        if (count <= 5) return { step, count };
      }
    }
  }

  function lineChart(figure) {
    const plot = figure.querySelector('[data-plot]');
    const data = JSON.parse(figure.querySelector('[data-chart-data]').textContent);
    const n = data.labels.length;
    let active = null;
    let geometry = null;

    function draw() {
      const width = plot.clientWidth;
      const height = plot.clientHeight;
      if (!width) return;
      const allValues = data.series.flatMap((s) => s.values);
      const { step, count } = niceScale(Math.max(1, ...allValues));
      const max = step * count;
      const endLabelSpace = data.series.length > 1 ? 34 : 8;
      const pad = { top: 12, right: endLabelSpace, bottom: 26, left: 8 + String(fmt.format(max)).length * 7.5 };
      const w = width - pad.left - pad.right;
      const h = height - pad.top - pad.bottom;
      const x = (i) => pad.left + (n === 1 ? w / 2 : (i / (n - 1)) * w);
      const y = (v) => pad.top + h - (v / max) * h;
      geometry = { x, y, pad, w, h };

      const svg = svgEl('svg', { viewBox: `0 0 ${width} ${height}`, 'aria-hidden': 'true' });
      // Grille horizontale et graduations arrondies
      for (let t = 0; t <= count; t++) {
        const v = step * t;
        const yy = Math.round(y(v)) + 0.5;
        svg.append(svgEl('line', { x1: pad.left, x2: width - pad.right, y1: yy, y2: yy, class: t === 0 ? 'baseline' : 'grid-line' }));
        const label = svgEl('text', { x: pad.left - 8, y: yy + 4, 'text-anchor': 'end', class: 'tick' });
        label.textContent = fmt.format(Math.round(v));
        svg.append(label);
      }
      // Dates (quelques-unes seulement)
      const every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(w / 70))));
      for (let i = 0; i < n; i += every) {
        const label = svgEl('text', { x: x(i), y: height - 6, 'text-anchor': i === 0 ? 'start' : 'middle', class: 'tick' });
        label.textContent = data.short[i];
        svg.append(label);
      }
      // Séries : voile de 10 % sous la première, lignes de 2 px
      data.series.forEach((s, si) => {
        const color = `var(--series-${s.slot})`;
        const points = s.values.map((v, i) => `${x(i).toFixed(1)},${y(v).toFixed(1)}`);
        if (si === 0) {
          svg.append(svgEl('path', { d: `M${x(0)},${y(0)} L${points.join(' L')} L${x(n - 1)},${y(0)} Z`, class: 'area', fill: color }));
        }
        svg.append(svgEl('path', { d: `M${points.join(' L')}`, class: 'line', stroke: color }));
      });
      // Valeur finale à droite de chaque courbe, si elles ne se chevauchent pas
      if (data.series.length > 1) {
        const ends = data.series.map((s) => y(s.values[n - 1]));
        if (Math.abs(ends[0] - ends[1]) >= 14) {
          data.series.forEach((s, si) => {
            const label = svgEl('text', { x: x(n - 1) + 8, y: ends[si] + 4, class: 'end-label' });
            label.textContent = fmt.format(s.values[n - 1]);
            svg.append(label);
          });
        }
      }
      // Calque du survol
      const hover = svgEl('g', { class: 'hover' });
      svg.append(hover);
      plot.replaceChildren(svg);
      geometry.hover = hover;
      if (active !== null) highlight(active, false);
    }

    function highlight(i, withTip = true) {
      if (!geometry) return;
      active = i;
      const { x, y, pad, h, hover } = geometry;
      hover.replaceChildren(svgEl('line', { x1: Math.round(x(i)) + 0.5, x2: Math.round(x(i)) + 0.5, y1: pad.top, y2: pad.top + h, class: 'cross' }));
      data.series.forEach((s) => {
        hover.append(svgEl('circle', { cx: x(i), cy: y(s.values[i]), r: 4.5, fill: `var(--series-${s.slot})`, class: 'marker' }));
      });
      if (!withTip) return;
      const title = document.createElement('div');
      title.className = 'tip__title';
      title.textContent = data.labels[i];
      const rows = data.series.map((s) => {
        const row = document.createElement('div');
        row.className = 'tip__row';
        const key = document.createElement('i');
        key.className = `key key--line series-${s.slot}`;
        const value = document.createElement('strong');
        value.textContent = fmt.format(s.values[i]);
        const name = document.createElement('span');
        name.textContent = s.name;
        row.append(key, value, name);
        return row;
      });
      const box = plot.getBoundingClientRect();
      showTip([title, ...rows], box.left + x(i), box.top + Math.min(...data.series.map((s) => y(s.values[i]))));
    }

    function clear() {
      active = null;
      geometry?.hover.replaceChildren();
      hideTip();
    }

    plot.addEventListener('pointermove', (e) => {
      if (!geometry) return;
      const box = plot.getBoundingClientRect();
      const px = e.clientX - box.left;
      const { pad, w } = geometry;
      const i = n === 1 ? 0 : Math.round(((px - pad.left) / w) * (n - 1));
      highlight(Math.max(0, Math.min(n - 1, i)));
    });
    plot.addEventListener('pointerleave', clear);
    plot.addEventListener('blur', clear);
    plot.addEventListener('keydown', (e) => {
      const moves = { ArrowRight: 1, ArrowLeft: -1, Home: -Infinity, End: Infinity };
      if (!(e.key in moves)) return;
      e.preventDefault();
      const start = active ?? n - 1;
      highlight(Math.max(0, Math.min(n - 1, start + moves[e.key])));
    });
    plot.addEventListener('focus', () => highlight(active ?? n - 1));

    draw();
    if ('ResizeObserver' in window) new ResizeObserver(() => draw()).observe(plot);
  }

  document.querySelectorAll('[data-line-chart]').forEach(lineChart);
})();
