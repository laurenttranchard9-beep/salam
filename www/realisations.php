<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$page = [
    'title' => 'Réalisations · ' . site_name(),
    'description' => "Aux Saveurs Braisées et La Fleur d'Or : deux restaurants, deux cartes en ligne, deux besoins différents.",
    'path' => 'realisations',
    'nav' => 'realisations',
    'bodyClass' => 'page-realisations',
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
$hero = [
    'kicker' => 'Réalisations',
    'title' => 'Déjà en service',
    'lead' => "Deux restaurants, deux besoins différents. Ouvrez leurs sites sur votre téléphone : c'est là qu'ils sont les plus beaux.",
    'theme' => 'ember',
    'word' => 'Réalisations',
];
?>
<main id="contenu">
  <?php require __DIR__ . '/includes/partials/page-hero.php'; ?>
  <?php section('realisations'); ?>
  <?php section('imprimes-apercu', ['teaserLabel' => 'realisations']); ?>
  <?php require __DIR__ . '/includes/partials/cta-band.php'; ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
