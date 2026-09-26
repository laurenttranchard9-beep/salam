<?php
/**
 * Le dépliant d'Aux Saveurs Braisées, qui s'ouvre au fil du défilement (menus.js).
 * Chaque volet a deux faces : l'intérieur devant, l'extérieur au dos.
 * Sans JavaScript, la carte reste simplement ouverte.
 */
$panel = static fn (string $face): string => h(asset('img/menus/asb-' . $face . '.webp'));
$volets = [
    'left' => ['interieur-1', 'exterieur-3'],
    'mid' => ['interieur-2', 'exterieur-2'],
    'right' => ['interieur-3', 'exterieur-1'],
];
?>
<section class="unfold" id="depliant" aria-labelledby="unfold-title" data-unfold>
  <div class="unfold__stage">
    <header class="unfold__head">
      <p class="kicker kicker--light">Exemple · Aux Saveurs Braisées</p>
      <h2 class="unfold__title" id="unfold-title" data-words-in><?= split_words('Dépliez la carte') ?></h2>
      <div class="unfold__meter" aria-hidden="true"><span data-fold-meter></span></div>
    </header>
    <div class="unfold__scene">
      <div class="fold" role="img" aria-label="La carte imprimée d'Aux Saveurs Braisées, un A3 plié en trois volets, qui s'ouvre au fil du défilement" data-fold>
        <?php foreach ($volets as $side => [$front, $back]): ?>
          <div class="fold__panel fold__panel--<?= $side ?>" data-fold-panel="<?= $side ?>">
            <img class="fold__face" src="<?= $panel($front) ?>" alt="" width="700" height="1485" loading="lazy" decoding="async">
            <img class="fold__face fold__face--back" src="<?= $panel($back) ?>" alt="" width="700" height="1485" loading="lazy" decoding="async">
          </div>
        <?php endforeach; ?>
      </div>
      <div class="fold__shadow" aria-hidden="true" data-fold-shadow></div>
    </div>
    <ol class="unfold__notes">
      <?php foreach (fold_notes() as $i => [$title, $text]): ?>
        <li class="note" data-fold-note>
          <span class="note__num"><?= $i + 1 ?></span>
          <span class="note__body"><strong><?= h($title) ?></strong><span><?= h($text) ?></span></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
