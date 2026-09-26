<?php
/*
 * Chargé en premier par chaque page. Ce fichier reste compatible avec les
 * vieilles versions de PHP pour pouvoir afficher un message clair si le
 * serveur (ou XAMPP) n'a pas ce qu'il faut, au lieu d'une page blanche.
 */

// Jamais de message technique affiché aux visiteurs (XAMPP les affiche par défaut)
ini_set('display_errors', '0');

$problems = array();
if (PHP_VERSION_ID < 80100) {
    $problems[] = 'Ce site demande PHP 8.1 ou plus. Version installée : ' . PHP_VERSION . '. Avec XAMPP, installez une version récente (PHP 8.1, 8.2 ou plus).';
}
if (!extension_loaded('pdo_sqlite')) {
    $problems[] = "L'extension PHP « pdo_sqlite » n'est pas activée. Avec XAMPP : ouvrez C:\\xampp\\php\\php.ini, cherchez la ligne « ;extension=pdo_sqlite », retirez le point-virgule du début, enregistrez, puis redémarrez Apache dans le panneau XAMPP.";
}
if (!extension_loaded('mbstring')) {
    $problems[] = "L'extension PHP « mbstring » n'est pas activée. Avec XAMPP : dans C:\\xampp\\php\\php.ini, retirez le point-virgule devant « extension=mbstring », puis redémarrez Apache.";
}
if ($problems) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Configuration à compléter</title>'
        . '<body style="font:16px/1.6 system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1rem;color:#24130d">'
        . '<h1 style="font-size:1.5rem">Il manque quelque chose sur le serveur</h1><ul>';
    foreach ($problems as $problem) {
        echo '<li style="margin-bottom:.75rem">' . htmlspecialchars($problem, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul></body></html>';
    exit;
}

require __DIR__ . '/core.php';
