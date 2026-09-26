<?php
$admin = current_admin();
$excluded = ($_COOKIE['miam_exclude'] ?? '') === '1';
$dataDir = data_dir();
$checks = [
    ['PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.1', '>='), 'PHP 8.1 ou plus est nécessaire.'],
    ['SQLite ' . db()->query('SELECT sqlite_version()')->fetchColumn(), true, ''],
    ['Dossier de données accessible en écriture', is_writable($dataDir), 'Donnez les droits d\'écriture au dossier data/ (755 ou 775 selon l\'hébergeur).'],
    ['Connexion sécurisée (HTTPS)', is_https(), 'Activez le certificat SSL chez votre hébergeur, puis la redirection HTTPS dans le .htaccess.'],
    ['Fonction mail() de PHP', function_exists('mail'), 'Sans elle, pas de notification par email : les messages restent visibles ici.'],
];
$missingLegal = missing_legal_fields();
?>
<header class="head">
  <div>
    <h1 class="head__title">Réglages</h1>
    <p class="head__sub">Les modifications s'appliquent immédiatement au site.</p>
  </div>
</header>

<form method="post" class="stack-lg">
  <?= csrf_field() ?><input type="hidden" name="action" value="settings">

  <section class="card">
    <header class="card__head"><h2 class="card__title">Notifications</h2></header>
    <div class="form-grid">
      <label class="field"><span>Recevoir les nouvelles demandes à l'adresse</span>
        <input class="input" type="email" name="notify_email" value="<?= h((string) setting('notify_email', '')) ?>" placeholder="vous@exemple.fr" autocomplete="email"></label>
      <label class="switch-row">
        <input type="checkbox" name="notify_enabled" value="1"<?= setting('notify_enabled', '1') === '1' ? ' checked' : '' ?>>
        <span><strong>M'envoyer un email à chaque nouvelle demande</strong><small>Vous pourrez répondre directement depuis votre messagerie.</small></span>
      </label>
    </div>
  </section>

  <section class="card">
    <header class="card__head"><h2 class="card__title">Coordonnées affichées sur le site</h2><p class="card__hint">Laissez vide ce que vous ne voulez pas montrer.</p></header>
    <div class="form-grid form-grid--3">
      <label class="field"><span>Téléphone</span><input class="input" type="tel" name="public_phone" value="<?= h((string) setting('public_phone', '')) ?>" placeholder="06 12 34 56 78"><small>Ajoute un bouton « Appeler » en haut du site.</small></label>
      <label class="field"><span>Email</span><input class="input" type="email" name="public_email" value="<?= h((string) setting('public_email', '')) ?>" placeholder="contact@exemple.fr"></label>
      <label class="field"><span>Zone d'intervention</span><input class="input" name="zone" value="<?= h((string) setting('zone', '')) ?>" placeholder="Toulouse et partout en France"></label>
    </div>
  </section>

  <section class="card">
    <header class="card__head"><h2 class="card__title">Tarifs affichés</h2><p class="card__hint">Par exemple « dès 390 € ». Vide = « Sur devis ».</p></header>
    <div class="form-grid form-grid--4">
      <?php foreach (offers() as $key => $offer): ?>
        <label class="field"><span><i class="dot" style="background:<?= h($offer['colors'][0]) ?>"></i><?= h($offer['name']) ?></span>
          <input class="input" name="price_<?= h($key) ?>" value="<?= h((string) setting('price_' . $key, '')) ?>" placeholder="Sur devis" maxlength="40"></label>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="sticky-save"><button class="btn btn--primary" type="submit"><?= icon('check') ?>Enregistrer les réglages</button></div>
</form>

<div class="grid grid--2">
  <section class="card">
    <header class="card__head"><h2 class="card__title">Tester les emails</h2></header>
    <p class="muted small">Envoie un email de test à <?= h((string) setting('notify_email', '') ?: 'l\'adresse de notification') ?>.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="test_email">
      <button class="btn" type="submit"<?= setting('notify_email', '') === '' ? ' disabled' : '' ?>><?= icon('mail') ?>Envoyer un email de test</button></form>
  </section>

  <section class="card">
    <header class="card__head"><h2 class="card__title">Mesure d'audience</h2></header>
    <form method="post" class="stack" data-autosubmit><?= csrf_field() ?><input type="hidden" name="action" value="exclude">
      <label class="switch-row">
        <input type="checkbox" name="exclude" value="1"<?= $excluded ? ' checked' : '' ?>>
        <span><strong>Ne pas compter mes visites sur cet appareil</strong><small>Activé automatiquement à la connexion. Faites-le sur chacun de vos appareils.</small></span>
      </label>
      <button class="btn btn--small" type="submit" data-hide-with-js>Appliquer</button>
    </form>
  </section>
</div>

<div class="grid grid--2">
  <section class="card">
    <header class="card__head"><h2 class="card__title">Mot de passe</h2><p class="card__hint">Compte : <?= h($admin['username']) ?><?= $admin['last_login_at'] ? ' · dernière connexion ' . h(relative_date_fr($admin['last_login_at'])) : '' ?></p></header>
    <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <input type="text" name="username" value="<?= h($admin['username']) ?>" autocomplete="username" hidden>
      <label class="field"><span>Mot de passe actuel</span><input class="input" type="password" name="current" required autocomplete="current-password"></label>
      <label class="field"><span>Nouveau mot de passe <small>(<?= ADMIN_MIN_PASSWORD ?> caractères minimum)</small></span><input class="input" type="password" name="new" required minlength="<?= ADMIN_MIN_PASSWORD ?>" autocomplete="new-password"></label>
      <label class="field"><span>Confirmer</span><input class="input" type="password" name="confirm" required minlength="<?= ADMIN_MIN_PASSWORD ?>" autocomplete="new-password"></label>
      <div><button class="btn" type="submit">Changer le mot de passe</button></div>
    </form>
  </section>

  <section class="card">
    <header class="card__head"><h2 class="card__title">État du site</h2></header>
    <ul class="checks">
      <?php foreach ($checks as [$label, $ok, $help]): ?>
        <li class="checks__item checks__item--<?= $ok ? 'ok' : 'ko' ?>"><?= icon($ok ? 'check' : 'alert') ?><span><?= h($label) ?><?php if (!$ok): ?><small><?= h($help) ?></small><?php endif; ?></span><span class="sr-only"><?= $ok ? 'OK' : 'À corriger' ?></span></li>
      <?php endforeach; ?>
      <li class="checks__item checks__item--<?= $missingLegal ? 'ko' : 'ok' ?>"><?= icon($missingLegal ? 'alert' : 'check') ?><span>Mentions légales<?php if ($missingLegal): ?><small>À compléter dans config.php : <?= h(implode(', ', $missingLegal)) ?>.</small><?php endif; ?></span></li>
    </ul>
  </section>
</div>

<section class="card card--danger">
  <header class="card__head"><h2 class="card__title">Effacer les statistiques</h2><p class="card__hint">Utile après vos essais, avant la mise en ligne. Les messages ne sont pas touchés.</p></header>
  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="stats_reset">
    <label class="field"><span>Tapez EFFACER pour confirmer</span><input class="input" name="confirm" autocomplete="off" pattern="EFFACER" required></label>
    <button class="btn btn--danger" type="submit"><?= icon('trash') ?>Effacer toutes les statistiques</button>
  </form>
</section>
