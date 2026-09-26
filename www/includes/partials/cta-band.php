<?php /** Bandeau final : texte qui défile au rythme de la molette, et bouton vers la page contact. */ ?>
<section class="cta-band" aria-labelledby="cta-band-title">
  <div class="cta-band__marquee" aria-hidden="true" data-velocity-marquee>
    <div class="cta-band__track" data-marquee-track>
      <?php for ($copy = 0; $copy < 2; $copy++): ?>
        <span class="cta-band__group"><?php for ($n = 0; $n < 3; $n++): ?><span>Parlons de votre carte</span><svg><use href="#i-spark"/></svg><?php endfor; ?></span>
      <?php endfor; ?>
    </div>
  </div>
  <div class="wrap cta-band__inner" data-reveal>
    <h2 class="cta-band__title" id="cta-band-title">Une carte à mettre en ligne&nbsp;?</h2>
    <p class="cta-band__lead">Décrivez votre restaurant en quelques lignes : je vous réponds avec un devis. Votre demande ne vous engage à rien.</p>
    <a class="pill pill--white pill--halo pill--lg" href="<?= h(url('contact')) ?>" data-magnetic data-track="cta" data-label="bandeau-devis">Demander un devis <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
  </div>
</section>
