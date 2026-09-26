<?php
declare(strict_types=1);

/*
 * Pour essayer le site sur votre ordinateur sans installer Apache :
 *
 *   php -S localhost:8000 -t www tools/dev-router.php
 *
 * puis ouvrez http://localhost:8000. Ce fichier reproduit les règles du .htaccess.
 * En ligne, c'est Apache et le .htaccess qui font ce travail : ne l'envoyez pas sur le serveur.
 */

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$root = (string) realpath(__DIR__ . '/../www');
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Fichiers et dossiers internes
if (preg_match('~^/(includes|data)(/|$)|^/config\.php$|/\.~', $path)) {
    http_response_code(403);
    exit('Accès interdit');
}

$routes = [
    '/formules' => ['formules.php', []],
    '/demo' => ['demo.php', []],
    '/realisations' => ['realisations.php', []],
    '/menus' => ['menus.php', []],
    '/methode' => ['methode.php', []],
    '/contact' => ['contact.php', []],
    '/mentions-legales' => ['legal.php', ['page' => 'mentions']],
    '/confidentialite' => ['legal.php', ['page' => 'confidentialite']],
    '/robots.txt' => ['robots.php', []],
    '/sitemap.xml' => ['sitemap.php', []],
];
$route = $routes[rtrim($path, '/')] ?? null;

if ($route === null) {
    $file = $root . $path;
    if (is_file($file) || (is_dir($file) && is_file(rtrim($file, '/') . '/index.php'))) {
        return false; // le serveur intégré de PHP sert le fichier lui-même
    }
    $route = ['404.php', []];
}

[$script, $query] = $route;
$_GET += $query;
$_SERVER['SCRIPT_NAME'] = '/' . $script;
$_SERVER['SCRIPT_FILENAME'] = $root . '/' . $script;
chdir($root);
require $root . '/' . $script;
