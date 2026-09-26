<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
$base = base_path();
echo "User-agent: *\n";
echo "Disallow: {$base}/admin/\n";
echo "Disallow: {$base}/api/\n";
echo "\nSitemap: " . absolute_url('sitemap.xml') . "\n";
