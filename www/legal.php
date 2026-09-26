<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$which = ($_GET['page'] ?? '') === 'confidentialite' ? 'confidentialite' : 'mentions';

/** Valeur de config.php, ou mention « à compléter » bien visible. */
$field = static function (string $key): string {
    $value = trim((string) config('legal.' . $key, ''));
    return $value !== '' ? nl2br(h($value)) : '<span class="todo">à compléter</span>';
};
$contactEmail = trim((string) config('legal.email', ''));

$page = $which === 'mentions'
    ? ['title' => 'Mentions légales · ' . site_name(), 'description' => 'Mentions légales du site ' . site_name() . '.', 'path' => 'mentions-legales', 'heading' => 'Mentions légales', 'intro' => 'Qui édite ce site, et qui l\'héberge.']
    : ['title' => 'Confidentialité et cookies · ' . site_name(), 'description' => 'Comment ' . site_name() . ' traite vos données et utilise les cookies.', 'path' => 'confidentialite', 'heading' => 'Confidentialité', 'intro' => 'Vos données, les cookies, et vos droits. En clair.'];
$page['bodyClass'] = 'legal';

send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
?>
<main id="contenu">
  <?php $hero = ['kicker' => 'Informations', 'title' => $page['heading'], 'lead' => $page['intro'], 'theme' => 'ink', 'word' => $page['heading'], 'compact' => true];
  require __DIR__ . '/includes/partials/page-hero.php'; ?>

  <article class="prose">
  <?php if ($which === 'mentions'): ?>
    <h2>Éditeur du site</h2>
    <p>
      <strong><?= $field('publisher') ?></strong>, <?= $field('status') ?><br>
      SIRET : <?= $field('siret') ?><br>
      Adresse : <?= $field('address') ?><br>
      Email : <?= $contactEmail !== '' ? '<a href="mailto:' . h($contactEmail) . '">' . h($contactEmail) . '</a>' : $field('email') ?><br>
      Téléphone : <?= $field('phone') ?>
    </p>

    <h2>Directeur de la publication</h2>
    <p><?= $field('director') ?></p>

    <h2>Hébergement</h2>
    <p>
      <?= $field('host_name') ?><br>
      <?= $field('host_address') ?><br>
      Téléphone : <?= $field('host_phone') ?>
    </p>

    <h2>Propriété intellectuelle</h2>
    <p>Les textes, illustrations et la mise en page de ce site sont la propriété de leur auteur. Toute reproduction sans autorisation est interdite.</p>
    <p>Les captures d'écran des sites réalisés sont présentées à titre de références. Les restaurants et les chiffres de la démonstration interactive sont fictifs.</p>
    <p>Polices de caractères : Bricolage Grotesque et Unbounded, sous licence libre SIL Open Font License, hébergées sur ce site.</p>

    <h2>Données personnelles</h2>
    <p>Tout est expliqué sur la page <a href="<?= h(url('confidentialite')) ?>">Confidentialité et cookies</a>.</p>

  <?php else: ?>
    <h2>Qui est responsable de vos données&nbsp;?</h2>
    <p><?= $field('publisher') ?>, éditeur de ce site<?= $contactEmail !== '' ? ', joignable à <a href="mailto:' . h($contactEmail) . '">' . h($contactEmail) . '</a>' : '' ?>.</p>

    <h2>Le formulaire de contact</h2>
    <p>Quand vous m'écrivez, je reçois ce que vous avez saisi&nbsp;: nom, établissement, email, téléphone, type d'établissement, taille de la carte, formule et options qui vous intéressent, et votre message.</p>
    <ul>
      <li><strong>Pourquoi&nbsp;:</strong> vous répondre et, si vous le souhaitez, vous proposer un devis.</li>
      <li><strong>Sur quelle base&nbsp;:</strong> votre demande (mesures précontractuelles prises à votre demande).</li>
      <li><strong>Combien de temps&nbsp;:</strong> trois ans au plus après notre dernier échange, puis le message est supprimé.</li>
      <li><strong>Qui y a accès&nbsp;:</strong> moi seul. Rien n'est vendu, loué ni transmis à des tiers.</li>
    </ul>
    <p>Si vous avez accepté la mesure d'audience, la source de votre visite (par exemple « Google » ou « QR code ») est jointe à votre message.</p>

    <h2>La mesure d'audience</h2>
    <p>Ce site n'utilise aucun outil de mesure externe (pas de Google Analytics) et aucune publicité. Les statistiques sont calculées sur le serveur du site et ne quittent jamais celui-ci.</p>
    <p><strong>Sans votre accord</strong>, un compteur anonyme additionne les pages vues par jour, avec la page consultée, le type d'appareil (déduit de votre navigateur) et le site qui vous a envoyé ici. Aucun cookie, aucun identifiant&nbsp;: il est impossible de savoir qui a vu quoi.</p>
    <p><strong>Avec votre accord</strong>, un identifiant aléatoire permet en plus de compter les visiteurs uniques, la durée des visites, la part de page lue et les clics sur les boutons, ainsi que le navigateur, le système, la largeur d'écran et la langue. Ces données sont conservées 13 mois au plus&nbsp;; les totaux anonymes, 25 mois.</p>

    <h2>Les cookies utilisés</h2>
    <div class="table-scroll">
      <table>
        <thead><tr><th>Nom</th><th>À quoi il sert</th><th>Durée</th></tr></thead>
        <tbody>
          <tr><td>miam_choix</td><td>Mémorise votre choix (accepter ou refuser) pour ne pas vous le redemander à chaque page.</td><td>6 mois</td></tr>
          <tr><td>miam_id</td><td>Identifiant aléatoire de mesure d'audience. Déposé seulement si vous acceptez.</td><td>13 mois</td></tr>
          <tr><td>miam_session</td><td>Regroupe les pages vues pendant une même visite (stockage de l'onglet). Seulement si vous acceptez.</td><td>Jusqu'à la fermeture de l'onglet</td></tr>
        </tbody>
      </table>
    </div>
    <p>Vous pouvez changer d'avis à tout moment&nbsp;: <button type="button" class="linklike" data-cookie-manage>gérer les cookies</button>. Si vous refusez, l'identifiant est supprimé.</p>

    <h2>Vos droits</h2>
    <p>Vous pouvez accéder à vos données, les faire corriger ou supprimer, vous opposer à leur utilisation ou en demander la limitation et la portabilité. Écrivez-moi<?= $contactEmail !== '' ? ' à <a href="mailto:' . h($contactEmail) . '">' . h($contactEmail) . '</a>' : '' ?>, je vous réponds sous un mois.</p>
    <p>Si vous estimez que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la CNIL&nbsp;: <a href="https://www.cnil.fr/fr/plaintes" rel="noopener" target="_blank">www.cnil.fr</a>.</p>
  <?php endif; ?>
  </article>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
