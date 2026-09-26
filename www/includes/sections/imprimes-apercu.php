<?php
/**
 * Aperçu des menus imprimés : les couvertures en éventail, lien vers la page dédiée.
 * @var string $teaserLabel libellé de suivi du bouton (accueil, realisations…)
 */
$covers = [
    ['asb-exterieur-3', "Couverture de la carte d'Aux Saveurs Braisées"],
    ['fdo-bistrot-exterieur-3', "Couverture de la carte de La Fleur d'Or, style bistrot"],
    ['fdo-ardoise-exterieur-3', "Couverture de la carte de La Fleur d'Or, style ardoise"],
];
?>
<section class="section papers" aria-labelledby="papers-title">
  <div class="wrap papers__grid">
    <div class="papers__text" data-reveal>
      <p class="kicker">Menus imprimés</p>
      <h2 class="h2" id="papers-title">Aussi sur papier</h2>
      <p class="lead">Pour les tables, je mets aussi en page vos cartes à imprimer : un A3 plié en trois volets, à vos couleurs et assorti à votre site.</p>
      <a class="pill pill--ink" href="<?= h(url('menus')) ?>" data-magnetic data-track="cta" data-label="<?= h($teaserLabel ?? 'apercu') ?>-menus">Voir les cartes imprimées <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
    </div>
    <a class="covers" href="<?= h(url('menus')) ?>" aria-label="Voir les cartes imprimées" data-covers tabindex="-1">
      <?php foreach ($covers as [$file, $alt]): ?>
        <span class="covers__item" data-cover><img src="<?= h(asset('img/menus/' . $file . '.webp')) ?>" alt="<?= h($alt) ?>" width="700" height="1485" loading="lazy" decoding="async"></span>
      <?php endforeach; ?>
      <span class="covers__tag" aria-hidden="true">A3 · 3 volets</span>
    </a>
  </div>
</section>
