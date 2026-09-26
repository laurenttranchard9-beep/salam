<?php
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM messages WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m): ?>
  <header class="head"><h1 class="head__title">Message introuvable</h1></header>
  <p class="empty">Ce message a peut-être été supprimé. <a href="<?= h(admin_url('messages')) ?>">Retour aux messages</a>.</p>
<?php return; endif;

$prev = db()->prepare('SELECT id FROM messages WHERE created_at > ? OR (created_at = ? AND id > ?) ORDER BY created_at ASC, id ASC LIMIT 1');
$prev->execute([$m['created_at'], $m['created_at'], $id]);
$newer = $prev->fetchColumn();
$next = db()->prepare('SELECT id FROM messages WHERE created_at < ? OR (created_at = ? AND id < ?) ORDER BY created_at DESC, id DESC LIMIT 1');
$next->execute([$m['created_at'], $m['created_at'], $id]);
$older = $next->fetchColumn();

$firstName = explode(' ', $m['name'])[0];
$subject = 'Votre carte en ligne' . ($m['business'] !== '' ? ' · ' . $m['business'] : '');
$body = "Bonjour $firstName,\n\nMerci pour votre message !\n\n\n\n" . (string) config('owner_name') . "\n\n---\nVotre message du " . format_date_fr($m['created_at']) . " :\n> " . str_replace("\n", "\n> ", $m['message']);
$mailto = 'mailto:' . rawurlencode($m['email']) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
$tel = preg_replace('/[^0-9+]/', '', $m['phone']);
$options = option_labels($m['options']);
$here = admin_url('message', ['id' => $id]);
?>
<header class="head">
  <div>
    <a class="back" href="<?= h(admin_url('messages')) ?>"><?= icon('back') ?>Messages</a>
    <h1 class="head__title"><?= h($m['name']) ?></h1>
    <p class="head__sub"><?= $m['business'] !== '' ? h($m['business']) . ' · ' : '' ?>reçu le <?= h(format_date_fr($m['created_at'])) ?></p>
  </div>
  <nav class="head__actions" aria-label="Navigation entre messages">
    <?php if ($newer): ?><a class="btn btn--icon" href="<?= h(admin_url('message', ['id' => $newer])) ?>" title="Message plus récent"><?= icon('back') ?><span class="sr-only">Message plus récent</span></a><?php endif; ?>
    <?php if ($older): ?><a class="btn btn--icon" href="<?= h(admin_url('message', ['id' => $older])) ?>" title="Message plus ancien"><?= icon('next') ?><span class="sr-only">Message plus ancien</span></a><?php endif; ?>
  </nav>
</header>

<div class="grid grid--2-1">
  <div class="stack-lg">
    <section class="card">
      <div class="actions">
        <a class="btn btn--primary" href="<?= h($mailto) ?>"><?= icon('mail') ?>Répondre par email</a>
        <?php if ($tel !== ''): ?><a class="btn" href="tel:<?= h($tel) ?>"><?= icon('phone') ?>Appeler</a><?php endif; ?>
        <button class="btn" type="button" data-copy="<?= h($m['email']) ?>"><?= icon('copy') ?><span>Copier l'email</span></button>
      </div>
      <?php if ($m['callback']): ?>
        <p class="notice notice--call"><?= icon('phone') ?><span><?= h($firstName) ?> préfère être rappelé·e<?= $m['phone'] !== '' ? ' au ' . h($m['phone']) : '' ?>.</span></p>
      <?php endif; ?>
      <h2 class="card__title card__title--sm">Son message</h2>
      <div class="letter"><?= nl2br(h($m['message'])) ?></div>
    </section>

    <section class="card">
      <h2 class="card__title card__title--sm">Note interne</h2>
      <p class="muted small">Visible uniquement ici : où en est l'échange, le montant du devis, la date de rappel…</p>
      <form method="post" class="stack">
        <?= csrf_field() ?><input type="hidden" name="action" value="message_note"><input type="hidden" name="id" value="<?= $id ?>">
        <label class="sr-only" for="note">Note interne</label>
        <textarea id="note" name="note" rows="4" class="input" placeholder="Rappelé le 12, devis envoyé…"><?= h($m['note']) ?></textarea>
        <div><button class="btn" type="submit">Enregistrer la note</button></div>
      </form>
    </section>
  </div>

  <aside class="stack-lg">
    <section class="card">
      <h2 class="card__title card__title--sm">Statut</h2>
      <form method="post" class="status-picker" data-autosubmit>
        <?= csrf_field() ?><input type="hidden" name="action" value="message_status"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="back" value="<?= h($here) ?>">
        <?php foreach (message_statuses() as $key => $s): ?>
          <label class="status-picker__opt"><input type="radio" name="status" value="<?= h($key) ?>"<?= $m['status'] === $key ? ' checked' : '' ?>><span class="badge badge--<?= h($s['tone']) ?>"><?= h($s['label']) ?></span></label>
        <?php endforeach; ?>
        <button class="btn btn--small" type="submit" data-hide-with-js>Changer</button>
      </form>
    </section>

    <section class="card">
      <h2 class="card__title card__title--sm">Coordonnées et projet</h2>
      <dl class="facts">
        <div><dt>Email</dt><dd><a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a></dd></div>
        <div><dt>Téléphone</dt><dd><?= $m['phone'] !== '' ? '<a href="tel:' . h($tel) . '">' . h($m['phone']) . '</a>' : '—' ?></dd></div>
        <div><dt>Établissement</dt><dd><?= h($m['business'] ?: '—') ?><?= $m['business_type'] !== '' ? ' <span class="muted">(' . h($m['business_type']) . ')</span>' : '' ?></dd></div>
        <div><dt>Taille de la carte</dt><dd><?= h($m['menu_size'] ?: '—') ?></dd></div>
        <div><dt>Formule</dt><dd><?= h(offer_name($m['offer'])) ?></dd></div>
        <div><dt>Options</dt><dd><?= $options ? h(implode(', ', $options)) : '—' ?></dd></div>
        <div><dt>Arrivé par</dt><dd><?= $m['source'] !== '' ? h($m['source']) : '<span class="muted">Inconnu (cookies refusés)</span>' ?></dd></div>
      </dl>
    </section>

    <section class="card card--quiet">
      <form method="post" class="stack">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn btn--ghost" type="submit" name="action" value="message_unread">Marquer comme non lu</button>
        <button class="btn btn--danger" type="submit" name="action" value="message_delete" data-confirm="Supprimer définitivement le message de <?= h($m['name']) ?> ?"><?= icon('trash') ?>Supprimer</button>
      </form>
    </section>
  </aside>
</div>
