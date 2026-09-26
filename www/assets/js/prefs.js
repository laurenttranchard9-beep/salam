/*
 * Chargé tout de suite (sans « defer »), avant l'affichage : décide si le site
 * s'anime pleinement ou en mode doux (« calm »).
 * - Si l'appareil demande moins d'animations (réglage Windows, macOS, iOS, Android),
 *   le mode doux est utilisé : fondus, pas de défilement « glissant », pas de parallaxe.
 * - Le visiteur peut forcer son choix depuis le pied de page (mémorisé sur cet appareil).
 */
(function () {
  var root = document.documentElement;
  var choice = null;
  root.className += ' js';
  try { choice = window.localStorage.getItem('miam_motion'); } catch (e) { /* stockage indisponible */ }
  var osReduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  if (osReduce) root.className += ' os-reduce';
  if (choice === 'calm' || (osReduce && choice !== 'full')) root.className += ' calm';
})();
