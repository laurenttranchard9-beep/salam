<?php /** @var ?string $error @var string $username @var string $next */ ?>
<div class="auth__card">
  <p class="auth__brand"><?= h(mb_strtolower(site_name())) ?><?= icon('spark', 'brand__spark') ?></p>
  <h1 class="auth__title">Espace gestion</h1>
  <p class="auth__lead">Messages reçus, statistiques et réglages du site.</p>
  <?php foreach ($flashes as $f): ?><p class="auth__info" role="status"><?= icon('info') ?><?= h($f['text']) ?></p><?php endforeach; ?>
  <?php if ($error): ?><p class="auth__error" role="alert"><?= icon('alert') ?><?= h($error) ?></p><?php endif; ?>
  <form method="post" class="stack" action="<?= h(admin_url()) ?>">
    <?= csrf_field() ?><input type="hidden" name="action" value="login"><input type="hidden" name="next" value="<?= h($next) ?>">
    <label class="field"><span>Identifiant</span><input name="username" value="<?= h($username) ?>" required autocomplete="username" autocapitalize="none" <?= $username === '' ? 'autofocus' : '' ?>></label>
    <label class="field"><span>Mot de passe</span><input type="password" name="password" required autocomplete="current-password" <?= $username !== '' ? 'autofocus' : '' ?>></label>
    <button class="btn btn--primary btn--block" type="submit">Se connecter</button>
  </form>
  <details class="auth__help">
    <summary>Mot de passe oublié&nbsp;?</summary>
    <p>Par FTP, déposez un fichier vide nommé <code>reset-admin</code> dans le dossier <code>data/</code> du site, puis rechargez cette page : vous pourrez créer un nouveau compte. Les messages et les statistiques sont conservés.</p>
  </details>
  <a class="auth__back" href="<?= h(url('')) ?>"><?= icon('back') ?>Retour au site</a>
</div>
