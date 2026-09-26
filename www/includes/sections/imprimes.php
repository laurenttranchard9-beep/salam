<?php
/**
 * Galerie des cartes imprimées : chaque feuille se retourne (extérieur / intérieur)
 * et s'agrandit dans une visionneuse pour lire chaque ligne.
 * @var array $printMenus
 */
$menuImg = static fn (string $key, string $side, string $size = ''): string => asset('img/menus/' . $key . '-' . $side . ($size !== '' ? '-' . $size : '') . '.webp');
$sides = ['exterieur' => 'Extérieur', 'interieur' => 'Intérieur'];
?>
<section class="section prints" id="creations" aria-labelledby="prints-title">
  <div class="wrap">
    <header class="section-head" data-reveal>
      <p class="kicker">Mes créations</p>
      <h2 class="h2" id="prints-title">Trois cartes, trois caractères</h2>
      <p class="lead">Des cartes réellement imprimées, posées sur les tables de mes clients. Retournez la feuille pour voir l'autre face, et agrandissez-la pour lire chaque ligne.</p>
    </header>
    <div class="prints__list">
      <?php foreach ($printMenus as $i => $menu):
          $title = $menu['restaurant'] . ', style ' . $menu['style'];
          $data = [
              'title' => $title,
              'sides' => array_map(static fn (string $side, string $label): array => [
                  'key' => $side,
                  'label' => $label,
                  'src' => $menuImg($menu['key'], $side),
                  'zoom' => $menuImg($menu['key'], $side, 'zoom'),
                  'alt' => $label . ' de la carte ' . $title,
              ], array_keys($sides), $sides),
          ]; ?>
        <article class="print<?= $i % 2 ? ' print--flip' : '' ?>" id="carte-<?= h($menu['key']) ?>" style="--p1:<?= h($menu['colors'][0]) ?>;--p2:<?= h($menu['colors'][1]) ?>" data-print="<?= h(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" data-print-key="<?= h($menu['key']) ?>" data-reveal>
          <div class="print__visual">
            <button type="button" class="paper" data-print-zoom data-track="menu-imprime" data-label="<?= h($menu['key']) ?>">
              <span class="sr-only">Agrandir la carte <?= h($title) ?></span>
              <span class="paper__card" data-paper>
                <?php foreach (array_keys($sides) as $n => $side): ?>
                  <img class="paper__face<?= $n ? ' paper__face--back' : '' ?>" src="<?= h($menuImg($menu['key'], $side)) ?>" alt="" width="1600" height="1131" loading="lazy" decoding="async">
                <?php endforeach; ?>
              </span>
              <span class="paper__zoom" aria-hidden="true"><svg><use href="#i-search"/></svg></span>
            </button>
          </div>
          <div class="print__text">
            <p class="print__style">Style <?= h($menu['style']) ?></p>
            <h3 class="print__name"><?= h($menu['restaurant']) ?></h3>
            <p class="print__look"><?= h($menu['look']) ?></p>
            <ul class="ticks">
              <?php foreach ($menu['points'] as $point): ?>
                <li><svg aria-hidden="true"><use href="#i-check"/></svg><?= h($point) ?></li>
              <?php endforeach; ?>
            </ul>
            <div class="print__actions">
              <div class="flipseg" role="group" aria-label="Face de la carte">
                <?php foreach ($sides as $side => $label): ?>
                  <button type="button" data-side="<?= $side ?>" aria-pressed="<?= $side === 'exterieur' ? 'true' : 'false' ?>"><?= h($label) ?></button>
                <?php endforeach; ?>
              </div>
              <button type="button" class="pill pill--ink pill--sm" data-print-zoom data-track="menu-imprime" data-label="<?= h($menu['key']) ?>">Agrandir <svg aria-hidden="true"><use href="#i-search"/></svg></button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<dialog class="zoom" id="zoom" aria-labelledby="zoom-title" data-lenis-prevent>
  <div class="zoom__bar">
    <p class="zoom__title" id="zoom-title" data-zoom-title></p>
    <div class="zoom__actions">
      <div class="flipseg flipseg--light" role="group" aria-label="Face de la carte" data-zoom-sides>
        <?php foreach ($sides as $side => $label): ?>
          <button type="button" data-side="<?= $side ?>" aria-pressed="false"><?= h($label) ?></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="zoom__toggle" aria-pressed="false" data-zoom-toggle><svg aria-hidden="true"><use href="#i-search"/></svg>Zoom</button>
    </div>
    <button type="button" class="round zoom__close" data-sheet-close><svg aria-hidden="true"><use href="#i-close"/></svg><span class="sr-only">Fermer</span></button>
  </div>
  <div class="zoom__view" role="region" tabindex="0" aria-label="Carte agrandie" data-zoom-view>
    <img class="zoom__img" src="<?= h($menuImg($printMenus[0]['key'], 'exterieur')) ?>" alt="" loading="lazy" draggable="false" data-zoom-img>
  </div>
  <p class="zoom__hint" aria-live="polite" data-zoom-hint></p>
</dialog>
