<?php
/** @var array $page */
$home = !empty($page['isHome']) ? '' : url('');
$phone = trim((string) setting('public_phone', ''));
$phoneHref = preg_replace('/[^0-9+]/', '', $phone);
$links = [
    'gamme' => 'La gamme',
    'demo' => 'La démo',
    'realisations' => 'Réalisations',
    'methode' => 'La fabrication',
    'faq' => 'Questions',
    'contact' => 'Contact',
];
?>
<header class="topbar" data-topbar>
  <a class="logo" href="<?= h($home) ?>#accueil" aria-label="<?= h(site_name()) ?>, retour à l'accueil">
    <span class="logo__word"><?= h(mb_strtolower(site_name())) ?></span><svg class="logo__spark" aria-hidden="true"><use href="#i-spark"/></svg>
  </a>
  <div class="topbar__actions">
    <a class="pill pill--glass topbar__cta" href="<?= h($home) ?>#contact" data-track="cta" data-label="header-contact">Contactez-moi</a>
    <?php if ($phoneHref !== ''): ?>
      <a class="round" href="tel:<?= h($phoneHref) ?>" aria-label="Appeler le <?= h($phone) ?>" data-track="tel" data-label="header"><svg aria-hidden="true"><use href="#i-phone"/></svg></a>
    <?php else: ?>
      <a class="round" href="<?= h($home) ?>#demo" aria-label="Essayer la démo" title="Essayer la démo"><svg aria-hidden="true"><use href="#i-star"/></svg></a>
    <?php endif; ?>
    <button class="round" type="button" data-menu-open aria-haspopup="dialog" aria-controls="menu">
      <svg aria-hidden="true"><use href="#i-menu"/></svg><span class="sr-only">Ouvrir le menu</span>
    </button>
  </div>
</header>

<dialog class="menu" id="menu" aria-label="Menu">
  <div class="menu__panel">
    <div class="menu__head">
      <span class="logo logo--ink"><span class="logo__word"><?= h(mb_strtolower(site_name())) ?></span><svg class="logo__spark" aria-hidden="true"><use href="#i-spark"/></svg></span>
      <button class="round round--ink" type="button" data-menu-close><svg aria-hidden="true"><use href="#i-close"/></svg><span class="sr-only">Fermer le menu</span></button>
    </div>
    <nav class="menu__nav" aria-label="Sections">
      <ol>
        <?php $i = 0; foreach ($links as $id => $label): $i++; ?>
          <li style="--i:<?= $i ?>"><a href="<?= h($home) ?>#<?= h($id) ?>" data-menu-link><span class="menu__num"><?= sprintf('%02d', $i) ?></span><?= h($label) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <div class="menu__foot">
      <a class="pill pill--ink" href="<?= h($home) ?>#contact" data-menu-link data-track="cta" data-label="menu-devis">Demander un devis <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
      <?php if ($phoneHref !== ''): ?>
        <a class="menu__tel" href="tel:<?= h($phoneHref) ?>" data-track="tel" data-label="menu"><svg aria-hidden="true"><use href="#i-phone"/></svg><?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</dialog>
