<?php
/**
 * Grand en-tête coloré des pages intérieures.
 * @var array $hero kicker, title, lead, theme (sun|basil|ember|plum|tomato|ink), word (mot qui défile derrière)
 */
$word = mb_strtoupper($hero['word'] ?? $hero['title']);
?>
<header class="phero phero--<?= h($hero['theme'] ?? 'tomato') ?><?= !empty($hero['compact']) ? ' phero--compact' : '' ?>" data-page-hero>
  <div class="phero__marquee" aria-hidden="true">
    <?php for ($row = 0; $row < 2; $row++): ?>
      <div class="phero__row"><span><?= str_repeat(h($word) . ' · ', 6) ?></span><span><?= str_repeat(h($word) . ' · ', 6) ?></span></div>
    <?php endfor; ?>
  </div>
  <div class="wrap phero__inner">
    <p class="kicker phero__kicker"><?= h($hero['kicker']) ?></p>
    <h1 class="phero__title"><?= split_words($hero['title']) ?></h1>
    <?php if (!empty($hero['lead'])): ?><p class="phero__lead"><?= h($hero['lead']) ?></p><?php endif; ?>
  </div>
  <a class="scroll-cue" href="#suite" aria-label="Voir la suite"><span class="scroll-cue__mouse" aria-hidden="true"><i></i></span><span class="scroll-cue__label">Faites défiler</span></a>
</header>
<div id="suite"></div>
