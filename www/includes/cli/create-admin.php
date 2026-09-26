<?php
/*
 * Création du compte de l'espace gestion en ligne de commande (utilisé par install.sh).
 * Le compte est créé tout de suite : personne d'autre ne peut le réclamer depuis le web.
 *
 *   php includes/cli/create-admin.php identifiant < fichier-contenant-le-mot-de-passe
 *   php includes/cli/create-admin.php --existe      (code 0 si un compte existe déjà)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/admin/auth.php';

$arg = (string) ($argv[1] ?? '');
if ($arg === '--existe') {
    exit(admin_count() > 0 ? 0 : 1);
}

$username = $arg;
$password = rtrim((string) stream_get_contents(STDIN), "\r\n");

if (!preg_match('/^[\p{L}0-9._@\-]{3,60}$/u', $username)) {
    fwrite(STDERR, "L'identifiant doit faire au moins 3 caractères (lettres, chiffres, point, tiret).\n");
    exit(2);
}
$error = validate_new_password($password, $password);
if ($error !== null) {
    fwrite(STDERR, $error . "\n");
    exit(2);
}
if (admin_count() > 0 && !admin_reset_requested()) {
    fwrite(STDERR, "Un compte existe déjà.\n");
    exit(3);
}
create_admin($username, $password);
echo "Compte « {$username} » créé.\n";
