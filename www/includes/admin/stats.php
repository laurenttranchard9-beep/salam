<?php
declare(strict_types=1);

/*
 * Requêtes des statistiques de l'espace gestion.
 * Les dates sont au format AAAA-MM-JJ, bornes incluses.
 */

const STAT_PERIODS = [7 => '7 jours', 30 => '30 jours', 90 => '90 jours', 365 => '12 mois'];

/** [début, fin] d'une période qui se termine aujourd'hui, et la période précédente de même durée. */
function stat_range(int $days, int $shift = 0): array
{
    $end = strtotime('today') - $shift * $days * 86400;
    return [date('Y-m-d', $end - ($days - 1) * 86400), date('Y-m-d', $end)];
}

function stat_value(string $sql, array $params): float
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (float) ($stmt->fetchColumn() ?: 0);
}

function stat_totals(string $from, string $to): array
{
    $range = [$from, $to];
    $yes = stat_value("SELECT SUM(n) FROM consents WHERE choice = 'oui' AND day BETWEEN ? AND ?", $range);
    $no = stat_value("SELECT SUM(n) FROM consents WHERE choice = 'non' AND day BETWEEN ? AND ?", $range);
    return [
        'pageviews' => (int) stat_value('SELECT SUM(n) FROM hits WHERE day BETWEEN ? AND ?', $range),
        'visitors' => (int) stat_value('SELECT COUNT(DISTINCT visitor) FROM views WHERE day BETWEEN ? AND ?', $range),
        'visits' => (int) stat_value("SELECT COUNT(DISTINCT visitor || '-' || session) FROM views WHERE day BETWEEN ? AND ?", $range),
        'duration' => (int) round(stat_value('SELECT AVG(duration) FROM views WHERE duration > 0 AND day BETWEEN ? AND ?', $range)),
        'scroll' => (int) round(stat_value('SELECT AVG(scroll) FROM views WHERE scroll > 0 AND day BETWEEN ? AND ?', $range)),
        'messages' => (int) stat_value("SELECT COUNT(*) FROM messages WHERE status != 'spam' AND substr(created_at, 1, 10) BETWEEN ? AND ?", $range),
        'consent_yes' => (int) $yes,
        'consent_no' => (int) $no,
        'consent_rate' => $yes + $no > 0 ? (int) round($yes / ($yes + $no) * 100) : null,
    ];
}

/** Évolution en % par rapport à la période précédente (null si pas de référence). */
function stat_delta(int|float|null $now, int|float|null $before): ?int
{
    if ($now === null || $before === null || $before == 0) {
        return null;
    }
    return (int) round(($now - $before) / $before * 100);
}

/**
 * Pages vues (tous les visiteurs) et visiteurs uniques (avec consentement), jour par jour,
 * ou semaine par semaine au-delà de 90 jours.
 */
function stat_timeline(string $from, string $to): array
{
    $byWeek = (strtotime($to) - strtotime($from)) / 86400 > 100;
    $bucket = $byWeek ? "date(day, 'weekday 0', '-6 days')" : 'day';

    $keys = [];
    for ($t = strtotime($from); $t <= strtotime($to); $t += 86400) {
        $key = $byWeek ? date('Y-m-d', strtotime('monday this week', $t)) : date('Y-m-d', $t);
        $keys[$key] = true;
    }
    $pageviews = array_fill_keys(array_keys($keys), 0);
    $visitors = $pageviews;

    $stmt = db()->prepare("SELECT $bucket AS k, SUM(n) AS n FROM hits WHERE day BETWEEN ? AND ? GROUP BY k");
    $stmt->execute([$from, $to]);
    foreach ($stmt as $row) {
        if (isset($pageviews[$row['k']])) {
            $pageviews[$row['k']] = (int) $row['n'];
        }
    }
    $stmt = db()->prepare("SELECT $bucket AS k, COUNT(DISTINCT visitor) AS n FROM views WHERE day BETWEEN ? AND ? GROUP BY k");
    $stmt->execute([$from, $to]);
    foreach ($stmt as $row) {
        if (isset($visitors[$row['k']])) {
            $visitors[$row['k']] = (int) $row['n'];
        }
    }

    $labels = array_map(static function (string $day) use ($byWeek): string {
        return ($byWeek ? 'Sem. du ' : '') . format_date_fr($day, false);
    }, array_keys($pageviews));
    $short = array_map(static fn (string $day): string => date('d/m', strtotime($day)), array_keys($pageviews));

    return [
        'byWeek' => $byWeek,
        'keys' => array_keys($pageviews),
        'labels' => $labels,
        'short' => $short,
        'series' => [
            ['name' => 'Pages vues', 'values' => array_values($pageviews), 'slot' => 1],
            ['name' => 'Visiteurs uniques', 'values' => array_values($visitors), 'slot' => 2],
        ],
    ];
}

/** Classement d'une colonne : [['label' => …, 'n' => …], …] */
function stat_breakdown(string $table, string $column, string $from, string $to, int $limit = 8): array
{
    $allowed = ['hits' => ['source', 'path', 'device'], 'views' => ['browser', 'os', 'device', 'source', 'utm_campaign']];
    if (!in_array($column, $allowed[$table] ?? [], true)) {
        return [];
    }
    $count = $table === 'hits' ? 'SUM(n)' : 'COUNT(*)';
    $extra = match (true) {
        $table === 'views' && $column === 'utm_campaign' => " AND utm_campaign != ''",
        $column === 'source' => " AND source != 'Interne'", // navigation d'une page à l'autre du site
        default => '',
    };
    $stmt = db()->prepare("SELECT $column AS label, $count AS n FROM $table WHERE day BETWEEN ? AND ?$extra GROUP BY $column ORDER BY n DESC LIMIT " . (int) $limit);
    $stmt->execute([$from, $to]);
    return array_map(static fn (array $r): array => ['label' => (string) $r['label'], 'n' => (int) $r['n']], $stmt->fetchAll());
}

/** Pages vues détaillées par jour de la semaine (1 = lundi) et par heure. */
function stat_heatmap(string $from, string $to): array
{
    $grid = array_fill(1, 7, array_fill(0, 24, 0));
    $stmt = db()->prepare('SELECT weekday, hour, COUNT(*) AS n FROM views WHERE day BETWEEN ? AND ? GROUP BY weekday, hour');
    $stmt->execute([$from, $to]);
    foreach ($stmt as $row) {
        $grid[(int) $row['weekday']][(int) $row['hour']] = (int) $row['n'];
    }
    return $grid;
}

/** Nombre de visiteurs distincts par événement et libellé. */
function stat_events(string $from, string $to, array $names): array
{
    if (!$names) {
        return [];
    }
    $in = implode(', ', array_fill(0, count($names), '?'));
    $stmt = db()->prepare("SELECT name, label, COUNT(*) AS n, COUNT(DISTINCT visitor) AS people
                           FROM events WHERE day BETWEEN ? AND ? AND name IN ($in)
                           GROUP BY name, label ORDER BY n DESC");
    $stmt->execute(array_merge([$from, $to], $names));
    return $stmt->fetchAll();
}

/** Parcours : visiteurs → formule consultée → formulaire commencé → formulaire envoyé. */
function stat_funnel(string $from, string $to): array
{
    $range = [$from, $to];
    $people = static fn (string $where): int => (int) stat_value("SELECT COUNT(DISTINCT visitor) FROM events WHERE day BETWEEN ? AND ? AND $where", $range);
    return [
        ['label' => 'Visiteurs uniques', 'n' => (int) stat_value('SELECT COUNT(DISTINCT visitor) FROM views WHERE day BETWEEN ? AND ?', $range)],
        ['label' => 'Ont regardé une formule', 'n' => $people("name IN ('offre', 'offre-accueil')")],
        ['label' => 'Ont commencé le formulaire', 'n' => $people("name = 'formulaire' AND label = 'debut'")],
        ['label' => 'Ont envoyé une demande', 'n' => $people("name = 'formulaire' AND label = 'envoye'")],
    ];
}

function stat_live(): int
{
    return (int) stat_value('SELECT COUNT(DISTINCT visitor) FROM views WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 300)]);
}

function format_duration(int $seconds): string
{
    if ($seconds <= 0) {
        return '—';
    }
    if ($seconds < 60) {
        return $seconds . ' s';
    }
    return intdiv($seconds, 60) . ' min ' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
}

function format_number(int|float $n): string
{
    return number_format((float) $n, 0, ',', "\u{202F}");
}
