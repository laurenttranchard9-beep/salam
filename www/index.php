<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/content.php';
require __DIR__ . '/includes/partials/offer-art.php';
require __DIR__ . '/includes/partials/phone.php';

$offers = offers();
$cuisines = demo_cuisines();
$projects = projects();
$faq = faq();
$phone = trim((string) setting('public_phone', ''));
$publicEmail = trim((string) setting('public_email', ''));
$zone = trim((string) setting('zone', ''));
$sent = ($_GET['envoi'] ?? '') === 'ok';
$formError = (string) ($_GET['erreur'] ?? '');

$euro = static fn (int $cents): string => number_format($cents / 100, 2, ',', ' ') . "\u{00A0}€";

$jsonld = [
    '@context' => 'https://schema.org',
    '@graph' => [
        array_filter([
            '@type' => 'ProfessionalService',
            'name' => site_name(),
            'description' => 'Création de cartes en ligne et de sites pour restaurants : carte sur mobile, QR code, espace gestion et statistiques.',
            'url' => absolute_url(),
            'image' => absolute_url('assets/img/og.jpg'),
            'telephone' => $phone ?: null,
            'email' => $publicEmail ?: null,
            'areaServed' => $zone ?: null,
            'founder' => ['@type' => 'Person', 'name' => (string) config('owner_name')],
        ]),
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ], $faq),
        ],
    ],
];

$page = [
    'title' => site_name() . ' · Cartes en ligne et sites pour restaurants',
    'description' => "Je crée la carte en ligne et le site de votre restaurant : lisible sur tous les téléphones, modifiable par vous en quelques secondes, prête pour le QR code de vos tables.",
    'path' => '',
    'isHome' => true,
    'bodyClass' => 'home',
    'scripts' => ['js/site.js'],
    'jsonld' => $jsonld,
];

send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
?>
<main id="contenu">

  <!-- ============ HERO ============ -->
  <section class="hero" id="accueil" data-hero>
    <a class="hero-phone hero-phone--left" href="#realisation-asb" data-track="realisation" data-label="hero-asb">
      <div class="hero-phone__float">
        <?= phone('asb-mobile.webp', "La carte en ligne du restaurant Aux Saveurs Braisées, sur téléphone", ['loading' => 'eager', 'statusBg' => '#1d1512']) ?>
        <span class="sticker sticker--qr" aria-hidden="true">
          <svg viewBox="0 0 42 42"><?php
            $qr = ['1111111010111', '1000001001001', '1011101011101', '1011101000101', '1011101011101', '1000001010001', '1111111010111', '0000000011000', '1101011101011', '0110100100110', '1011011011101', '0100110110010', '1101101011011'];
            foreach ($qr as $r => $line) {
                foreach (str_split($line) as $c => $on) {
                    if ($on === '1') {
                        echo '<rect x="' . (1 + $c * 3) . '" y="' . (1 + $r * 3) . '" width="3" height="3"/>';
                    }
                }
            }
          ?></svg>
          <span>Scannez,<br>c'est servi</span>
        </span>
      </div>
      <span class="hero-phone__tag"><small>Réalisation</small>Aux Saveurs Braisées</span>
    </a>
    <a class="hero-phone hero-phone--right" href="#realisation-fdo" data-track="realisation" data-label="hero-fdo">
      <div class="hero-phone__float">
        <?= phone('fdo-mobile.webp', "Le site du restaurant La Fleur d'Or, sur téléphone", ['loading' => 'eager', 'statusBg' => '#3a2319']) ?>
      </div>
      <span class="hero-phone__tag"><small>Réalisation</small>La Fleur d'Or</span>
    </a>

    <div class="hero__content">
      <p class="hero__kicker">Cartes et sites pour restaurants</p>
      <h1 class="hero__title">
        <span class="hero__big">Des cartes</span>
        <span class="hero__small">qui</span>
        <span class="hero__big">donnent faim</span>
      </h1>
      <p class="hero__lead">Je crée la carte en ligne et le site de votre restaurant&nbsp;: lisible sur tous les téléphones, modifiable par vous en quelques secondes, prête pour le QR code de vos tables.</p>
      <div class="hero__actions">
        <a class="pill pill--white pill--halo" href="#gamme" data-track="cta" data-label="hero-gamme"><svg aria-hidden="true"><use href="#i-home"/></svg>Voir la gamme</a>
      </div>
    </div>
  </section>

  <!-- ============ RUBANS ============ -->
  <div class="ribbons" aria-hidden="true">
    <?php
    $ribbonA = ['Pizzeria', 'Brasserie', 'Sushi', 'Crêperie', 'Food truck', 'Burger', 'Bistrot', 'Boulangerie', 'Tapas', 'Traiteur'];
    $ribbonB = ['Carte en ligne', 'QR code', 'Espace gestion', 'Statistiques', 'Recherche de plat', 'Ma liste', 'Horaires en direct'];
    foreach (['a' => $ribbonA, 'b' => $ribbonB] as $name => $words): ?>
      <div class="ribbon ribbon--<?= $name ?>">
        <div class="ribbon__track">
          <?php for ($copy = 0; $copy < 2; $copy++): ?>
            <span class="ribbon__group">
              <?php foreach ($words as $word): ?><span><?= h($word) ?></span><svg><use href="#i-spark"/></svg><?php endforeach; ?>
            </span>
          <?php endfor; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ============ LA GAMME ============ -->
  <section class="section gamme" id="gamme" aria-labelledby="gamme-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="kicker">La gamme</p>
        <h2 class="h2" id="gamme-title">Choisissez votre formule</h2>
        <p class="lead">Du menu consultable au site complet de votre restaurant. Chaque formule se goûte en détail&nbsp;: cliquez sur celle qui vous tente.</p>
      </header>
    </div>
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
      <dialog class="sheet" id="offre-<?= h($key) ?>" data-offer="<?= h($key) ?>" aria-labelledby="offre-<?= h($key) ?>-titre" style="--c1:<?= h($c1) ?>;--c2:<?= h($c2) ?>;--ct:<?= h($ct) ?>">
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
              <a class="pill pill--white" href="#contact" data-choose-offer="<?= h($key) ?>" data-track="offre-choisie" data-label="<?= h($key) ?>">Choisir cette formule <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
              <span class="sheet__price"><?= h(offer_price($key)) ?></span>
            </div>
          </div>
        </div>
      </dialog>
    <?php endforeach; ?>
  </section>

  <!-- ============ DÉMO ============ -->
  <?php $first = array_key_first($cuisines); [$d1, $d2, $d3] = $cuisines[$first]['colors']; ?>
  <section class="demo" id="demo" aria-labelledby="demo-title" data-demo style="--d1:<?= h($d1) ?>;--d2:<?= h($d2) ?>;--d3:<?= h($d3) ?>">
    <div class="demo__marquee" aria-hidden="true">
      <?php for ($row = 0; $row < 3; $row++): ?>
        <div class="demo__row"><span data-demo-word><?= str_repeat(h($cuisines[$first]['label']) . ' ', 8) ?></span><span data-demo-word><?= str_repeat(h($cuisines[$first]['label']) . ' ', 8) ?></span></div>
      <?php endfor; ?>
    </div>
    <div class="wrap demo__grid">
      <div class="demo__intro" data-reveal>
        <p class="kicker kicker--light">La démo</p>
        <h2 class="h2" id="demo-title">Goûtez avant de commander</h2>
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
            <div class="phone__view demo-app" id="demo-screen" role="tabpanel" aria-labelledby="tab-client" data-demo-screen tabindex="0">
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

  <!-- ============ RÉALISATIONS ============ -->
  <section class="section projects" id="realisations" aria-labelledby="realisations-title">
    <div class="wrap">
      <header class="section-head section-head--light" data-reveal>
        <p class="kicker kicker--light">Réalisations</p>
        <h2 class="h2" id="realisations-title">Déjà en service</h2>
        <p class="lead">Deux restaurants, deux besoins différents. Ouvrez leurs sites sur votre téléphone&nbsp;: c'est là qu'ils sont les plus beaux.</p>
      </header>

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

  <!-- ============ FABRICATION ============ -->
  <section class="section method" id="methode" aria-labelledby="methode-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="kicker">La fabrication</p>
        <h2 class="h2" id="methode-title">De l'ardoise à l'écran</h2>
        <p class="lead">Vous n'avez rien de technique à faire. Vous m'envoyez votre carte, je m'occupe du reste.</p>
      </header>
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

  <!-- ============ FAQ ============ -->
  <section class="section faq" id="faq" aria-labelledby="faq-title">
    <div class="wrap faq__grid">
      <header class="faq__intro" data-reveal>
        <p class="kicker">Questions</p>
        <h2 class="h2" id="faq-title">On me demande souvent</h2>
        <p class="lead">Une autre question&nbsp;? Posez-la directement dans le formulaire, je vous réponds.</p>
        <a class="pill pill--ink" href="#contact" data-track="cta" data-label="faq-contact">Poser ma question <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
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

  <!-- ============ CONTACT ============ -->
  <section class="contact" id="contact" aria-labelledby="contact-title">
    <div class="wrap contact__grid">
      <div class="contact__intro" data-reveal>
        <p class="kicker kicker--light">Contact</p>
        <h2 class="h2" id="contact-title">Parlons de votre carte</h2>
        <p class="contact__lead">Racontez-moi votre projet en quelques lignes. Je vous réponds par email, ou je vous rappelle si vous préférez.</p>
        <ul class="facts">
          <li><svg aria-hidden="true"><use href="#i-user"/></svg><?= h((string) config('owner_name')) ?>, créateur des sites</li>
          <?php if ($phone !== ''): ?>
            <li><svg aria-hidden="true"><use href="#i-phone"/></svg><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $phone)) ?>" data-track="tel" data-label="contact"><?= h($phone) ?></a></li>
          <?php endif; ?>
          <?php if ($publicEmail !== ''): ?>
            <li><svg aria-hidden="true"><use href="#i-mail"/></svg><a href="mailto:<?= h($publicEmail) ?>" data-track="mail" data-label="contact"><?= h($publicEmail) ?></a></li>
          <?php endif; ?>
          <?php if ($zone !== ''): ?>
            <li><svg aria-hidden="true"><use href="#i-pin"/></svg><?= h($zone) ?></li>
          <?php endif; ?>
        </ul>
        <div class="next">
          <p class="next__title">Et ensuite&nbsp;?</p>
          <ol>
            <li>Je lis votre message et je regarde votre carte actuelle.</li>
            <li>Je vous réponds avec mes questions, ou directement avec un devis.</li>
            <li>Si le devis vous convient, on fixe ensemble la date de mise en ligne.</li>
          </ol>
          <p class="next__note">Votre demande ne vous engage à rien.</p>
        </div>
      </div>

      <div class="contact__card">
        <div class="thanks" data-thanks <?= $sent ? '' : 'hidden' ?> tabindex="-1">
          <svg class="thanks__art" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="28" fill="#34c38f" stroke="#24130d" stroke-width="3"/><path d="m20 33 8 8 16-17" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <h3 class="thanks__title">Merci, c'est bien reçu&nbsp;!</h3>
          <p>Votre demande est arrivée. Je reviens vers vous très vite, par email ou par téléphone.</p>
          <button type="button" class="pill pill--ink" data-thanks-reset>Envoyer un autre message</button>
        </div>

        <form class="form" method="post" action="<?= h(url('api/contact.php')) ?>" data-contact-form <?= $sent ? 'hidden' : '' ?>>
          <input type="hidden" name="token" value="<?= h(form_token()) ?>">
          <div class="form__hp" aria-hidden="true">
            <label for="website">Ne pas remplir ce champ</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <?php if ($formError !== ''): ?>
            <p class="form__alert" role="alert">
              <?= h(match ($formError) {
                  'trop' => 'Trop de messages envoyés depuis votre connexion. Réessayez dans une heure.',
                  'jeton' => 'Le formulaire a expiré. Merci de le renvoyer.',
                  'serveur' => "Le message n'a pas pu être enregistré. Réessayez dans un instant.",
                  default => 'Certains champs sont à corriger : nom, email et message sont obligatoires.',
              }) ?>
            </p>
          <?php endif; ?>

          <div class="form__row">
            <div class="field">
              <label for="f-name">Votre nom <span class="req" aria-hidden="true">*</span></label>
              <input id="f-name" name="name" type="text" autocomplete="name" required maxlength="100" placeholder="Camille Martin">
              <p class="field__error" id="f-name-error" hidden></p>
            </div>
            <div class="field">
              <label for="f-business">Votre établissement</label>
              <input id="f-business" name="business" type="text" autocomplete="organization" maxlength="120" placeholder="Le Petit Bistrot">
            </div>
          </div>
          <div class="form__row">
            <div class="field">
              <label for="f-email">Email <span class="req" aria-hidden="true">*</span></label>
              <input id="f-email" name="email" type="email" autocomplete="email" required maxlength="160" placeholder="vous@exemple.fr" inputmode="email">
              <p class="field__error" id="f-email-error" hidden></p>
            </div>
            <div class="field">
              <label for="f-phone">Téléphone</label>
              <input id="f-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" placeholder="06 12 34 56 78" inputmode="tel">
              <p class="field__error" id="f-phone-error" hidden></p>
            </div>
          </div>
          <div class="form__row">
            <div class="field">
              <label for="f-type">Type d'établissement</label>
              <select id="f-type" name="business_type">
                <option value="">Choisir…</option>
                <?php foreach (business_types() as $type): ?><option><?= h($type) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="f-size">Taille de la carte</label>
              <select id="f-size" name="menu_size">
                <option value="">Choisir…</option>
                <?php foreach (menu_sizes() as $size): ?><option><?= h($size) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>

          <fieldset class="choice">
            <legend>La formule qui vous tente</legend>
            <div class="chips">
              <?php foreach ($offers as $key => $offer): ?>
                <label class="chip" style="--c1:<?= h($offer['colors'][0]) ?>">
                  <input type="radio" name="offer" value="<?= h($key) ?>"><span><?= h($offer['name']) ?></span>
                </label>
              <?php endforeach; ?>
              <label class="chip"><input type="radio" name="offer" value="indecis" checked><span>Je ne sais pas encore</span></label>
            </div>
          </fieldset>

          <fieldset class="choice">
            <legend>Options <span class="field__hint">facultatif</span></legend>
            <div class="chips">
              <?php foreach (contact_options() as $key => $label): ?>
                <label class="chip chip--check"><input type="checkbox" name="options[]" value="<?= h($key) ?>"><span><?= h($label) ?></span></label>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <div class="field">
            <label for="f-message">Votre projet <span class="req" aria-hidden="true">*</span></label>
            <textarea id="f-message" name="message" rows="5" required minlength="10" maxlength="5000" placeholder="Ma carte change deux fois par an, j'aimerais pouvoir modifier les prix moi-même…"></textarea>
            <div class="field__meta"><p class="field__error" id="f-message-error" hidden></p><span class="field__count" data-count>0 / 5000</span></div>
          </div>

          <label class="check">
            <input type="checkbox" name="callback" value="1">
            <span>Je préfère être rappelé·e par téléphone</span>
          </label>

          <div class="form__foot">
            <button class="pill pill--ink pill--lg" type="submit" data-submit>
              <span class="pill__label">Envoyer ma demande</span>
              <svg aria-hidden="true"><use href="#i-arrow-right"/></svg>
            </button>
            <p class="form__legal">Vos informations servent uniquement à répondre à votre demande. <a href="<?= h(url('confidentialite')) ?>">Confidentialité</a></p>
          </div>
          <p class="form__status" role="status" aria-live="polite" data-form-status></p>
        </form>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
