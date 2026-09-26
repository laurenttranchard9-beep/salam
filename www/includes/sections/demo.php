<?php $first = array_key_first($cuisines); [$d1, $d2, $d3] = $cuisines[$first]['colors']; ?>
<section class="demo<?= !empty($demoAsPage) ? ' demo--page' : '' ?>" id="demo" aria-labelledby="demo-title" data-demo style="--d1:<?= h($d1) ?>;--d2:<?= h($d2) ?>;--d3:<?= h($d3) ?>">
  <div class="demo__marquee" aria-hidden="true">
    <?php for ($row = 0; $row < 3; $row++): ?>
      <div class="demo__row"><span data-demo-word><?= str_repeat(h($cuisines[$first]['label']) . ' ', 8) ?></span><span data-demo-word><?= str_repeat(h($cuisines[$first]['label']) . ' ', 8) ?></span></div>
    <?php endfor; ?>
  </div>
  <div class="wrap demo__grid">
    <div class="demo__intro" data-reveal>
      <p class="kicker kicker--light">La démo</p>
      <h1 class="h2" id="demo-title">Goûtez avant de commander</h1>
      <p class="demo__lead">Choisissez une cuisine, jouez avec la carte, puis passez côté gestion&nbsp;: changez un prix ou déclarez un plat épuisé, et regardez la carte se mettre à jour.</p>
      <div class="flavors" role="radiogroup" aria-label="Type de cuisine" data-demo-flavors>
        <?php foreach ($cuisines as $key => $cuisine): ?>
          <button type="button" role="radio" class="flavor" data-cuisine="<?= h($key) ?>" aria-checked="<?= $key === $first ? 'true' : 'false' ?>" style="--f1:<?= h($cuisine['colors'][0]) ?>;--f2:<?= h($cuisine['colors'][1]) ?>"><?= h($cuisine['label']) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="demo__device">
      <div class="seg" role="tablist" aria-label="Vue de la démo">
        <button type="button" role="tab" id="tab-client" aria-selected="true" aria-controls="demo-screen" data-demo-tab="client">Côté client</button>
        <button type="button" role="tab" id="tab-gestion" aria-selected="false" aria-controls="demo-screen" data-demo-tab="gestion" tabindex="-1">Côté gestion</button>
        <button type="button" role="tab" id="tab-stats" aria-selected="false" aria-controls="demo-screen" data-demo-tab="stats" tabindex="-1">Statistiques</button>
      </div>
      <div class="phone phone--demo" data-tab="client">
        <div class="phone__screen">
          <div class="phone__status" aria-hidden="true"><span>9:41</span><span class="phone__icons"><i></i><i></i><i></i></span></div>
          <div class="phone__view demo-app" data-lenis-prevent id="demo-screen" role="tabpanel" aria-labelledby="tab-client" data-demo-screen tabindex="0">
            <div class="app">
              <div class="app__head">
                <p class="app__name"><?= h($cuisines[$first]['name']) ?></p>
                <p class="app__line"><?= h($cuisines[$first]['line']) ?></p>
              </div>
              <div class="app__list">
                <?php foreach ($cuisines[$first]['menu'] as $category => $dishes): ?>
                  <p class="app__cat"><?= h($category) ?></p>
                  <?php foreach ($dishes as [$name, $desc, $price]): ?>
                    <div class="dish"><div><p class="dish__name"><?= h($name) ?></p><p class="dish__desc"><?= h($desc) ?></p></div><p class="dish__price"><?= h($euro($price)) ?></p></div>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p class="demo__note">Restaurants et chiffres fictifs, pour la démonstration.</p>
    </div>

    <div class="demo__points" data-demo-points aria-live="polite">
      <ul class="demo__list" data-points="client">
        <li><strong>Recherche instantanée.</strong> Le client tape « piquant » ou « saumon », la carte se filtre à chaque lettre.</li>
        <li><strong>Étiquettes.</strong> Végé, épicé, fait maison&nbsp;: un geste pour ne voir que ce qui l'intéresse.</li>
        <li><strong>Ma liste.</strong> Il note ses plats avec le bouton +, puis vous appelle pour commander.</li>
      </ul>
      <ul class="demo__list" data-points="gestion" hidden>
        <li><strong>Un prix à changer&nbsp;?</strong> Modifiez-le, c'est enregistré. Retournez côté client pour vérifier.</li>
        <li><strong>Un plat en rupture&nbsp;?</strong> Un interrupteur, et il s'affiche « épuisé ».</li>
        <li><strong>Depuis votre téléphone,</strong> entre deux services, sans appeler personne.</li>
      </ul>
      <ul class="demo__list" data-points="stats" hidden>
        <li><strong>Qui regarde votre carte&nbsp;?</strong> Les jours, les heures, les téléphones ou les ordinateurs.</li>
        <li><strong>Respectueux.</strong> Seuls les visiteurs qui acceptent le cookie sont comptés en détail.</li>
        <li><strong>Utile.</strong> Vous voyez l'effet de vos QR codes et de vos publications.</li>
      </ul>
    </div>
  </div>
  <script type="application/json" id="demo-data"><?= json_encode($cuisines, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</section>
