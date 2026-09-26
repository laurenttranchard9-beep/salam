<?php
[$from, $to] = stat_range(30);
[$prevFrom, $prevTo] = stat_range(30, 1);
$now = stat_totals($from, $to);
$before = stat_totals($prevFrom, $prevTo);
$timeline = stat_timeline($from, $to);
$timeline['series'] = [$timeline['series'][0]];
$latest = db()->query("SELECT id, created_at, name, business, offer, status, read_at FROM messages WHERE status != 'spam' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$sources = stat_breakdown('hits', 'source', $from, $to, 5);
$live = stat_live();
$missingLegal = missing_legal_fields();
$notifyEmail = trim((string) setting('notify_email', ''));
$firstName = explode(' ', trim((string) config('owner_name', '')))[0] ?? '';
$hour = (int) date('G');
?>
<header class="head">
  <div>
    <p class="head__kicker"><?= h(ucfirst(format_date_fr(date('Y-m-d'), false))) ?></p>
    <h1 class="head__title"><?= $hour < 18 ? 'Bonjour' : 'Bonsoir' ?><?= $firstName !== '' ? ' ' . h($firstName) : '' ?>&nbsp;!</h1>
  </div>
  <p class="live<?= $live ? ' is-on' : '' ?>" title="Visiteurs ayant accepté les cookies, sur les 5 dernières minutes">
    <span class="live__dot" aria-hidden="true"></span><?= $live ?> visiteur<?= $live > 1 ? 's' : '' ?> en ce moment
  </p>
</header>

<?php if ($missingLegal): ?>
  <div class="notice notice--warn"><?= icon('alert') ?>
    <div><strong>Mentions légales à compléter</strong> : <?= h(implode(', ', $missingLegal)) ?>. Renseignez-les dans le fichier <code>config.php</code>, rubrique <code>legal</code>. <a href="<?= h(url('mentions-legales')) ?>" target="_blank" rel="noopener">Voir la page</a></div>
  </div>
<?php endif; ?>
<?php if ($notifyEmail === '' || setting('notify_enabled', '1') !== '1'): ?>
  <div class="notice"><?= icon('info') ?>
    <div>Les notifications par email sont désactivées : pensez à venir consulter vos messages ici. <a href="<?= h(admin_url('reglages')) ?>">Activer les notifications</a></div>
  </div>
<?php endif; ?>

<section class="tiles" aria-label="Les 30 derniers jours">
  <?= stat_tile('Pages vues · 30 jours', format_number($now['pageviews']), stat_delta($now['pageviews'], $before['pageviews'])) ?>
  <?= stat_tile('Visiteurs uniques', format_number($now['visitors']), stat_delta($now['visitors'], $before['visitors']), 'Avec consentement') ?>
  <?= stat_tile('Demandes reçues', format_number($now['messages']), stat_delta($now['messages'], $before['messages'])) ?>
  <?= stat_tile('Cookies acceptés', $now['consent_rate'] === null ? '—' : $now['consent_rate'] . ' %', null, $now['consent_yes'] + $now['consent_no'] > 0 ? $now['consent_yes'] . ' acceptations, ' . $now['consent_no'] . ' refus' : 'Aucun choix enregistré') ?>
</section>

<div class="grid grid--2-1">
  <section class="card">
    <header class="card__head">
      <h2 class="card__title">Pages vues par jour</h2>
      <a class="link" href="<?= h(admin_url('stats')) ?>">Toutes les statistiques <?= icon('next') ?></a>
    </header>
    <?= line_chart($timeline, 'Pages vues par jour sur les 30 derniers jours') ?>
  </section>
  <section class="card">
    <header class="card__head"><h2 class="card__title">D'où viennent vos visiteurs</h2></header>
    <?= bar_list($sources, 'pages vues') ?>
  </section>
</div>

<section class="card">
  <header class="card__head">
    <h2 class="card__title">Dernières demandes</h2>
    <a class="link" href="<?= h(admin_url('messages')) ?>">Tous les messages <?= icon('next') ?></a>
  </header>
  <?php if (!$latest): ?>
    <p class="empty">Aucune demande pour l'instant. Elles arriveront ici dès qu'un visiteur remplira le formulaire de contact.</p>
  <?php else: ?>
    <ul class="inbox inbox--compact">
      <?php foreach ($latest as $m): ?>
        <li class="inbox__item<?= $m['read_at'] ? '' : ' is-unread' ?>">
          <a class="inbox__link" href="<?= h(admin_url('message', ['id' => $m['id']])) ?>">
            <span class="inbox__who"><strong><?= h($m['name']) ?></strong><?php if ($m['business'] !== ''): ?> <span class="muted">· <?= h($m['business']) ?></span><?php endif; ?></span>
            <span class="inbox__meta"><span class="chip"><?= h(offer_name($m['offer'])) ?></span><?= status_badge($m['status']) ?><time datetime="<?= h($m['created_at']) ?>"><?= h(relative_date_fr($m['created_at'])) ?></time></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
