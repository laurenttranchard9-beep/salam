<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$sent = ($_GET['envoi'] ?? '') === 'ok';
$formError = (string) ($_GET['erreur'] ?? '');
// Formule présélectionnée depuis la page Formules (?formule=gestion)
$chosenOffer = isset($offers[$_GET['formule'] ?? '']) ? (string) $_GET['formule'] : 'indecis';
// Option cochée d'avance depuis la page Menus imprimés (?option=imprime)
$chosenOptions = array_intersect(array_keys(contact_options()), [(string) ($_GET['option'] ?? '')]);

$page = [
    'title' => 'Contact · ' . site_name(),
    'description' => 'Parlons de la carte en ligne de votre restaurant : décrivez votre projet, je vous réponds avec un devis.',
    'path' => 'contact',
    'nav' => 'contact',
    'bodyClass' => 'page-contact',
];
send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
?>
<main id="contenu">
  <?php section('contact', ['contactAsPage' => true]); ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
