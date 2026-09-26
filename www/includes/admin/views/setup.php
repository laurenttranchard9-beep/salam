<?php /** @var ?string $error @var string $username @var bool $reset */ ?>
<div class="auth__card">
  <p class="auth__brand"><?= h(mb_strtolower(site_name())) ?><?= icon('spark', 'brand__spark') ?></p>
  <h1 class="auth__title"><?= $reset ? 'Nouveau compte' : 'Bienvenue !' ?></h1>
  <p class="auth__lead"><?= $reset
      ? "Une réinitialisation a été demandée. Créez le nouveau compte : l'ancien sera remplacé, les messages et les statistiques sont conservés."
      : "Créez le compte qui gérera le site : vous seul pourrez lire les messages et les statistiques." ?></p>
  <?php if ($error): ?><p class="auth__error" role="alert"><?= icon('alert') ?><?= h($error) ?></p><?php endif; ?>
  <form method="post" class="stack">
    <?= csrf_field() ?><input type="hidden" name="action" value="setup">
    <label class="field"><span>Identifiant</span><input name="username" value="<?= h($username) ?>" required minlength="3" maxlength="60" autocomplete="username" autocapitalize="none" autofocus></label>
    <label class="field"><span>Mot de passe <small>(<?= ADMIN_MIN_PASSWORD ?> caractères minimum)</small></span><input type="password" name="password" required minlength="<?= ADMIN_MIN_PASSWORD ?>" autocomplete="new-password"></label>
    <label class="field"><span>Confirmer le mot de passe</span><input type="password" name="password_confirm" required minlength="<?= ADMIN_MIN_PASSWORD ?>" autocomplete="new-password"></label>
    <button class="btn btn--primary btn--block" type="submit">Créer mon compte</button>
  </form>
  <a class="auth__back" href="<?= h(url('')) ?>"><?= icon('back') ?>Retour au site</a>
</div>
