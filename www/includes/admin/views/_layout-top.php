<?php
/** @var string $current  @var bool $shell  @var array $flashes  @var int $unread */
$titles = ['tableau' => 'Tableau de bord', 'messages' => 'Messages', 'message' => 'Message', 'stats' => 'Statistiques', 'reglages' => 'Réglages', 'login' => 'Connexion', 'setup' => 'Création du compte', 'introuvable' => 'Page introuvable'];
$nav = [
    'tableau' => ['Tableau de bord', 'home'],
    'messages' => ['Messages', 'inbox'],
    'stats' => ['Statistiques', 'chart'],
    'reglages' => ['Réglages', 'settings'],
];
$active = $current === 'message' ? 'messages' : $current;
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= h(($titles[$current] ?? 'Gestion') . ($unread ? ' (' . $unread . ')' : '') . ' · Gestion ' . site_name()) ?></title>
<link rel="icon" href="<?= h(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= h(asset('css/admin.css')) ?>">
<script src="<?= h(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="<?= $shell ? 'shell' : 'auth' ?>">
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
  <symbol id="a-home" viewBox="0 0 24 24"><path d="M4 11 12 4.5 20 11M6 9.5V19a1 1 0 0 0 1 1h3.5v-5h3v5H17a1 1 0 0 0 1-1V9.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-inbox" viewBox="0 0 24 24"><path d="M4 13.5 6.3 6a1.5 1.5 0 0 1 1.4-1h8.6a1.5 1.5 0 0 1 1.4 1L20 13.5V18a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4 13.5h4.5l1 2h5l1-2H20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="a-chart" viewBox="0 0 24 24"><path d="M4 20h16M7 16.5V11M12 16.5V6.5M17 16.5v-3.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></symbol>
  <symbol id="a-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.8v2.4M12 18.8v2.4M4.5 7.5l2 1.2M17.5 15.3l2 1.2M4.5 16.5l2-1.2M17.5 8.7l2-1.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="1.8"/></symbol>
  <symbol id="a-external" viewBox="0 0 24 24"><path d="M7 17 17 7M8 7h9v9" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-logout" viewBox="0 0 24 24"><path d="M14 5h3.5A1.5 1.5 0 0 1 19 6.5v11a1.5 1.5 0 0 1-1.5 1.5H14M10 16l-4-4 4-4M6 12h9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-phone" viewBox="0 0 24 24"><path d="M6.6 3.5h2.2c.5 0 .9.3 1 .8l.8 3.4c.1.4 0 .9-.4 1.1l-1.7 1.2a12 12 0 0 0 5.5 5.5l1.2-1.7c.3-.4.7-.5 1.1-.4l3.4.8c.5.1.8.5.8 1v2.2c0 1.2-1 2.1-2.2 2A16.5 16.5 0 0 1 4.6 5.7c-.1-1.2.8-2.2 2-2.2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="a-trash" viewBox="0 0 24 24"><path d="M5 7h14M10 7V5h4v2M7 7l.8 12a1.5 1.5 0 0 0 1.5 1.4h5.4a1.5 1.5 0 0 0 1.5-1.4L17 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-download" viewBox="0 0 24 24"><path d="M12 4v11M7 10.5l5 5 5-5M5 20h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></symbol>
  <symbol id="a-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-back" viewBox="0 0 24 24"><path d="M19 12H5M11 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-next" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="a-copy" viewBox="0 0 24 24"><rect x="8" y="8" width="12" height="12" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 8V6.5A2.5 2.5 0 0 0 13.5 4h-7A2.5 2.5 0 0 0 4 6.5v7A2.5 2.5 0 0 0 6.5 16H8" fill="none" stroke="currentColor" stroke-width="1.8"/></symbol>
  <symbol id="a-alert" viewBox="0 0 24 24"><path d="M12 4 21 19.5H3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10v4.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><circle cx="12" cy="17" r="1.1" fill="currentColor"/></symbol>
  <symbol id="a-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 11v5.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><circle cx="12" cy="7.8" r="1.1" fill="currentColor"/></symbol>
  <symbol id="a-spark" viewBox="0 0 24 24"><path d="M12 1.5c.6 5.6 4.9 9.9 10.5 10.5-5.6.6-9.9 4.9-10.5 10.5C11.4 16.9 7.1 12.6 1.5 12 7.1 11.4 11.4 7.1 12 1.5Z" fill="currentColor"/></symbol>
  <symbol id="a-user" viewBox="0 0 24 24"><circle cx="12" cy="8.5" r="3.8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20c1.2-3.6 4-5.5 7.5-5.5s6.3 1.9 7.5 5.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
</svg>
<?php if ($shell): ?>
<a class="skip" href="#main">Aller au contenu</a>
<div class="app">
  <aside class="side">
    <a class="brand" href="<?= h(admin_url()) ?>">
      <span class="brand__word"><?= h(mb_strtolower(site_name())) ?></span><?= icon('spark', 'brand__spark') ?>
      <span class="brand__sub">Gestion</span>
    </a>
    <nav class="nav" aria-label="Espace gestion">
      <?php foreach ($nav as $key => [$label, $ic]): ?>
        <a class="nav__link<?= $active === $key ? ' is-active' : '' ?>" href="<?= h(admin_url($key === 'tableau' ? '' : $key)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
          <?= icon($ic) ?><span><?= h($label) ?></span>
          <?php if ($key === 'messages' && $unread > 0): ?><span class="count" aria-label="<?= $unread ?> non lu<?= $unread > 1 ? 's' : '' ?>"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side__foot">
      <a class="nav__link" href="<?= h(url('')) ?>" target="_blank" rel="noopener"><?= icon('external') ?><span>Voir le site</span></a>
      <form method="post" action="<?= h(admin_url()) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="logout">
        <button class="nav__link" type="submit"><?= icon('logout') ?><span>Déconnexion</span></button>
      </form>
    </div>
  </aside>
  <main class="main" id="main" tabindex="-1">
    <?php foreach ($flashes as $f): ?>
      <div class="flash flash--<?= h($f['type']) ?>" role="status"><?= icon($f['type'] === 'error' ? 'alert' : ($f['type'] === 'ok' ? 'check' : 'info')) ?><span><?= h($f['text']) ?></span><button type="button" class="flash__close" data-dismiss aria-label="Fermer">×</button></div>
    <?php endforeach; ?>
<?php else: ?>
<main class="auth__main">
<?php endif; ?>
