<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
$pages = [
    ['', filemtime(__DIR__ . '/index.php'), '1.0'],
    ['formules', filemtime(__DIR__ . '/formules.php'), '0.9'],
    ['demo', filemtime(__DIR__ . '/demo.php'), '0.7'],
    ['realisations', filemtime(__DIR__ . '/realisations.php'), '0.8'],
    ['menus', filemtime(__DIR__ . '/menus.php'), '0.7'],
    ['methode', filemtime(__DIR__ . '/methode.php'), '0.7'],
    ['contact', filemtime(__DIR__ . '/contact.php'), '0.9'],
    ['mentions-legales', filemtime(__DIR__ . '/legal.php'), '0.2'],
    ['confidentialite', filemtime(__DIR__ . '/legal.php'), '0.2'],
];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as [$path, $modified, $priority]) {
    echo '  <url><loc>' . h(absolute_url($path)) . '</loc><lastmod>' . date('Y-m-d', (int) $modified) . '</lastmod><priority>' . $priority . "</priority></url>\n";
}
echo "</urlset>\n";
