<?php
declare(strict_types=1);

/*
 * Configuration, base de données, fonctions communes.
 * Chargé par bootstrap.php, une fois l'environnement vérifié.
 */

define('APP_ROOT', dirname(__DIR__));

$GLOBALS['config'] = require APP_ROOT . '/config.php';
date_default_timezone_set((string) ($GLOBALS['config']['timezone'] ?? 'Europe/Paris'));
mb_internal_encoding('UTF-8');
ini_set('display_errors', '0');

require_once __DIR__ . '/db.php';

/** Lit une valeur de config.php, avec la notation « legal.siret ». */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
}

/**
 * Chemin du site depuis la racine du domaine ('' ou '/sous-dossier').
 * Permet d'installer le site à la racine comme dans un sous-dossier.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = (string) config('base_url', '');
    if ($configured !== '') {
        return $base = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $file = str_replace('\\', '/', (string) (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: ''));
    $root = str_replace('\\', '/', (string) (realpath(APP_ROOT) ?: APP_ROOT));
    // Sous Windows (XAMPP), « C:/xampp » et « c:/Xampp » désignent le même dossier
    $same = PHP_OS_FAMILY === 'Windows'
        ? static fn (string $a, string $b): bool => strtolower($a) === strtolower($b)
        : static fn (string $a, string $b): bool => $a === $b;
    if ($file !== '' && $same(substr($file, 0, strlen($root) + 1), $root . '/')) {
        $relative = substr($file, strlen($root));
        if ($same(substr($script, -strlen($relative)), $relative)) {
            return $base = rtrim(substr($script, 0, -strlen($relative)), '/');
        }
    }
    // Repli : le dossier du script, sans les sous-dossiers connus du site
    return $base = (string) preg_replace('~/(admin|api)$~', '', rtrim(dirname($script), '/.'));
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** URL d'un fichier de assets/ avec sa date de modification, pour vider le cache à chaque mise à jour. */
function asset(string $path): string
{
    $version = @filemtime(APP_ROOT . '/assets/' . $path) ?: 1;
    return url('assets/' . $path) . '?v=' . $version;
}

function site_origin(): string
{
    $configured = (string) config('base_url', '');
    if ($configured !== '') {
        $parts = parse_url($configured);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
    $host = preg_replace('/[^a-z0-9.\-:\[\]]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

function absolute_url(string $path = ''): string
{
    return site_origin() . url($path);
}

function site_name(): string
{
    return (string) config('site_name', 'Miam');
}

/** Réglage modifiable depuis l'espace gestion (table settings), sinon valeur par défaut de config.php. */
function setting(string $key, ?string $default = null): ?string
{
    $all = settings_all();
    if (array_key_exists($key, $all)) {
        return $all[$key];
    }
    $fallback = config('defaults.' . $key);
    return $fallback !== null ? (string) $fallback : $default;
}

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache;
}

function set_setting(string $key, ?string $value): void
{
    if ($value === null) {
        db()->prepare('DELETE FROM settings WHERE key = ?')->execute([$key]);
    } else {
        db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, $value]);
    }
    settings_all(true);
}

/** Clé secrète propre à l'installation, créée au premier lancement. */
function app_secret(): string
{
    $secret = settings_all()['secret'] ?? '';
    if (strlen($secret) < 32) {
        $secret = bin2hex(random_bytes(32));
        set_setting('secret', $secret);
    }
    return $secret;
}

/**
 * Empreinte de l'adresse IP, qui change chaque jour : sert uniquement à limiter
 * les abus (connexions, envois de formulaire). L'IP elle-même n'est jamais stockée.
 */
function client_fingerprint(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return substr(hash_hmac('sha256', $ip . '|' . date('Y-m-d'), app_secret()), 0, 32);
}

/**
 * Limiteur simple : renvoie false si la clé a dépassé $max essais dans la fenêtre.
 */
function throttle(string $key, int $max, int $windowSeconds): bool
{
    $pdo = db();
    $now = time();
    $pdo->prepare('DELETE FROM throttle WHERE reset_at < ?')->execute([$now]);
    $stmt = $pdo->prepare('SELECT n FROM throttle WHERE k = ?');
    $stmt->execute([$key]);
    $count = $stmt->fetchColumn();
    if ($count === false) {
        $pdo->prepare('INSERT INTO throttle (k, n, reset_at) VALUES (?, 1, ?)')->execute([$key, $now + $windowSeconds]);
        return true;
    }
    if ((int) $count >= $max) {
        return false;
    }
    $pdo->prepare('UPDATE throttle SET n = n + 1 WHERE k = ?')->execute([$key]);
    return true;
}

function throttle_reset(string $key): void
{
    db()->prepare('DELETE FROM throttle WHERE k = ?')->execute([$key]);
}

/**
 * En-têtes de sécurité envoyés par PHP. Les autres (nosniff, Referrer-Policy…)
 * sont posés par le .htaccess.
 */
function send_page_headers(bool $private = false): void
{
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    if ($private) {
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
    }
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Jeton du formulaire de contact : date d'affichage signée.
 * Sans cookie ni session : il prouve que le formulaire a bien été affiché par le site,
 * et depuis assez longtemps pour avoir été rempli par un humain.
 */
function form_token(): string
{
    $time = (string) time();
    return $time . '.' . substr(hash_hmac('sha256', 'contact|' . $time, app_secret()), 0, 32);
}

/** Renvoie 'ok', 'trop-rapide' ou 'invalide'. */
function check_form_token(string $token, int $minSeconds = 3, int $maxSeconds = 172800): string
{
    if (!preg_match('/^(\d{10})\.([a-f0-9]{32})$/', $token, $m)) {
        return 'invalide';
    }
    $expected = substr(hash_hmac('sha256', 'contact|' . $m[1], app_secret()), 0, 32);
    if (!hash_equals($expected, $m[2])) {
        return 'invalide';
    }
    $age = time() - (int) $m[1];
    if ($age < $minSeconds) {
        return 'trop-rapide';
    }
    return $age > $maxSeconds ? 'invalide' : 'ok';
}

/** Le visiteur a-t-il accepté la mesure d'audience ? (cookie posé par consent.js) */
function has_analytics_consent(): bool
{
    return ($_COOKIE['miam_choix'] ?? '') === 'oui'
        && preg_match('/^[a-f0-9]{32}$/', (string) ($_COOKIE['miam_id'] ?? '')) === 1;
}

/** Mois en français, sans dépendre de l'extension intl. */
function format_date_fr(string $datetime, bool $withTime = true): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    static $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $text = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $withTime ? $text . ' à ' . date('H:i', $ts) : $text;
}

/** « il y a 5 min », « hier à 14:02 »… */
function relative_date_fr(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return "à l'instant";
    }
    if ($diff < 3600) {
        return 'il y a ' . intdiv($diff, 60) . ' min';
    }
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return "aujourd'hui à " . date('H:i', $ts);
    }
    if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
        return 'hier à ' . date('H:i', $ts);
    }
    return format_date_fr($datetime);
}

function str_limit(string $text, int $length): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
}

/** Nettoie une saisie texte : espaces superflus, caractères de contrôle, longueur max. */
function clean_text(mixed $value, int $max, bool $multiline = false): string
{
    $text = is_string($value) ? $value : '';
    $text = str_replace("\r\n", "\n", $text);
    $text = preg_replace($multiline ? '/[^\P{C}\n\t]/u' : '/\p{C}/u', '', $text) ?? '';
    if (!$multiline) {
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
    } else {
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? '';
    }
    return mb_substr(trim($text), 0, $max);
}
