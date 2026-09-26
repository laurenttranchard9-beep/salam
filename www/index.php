<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/page-setup.php';

$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'ProfessionalService',
] + array_filter([
    'name' => site_name(),
    'description' => 'Création de cartes en ligne et de sites pour restaurants : carte sur mobile, QR code, espace gestion et statistiques.',
    'url' => absolute_url(),
    'image' => absolute_url('assets/img/og.jpg'),
    'telephone' => $phone ?: null,
    'email' => $publicEmail ?: null,
    'areaServed' => $zone ?: null,
    'founder' => ['@type' => 'Person', 'name' => (string) config('owner_name')],
]);

$page = [
    'title' => site_name() . ' · Cartes en ligne et sites pour restaurants',
    'description' => "Je crée la carte en ligne et le site de votre restaurant : lisible sur tous les téléphones, modifiable par vous en quelques secondes, prête pour le QR code de vos tables.",
    'path' => '',
    'isHome' => true,
    'bodyClass' => 'home',
    'vendor' => ['js/vendor/gsap.min.js', 'js/vendor/ScrollTrigger.min.js'],
    'scripts' => ['js/home.js'],
    'jsonld' => $jsonld,
];

// Les points qui apparaissent pendant que la carte défile dans le téléphone
$notes = [
    ['Lisible sans zoomer', 'Des textes nets et des prix alignés, pensés pour un écran de téléphone.'],
    ['Catégories en un geste', "Formules, entrées, plats, desserts : on saute directement où l'on veut."],
    ['Recherche instantanée', 'Un mot, et la carte ne montre plus que les plats qui correspondent.'],
    ['Toujours à jour', "Un prix change ? Vous le modifiez depuis l'espace gestion, c'est en ligne aussitôt."],
    ['Sans application', "Le client scanne le QR code de la table : l'appareil photo suffit."],
];

send_page_headers();
require __DIR__ . '/includes/partials/head.php';
require __DIR__ . '/includes/partials/header.php';
$qrCells = ['1111111010111', '1000001001001', '1011101011101', '1011101000101', '1011101011101', '1000001010001', '1111111010111', '0000000011000', '1101011101011', '0110100100110', '1011011011101', '0100110110010', '1101101011011'];
?>
<main id="contenu" class="story">

  <!-- 1. L'ouverture : vos deux réalisations, qui s'écartent quand on descend -->
  <section class="hero" id="accueil" data-hero>
    <a class="hero-phone hero-phone--left" href="<?= h(url('realisations')) ?>#realisation-asb" data-track="realisation" data-label="hero-asb">
      <div class="hero-phone__intro">
        <div class="hero-phone__move" data-hero-phone="left">
          <div class="hero-phone__float">
            <?= phone('asb-mobile.webp', "La carte en ligne du restaurant Aux Saveurs Braisées, sur téléphone", ['loading' => 'eager', 'statusBg' => '#1d1512']) ?>
            <span class="sticker sticker--qr" aria-hidden="true">
              <svg viewBox="0 0 42 42"><?php foreach ($qrCells as $r => $line) { foreach (str_split($line) as $c => $on) { if ($on === '1') { echo '<rect x="' . (1 + $c * 3) . '" y="' . (1 + $r * 3) . '" width="3" height="3"/>'; } } } ?></svg>
              <span>Scannez,<br>c'est servi</span>
            </span>
          </div>
          <span class="hero-phone__tag"><small>Réalisation</small>Aux Saveurs Braisées</span>
        </div>
      </div>
    </a>
    <a class="hero-phone hero-phone--right" href="<?= h(url('realisations')) ?>#realisation-fdo" data-track="realisation" data-label="hero-fdo">
      <div class="hero-phone__intro">
        <div class="hero-phone__move" data-hero-phone="right">
          <div class="hero-phone__float">
            <?= phone('fdo-mobile.webp', "Le site du restaurant La Fleur d'Or, sur téléphone", ['loading' => 'eager', 'statusBg' => '#3a2319']) ?>
          </div>
          <span class="hero-phone__tag"><small>Réalisation</small>La Fleur d'Or</span>
        </div>
      </div>
    </a>

    <div class="hero__content" data-hero-content>
      <p class="hero__kicker">Cartes et sites pour restaurants</p>
      <h1 class="hero__title">
        <span class="hero__big"><?= split_words('Des cartes', 0) ?></span>
        <span class="hero__small"><?= split_words('qui', 2) ?></span>
        <span class="hero__big"><?= split_words('donnent faim', 3) ?></span>
      </h1>
      <p class="hero__lead">Je crée la carte en ligne et le site de votre restaurant&nbsp;: lisible sur tous les téléphones, modifiable par vous en quelques secondes, prête pour le QR code de vos tables.</p>
      <div class="hero__actions">
        <a class="pill pill--white pill--halo" href="<?= h(url('formules')) ?>" data-magnetic data-track="cta" data-label="hero-gamme"><svg aria-hidden="true"><use href="#i-home"/></svg>Voir la gamme</a>
      </div>
    </div>

    <a class="scroll-cue scroll-cue--hero" href="#carte-qui-defile" aria-label="Descendre vers la suite"><span class="scroll-cue__mouse" aria-hidden="true"><i></i></span><span class="scroll-cue__label">Faites défiler</span></a>
  </section>

  <div class="ribbons" aria-hidden="true">
    <?php
    $ribbonA = ['Pizzeria', 'Brasserie', 'Sushi', 'Crêperie', 'Food truck', 'Burger', 'Bistrot', 'Boulangerie', 'Tapas', 'Traiteur'];
    $ribbonB = ['Carte en ligne', 'QR code', 'Espace gestion', 'Statistiques', 'Recherche de plat', 'Carte imprimée', 'Ma liste', 'Horaires en direct'];
    foreach (['a' => $ribbonA, 'b' => $ribbonB] as $name => $words): ?>
      <div class="ribbon ribbon--<?= $name ?>">
        <div class="ribbon__track" data-ribbon="<?= $name ?>">
          <?php for ($copy = 0; $copy < 2; $copy++): ?>
            <span class="ribbon__group"><?php foreach ($words as $word): ?><span><?= h($word) ?></span><svg><use href="#i-spark"/></svg><?php endforeach; ?></span>
          <?php endfor; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- 2. La carte qui défile avec la molette -->
  <section class="scroll-phone" id="carte-qui-defile" aria-labelledby="scroll-phone-title" data-scroll-phone>
    <div class="scroll-phone__stage">
      <div class="scroll-phone__copy">
        <p class="kicker kicker--light">Côté client</p>
        <h2 class="scroll-phone__title" id="scroll-phone-title" data-words-in><?= split_words('Descendez, la carte suit.') ?></h2>
        <p class="scroll-phone__lead">Voici une vraie carte, celle d'Aux Saveurs Braisées. Vos clients la parcourent exactement comme ça, du bout du pouce.</p>
        <div class="scroll-phone__meter" aria-hidden="true"><span data-scroll-meter></span></div>
      </div>
      <div class="scroll-phone__device">
        <div class="scroll-phone__halo" aria-hidden="true"></div>
        <?= phone('asb-mobile-long.webp', "La carte d'Aux Saveurs Braisées, qui défile dans un téléphone", ['class' => 'phone--scroll', 'statusBg' => '#1d1512']) ?>
      </div>
      <ol class="scroll-phone__notes">
        <?php foreach ($notes as $i => [$title, $text]): ?>
          <li class="note" data-note>
            <span class="note__num"><?= $i + 1 ?></span>
            <span class="note__body"><strong><?= h($title) ?></strong><span><?= h($text) ?></span></span>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- 3. Le manifeste, révélé mot à mot -->
  <section class="manifesto" aria-label="Ce qui compte">
    <div class="wrap">
      <p class="manifesto__text" data-words>Vos clients découvrent votre restaurant sur leur téléphone, souvent avant même de pousser la porte. Votre carte doit <em>donner faim</em>, se lire en un clin d'œil et rester <em>à jour</em> sans effort.</p>
    </div>
  </section>

  <!-- 4. La gamme, qui passe à l'horizontale -->
  <section class="lineup" aria-labelledby="lineup-title" data-lineup>
    <div class="lineup__pin">
      <div class="lineup__track" data-lineup-track>
        <div class="lineup__intro">
          <p class="kicker">La gamme</p>
          <h2 class="lineup__title" id="lineup-title">Quatre formules, une seule envie&nbsp;: que vos clients commandent.</h2>
          <p class="lineup__hint"><span>Continuez à descendre</span><svg aria-hidden="true"><use href="#i-arrow-right"/></svg></p>
        </div>
        <?php $i = 0; foreach ($offers as $key => $offer): $i++; [$c1, $c2, $ct] = $offer['colors']; ?>
          <a class="lineup__card" href="<?= h(url('formules')) ?>#offre-<?= h($key) ?>" style="--c1:<?= h($c1) ?>;--c2:<?= h($c2) ?>;--ct:<?= h($ct) ?>" data-lineup-card data-color="<?= h($c1) ?>" data-track="offre-accueil" data-label="<?= h($key) ?>">
            <span class="lineup__num">0<?= $i ?></span>
            <span class="lineup__kicker"><?= h($offer['kicker']) ?></span>
            <span class="lineup__name"><?= h($offer['name']) ?></span>
            <span class="lineup__art"><?= offer_art($key) ?></span>
            <span class="lineup__pitch"><?= h($offer['pitch']) ?></span>
            <span class="lineup__foot"><span><?= h(offer_price($key)) ?></span><span class="lineup__go">Découvrir <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></span></span>
          </a>
        <?php endforeach; ?>
        <div class="lineup__outro">
          <p>Envie de comparer&nbsp;?</p>
          <a class="pill pill--ink" href="<?= h(url('formules')) ?>" data-magnetic>Toutes les formules <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. Les étapes, qui s'empilent -->
  <section class="section steps-stack" aria-labelledby="steps-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="kicker">La fabrication</p>
        <h2 class="h2" id="steps-title">De l'ardoise à l'écran</h2>
        <p class="lead">Vous n'avez rien de technique à faire. Vous m'envoyez votre carte, je m'occupe du reste.</p>
      </header>
      <ol class="stack" data-stack>
        <?php foreach (method_steps() as $n => $step): ?>
          <li class="stack__card" style="--c:<?= h($step['color']) ?>;--i:<?= $n ?>" data-stack-card>
            <span class="stack__num"><?= sprintf('%02d', $n + 1) ?></span>
            <div class="stack__body">
              <h3 class="stack__title"><?= h($step['title']) ?></h3>
              <p><?= h($step['text']) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
      <p class="more-link" data-reveal><a href="<?= h(url('methode')) ?>">Le déroulé complet et les questions fréquentes <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></a></p>
    </div>
  </section>

  <!-- 6. Les réalisations, en parallaxe -->
  <section class="section showcase" aria-labelledby="showcase-title">
    <div class="wrap">
      <header class="section-head section-head--light" data-reveal>
        <p class="kicker kicker--light">Réalisations</p>
        <h2 class="h2" id="showcase-title">Déjà en service</h2>
      </header>
      <div class="showcase__grid">
        <?php foreach ($projects as $project): ?>
          <a class="showcase__card" href="<?= h(url('realisations')) ?>#realisation-<?= h($project['key']) ?>" style="--p1:<?= h($project['colors'][0]) ?>;--p2:<?= h($project['colors'][1]) ?>" data-reveal>
            <span class="showcase__media"><img src="<?= h(asset('img/' . $project['desktop'])) ?>" alt="" width="1440" height="900" loading="lazy" decoding="async" data-parallax></span>
            <span class="showcase__text">
              <span class="showcase__offer">Formule <?= h($offers[$project['offer']]['name']) ?></span>
              <span class="showcase__name"><?= h($project['name']) ?></span>
              <span class="showcase__kind"><?= h($project['kind']) ?></span>
              <span class="showcase__go">Voir le projet <svg aria-hidden="true"><use href="#i-arrow-right"/></svg></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 7. Les menus imprimés, en éventail -->
  <?php section('imprimes-apercu', ['teaserLabel' => 'accueil']); ?>

  <?php require __DIR__ . '/includes/partials/cta-band.php'; ?>
</main>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
</body>
</html>
