<?php
declare(strict_types=1);

/*
 * Outils de la mesure d'audience : appareil, navigateur, source de trafic.
 * Tout est déduit d'informations envoyées par n'importe quel navigateur
 * (User-Agent, page d'origine) : rien n'est lu sur l'appareil du visiteur.
 */

function ua_is_bot(string $ua): bool
{
    if ($ua === '' || strlen($ua) < 20) {
        return true;
    }
    return preg_match('/bot|crawl|spider|slurp|mediapartners|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|preview|facebookexternalhit|embedly|whatsapp|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|phantom|puppeteer|playwright|selenium|scrapy/i', $ua) === 1;
}

function device_from_ua(string $ua): string
{
    if (preg_match('/iPad|Tablet|PlayBook|Silk|Android(?!.*Mobile)/i', $ua)) {
        return 'Tablette';
    }
    if (preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $ua)) {
        return 'Mobile';
    }
    return 'Ordinateur';
}

function browser_from_ua(string $ua): string
{
    return match (true) {
        (bool) preg_match('/Edg(e|A|iOS)?\//', $ua) => 'Edge',
        (bool) preg_match('/OPR\/|Opera/', $ua) => 'Opera',
        (bool) preg_match('/SamsungBrowser/', $ua) => 'Samsung Internet',
        (bool) preg_match('/Firefox|FxiOS/', $ua) => 'Firefox',
        (bool) preg_match('/Chrome|CriOS|Chromium/', $ua) => 'Chrome',
        (bool) preg_match('/Safari/', $ua) => 'Safari',
        default => 'Autre',
    };
}

function os_from_ua(string $ua): string
{
    return match (true) {
        (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
        (bool) preg_match('/Android/', $ua) => 'Android',
        (bool) preg_match('/Windows/', $ua) => 'Windows',
        (bool) preg_match('/CrOS/', $ua) => 'ChromeOS',
        (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
        (bool) preg_match('/Linux/', $ua) => 'Linux',
        default => 'Autre',
    };
}

/** « Google », « Facebook », « QR code », « Direct »… */
function traffic_source(string $referrer, string $utmSource = ''): string
{
    $utm = strtolower(trim($utmSource));
    if ($utm !== '') {
        return match (true) {
            in_array($utm, ['qr', 'qrcode', 'qr-code', 'qr_code'], true) => 'QR code',
            str_contains($utm, 'google') => 'Google',
            in_array($utm, ['facebook', 'fb'], true) => 'Facebook',
            in_array($utm, ['instagram', 'ig'], true) => 'Instagram',
            str_contains($utm, 'mail') || str_contains($utm, 'newsletter') => 'Email',
            default => mb_substr(ucfirst(preg_replace('/[^a-z0-9 ._\-]/i', '', $utm) ?? ''), 0, 40) ?: 'Autre',
        };
    }
    if ($referrer === '') {
        return 'Direct';
    }
    $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
    if ($host === '') {
        return 'Direct';
    }
    $own = strtolower((string) parse_url(site_origin(), PHP_URL_HOST));
    if ($host === $own || preg_replace('/^www\./', '', $host) === preg_replace('/^www\./', '', $own)) {
        return 'Interne';
    }
    $known = [
        '/(^|\.)google\./' => 'Google',
        '/(^|\.)bing\.com$/' => 'Bing',
        '/duckduckgo\./' => 'DuckDuckGo',
        '/qwant\./' => 'Qwant',
        '/ecosia\./' => 'Ecosia',
        '/yahoo\./' => 'Yahoo',
        '/(^|\.)(facebook\.com|fb\.com|fb\.me)$/' => 'Facebook',
        '/instagram\.com$/' => 'Instagram',
        '/(linkedin\.com|lnkd\.in)$/' => 'LinkedIn',
        '/(^|\.)(t\.co|twitter\.com|x\.com)$/' => 'X (Twitter)',
        '/tiktok\.com$/' => 'TikTok',
        '/pinterest\./' => 'Pinterest',
        '/whatsapp\./' => 'WhatsApp',
        '/(youtube\.com|youtu\.be)$/' => 'YouTube',
        '/(mail\.|outlook\.|gmail)/' => 'Email',
    ];
    foreach ($known as $pattern => $label) {
        if (preg_match($pattern, $host)) {
            return $label;
        }
    }
    return mb_substr(preg_replace('/^www\./', '', $host) ?? $host, 0, 60);
}

/** Chemin de page propre, relatif au site (« / », « /confidentialite »…). */
function normalize_path(mixed $path): string
{
    $path = is_string($path) ? $path : '/';
    $path = (string) parse_url($path, PHP_URL_PATH);
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . ltrim($path, '/');
    if ($path === '/index.php') {
        $path = '/';
    }
    return preg_match('~^/[a-z0-9\-_./]{0,100}$~i', $path) ? $path : '/autre';
}

/** Libellés lisibles pour les pages et les événements, dans l'espace gestion. */
function page_label(string $path): string
{
    return [
        '/' => 'Accueil',
        '/formules' => 'Formules',
        '/demo' => 'La démo',
        '/realisations' => 'Réalisations',
        '/methode' => 'Méthode',
        '/contact' => 'Contact',
        '/mentions-legales' => 'Mentions légales',
        '/confidentialite' => 'Confidentialité',
    ][$path] ?? $path;
}

function event_label(string $name, string $label): string
{
    $offers = function_exists('offers') ? offers() : [];
    $offerName = static fn (string $k): string => $offers[$k]['name'] ?? $k;
    return match ($name) {
        'offre' => 'Fiche ouverte : ' . $offerName($label),
        'offre-choisie' => 'Formule choisie : ' . $offerName($label),
        'demo-cuisine' => 'Démo, cuisine : ' . ucfirst($label),
        'demo-vue' => 'Démo, vue : ' . ucfirst($label),
        'demo' => 'Démo, action : ' . $label,
        'realisation' => 'Réalisation : ' . $label,
        'formulaire' => 'Formulaire : ' . $label,
        'faq' => 'Question n° ' . $label . ' ouverte',
        'cta' => 'Bouton : ' . $label,
        'tel' => 'Appel : ' . $label,
        'mail' => 'Email : ' . $label,
        'menu' => 'Menu : ' . $label,
        default => $name . ($label !== '' ? ' : ' . $label : ''),
    };
}

/** Efface les données de mesure trop anciennes (13 mois pour le détail, 25 mois pour les totaux). */
function purge_old_analytics(): void
{
    $pdo = db();
    $detail = date('Y-m-d', strtotime('-395 days'));
    $totals = date('Y-m-d', strtotime('-760 days'));
    $pdo->prepare('DELETE FROM views WHERE day < ?')->execute([$detail]);
    $pdo->prepare('DELETE FROM events WHERE day < ?')->execute([$detail]);
    $pdo->prepare('DELETE FROM hits WHERE day < ?')->execute([$totals]);
    $pdo->prepare('DELETE FROM consents WHERE day < ?')->execute([$totals]);
}
