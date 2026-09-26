<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

http_response_code(404);
$page = [
    'title' => 'Page introuvable · ' . site_name(),
    'description' => "Cette page n'existe pas.",
    'path' => '',
    'robots' => 'noindex',
    'bodyClass' => 'error',
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
?>
<main id="contenu" class="not-found">
  <div>
    <p class="not-found__big" aria-hidden="true">404</p>
    <h1>Pas au menu</h1>
    <p>Cette page n'existe pas, ou plus. La carte, elle, est toujours là.</p>
    <a class="pill pill--white pill--halo" href="<?= h(url('')) ?>"><svg aria-hidden="true"><use href="#i-home"/></svg>Retour à l'accueil</a>
  </div>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
