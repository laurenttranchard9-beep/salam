<?php
declare(strict_types=1);

/*
 * Remplit la base avec des données FICTIVES pour essayer l'espace gestion en local.
 * À ne jamais lancer sur le site en ligne.
 *
 *   php tools/seed-demo.php            ajoute 90 jours de statistiques et 12 messages
 *   php tools/seed-demo.php --reset    vide d'abord messages et statistiques
 */

if (PHP_SAPI !== 'cli') {
    exit("À lancer en ligne de commande uniquement.\n");
}

$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__) . '/www/includes/bootstrap.php';
require dirname(__DIR__) . '/www/includes/content.php';

$pdo = db();
if (in_array('--reset', $argv, true)) {
    foreach (['messages', 'hits', 'views', 'events', 'consents'] as $table) {
        $pdo->exec("DELETE FROM $table");
    }
}

mt_srand(42);
$pick = static function (array $weights): string {
    $r = mt_rand(1, array_sum($weights));
    foreach ($weights as $value => $w) {
        if (($r -= $w) <= 0) {
            return (string) $value;
        }
    }
    return (string) array_key_first($weights);
};
$hex = static fn (int $bytes): string => bin2hex(random_bytes($bytes));

$sources = ['Direct' => 34, 'Google' => 30, 'QR code' => 14, 'Facebook' => 9, 'Instagram' => 8, 'auxsaveursbraisee.fr' => 3, 'fleurdor31.fr' => 2];
$devices = ['Mobile' => 68, 'Ordinateur' => 27, 'Tablette' => 5];
$browsers = ['Mobile' => ['Safari' => 55, 'Chrome' => 38, 'Samsung Internet' => 7], 'Ordinateur' => ['Chrome' => 60, 'Safari' => 18, 'Firefox' => 12, 'Edge' => 10], 'Tablette' => ['Safari' => 70, 'Chrome' => 30]];
$systems = ['Mobile' => ['iOS' => 55, 'Android' => 45], 'Ordinateur' => ['Windows' => 58, 'macOS' => 38, 'Linux' => 4], 'Tablette' => ['iOS' => 70, 'Android' => 30]];
$hours = [9 => 3, 10 => 5, 11 => 8, 12 => 11, 13 => 9, 14 => 6, 15 => 5, 16 => 5, 17 => 6, 18 => 8, 19 => 10, 20 => 9, 21 => 7, 22 => 4, 23 => 2, 8 => 2, 7 => 1];
$cuisineKeys = array_keys(demo_cuisines());

$pdo->exec('BEGIN');
$hitStmt = $pdo->prepare('INSERT INTO hits (day, path, device, source, n) VALUES (?, ?, ?, ?, 1) ON CONFLICT (day, path, device, source) DO UPDATE SET n = n + 1');
$viewStmt = $pdo->prepare('INSERT INTO views (view_id, created_at, day, hour, weekday, visitor, session, path, source, device, browser, os, screen, lang, duration, scroll) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$eventStmt = $pdo->prepare('INSERT INTO events (created_at, day, visitor, session, name, label, path) VALUES (?, ?, ?, ?, ?, ?, ?)');
$consentStmt = $pdo->prepare('INSERT INTO consents (day, choice, n) VALUES (?, ?, 1) ON CONFLICT (day, choice) DO UPDATE SET n = n + 1');

for ($d = 89; $d >= 0; $d--) {
    $ts = strtotime("-$d days");
    $day = date('Y-m-d', $ts);
    $weekday = (int) date('N', $ts);
    $trend = 1 + (89 - $d) / 120;                     // fréquentation qui monte doucement
    $weekend = $weekday >= 5 ? 1.35 : 1.0;
    $visits = (int) round(mt_rand(9, 16) * $trend * $weekend);
    for ($v = 0; $v < $visits; $v++) {
        $device = $pick($devices);
        $source = $pick($sources);
        $pages = mt_rand(1, 10) > 8 ? 2 : 1;
        $consent = mt_rand(1, 100) <= 64;
        $consentStmt->execute([$day, $consent ? 'oui' : 'non']);
        $visitor = $hex(16);
        $session = $hex(8);
        $hour = (int) $pick($hours);
        for ($p = 0; $p < $pages; $p++) {
            $path = $p === 0 ? '/' : (mt_rand(0, 1) ? '/confidentialite' : '/mentions-legales');
            $hitStmt->execute([$day, $path, $device, $p === 0 ? $source : 'Interne']);
            if ($consent) {
                $created = $day . sprintf(' %02d:%02d:%02d', $hour, mt_rand(0, 59), mt_rand(0, 59));
                $viewStmt->execute([$hex(12), $created, $day, $hour, $weekday, $visitor, $session, $path, $p === 0 ? $source : 'Interne', $device,
                    $pick($browsers[$device]), $pick($systems[$device]), $device === 'Mobile' ? 390 : 1440, 'fr-FR', mt_rand(12, 240), mt_rand(25, 100)]);
            }
        }
        if ($consent) {
            $event = static function (string $name, string $label) use ($eventStmt, $day, $hour, $visitor, $session): void {
                $eventStmt->execute([$day . sprintf(' %02d:%02d:00', $hour, mt_rand(0, 59)), $day, $visitor, $session, $name, $label, '/']);
            };
            if (mt_rand(1, 100) <= 45) {
                $event('offre', $pick(['carte' => 3, 'gestion' => 5, 'site' => 4, 'sur-mesure' => 1]));
            }
            if (mt_rand(1, 100) <= 35) {
                $event('demo-cuisine', $cuisineKeys[mt_rand(0, count($cuisineKeys) - 1)]);
            }
            if (mt_rand(1, 100) <= 20) {
                $event('faq', (string) mt_rand(1, 8));
            }
            if (mt_rand(1, 100) <= 30) {
                $event('cta', $pick(['hero-gamme' => 6, 'header-contact' => 3, 'footer-devis' => 2, 'menu-devis' => 1, 'faq-contact' => 1]));
            }
            if (mt_rand(1, 100) <= 12) {
                $event('realisation', $pick(['asb-visite' => 3, 'fdo-visite' => 3, 'hero-asb' => 2, 'hero-fdo' => 2]));
            }
            if (mt_rand(1, 100) <= 9) {
                $event('formulaire', 'debut');
                if (mt_rand(1, 100) <= 45) {
                    $event('formulaire', 'envoye');
                }
            }
        }
    }
}

$people = [
    ['Sophie Garnier', 'Pizzeria Da Sofia', 'Pizzeria', 'gestion', ['qr', 'domaine'], 'De 30 à 100 plats', "Bonjour, je change mes pizzas du moment chaque mois et j'aimerais pouvoir modifier la carte moi-même depuis mon téléphone. Est-ce possible d'avoir un QR code par table ?"],
    ['Karim Belkacem', 'Le Comptoir de Karim', 'Brasserie ou bistrot', 'site', ['emporter', 'photos'], 'Plus de 100 plats', "On fait beaucoup de vente à emporter le soir. Le système « Ma liste » de La Fleur d'Or m'intéresse beaucoup. On peut en parler ?"],
    ['Julie Marchand', 'Crêperie des Halles', 'Restaurant', 'carte', ['qr'], 'Moins de 30 plats', 'Juste une carte simple, lisible sur téléphone, avec nos galettes et crêpes. Budget serré.'],
    ['Thomas Nguyen', 'Pho Saigon', 'Restaurant', 'indecis', ['traduction'], 'De 30 à 100 plats', "Beaucoup de touristes l'été : une carte en anglais serait idéale. Quels sont les délais ?"],
    ['Marion Lefèvre', 'Food truck La Belle Frite', 'Food truck', 'carte', [], 'Moins de 30 plats', 'Je change d\'emplacement chaque jour, est-ce que je peux afficher où je suis ?'],
    ['Antoine Roux', 'Bistrot du Marché', 'Brasserie ou bistrot', 'sur-mesure', ['domaine', 'traduction'], 'De 30 à 100 plats', "J'ai déjà un site vitrine, je voudrais seulement une carte avec l'ardoise du jour modifiable."],
];
$statuses = ['nouveau', 'nouveau', 'en_cours', 'traite', 'nouveau', 'en_cours'];
$msgStmt = $pdo->prepare('INSERT INTO messages (created_at, name, business, email, phone, business_type, offer, options, menu_size, callback, message, status, read_at, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
foreach ($people as $i => [$name, $business, $type, $offer, $options, $size, $text]) {
    $created = date('Y-m-d H:i:s', strtotime('-' . ($i * 2 + mt_rand(0, 1)) . ' days -' . mt_rand(1, 9) . ' hours'));
    $email = strtolower(str_replace(' ', '.', iconv('UTF-8', 'ASCII//TRANSLIT', $name))) . '@exemple.fr';
    $msgStmt->execute([$created, $name, $business, $email, $i % 2 ? '06 ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99) : '', $type, $offer, json_encode($options), $size, $i % 3 === 1 ? 1 : 0, $text, $statuses[$i], $i < 2 ? null : $created, ['Google', 'QR code', 'Direct', 'Instagram', '', 'Google'][$i]]);
}
$pdo->exec('COMMIT');

echo "Données de démonstration ajoutées.\n";
