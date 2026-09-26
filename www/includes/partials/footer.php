<?php
/** @var array $page */
$home = !empty($page['isHome']) ? '' : url('');
?>
<footer class="footer">
  <div class="wrap">
    <div class="footer__top">
      <p class="footer__cta">Votre carte mérite mieux qu'un PDF.</p>
      <a class="pill pill--white" href="<?= h($home) ?>#contact" data-track="cta" data-label="footer-devis">Demander un devis <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
    </div>
    <p class="footer__word" aria-hidden="true"><?= h(mb_strtolower(site_name())) ?></p>
    <div class="footer__bottom">
      <p>© <?= date('Y') ?> <?= h(site_name()) ?> · Sites et cartes en ligne pour restaurants, par <?= h((string) config('owner_name')) ?></p>
      <nav aria-label="Informations">
        <a href="<?= h(url('mentions-legales')) ?>">Mentions légales</a>
        <a href="<?= h(url('confidentialite')) ?>">Confidentialité</a>
        <button type="button" class="linklike" data-cookie-manage>Gérer les cookies</button>
        <a href="<?= h(url('admin/')) ?>" rel="nofollow">Espace gestion</a>
      </nav>
    </div>
  </div>
</footer>

<div class="cookie" id="cookie-banner" role="dialog" aria-live="polite" aria-labelledby="cookie-title" aria-describedby="cookie-text" tabindex="-1" hidden>
  <svg class="cookie__art" viewBox="0 0 64 64" aria-hidden="true">
    <path d="M32 5a27 27 0 1 0 26.6 31.4 7 7 0 0 1-8.1-6.6 7 7 0 0 1-7.4-8.3A7 7 0 0 1 36.6 14 7 7 0 0 1 32 5Z" fill="#e9a55b"/>
    <path d="M32 5a27 27 0 1 0 26.6 31.4 7 7 0 0 1-8.1-6.6 7 7 0 0 1-7.4-8.3A7 7 0 0 1 36.6 14 7 7 0 0 1 32 5Z" fill="none" stroke="#24130d" stroke-width="3" stroke-linejoin="round"/>
    <circle cx="21" cy="24" r="3.6" fill="#5a2e17"/><circle cx="18" cy="39" r="3" fill="#5a2e17"/><circle cx="31" cy="47" r="3.6" fill="#5a2e17"/><circle cx="33" cy="32" r="2.6" fill="#5a2e17"/><circle cx="44" cy="42" r="3" fill="#5a2e17"/>
  </svg>
  <div class="cookie__body">
    <p class="cookie__title" id="cookie-title">Un petit cookie ?</p>
    <p class="cookie__text" id="cookie-text">Avec votre accord, je mesure la fréquentation du site : pages vues, durée de visite, type d'appareil. Pas de publicité, rien n'est revendu, et le cookie expire au bout de 13 mois. <a href="<?= h(url('confidentialite')) ?>">En savoir plus</a></p>
    <div class="cookie__actions">
      <button type="button" class="cookie__btn" data-consent="non">Refuser</button>
      <button type="button" class="cookie__btn" data-consent="oui">Accepter</button>
    </div>
  </div>
</div>
