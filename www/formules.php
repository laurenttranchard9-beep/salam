<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$page = [
    'title' => 'Les formules · ' . site_name(),
    'description' => 'La Carte, Carte + Gestion, Le Site Resto ou Sur mesure : les formules pour mettre la carte de votre restaurant en ligne.',
    'path' => 'formules',
    'nav' => 'formules',
    'bodyClass' => 'page-formules topbar-ink',
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
$hero = [
    'kicker' => 'La gamme',
    'title' => 'Choisissez votre formule',
    'lead' => "Du menu consultable au site complet de votre restaurant. Chaque formule s'ouvre en détail : cliquez sur celle qui vous tente.",
    'theme' => 'sun',
    'word' => 'Formules',
];
$columns = array_slice($offers, 0, 3, true);
?>
<main id="contenu">
  <?php require __DIR__ . '/includes/partials/page-hero.php'; ?>
  <?php section('gamme'); ?>

  <section class="section compare" aria-labelledby="compare-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="kicker">En un coup d'œil</p>
        <h2 class="h2" id="compare-title">Ce que contient chaque formule</h2>
      </header>
      <div class="compare__scroll" data-reveal data-lenis-prevent-horizontal>
        <table class="compare__table">
          <caption class="sr-only">Comparaison des formules</caption>
          <thead>
            <tr>
              <th scope="col"><span class="sr-only">Fonction</span></th>
              <?php foreach ($columns as $key => $offer): ?>
                <th scope="col" style="--c1:<?= h($offer['colors'][0]) ?>;--c2:<?= h($offer['colors'][1]) ?>;--ct:<?= h($offer['colors'][2]) ?>">
                  <span class="compare__name"><?= h($offer['name']) ?></span>
                  <span class="compare__price"><?= h(offer_price($key)) ?></span>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach (offer_comparison() as [$label, $a, $b, $c]): ?>
              <tr>
                <th scope="row"><?= h($label) ?></th>
                <?php foreach ([$a, $b, $c] as $included): ?>
                  <td><?= $included
                      ? '<span class="yes"><svg aria-hidden="true"><use href="#i-check"/></svg><span class="sr-only">Inclus</span></span>'
                      : '<span class="no" aria-hidden="true">–</span><span class="sr-only">Non inclus</span>' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td></td>
              <?php foreach ($columns as $key => $offer): ?>
                <td><a class="pill pill--ink pill--sm" href="<?= h(url('contact') . '?formule=' . $key) ?>" data-track="offre-choisie" data-label="<?= h($key) ?>">Choisir</a></td>
              <?php endforeach; ?>
            </tr>
          </tfoot>
        </table>
      </div>
      <p class="compare__note" data-reveal>Traduction, ardoise du jour, plusieurs cartes ou plusieurs établissements&nbsp;? C'est la formule <strong>Sur mesure</strong>&nbsp;: on compose ensemble. <a href="<?= h(url('methode')) ?>#faq">Voir les questions fréquentes</a></p>
    </div>
  </section>

  <?php require __DIR__ . '/includes/partials/cta-band.php'; ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
