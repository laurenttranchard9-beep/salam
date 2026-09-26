<?php
declare(strict_types=1);

/*
 * Base de données SQLite : un seul fichier dans data/, créé au premier lancement.
 * Les évolutions du schéma sont numérotées (PRAGMA user_version).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = rtrim((string) config('data_dir', APP_ROOT . '/data'), '/\\');
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        http_response_code(500);
        exit('Le dossier de données est introuvable et ne peut pas être créé : ' . htmlspecialchars($dir));
    }
    if (!is_writable($dir)) {
        http_response_code(500);
        exit('Le dossier de données doit être accessible en écriture par PHP : ' . htmlspecialchars($dir));
    }

    $pdo = new PDO('sqlite:' . $dir . '/site.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    $migrations = [
        1 => [
            'CREATE TABLE admins (
                id INTEGER PRIMARY KEY,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL,
                last_login_at TEXT
            )',
            'CREATE TABLE settings (
                key TEXT PRIMARY KEY,
                value TEXT
            )',
            // Messages du formulaire de contact
            "CREATE TABLE messages (
                id INTEGER PRIMARY KEY,
                created_at TEXT NOT NULL,
                name TEXT NOT NULL,
                business TEXT NOT NULL DEFAULT '',
                email TEXT NOT NULL,
                phone TEXT NOT NULL DEFAULT '',
                business_type TEXT NOT NULL DEFAULT '',
                offer TEXT NOT NULL DEFAULT '',
                options TEXT NOT NULL DEFAULT '[]',
                menu_size TEXT NOT NULL DEFAULT '',
                callback INTEGER NOT NULL DEFAULT 0,
                message TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'nouveau',
                note TEXT NOT NULL DEFAULT '',
                read_at TEXT,
                updated_at TEXT,
                source TEXT NOT NULL DEFAULT ''
            )",
            'CREATE INDEX messages_status ON messages (status, created_at)',
            // Compteur anonyme : aucun identifiant, uniquement des totaux par jour
            'CREATE TABLE hits (
                day TEXT NOT NULL,
                path TEXT NOT NULL,
                device TEXT NOT NULL,
                source TEXT NOT NULL,
                n INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (day, path, device, source)
            )',
            // Nombre de choix « accepter » / « refuser » par jour
            'CREATE TABLE consents (
                day TEXT NOT NULL,
                choice TEXT NOT NULL,
                n INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (day, choice)
            )',
            // Pages vues détaillées, uniquement pour les visiteurs qui ont accepté
            'CREATE TABLE views (
                id INTEGER PRIMARY KEY,
                view_id TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL,
                day TEXT NOT NULL,
                hour INTEGER NOT NULL,
                weekday INTEGER NOT NULL,
                visitor TEXT NOT NULL,
                session TEXT NOT NULL,
                path TEXT NOT NULL,
                source TEXT NOT NULL,
                referrer TEXT NOT NULL DEFAULT \'\',
                utm_source TEXT NOT NULL DEFAULT \'\',
                utm_medium TEXT NOT NULL DEFAULT \'\',
                utm_campaign TEXT NOT NULL DEFAULT \'\',
                device TEXT NOT NULL,
                browser TEXT NOT NULL,
                os TEXT NOT NULL,
                screen INTEGER,
                lang TEXT NOT NULL DEFAULT \'\',
                duration INTEGER,
                scroll INTEGER
            )',
            'CREATE INDEX views_day ON views (day)',
            'CREATE INDEX views_visitor ON views (visitor)',
            // Actions (clics sur les boutons, formules consultées…), avec consentement
            'CREATE TABLE events (
                id INTEGER PRIMARY KEY,
                created_at TEXT NOT NULL,
                day TEXT NOT NULL,
                visitor TEXT NOT NULL,
                session TEXT NOT NULL,
                name TEXT NOT NULL,
                label TEXT NOT NULL DEFAULT \'\',
                path TEXT NOT NULL DEFAULT \'\'
            )',
            'CREATE INDEX events_day ON events (day, name)',
            'CREATE TABLE throttle (
                k TEXT PRIMARY KEY,
                n INTEGER NOT NULL,
                reset_at INTEGER NOT NULL
            )',
        ],
    ];

    if ($version >= max(array_keys($migrations))) {
        return;
    }

    // BEGIN IMMEDIATE : si deux visiteurs arrivent en même temps au premier lancement,
    // le second attend puis relit la version au lieu de recréer les tables.
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
        foreach ($migrations as $target => $statements) {
            if ($version >= $target) {
                continue;
            }
            foreach ($statements as $sql) {
                $pdo->exec($sql);
            }
            $pdo->exec('PRAGMA user_version = ' . (int) $target);
            $version = $target;
        }
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}
