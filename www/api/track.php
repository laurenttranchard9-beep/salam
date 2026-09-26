<?php
declare(strict_types=1);

/*
 * Reçoit les mesures envoyées par assets/js/consent.js.
 * Répond toujours 204 (rien à renvoyer au navigateur).
 */

require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/analytics.php';

header('Cache-Control: no-store');

$done = static function (): never {
    http_response_code(204);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
// Les visites du propriétaire (cookie posé à la connexion à l'espace gestion) et les robots ne comptent pas.
if (($_COOKIE['miam_exclude'] ?? '') === '1' || ua_is_bot($ua)) {
    $done();
}

// Uniquement depuis le site lui-même
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '' && strtolower((string) parse_url($origin, PHP_URL_HOST)) !== strtolower((string) parse_url(site_origin(), PHP_URL_HOST))) {
    $done();
}

$raw = file_get_contents('php://input', false, null, 0, 4096);
$data = json_decode($raw === false ? '' : $raw, true);
if (!is_array($data)) {
    $done();
}

$pdo = db();
$day = date('Y-m-d');
$consent = has_analytics_consent();
$visitor = $consent ? (string) $_COOKIE['miam_id'] : '';
$isHex = static fn (mixed $v, int $len): bool => is_string($v) && preg_match('/^[a-f0-9]{' . $len . '}$/', $v) === 1;

try {
    switch ($data['t'] ?? '') {
        case 'view':
            $path = normalize_path($data['path'] ?? '/');
            $referrer = is_string($data['ref'] ?? null) ? mb_substr($data['ref'], 0, 500) : '';
            $utm = is_array($data['utm'] ?? null) ? $data['utm'] : [];
            $utmValue = static fn (string $key): string => clean_text($utm[$key] ?? '', 80);
            $source = traffic_source($referrer, $utmValue('source'));
            $device = device_from_ua($ua);

            // 1. Compteur anonyme : pour tous les visiteurs, sans identifiant
            if (!empty($data['hit'])) {
                $pdo->prepare('INSERT INTO hits (day, path, device, source, n) VALUES (?, ?, ?, ?, 1)
                               ON CONFLICT (day, path, device, source) DO UPDATE SET n = n + 1')
                    ->execute([$day, $path, $device, $source]);
            }

            // 2. Détail de la visite : seulement avec le consentement (vérifié ici, côté serveur)
            if ($consent && $isHex($data['id'] ?? null, 24) && $isHex($data['s'] ?? null, 16)) {
                $referrerHost = $referrer !== '' ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
                $pdo->prepare('INSERT OR IGNORE INTO views
                    (view_id, created_at, day, hour, weekday, visitor, session, path, source, referrer,
                     utm_source, utm_medium, utm_campaign, device, browser, os, screen, lang)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([
                        $data['id'], now(), $day, (int) date('G'), (int) date('N'),
                        $visitor, $data['s'], $path, $source, mb_substr($referrerHost, 0, 120),
                        $utmValue('source'), $utmValue('medium'), $utmValue('campaign'),
                        $device, browser_from_ua($ua), os_from_ua($ua),
                        max(0, min(10000, (int) ($data['w'] ?? 0))) ?: null,
                        clean_text($data['lang'] ?? '', 12),
                    ]);
            }
            break;

        case 'leave':
            if ($consent && $isHex($data['id'] ?? null, 24)) {
                $duration = max(0, min(4 * 3600, (int) ($data['d'] ?? 0)));
                $scroll = max(0, min(100, (int) ($data['sc'] ?? 0)));
                $pdo->prepare('UPDATE views SET duration = MAX(COALESCE(duration, 0), ?), scroll = MAX(COALESCE(scroll, 0), ?)
                               WHERE view_id = ? AND visitor = ?')
                    ->execute([$duration, $scroll, $data['id'], $visitor]);
            }
            break;

        case 'event':
            $name = (string) ($data['n'] ?? '');
            if ($consent && preg_match('/^[a-z0-9\-]{1,40}$/', $name) && $isHex($data['s'] ?? null, 16)) {
                $pdo->prepare('INSERT INTO events (created_at, day, visitor, session, name, label, path) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([now(), $day, $visitor, $data['s'], $name, clean_text($data['l'] ?? '', 80), normalize_path($data['path'] ?? '/')]);
            }
            break;

        case 'consent':
            $choice = $data['v'] ?? '';
            if ($choice === 'oui' || $choice === 'non') {
                $pdo->prepare('INSERT INTO consents (day, choice, n) VALUES (?, ?, 1)
                               ON CONFLICT (day, choice) DO UPDATE SET n = n + 1')
                    ->execute([$day, $choice]);
            }
            break;
    }
} catch (Throwable $e) {
    error_log('Mesure d\'audience : ' . $e->getMessage());
}

$done();
