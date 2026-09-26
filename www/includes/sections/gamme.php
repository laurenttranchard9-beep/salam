<section class="section gamme" id="gamme" aria-label="Les formules">
  <div class="shelf" data-shelf>
    <?php $i = 0; foreach ($offers as $key => $offer): $i++; [$c1, $c2, $ct] = $offer['colors']; ?>
      <article class="bottle" style="--c1:<?= h($c1) ?>;--c2:<?= h($c2) ?>;--ct:<?= h($ct) ?>;--i:<?= $i ?>" data-reveal>
        <p class="bottle__kicker"><?= h($offer['kicker']) ?></p>
        <h3 class="bottle__name">
          <button type="button" class="bottle__btn" data-offer-open="<?= h($key) ?>" aria-haspopup="dialog"><?= h($offer['name']) ?></button>
        </h3>
        <div class="bottle__art"><?= offer_art($key) ?></div>
        <p class="bottle__pitch"><?= h($offer['pitch']) ?></p>
        <div class="bottle__foot">
          <span class="bottle__price"><?= h(offer_price($key)) ?></span>
          <span class="bottle__more" aria-hidden="true"><svg><use href="#i-plus"/></svg></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php foreach ($offers as $key => $offer): [$c1, $c2, $ct] = $offer['colors']; ?>
    <dialog class="sheet" data-lenis-prevent id="offre-<?= h($key) ?>" data-offer="<?= h($key) ?>" aria-labelledby="offre-<?= h($key) ?>-titre" style="--c1:<?= h($c1) ?>;--c2:<?= h($c2) ?>;--ct:<?= h($ct) ?>">
      <div class="sheet__marquee" aria-hidden="true">
        <?php for ($row = 0; $row < 4; $row++): ?>
          <div class="sheet__row"><span><?= str_repeat(h($offer['name']) . ' · ', 6) ?></span><span><?= str_repeat(h($offer['name']) . ' · ', 6) ?></span></div>
        <?php endfor; ?>
      </div>
      <button class="round sheet__close" type="button" data-sheet-close><svg aria-hidden="true"><use href="#i-close"/></svg><span class="sr-only">Fermer</span></button>
      <div class="sheet__inner">
        <div class="sheet__visual">
          <?php if (!empty($offer['shot'])): ?>
            <?= phone($offer['shot'], 'Exemple de réalisation : ' . $offer['example'], ['class' => 'phone--sheet', 'statusBg' => $key === 'site' ? '#3a2319' : '#1d1512']) ?>
            <p class="sheet__example">Exemple&nbsp;: <?= h($offer['example']) ?></p>
          <?php else: ?>
            <div class="sheet__art"><?= offer_art($key) ?></div>
          <?php endif; ?>
        </div>
        <div class="sheet__text">
          <p class="sheet__kicker"><?= h($offer['kicker']) ?></p>
          <h3 class="sheet__title" id="offre-<?= h($key) ?>-titre"><?= h($offer['name']) ?></h3>
          <p class="sheet__pitch"><?= h($offer['pitch']) ?></p>
          <p class="sheet__desc"><?= h($offer['description']) ?></p>
          <p class="sheet__label">Au menu</p>
          <ul class="ticks">
            <?php foreach ($offer['features'] as $feature): ?>
              <li><svg aria-hidden="true"><use href="#i-check"/></svg><?= h($feature) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="sheet__actions">
            <a class="pill pill--white" href="<?= h(url('contact') . '?formule=' . $key) ?>" data-choose-offer="<?= h($key) ?>" data-track="offre-choisie" data-label="<?= h($key) ?>">Choisir cette formule <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
            <span class="sheet__price"><?= h(offer_price($key)) ?></span>
          </div>
        </div>
      </div>
    </dialog>
  <?php endforeach; ?>
</section>
