<?php
declare(strict_types=1);

/*
 * Réception du formulaire de contact.
 * Répond en JSON à la page (envoi sans rechargement), ou redirige
 * vers l'accueil si le navigateur n'exécute pas JavaScript.
 */

require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/content.php';
require dirname(__DIR__) . '/includes/mailer.php';

$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

/** Réponse JSON pour la page, ou redirection pour un envoi classique. */
$reply = static function (int $status, array $data, string $query) use ($wantsJson): never {
    if ($wantsJson) {
        json_response($data, $status);
    }
    header('Location: ' . url('contact') . $query . '#formulaire', true, 303);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . url('contact'), true, 303);
    exit;
}

// Piège à robots : ce champ est invisible pour les humains
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    $reply(200, ['ok' => true], '?envoi=ok');
}

$token = check_form_token((string) ($_POST['token'] ?? ''));
if ($token === 'trop-rapide') {
    $reply(400, ['ok' => false, 'error' => 'rapide', 'message' => 'Une petite seconde… nouvel essai en cours.'], '?erreur=jeton');
}
if ($token !== 'ok') {
    $reply(400, ['ok' => false, 'error' => 'jeton', 'token' => form_token(), 'message' => 'Le formulaire avait expiré. Merci de le renvoyer.'], '?erreur=jeton');
}

$offers = offers();
$name = clean_text($_POST['name'] ?? '', 100);
$business = clean_text($_POST['business'] ?? '', 120);
$email = clean_text($_POST['email'] ?? '', 160);
$phone = clean_text($_POST['phone'] ?? '', 30);
$businessType = in_array($_POST['business_type'] ?? '', business_types(), true) ? (string) $_POST['business_type'] : '';
$menuSize = in_array($_POST['menu_size'] ?? '', menu_sizes(), true) ? (string) $_POST['menu_size'] : '';
$offer = (string) ($_POST['offer'] ?? '');
$offer = isset($offers[$offer]) ? $offer : 'indecis';
$options = array_values(array_intersect(array_keys(contact_options()), array_map('strval', (array) ($_POST['options'] ?? []))));
$callback = !empty($_POST['callback']) ? 1 : 0;
$message = clean_text($_POST['message'] ?? '', 5000, true);

$errors = [];
if (mb_strlen($name) < 2) {
    $errors['name'] = 'Indiquez votre nom.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Cette adresse email ne semble pas valide.';
}
if ($phone !== '' && !preg_match('/^[+0-9 ().\-]{6,30}$/', $phone)) {
    $errors['phone'] = 'Ce numéro ne semble pas valide.';
}
if (mb_strlen($message) < 10) {
    $errors['message'] = "Dites-m'en un peu plus (10 caractères minimum).";
}
if ($errors) {
    $reply(422, ['ok' => false, 'errors' => $errors, 'message' => 'Quelques champs sont à corriger.'], '?erreur=champs');
}

// 5 messages par heure au plus depuis une même connexion
if (!throttle('contact:' . client_fingerprint(), 5, 3600)) {
    $reply(429, ['ok' => false, 'error' => 'trop', 'message' => 'Trop de messages envoyés depuis votre connexion. Réessayez dans une heure.'], '?erreur=trop');
}

// Beaucoup de liens : très probablement du spam, rangé à part plutôt que supprimé
$links = preg_match_all('~https?://|www\.~i', $message);
$status = $links > 3 ? 'spam' : 'nouveau';

// D'où vient ce prospect ? (seulement si la mesure d'audience a été acceptée)
$source = '';
if (has_analytics_consent()) {
    $stmt = db()->prepare('SELECT source FROM views WHERE visitor = ? ORDER BY id ASC LIMIT 1');
    $stmt->execute([$_COOKIE['miam_id']]);
    $source = (string) ($stmt->fetchColumn() ?: '');
}

$row = [
    'created_at' => now(),
    'name' => $name,
    'business' => $business,
    'email' => $email,
    'phone' => $phone,
    'business_type' => $businessType,
    'offer' => $offer,
    'options' => json_encode($options),
    'menu_size' => $menuSize,
    'callback' => $callback,
    'message' => $message,
    'status' => $status,
    'source' => $source,
];

try {
    $columns = array_keys($row);
    db()->prepare('INSERT INTO messages (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')')
        ->execute(array_values($row));
    $id = (int) db()->lastInsertId();
} catch (Throwable $e) {
    error_log('Message non enregistré : ' . $e->getMessage());
    $reply(500, ['ok' => false, 'message' => "Le message n'a pas pu être enregistré. Réessayez dans un instant."], '?erreur=serveur');
}

if ($status !== 'spam') {
    send_new_message_notification($id, $row);
}

$reply(200, ['ok' => true], '?envoi=ok');
