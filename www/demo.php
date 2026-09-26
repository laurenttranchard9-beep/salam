<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$page = [
    'title' => 'La démo · ' . site_name(),
    'description' => "Essayez une carte en ligne comme vos clients : recherche, filtres, « Ma liste ». Puis passez côté gestion pour changer un prix ou déclarer un plat épuisé.",
    'path' => 'demo',
    'nav' => 'demo',
    'bodyClass' => 'page-demo',
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
?>
<main id="contenu">
  <?php section('demo', ['demoAsPage' => true]); ?>
  <?php require __DIR__ . '/includes/partials/cta-band.php'; ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
