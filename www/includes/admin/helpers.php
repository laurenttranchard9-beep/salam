<?php
declare(strict_types=1);

/*
 * Fonctions d'affichage de l'espace gestion.
 */

function message_statuses(): array
{
    return [
        'nouveau' => ['label' => 'Nouveau', 'tone' => 'new'],
        'en_cours' => ['label' => 'En cours', 'tone' => 'progress'],
        'traite' => ['label' => 'Traité', 'tone' => 'done'],
        'archive' => ['label' => 'Archivé', 'tone' => 'muted'],
        'spam' => ['label' => 'Indésirable', 'tone' => 'spam'],
    ];
}

function status_badge(string $status): string
{
    $s = message_statuses()[$status] ?? ['label' => $status, 'tone' => 'muted'];
    return '<span class="badge badge--' . h($s['tone']) . '">' . h($s['label']) . '</span>';
}

function unread_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM messages WHERE read_at IS NULL AND status != 'spam'")->fetchColumn();
}

function offer_name(string $key): string
{
    return offers()[$key]['name'] ?? 'Pas encore décidé';
}

function option_labels(string $json): array
{
    $labels = contact_options();
    return array_map(static fn (string $k): string => $labels[$k] ?? $k, json_decode($json ?: '[]', true) ?: []);
}

/** Champs obligatoires des mentions légales encore vides dans config.php. */
function missing_legal_fields(): array
{
    $labels = ['publisher' => 'éditeur', 'siret' => 'SIRET', 'address' => 'adresse', 'email' => 'email', 'host_name' => 'nom de l\'hébergeur', 'host_address' => 'adresse de l\'hébergeur'];
    return array_values(array_filter($labels, static fn (string $label, string $key): bool => trim((string) config('legal.' . $key, '')) === '', ARRAY_FILTER_USE_BOTH));
}

function icon(string $name, string $class = ''): string
{
    return '<svg class="ic' . ($class !== '' ? ' ' . h($class) : '') . '" aria-hidden="true"><use href="#a-' . h($name) . '"/></svg>';
}

/** Affiche une page de l'espace gestion, dans le cadre (menu) ou seule (connexion). */
function render_view(string $view, array $vars = [], bool $shell = true): void
{
    extract($vars, EXTR_SKIP);
    $flashes = take_flashes();
    $current = $view;
    $unread = $shell ? unread_count() : 0;
    require __DIR__ . '/views/_layout-top.php';
    require __DIR__ . '/views/' . $view . '.php';
    require __DIR__ . '/views/_layout-bottom.php';
}

/** Tuile de chiffre clé, avec évolution facultative. */
function stat_tile(string $label, string $value, ?int $delta = null, string $hint = '', bool $upIsGood = true): string
{
    $html = '<div class="tile"><p class="tile__label">' . h($label) . '</p><p class="tile__value">' . h($value) . '</p>';
    if ($delta !== null) {
        $good = $delta === 0 ? null : (($delta > 0) === $upIsGood);
        $class = $good === null ? 'flat' : ($good ? 'up' : 'down');
        $arrow = $delta > 0 ? '↑' : ($delta < 0 ? '↓' : '→');
        $html .= '<p class="tile__delta tile__delta--' . $class . '"><span aria-hidden="true">' . $arrow . '</span> ' . ($delta > 0 ? '+' : '') . $delta . '&nbsp;% <span class="tile__vs">vs période précédente</span></p>';
    } elseif ($hint !== '') {
        $html .= '<p class="tile__hint">' . h($hint) . '</p>';
    }
    return $html . '</div>';
}

/**
 * Liste en barres horizontales (une seule série : une seule couleur),
 * valeur écrite au bout de chaque barre.
 */
function bar_list(array $rows, string $unit = '', ?callable $labeler = null, string $empty = 'Pas encore de données sur cette période.'): string
{
    if (!$rows) {
        return '<p class="empty">' . h($empty) . '</p>';
    }
    $max = max(array_column($rows, 'n')) ?: 1;
    $total = array_sum(array_column($rows, 'n')) ?: 1;
    $labels = array_map(static fn (array $row): string => $labeler ? $labeler($row['label']) : $row['label'], $rows);
    // Libellés longs : sur leur propre ligne, au-dessus de la barre, plutôt que tronqués
    $stacked = max(array_map('mb_strlen', $labels)) > 24;
    $html = '<ul class="bars' . ($stacked ? ' bars--stacked' : '') . '">';
    foreach ($rows as $i => $row) {
        $label = $labels[$i];
        $pct = (int) round($row['n'] / $total * 100);
        $width = max(1.5, $row['n'] / $max * 100);
        $html .= '<li class="bars__row" tabindex="0" data-tip="' . h($label . ' : ' . format_number($row['n']) . ($unit !== '' ? ' ' . $unit : '') . ' (' . $pct . ' %)') . '">'
            . '<span class="bars__label">' . h($label) . '</span>'
            . '<span class="bars__track"><span class="bars__fill" style="width:' . round($width, 1) . '%"></span></span>'
            . '<span class="bars__value">' . format_number($row['n']) . '</span></li>';
    }
    return $html . '</ul>';
}

/** Répartition sur une seule barre (≤ 3 parts), avec légende chiffrée. */
function split_bar(array $rows, array $order): string
{
    $total = array_sum(array_column($rows, 'n'));
    if ($total === 0) {
        return '<p class="empty">Pas encore de données sur cette période.</p>';
    }
    $byLabel = array_column($rows, 'n', 'label');
    $html = '<div class="split" role="img" aria-label="' . h(implode(', ', array_map(static fn ($l) => $l . ' ' . (int) round(($byLabel[$l] ?? 0) / $total * 100) . ' %', $order))) . '">';
    $legend = '<ul class="legend">';
    foreach ($order as $i => $label) {
        $n = (int) ($byLabel[$label] ?? 0);
        $pct = $n / $total * 100;
        if ($n > 0) {
            $html .= '<span class="split__part series-' . ($i + 1) . '" style="flex-grow:' . round($pct, 2) . '" data-tip="' . h($label . ' : ' . format_number($n) . ' (' . round($pct) . ' %)') . '" tabindex="0"></span>';
        }
        $legend .= '<li><span class="key series-' . ($i + 1) . '"></span>' . h($label) . ' <strong>' . round($pct) . '&nbsp;%</strong></li>';
    }
    return $html . '</div>' . $legend . '</ul>';
}

/** Téléchargement des messages au format CSV (ouvrable dans Excel). */
function export_messages_csv(): void
{
    $rows = db()->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="messages-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // pour qu'Excel lise les accents
    // Une cellule qui commence par = + - @ serait interprétée comme une formule par le tableur
    $safe = static fn ($v): string => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;
    fputcsv($out, ['Date', 'Nom', 'Établissement', 'Email', 'Téléphone', 'Rappel souhaité', 'Type', 'Taille de la carte', 'Formule', 'Options', 'Statut', 'Source', 'Message', 'Note'], ';', '"', '');
    foreach ($rows as $m) {
        fputcsv($out, array_map($safe, [
            $m['created_at'], $m['name'], $m['business'], $m['email'], $m['phone'], $m['callback'] ? 'oui' : 'non',
            $m['business_type'], $m['menu_size'], offer_name($m['offer']), implode(', ', option_labels($m['options'])),
            message_statuses()[$m['status']]['label'] ?? $m['status'], $m['source'], $m['message'], $m['note'],
        ]), ';', '"', '');
    }
    fclose($out);
}

/**
 * Courbe dessinée par admin.js à la largeur réelle de l'écran.
 * Sans JavaScript, le tableau des chiffres reste lisible.
 */
function line_chart(array $timeline, string $label): string
{
    $series = $timeline['series'];
    $data = ['labels' => $timeline['labels'], 'short' => $timeline['short'], 'series' => $series];
    $html = '<figure class="chart" data-line-chart>';
    if (count($series) > 1) {
        $html .= '<ul class="legend legend--lines">';
        foreach ($series as $s) {
            $html .= '<li><span class="key key--line series-' . (int) $s['slot'] . '"></span>' . h($s['name']) . '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '<div class="chart__plot" data-plot role="img" aria-label="' . h($label) . '" tabindex="0"></div>';
    $html .= '<script type="application/json" data-chart-data>' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
    $html .= '<details class="chart__table"><summary>Voir les chiffres</summary><div class="table-wrap"><table class="table"><thead><tr><th scope="col">' . ($timeline['byWeek'] ? 'Semaine' : 'Jour') . '</th>';
    foreach ($series as $s) {
        $html .= '<th scope="col" class="num">' . h($s['name']) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach (array_reverse(array_keys($timeline['labels'])) as $i) {
        $html .= '<tr><th scope="row">' . h($timeline['labels'][$i]) . '</th>';
        foreach ($series as $s) {
            $html .= '<td class="num">' . format_number($s['values'][$i]) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table></div></details></figure>';
}
