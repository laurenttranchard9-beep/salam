<?php
/** @var array $page */
$phone = trim((string) setting('public_phone', ''));
$phoneHref = preg_replace('/[^0-9+]/', '', $phone);
$current = $page['nav'] ?? (!empty($page['isHome']) ? 'accueil' : '');
$pages = [
    'formules' => 'Formules',
    'demo' => 'La démo',
    'realisations' => 'Réalisations',
    'menus' => 'Menus imprimés',
    'methode' => 'Méthode',
];
$menu = ['accueil' => ['Accueil', '']] + array_map(static fn (string $label): array => [$label, ''], $pages) + ['contact' => ['Contact', '']];
?>
<header class="topbar" data-topbar>
  <a class="logo" href="<?= h(url('')) ?>" aria-label="<?= h(site_name()) ?>, retour à l'accueil">
    <span class="logo__word"><?= h(mb_strtolower(site_name())) ?></span><svg class="logo__spark" aria-hidden="true"><use href="#i-spark"/></svg>
  </a>
  <nav class="topnav" aria-label="Pages">
    <?php foreach ($pages as $slug => $label): ?>
      <a class="topnav__link<?= $current === $slug ? ' is-active' : '' ?>" href="<?= h(url($slug)) ?>"<?= $current === $slug ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="topbar__actions">
    <a class="pill pill--glass topbar__cta<?= $current === 'contact' ? ' is-active' : '' ?>" href="<?= h(url('contact')) ?>" data-magnetic data-track="cta" data-label="header-contact">Contactez-moi</a>
    <?php if ($phoneHref !== ''): ?>
      <a class="round" href="tel:<?= h($phoneHref) ?>" aria-label="Appeler le <?= h($phone) ?>" data-track="tel" data-label="header"><svg aria-hidden="true"><use href="#i-phone"/></svg></a>
    <?php endif; ?>
    <button class="round topbar__burger" type="button" data-menu-open aria-haspopup="dialog" aria-controls="menu">
      <svg aria-hidden="true"><use href="#i-menu"/></svg><span class="sr-only">Ouvrir le menu</span>
    </button>
  </div>
</header>

<dialog class="menu" id="menu" aria-label="Menu" data-lenis-prevent>
  <div class="menu__panel">
    <div class="menu__head">
      <span class="logo logo--ink"><span class="logo__word"><?= h(mb_strtolower(site_name())) ?></span><svg class="logo__spark" aria-hidden="true"><use href="#i-spark"/></svg></span>
      <button class="round round--ink" type="button" data-menu-close><svg aria-hidden="true"><use href="#i-close"/></svg><span class="sr-only">Fermer le menu</span></button>
    </div>
    <nav class="menu__nav" aria-label="Pages du site">
      <ol>
        <?php $i = 0; foreach ($menu as $slug => [$label]): $i++; ?>
          <li style="--i:<?= $i ?>"><a href="<?= h(url($slug === 'accueil' ? '' : $slug)) ?>"<?= $current === $slug ? ' aria-current="page" class="is-active"' : '' ?>><span class="menu__num"><?= sprintf('%02d', $i) ?></span><?= h($label) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <div class="menu__foot">
      <a class="pill pill--ink" href="<?= h(url('contact')) ?>" data-track="cta" data-label="menu-devis">Demander un devis <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
      <?php if ($phoneHref !== ''): ?>
        <a class="menu__tel" href="tel:<?= h($phoneHref) ?>" data-track="tel" data-label="menu"><svg aria-hidden="true"><use href="#i-phone"/></svg><?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</dialog>
