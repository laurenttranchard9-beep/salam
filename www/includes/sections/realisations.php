<section class="section projects" id="realisations" aria-label="Les réalisations">
  <div class="wrap">
    <?php foreach ($projects as $index => $project): ?>
      <article class="project<?= $index % 2 ? ' project--flip' : '' ?>" id="realisation-<?= h($project['key']) ?>" style="--p1:<?= h($project['colors'][0]) ?>;--p2:<?= h($project['colors'][1]) ?>" data-reveal>
        <div class="project__visual">
          <div class="browser">
            <div class="browser__bar" aria-hidden="true"><i></i><i></i><i></i><span><?= h($project['domain']) ?></span></div>
            <img src="<?= h(asset('img/' . $project['desktop'])) ?>" alt="Le site <?= h($project['name']) ?> sur ordinateur" width="1440" height="900" loading="lazy" decoding="async">
          </div>
          <?= phone($project['mobile'], 'Le site ' . $project['name'] . ' sur téléphone', ['class' => 'phone--project', 'scroll' => true, 'statusBg' => $project['colors'][0]]) ?>
        </div>
        <div class="project__text">
          <p class="project__offer">Formule <?= h($offers[$project['offer']]['name']) ?></p>
          <h3 class="project__name"><?= h($project['name']) ?></h3>
          <p class="project__kind"><?= h($project['kind']) ?></p>
          <ul class="ticks ticks--light">
            <?php foreach ($project['points'] as $point): ?>
              <li><svg aria-hidden="true"><use href="#i-check"/></svg><?= h($point) ?></li>
            <?php endforeach; ?>
          </ul>
          <a class="pill pill--accent" href="<?= h($project['url']) ?>" target="_blank" rel="noopener" data-track="realisation" data-label="<?= h($project['key']) ?>-visite">Voir le site <svg aria-hidden="true"><use href="#i-external"/></svg><span class="sr-only">(nouvel onglet)</span></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
