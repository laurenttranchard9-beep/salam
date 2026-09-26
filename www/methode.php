<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$page = [
    'title' => 'La méthode · ' . site_name(),
    'description' => "Comment se passe la création de votre carte en ligne, étape par étape, et les réponses aux questions fréquentes.",
    'path' => 'methode',
    'nav' => 'methode',
    'bodyClass' => 'page-methode',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn (array $item): array => [
            '@type' => 'Question',
            'name' => $item['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
        ], $faq),
    ],
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
$hero = [
    'kicker' => 'La fabrication',
    'title' => "De l'ardoise à l'écran",
    'lead' => "Vous n'avez rien de technique à faire. Vous m'envoyez votre carte, je m'occupe du reste.",
    'theme' => 'plum',
    'word' => 'Méthode',
];
?>
<main id="contenu">
  <?php require __DIR__ . '/includes/partials/page-hero.php'; ?>
  <?php section('methode'); ?>
  <?php section('faq'); ?>
  <?php require __DIR__ . '/includes/partials/cta-band.php'; ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
