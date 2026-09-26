<?php
$statuses = message_statuses();
$filter = (string) ($_GET['statut'] ?? '');
$filter = isset($statuses[$filter]) || $filter === 'non-lus' ? $filter : '';
$q = clean_text($_GET['q'] ?? '', 80);
$perPage = 25;
$pageNum = max(1, (int) ($_GET['page'] ?? 1));

$where = [];
$params = [];
if ($filter === 'non-lus') {
    $where[] = "read_at IS NULL AND status != 'spam'";
} elseif ($filter !== '') {
    $where[] = 'status = ?';
    $params[] = $filter;
} else {
    $where[] = "status NOT IN ('archive', 'spam')";
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR business LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ? OR note LIKE ?)';
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$sqlWhere = str_replace('LIKE ?', "LIKE ? ESCAPE '\\'", $sqlWhere);

$stmt = db()->prepare("SELECT COUNT(*) FROM messages $sqlWhere");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$pageNum = min($pageNum, $pages);

$stmt = db()->prepare("SELECT * FROM messages $sqlWhere ORDER BY created_at DESC LIMIT $perPage OFFSET " . (($pageNum - 1) * $perPage));
$stmt->execute($params);
$messages = $stmt->fetchAll();

$counts = [];
foreach (db()->query('SELECT status, COUNT(*) AS n FROM messages GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['n'];
}
$allCount = array_sum(array_diff_key($counts, ['archive' => 0, 'spam' => 0]));
$tabs = ['' => ['Boîte de réception', $allCount], 'non-lus' => ['Non lus', $unread]];
foreach ($statuses as $key => $s) {
    $tabs[$key] = [$s['label'], $counts[$key] ?? 0];
}
$link = static fn (array $extra = []): string => admin_url('messages', array_filter(['statut' => $filter, 'q' => $q] + $extra, static fn ($v) => $v !== '' && $v !== null));
?>
<header class="head">
  <div>
    <h1 class="head__title">Messages</h1>
    <p class="head__sub">Les demandes envoyées depuis le formulaire de contact du site.</p>
  </div>
  <div class="head__actions">
    <?php if ($unread > 0): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="messages_read_all"><input type="hidden" name="back" value="<?= h($link()) ?>">
        <button class="btn" type="submit"><?= icon('check') ?>Tout marquer comme lu</button></form>
    <?php endif; ?>
    <a class="btn" href="<?= h(admin_url('export')) ?>"><?= icon('download') ?>Exporter (CSV)</a>
  </div>
</header>

<div class="toolbar">
  <nav class="tabs" aria-label="Filtrer par statut">
    <?php foreach ($tabs as $key => [$label, $n]): ?>
      <a class="tabs__item<?= $filter === $key ? ' is-active' : '' ?>" href="<?= h(admin_url('messages', array_filter(['statut' => $key, 'q' => $q]))) ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= h($label) ?> <span class="tabs__n"><?= $n ?></span></a>
    <?php endforeach; ?>
  </nav>
  <form class="search" method="get" role="search">
    <input type="hidden" name="p" value="messages">
    <?php if ($filter !== ''): ?><input type="hidden" name="statut" value="<?= h($filter) ?>"><?php endif; ?>
    <label class="sr-only" for="q">Rechercher dans les messages</label>
    <?= icon('search') ?><input id="q" name="q" type="search" value="<?= h($q) ?>" placeholder="Nom, email, établissement…">
  </form>
</div>

<?php if (!$messages): ?>
  <div class="card empty-state">
    <p class="empty-state__title"><?= $q !== '' ? 'Aucun message ne correspond à « ' . h($q) . ' ».' : 'Rien ici pour le moment.' ?></p>
    <p class="muted"><?= $q !== '' ? 'Essayez un autre mot, ou cherchez dans tous les statuts.' : 'Les nouvelles demandes du formulaire arrivent ici, et par email si les notifications sont activées.' ?></p>
  </div>
<?php else: ?>
  <ul class="inbox card">
    <?php foreach ($messages as $m): ?>
      <li class="inbox__item<?= $m['read_at'] ? '' : ' is-unread' ?>">
        <a class="inbox__link" href="<?= h(admin_url('message', ['id' => $m['id']])) ?>">
          <span class="inbox__dot" aria-hidden="true"></span>
          <span class="inbox__main">
            <span class="inbox__who"><strong><?= h($m['name']) ?></strong><?php if ($m['business'] !== ''): ?> <span class="muted">· <?= h($m['business']) ?></span><?php endif; ?><?php if (!$m['read_at']): ?><span class="sr-only"> (non lu)</span><?php endif; ?></span>
            <span class="inbox__excerpt"><?= h(str_limit($m['message'], 140)) ?></span>
          </span>
          <span class="inbox__meta">
            <?php if ($m['callback']): ?><span class="chip chip--call"><?= icon('phone') ?>À rappeler</span><?php endif; ?>
            <span class="chip"><?= h(offer_name($m['offer'])) ?></span>
            <?= status_badge($m['status']) ?>
            <time datetime="<?= h($m['created_at']) ?>"><?= h(relative_date_fr($m['created_at'])) ?></time>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
      <?php if ($pageNum > 1): ?><a class="btn" href="<?= h($link(['page' => $pageNum - 1])) ?>"><?= icon('back') ?>Plus récents</a><?php endif; ?>
      <span class="muted">Page <?= $pageNum ?> sur <?= $pages ?></span>
      <?php if ($pageNum < $pages): ?><a class="btn" href="<?= h($link(['page' => $pageNum + 1])) ?>">Plus anciens<?= icon('next') ?></a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
