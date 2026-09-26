<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$printMenus = print_menus();
$page = [
    'title' => 'Menus imprimés · ' . site_name(),
    'description' => "Des cartes à poser sur les tables : un A3 plié en trois volets, à vos couleurs et assorti à votre carte en ligne. Exemples réels : Aux Saveurs Braisées et La Fleur d'Or.",
    'path' => 'menus',
    'nav' => 'menus',
    'bodyClass' => 'page-menus topbar-ink',
    'vendor' => ['js/vendor/gsap.min.js', 'js/vendor/ScrollTrigger.min.js'],
    'scripts' => ['js/menus.js'],
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
$hero = [
    'kicker' => 'Menus imprimés',
    'title' => 'Aussi sur papier',
    'lead' => "Une carte en ligne n'empêche pas une belle carte sur la table. Je mets aussi en page vos menus à imprimer, à vos couleurs, assortis à votre site.",
    'theme' => 'paper',
    'word' => 'Sur la table',
];
?>
<main id="contenu">
  <?php require __DIR__ . '/includes/partials/page-hero.php'; ?>
  <?php section('depliant'); ?>
  <?php section('imprimes'); ?>

  <section class="section print-steps" aria-labelledby="print-steps-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="kicker">Comment ça se passe</p>
        <h2 class="h2" id="print-steps-title">De l'ardoise à l'imprimeur</h2>
        <p class="lead">Pas besoin de logiciel ni de savoir-faire : vous m'envoyez votre carte, vous recevez un fichier prêt à imprimer.</p>
      </header>
      <ol class="steps">
        <?php foreach (print_steps() as $n => $step): ?>
          <li class="step" style="--c:<?= h($step['color']) ?>;--i:<?= $n ?>" data-reveal>
            <span class="step__num"><?= $n + 1 ?></span>
            <h3 class="step__title"><?= h($step['title']) ?></h3>
            <p><?= h($step['text']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
      <p class="print-steps__note" data-reveal>Vous avez déjà une carte en ligne avec moi&nbsp;? La version imprimée reprend les mêmes plats, les mêmes prix et les mêmes couleurs. Pour en demander une, passez par <a href="<?= h(url('contact')) ?>?option=imprime#formulaire">ce formulaire</a> : l'option «&nbsp;Carte imprimée&nbsp;» y sera déjà cochée.</p>
    </div>
  </section>

  <?php require __DIR__ . '/includes/partials/cta-band.php'; ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
