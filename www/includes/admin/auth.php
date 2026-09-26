<?php
declare(strict_types=1);

/*
 * Espace gestion : session, connexion, protection des formulaires (CSRF).
 */

const ADMIN_IDLE_SECONDS = 2 * 3600;       // déconnexion après 2 h sans activité
const ADMIN_MAX_SECONDS = 12 * 3600;       // et au plus tard 12 h après la connexion
const ADMIN_MIN_PASSWORD = 10;

function data_dir(): string
{
    return rtrim((string) config('data_dir', APP_ROOT . '/data'), '/\\');
}

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Sessions rangées avec les données du site : à l'abri du nettoyage des autres sites de l'hébergement
    $dir = data_dir() . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) ADMIN_MAX_SECONDS);
    session_name('miam_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/admin/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    if (isset($_SESSION['admin_id'])) {
        $now = time();
        if ($now - (int) ($_SESSION['last_seen'] ?? 0) > ADMIN_IDLE_SECONDS || $now - (int) ($_SESSION['logged_at'] ?? 0) > ADMIN_MAX_SECONDS) {
            $_SESSION = [];
            session_regenerate_id(true);
            flash('info', 'Votre session a expiré, reconnectez-vous.');
        } else {
            $_SESSION['last_seen'] = $now;
        }
    }
}

function admin_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
}

function current_admin(): ?array
{
    static $admin = false;
    if ($admin !== false) {
        return $admin;
    }
    $admin = null;
    if (isset($_SESSION['admin_id'])) {
        $stmt = db()->prepare('SELECT id, username, created_at, last_login_at FROM admins WHERE id = ?');
        $stmt->execute([(int) $_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

/** Réinitialisation : déposez un fichier « reset-admin » dans data/ (par FTP) pour recréer le compte. */
function admin_reset_requested(): bool
{
    return is_file(data_dir() . '/reset-admin');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = (string) ($_POST['csrf'] ?? '');
    return $sent !== '' && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function flash(string $type, string $text): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function admin_url(string $page = '', array $params = []): string
{
    $query = $page !== '' ? ['p' => $page] + $params : $params;
    return url('admin/') . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

/** Cookie qui exclut cet appareil de la mesure d'audience (visites du propriétaire). */
function set_exclude_cookie(bool $on): void
{
    setcookie('miam_exclude', $on ? '1' : '', [
        'expires' => $on ? time() + 395 * 86400 : time() - 3600,
        'path' => base_path() . '/',
        'secure' => is_https(),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['miam_exclude'] = $on ? '1' : '';
}

function validate_new_password(string $password, string $confirm): ?string
{
    if (mb_strlen($password) < ADMIN_MIN_PASSWORD) {
        return 'Le mot de passe doit contenir au moins ' . ADMIN_MIN_PASSWORD . ' caractères.';
    }
    if ($password !== $confirm) {
        return 'Les deux mots de passe ne correspondent pas.';
    }
    return null;
}

function create_admin(string $username, string $password): void
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        if (admin_reset_requested()) {
            $pdo->exec('DELETE FROM admins');
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
            throw new RuntimeException('Un compte existe déjà.');
        }
        $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT), now()]);
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
    if (admin_reset_requested()) {
        @unlink(data_dir() . '/reset-admin');
    }
}

/** Renvoie null si la connexion réussit, sinon le message d'erreur. */
function attempt_login(string $username, string $password): ?string
{
    $key = 'login:' . client_fingerprint();
    if (!throttle($key, 8, 15 * 60)) {
        return 'Trop de tentatives. Patientez un quart d\'heure avant de réessayer.';
    }
    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    // Même durée de calcul que le compte existe ou non
    $hash = $admin['password_hash'] ?? '$2y$12$/vaBnBiPZ/YJpI.2/Kd/z.eal1Lh6ohx3AKqjyIwBrpi5eViofXWC';
    if (!password_verify($password, $hash) || !$admin) {
        return 'Identifiant ou mot de passe incorrect.';
    }
    throttle_reset($key);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['logged_at'] = $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    $update = db()->prepare('UPDATE admins SET last_login_at = ?' . (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT) ? ', password_hash = ?' : '') . ' WHERE id = ?');
    $params = [now()];
    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $params[] = (int) $admin['id'];
    $update->execute($params);

    if (setting('exclude_self', '1') === '1') {
        set_exclude_cookie(true);
    }
    return null;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
    }
    session_destroy();
}
