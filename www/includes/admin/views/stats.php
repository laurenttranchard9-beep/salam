<?php
$days = (int) ($_GET['periode'] ?? 30);
$days = isset(STAT_PERIODS[$days]) ? $days : 30;
[$from, $to] = stat_range($days);
[$prevFrom, $prevTo] = stat_range($days, 1);
$now = stat_totals($from, $to);
$before = stat_totals($prevFrom, $prevTo);
$range = [$from, $to];
$detailViews = (int) stat_value('SELECT COUNT(*) FROM views WHERE day BETWEEN ? AND ?', $range);
$pagesPerVisit = $now['visits'] > 0 ? round($detailViews / $now['visits'], 1) : null;

$timeline = stat_timeline($from, $to);
$sources = stat_breakdown('hits', 'source', $from, $to, 8);
$pagesRows = stat_breakdown('hits', 'path', $from, $to, 6);
$devices = stat_breakdown('hits', 'device', $from, $to, 3);
$browsers = stat_breakdown('views', 'browser', $from, $to, 6);
$systems = stat_breakdown('views', 'os', $from, $to, 6);
$campaigns = stat_breakdown('views', 'utm_campaign', $from, $to, 6);
$heat = stat_heatmap($from, $to);
$funnel = stat_funnel($from, $to);

$events = stat_events($from, $to, ['offre', 'offre-accueil', 'offre-choisie', 'demo-cuisine', 'faq', 'cta', 'tel', 'mail', 'realisation']);
$group = static function (array $names) use ($events): array {
    $rows = [];
    foreach ($events as $e) {
        if (in_array($e['name'], $names, true)) {
            // Une formule ouverte depuis l'accueil ou depuis la page Formules compte pour la même formule
            $name = $e['name'] === 'offre-accueil' ? 'offre' : $e['name'];
            $key = $name . '|' . $e['label'];
            $rows[$key] = ['label' => $key, 'n' => ($rows[$key]['n'] ?? 0) + (int) $e['n']];
        }
    }
    usort($rows, static fn (array $a, array $b): int => $b['n'] <=> $a['n']);
    return array_values($rows);
};
$offers = offers();
$cuisines = demo_cuisines();
$faqItems = faq();
$ctaNames = [
    'cta|hero-gamme' => 'Accueil : « Voir la gamme »', 'cta|header-contact' => 'En-tête : « Contactez-moi »',
    'cta|menu-devis' => 'Menu : « Demander un devis »', 'cta|footer-devis' => 'Pied de page : « Demander un devis »',
    'cta|faq-contact' => 'FAQ : « Poser ma question »', 'cta|bandeau-devis' => 'Bandeau : « Demander un devis »', 'realisation|hero-asb' => 'Accueil : téléphone Aux Saveurs Braisées',
    'realisation|hero-fdo' => "Accueil : téléphone La Fleur d'Or", 'realisation|asb-visite' => 'Visite du site Aux Saveurs Braisées',
    'realisation|fdo-visite' => "Visite du site La Fleur d'Or",
];
$eventLabel = static function (string $key) use ($offers, $cuisines, $faqItems, $ctaNames): string {
    [$name, $label] = explode('|', $key, 2) + ['', ''];
    return match ($name) {
        'offre', 'offre-choisie' => $offers[$label]['name'] ?? $label,
        'demo-cuisine' => $cuisines[$label]['label'] ?? $label,
        'faq' => $faqItems[(int) $label - 1]['q'] ?? 'Question ' . $label,
        'tel' => 'Appel depuis : ' . $label,
        'mail' => 'Email depuis : ' . $label,
        default => $ctaNames[$key] ?? event_label($name, $label),
    };
};

// Carte de chaleur : 5 niveaux d'une seule teinte
$maxHeat = max(array_map('max', $heat)) ?: 0;
$level = static fn (int $n): int => $n === 0 || $maxHeat === 0 ? 0 : min(5, (int) ceil($n / $maxHeat * 5));
$dayNames = [1 => 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$peak = null;
foreach ($heat as $d => $hours) {
    foreach ($hours as $hr => $n) {
        if ($n > 0 && ($peak === null || $n > $peak[2])) {
            $peak = [$d, $hr, $n];
        }
    }
}
$funnelTop = max(1, $funnel[0]['n']);
?>
<header class="head">
  <div>
    <h1 class="head__title">Statistiques</h1>
    <p class="head__sub">Du <?= h(format_date_fr($from, false)) ?> au <?= h(format_date_fr($to, false)) ?>, comparé aux <?= h(STAT_PERIODS[$days]) ?> précédents.</p>
  </div>
  <p class="live<?= stat_live() ? ' is-on' : '' ?>"><span class="live__dot" aria-hidden="true"></span><?= stat_live() ?> en ce moment</p>
</header>

<nav class="seg" aria-label="Période">
  <?php foreach (STAT_PERIODS as $n => $label): ?>
    <a class="seg__item<?= $n === $days ? ' is-active' : '' ?>" href="<?= h(admin_url('stats', ['periode' => $n])) ?>"<?= $n === $days ? ' aria-current="page"' : '' ?>><?= $n === $days ? icon('check') : '' ?><?= h($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="tiles tiles--4" aria-label="Chiffres clés">
  <?= stat_tile('Pages vues', format_number($now['pageviews']), stat_delta($now['pageviews'], $before['pageviews'])) ?>
  <?= stat_tile('Visiteurs uniques *', format_number($now['visitors']), stat_delta($now['visitors'], $before['visitors'])) ?>
  <?= stat_tile('Visites *', format_number($now['visits']), stat_delta($now['visits'], $before['visits'])) ?>
  <?= stat_tile('Demandes reçues', format_number($now['messages']), stat_delta($now['messages'], $before['messages'])) ?>
  <?= stat_tile('Temps moyen par page *', format_duration($now['duration']), stat_delta($now['duration'] ?: null, $before['duration'] ?: null)) ?>
  <?= stat_tile('Page lue en moyenne *', $now['scroll'] ? $now['scroll'] . ' %' : '—', null, 'Jusqu\'où les visiteurs descendent') ?>
  <?= stat_tile('Pages par visite *', $pagesPerVisit !== null ? number_format($pagesPerVisit, 1, ',', '') : '—') ?>
  <?= stat_tile('Cookies acceptés', $now['consent_rate'] === null ? '—' : $now['consent_rate'] . ' %', null, $now['consent_yes'] + $now['consent_no'] > 0 ? $now['consent_yes'] . ' oui · ' . $now['consent_no'] . ' non' : 'Aucun choix sur la période') ?>
</section>

<section class="card">
  <header class="card__head"><h2 class="card__title">Fréquentation <?= $timeline['byWeek'] ? 'par semaine' : 'par jour' ?></h2></header>
  <?= line_chart($timeline, 'Pages vues et visiteurs uniques ' . ($timeline['byWeek'] ? 'par semaine' : 'par jour')) ?>
</section>

<div class="grid grid--2">
  <section class="card">
    <header class="card__head"><h2 class="card__title">Sources</h2><p class="card__hint">Pages vues, tous visiteurs</p></header>
    <?= bar_list($sources, 'pages vues') ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Pages</h2><p class="card__hint">Pages vues, tous visiteurs</p></header>
    <?= bar_list($pagesRows, 'pages vues', 'page_label') ?>
  </section>
</div>

<div class="grid grid--3">
  <section class="card">
    <header class="card__head"><h2 class="card__title">Appareils</h2></header>
    <?= split_bar($devices, ['Mobile', 'Ordinateur', 'Tablette']) ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Navigateurs *</h2></header>
    <?= bar_list($browsers, 'pages vues') ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Systèmes *</h2></header>
    <?= bar_list($systems, 'pages vues') ?>
  </section>
</div>

<section class="card">
  <header class="card__head">
    <h2 class="card__title">Jours et heures de visite *</h2>
    <p class="card__hint"><?= $peak ? 'Le plus fréquenté : le ' . mb_strtolower($dayNames[$peak[0]]) . ' entre ' . $peak[1] . ' h et ' . ($peak[1] + 1) . ' h' : 'Pas encore de données sur cette période' ?></p>
  </header>
  <div class="heat-wrap">
    <div class="heat" role="img" aria-label="<?= h($peak ? 'Carte des visites par jour et par heure. Pic le ' . mb_strtolower($dayNames[$peak[0]]) . ' vers ' . $peak[1] . ' h.' : 'Carte des visites par jour et par heure, vide pour le moment.') ?>">
      <span></span>
      <?php for ($hr = 0; $hr < 24; $hr++): ?><span class="heat__hour"><?= $hr % 3 === 0 ? $hr . 'h' : '' ?></span><?php endfor; ?>
      <?php foreach ($heat as $d => $hours): ?>
        <span class="heat__day"><?= h(mb_substr($dayNames[$d], 0, 3)) ?></span>
        <?php foreach ($hours as $hr => $n): ?><span class="heat__cell heat--<?= $level($n) ?>" data-tip="<?= h($dayNames[$d] . ', ' . $hr . ' h – ' . ($hr + 1) . ' h : ' . $n . ' page' . ($n > 1 ? 's' : '') . ' vue' . ($n > 1 ? 's' : '')) ?>"></span><?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="heat-legend" aria-hidden="true"><span>Moins</span><?php for ($l = 0; $l <= 5; $l++): ?><i class="heat--<?= $l ?>"></i><?php endfor; ?><span>Plus</span></div>
</section>

<div class="grid grid--2">
  <section class="card">
    <header class="card__head"><h2 class="card__title">Parcours vers une demande *</h2><p class="card__hint">Visiteurs distincts à chaque étape</p></header>
    <?php if ($funnel[0]['n'] === 0): ?>
      <p class="empty">Pas encore de données sur cette période.</p>
    <?php else: ?>
      <ol class="funnel">
        <?php foreach ($funnel as $i => $step): $pct = (int) round($step['n'] / $funnelTop * 100); ?>
          <li class="funnel__step" tabindex="0" data-tip="<?= h($step['label'] . ' : ' . format_number($step['n']) . ' (' . $pct . ' % des visiteurs)') ?>">
            <span class="funnel__label"><?= h($step['label']) ?></span>
            <span class="funnel__track"><span class="funnel__fill ord-<?= $i + 1 ?>" style="width:<?= max(1.5, $pct) ?>%"></span></span>
            <span class="funnel__value"><?= format_number($step['n']) ?> <small><?= $pct ?>&nbsp;%</small></span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Formules consultées *</h2><p class="card__hint">Depuis l'accueil ou la page Formules</p></header>
    <?= bar_list($group(['offre', 'offre-accueil']), 'ouvertures', $eventLabel) ?>
  </section>
</div>

<div class="grid grid--3">
  <section class="card">
    <header class="card__head"><h2 class="card__title">Démo : cuisines testées *</h2></header>
    <?= bar_list($group(['demo-cuisine']), 'clics', $eventLabel) ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Questions ouvertes *</h2></header>
    <?= bar_list($group(['faq']), 'ouvertures', $eventLabel) ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Boutons cliqués *</h2></header>
    <?= bar_list($group(['cta', 'realisation', 'tel', 'mail', 'offre-choisie']), 'clics', static fn (string $k): string => str_starts_with($k, 'offre-choisie|') ? 'Formule choisie : ' . $eventLabel($k) : $eventLabel($k)) ?>
  </section>
</div>

<?php if ($campaigns): ?>
  <section class="card">
    <header class="card__head"><h2 class="card__title">Campagnes (utm_campaign) *</h2></header>
    <?= bar_list($campaigns, 'pages vues') ?>
  </section>
<?php endif; ?>

<details class="card about">
  <summary><?= icon('info') ?>Comment ces chiffres sont-ils calculés&nbsp;?</summary>
  <div class="about__body">
    <p><strong>Pages vues, sources, pages et appareils</strong> comptent tous les visiteurs, sans cookie ni identifiant : un simple compteur anonyme.</p>
    <p><strong>Les chiffres marqués d'une étoile (*)</strong> ne concernent que les visiteurs qui ont accepté les cookies. Ils sont donc plus bas que la réalité, mais leurs proportions restent parlantes.</p>
    <p>Les robots, et vos propres visites depuis un appareil où vous vous êtes connecté ici, ne sont pas comptés. Le détail des visites est effacé après 13 mois, les totaux anonymes après 25 mois.</p>
    <p><strong>Astuce QR code</strong> : faites pointer vos QR codes vers <code><?= h(absolute_url('?utm_source=qr')) ?></code> pour voir dans « Sources » combien de visites ils apportent.</p>
  </div>
</details>
