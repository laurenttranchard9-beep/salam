<?php
declare(strict_types=1);

/*
 * Espace gestion : messages du formulaire, statistiques, réglages.
 * Toutes les pages passent par ce fichier : /admin/?p=messages, /admin/?p=stats…
 */

require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/content.php';
require dirname(__DIR__) . '/includes/analytics.php';
require dirname(__DIR__) . '/includes/mailer.php';
require dirname(__DIR__) . '/includes/admin/auth.php';
require dirname(__DIR__) . '/includes/admin/stats.php';
require dirname(__DIR__) . '/includes/admin/helpers.php';

admin_session_start();
send_page_headers(true);

$page = (string) ($_GET['p'] ?? 'tableau');
$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

// ---------------------------------------------------------------------------
// 1. Premier lancement (ou réinitialisation) : création du compte
// ---------------------------------------------------------------------------
if (admin_count() === 0 || admin_reset_requested()) {
    $error = null;
    $username = '';
    if ($isPost && ($_POST['action'] ?? '') === 'setup') {
        $username = clean_text($_POST['username'] ?? '', 60);
        $password = (string) ($_POST['password'] ?? '');
        if (!csrf_valid()) {
            $error = 'La page a expiré, merci de réessayer.';
        } elseif (!preg_match('/^[\p{L}0-9._@\-]{3,60}$/u', $username)) {
            $error = "L'identifiant doit faire au moins 3 caractères (lettres, chiffres, point, tiret).";
        } else {
            $error = validate_new_password($password, (string) ($_POST['password_confirm'] ?? ''));
        }
        if ($error === null) {
            try {
                create_admin($username, $password);
                attempt_login($username, $password);
                flash('ok', 'Votre compte est créé. Bienvenue dans votre espace gestion !');
                redirect(admin_url());
            } catch (Throwable $e) {
                $error = 'Le compte existe déjà. Connectez-vous.';
            }
        }
    }
    render_view('setup', ['error' => $error, 'username' => $username, 'reset' => admin_reset_requested()], false);
    exit;
}

// ---------------------------------------------------------------------------
// 2. Connexion
// ---------------------------------------------------------------------------
if (!current_admin()) {
    $error = null;
    $username = '';
    if ($isPost && ($_POST['action'] ?? '') === 'login') {
        $username = clean_text($_POST['username'] ?? '', 60);
        $error = csrf_valid() ? attempt_login($username, (string) ($_POST['password'] ?? '')) : 'La page a expiré, merci de réessayer.';
        if ($error === null) {
            $next = (string) ($_POST['next'] ?? '');
            redirect(preg_match('/^p=[a-z]+(&[a-z_]+=[\w\-%]*)*$/', $next) ? url('admin/') . '?' . $next : admin_url());
        }
        usleep(400000); // freine les essais en série
    }
    $next = (string) ($_SERVER['QUERY_STRING'] ?? '');
    render_view('login', ['error' => $error, 'username' => $username, 'next' => $next], false);
    exit;
}

// ---------------------------------------------------------------------------
// 3. Actions (formulaires envoyés en POST, protégés par jeton CSRF)
// ---------------------------------------------------------------------------
if ($isPost) {
    if (!csrf_valid()) {
        flash('error', 'La page a expiré, votre action n\'a pas été enregistrée. Réessayez.');
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        redirect(url('admin/') . ($query !== '' ? '?' . $query : ''));
    }
    $pdo = db();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $back = (string) ($_POST['back'] ?? '');
    $backUrl = str_starts_with($back, url('admin/')) ? $back : admin_url();

    switch ($action) {
        case 'logout':
            logout();
            header('Location: ' . admin_url(), true, 303);
            exit;

        case 'message_status':
            $status = (string) ($_POST['status'] ?? '');
            if (isset(message_statuses()[$status])) {
                $pdo->prepare('UPDATE messages SET status = ?, updated_at = ? WHERE id = ?')->execute([$status, now(), $id]);
                flash('ok', 'Statut mis à jour : ' . message_statuses()[$status]['label'] . '.');
            }
            redirect($backUrl);

        case 'message_note':
            $pdo->prepare('UPDATE messages SET note = ?, updated_at = ? WHERE id = ?')
                ->execute([clean_text($_POST['note'] ?? '', 5000, true), now(), $id]);
            flash('ok', 'Note enregistrée.');
            redirect(admin_url('message', ['id' => $id]));

        case 'message_unread':
            $pdo->prepare('UPDATE messages SET read_at = NULL WHERE id = ?')->execute([$id]);
            flash('ok', 'Message marqué comme non lu.');
            redirect(admin_url('messages'));

        case 'message_delete':
            $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            flash('ok', 'Message supprimé définitivement.');
            redirect(admin_url('messages'));

        case 'messages_read_all':
            $pdo->prepare("UPDATE messages SET read_at = ? WHERE read_at IS NULL AND status != 'spam'")->execute([now()]);
            flash('ok', 'Tous les messages sont marqués comme lus.');
            redirect($backUrl);

        case 'settings':
            $email = trim((string) ($_POST['notify_email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', "L'adresse de notification n'est pas valide.");
                redirect(admin_url('reglages'));
            }
            $publicEmail = trim((string) ($_POST['public_email'] ?? ''));
            if ($publicEmail !== '' && !filter_var($publicEmail, FILTER_VALIDATE_EMAIL)) {
                flash('error', "L'email affiché sur le site n'est pas valide.");
                redirect(admin_url('reglages'));
            }
            set_setting('notify_email', $email);
            set_setting('notify_enabled', !empty($_POST['notify_enabled']) ? '1' : '0');
            set_setting('public_phone', clean_text($_POST['public_phone'] ?? '', 30));
            set_setting('public_email', $publicEmail);
            set_setting('zone', clean_text($_POST['zone'] ?? '', 80));
            foreach (array_keys(offers()) as $key) {
                set_setting('price_' . $key, clean_text($_POST['price_' . $key] ?? '', 40));
            }
            flash('ok', 'Réglages enregistrés. Le site est à jour.');
            redirect(admin_url('reglages'));

        case 'test_email':
            $to = trim((string) setting('notify_email', ''));
            $sent = send_mail($to, 'Test : les notifications fonctionnent', "Bonjour,\n\nSi vous lisez cet email, les notifications de " . site_name() . " arrivent bien.\n\nÀ chaque nouveau message du formulaire de contact, vous recevrez un email comme celui-ci.\n\n" . absolute_url('admin/'));
            flash($sent ? 'ok' : 'error', $sent
                ? "Email de test envoyé à $to. Pensez à regarder dans les indésirables."
                : "L'email n'a pas pu partir. Vérifiez l'adresse, ou demandez à votre hébergeur si la fonction mail() de PHP est active.");
            redirect(admin_url('reglages'));

        case 'exclude':
            $on = !empty($_POST['exclude']);
            set_setting('exclude_self', $on ? '1' : '0');
            set_exclude_cookie($on);
            flash('ok', $on ? 'Vos visites depuis cet appareil ne sont plus comptées.' : 'Vos visites depuis cet appareil sont de nouveau comptées.');
            redirect(admin_url('reglages'));

        case 'stats_reset':
            if (($_POST['confirm'] ?? '') === 'EFFACER') {
                foreach (['hits', 'views', 'events', 'consents'] as $table) {
                    $pdo->exec("DELETE FROM $table");
                }
                flash('ok', 'Toutes les statistiques ont été effacées.');
            } else {
                flash('error', 'Tapez EFFACER pour confirmer.');
            }
            redirect(admin_url('reglages'));

        case 'password':
            $admin = current_admin();
            $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
            $stmt->execute([$admin['id']]);
            if (!password_verify((string) ($_POST['current'] ?? ''), (string) $stmt->fetchColumn())) {
                flash('error', 'Le mot de passe actuel est incorrect.');
            } elseif ($problem = validate_new_password((string) ($_POST['new'] ?? ''), (string) ($_POST['confirm'] ?? ''))) {
                flash('error', $problem);
            } else {
                $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash((string) $_POST['new'], PASSWORD_DEFAULT), $admin['id']]);
                session_regenerate_id(true);
                flash('ok', 'Mot de passe modifié.');
            }
            redirect(admin_url('reglages'));
    }
    redirect(admin_url());
}

// ---------------------------------------------------------------------------
// 4. Pages
// ---------------------------------------------------------------------------
purge_old_analytics();
db()->prepare("DELETE FROM messages WHERE created_at < ? AND status IN ('traite', 'archive', 'spam')")
    ->execute([date('Y-m-d H:i:s', strtotime('-3 years'))]);

switch ($page) {
    case 'export':
        export_messages_csv();
        exit;
    case 'message':
        // Marqué comme lu avant l'affichage, pour que le compteur du menu soit juste
        db()->prepare('UPDATE messages SET read_at = ? WHERE id = ? AND read_at IS NULL')->execute([now(), (int) ($_GET['id'] ?? 0)]);
        render_view($page);
        break;
    case 'messages':
    case 'stats':
    case 'reglages':
    case 'tableau':
        render_view($page);
        break;
    default:
        http_response_code(404);
        render_view('introuvable');
}
