<section class="section faq" id="faq" aria-labelledby="faq-title">
  <div class="wrap faq__grid">
    <header class="faq__intro" data-reveal>
      <p class="kicker">Questions</p>
      <h2 class="h2" id="faq-title">On me demande souvent</h2>
      <p class="lead">Une autre question&nbsp;? Posez-la directement dans le formulaire, je vous réponds.</p>
      <a class="pill pill--ink" href="<?= h(url('contact')) ?>" data-track="cta" data-label="faq-contact">Poser ma question <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
    </header>
    <div class="faq__list">
      <?php foreach ($faq as $n => $item): ?>
        <details class="qa" data-track-open="faq" data-label="<?= $n + 1 ?>">
          <summary><span><?= h($item['q']) ?></span><svg aria-hidden="true"><use href="#i-plus"/></svg></summary>
          <div class="qa__a"><p><?= h($item['a']) ?></p></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
