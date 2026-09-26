<section class="section method" id="methode" aria-label="Les étapes">
  <div class="wrap">
    <ol class="steps">
      <?php foreach (method_steps() as $n => $step): ?>
        <li class="step" style="--c:<?= h($step['color']) ?>;--i:<?= $n ?>" data-reveal>
          <span class="step__num"><?= $n + 1 ?></span>
          <h3 class="step__title"><?= h($step['title']) ?></h3>
          <p><?= h($step['text']) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
