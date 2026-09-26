<?php
/**
 * Configuration du site.
 *
 * Modifiez ces valeurs avant la mise en ligne. Les réglages « métier »
 * (email de notification, téléphone affiché, tarifs) se changent ensuite
 * directement dans l'espace gestion : /admin/
 */
return [
    // Nom de la marque, affiché dans le logo, les titres et les emails.
    'site_name' => 'Miam',
    // Mention du logo, sous le grand « miam » du pied de page.
    'tagline'   => 'Créateur de site internet, menu et motion design',

    // Vous, le créateur des sites.
    'owner_name' => 'Laurent Tranchard',

    // Adresse publique du site, sans « / » final (ex. https://www.mon-domaine.fr).
    // Laissez vide pour la détecter automatiquement.
    'base_url' => '',

    'timezone' => 'Europe/Paris',

    // Dossier de la base de données SQLite. Il est protégé par un .htaccess,
    // mais vous pouvez le placer hors du dossier public si votre hébergeur le permet.
    'data_dir' => __DIR__ . '/data',

    // Expéditeur des emails de notification. Vide = no-reply@<votre domaine>.
    'mail_from' => '',

    // Valeurs de départ : modifiables ensuite dans l'espace gestion > Réglages.
    'defaults' => [
        'notify_email'   => 'laurenttranchard9@gmail.com',
        'notify_enabled' => '1',
        'public_phone'   => '',
        'public_email'   => '',
        'zone'           => '',
    ],

    // Mentions légales (obligatoires). Les champs vides s'affichent « à compléter ».
    'legal' => [
        'publisher'     => 'Laurent Tranchard',
        'status'        => 'Entrepreneur individuel',
        'siret'         => '',
        'address'       => '',
        'email'         => 'laurenttranchard9@gmail.com',
        'phone'         => '',
        'director'      => 'Laurent Tranchard',
        'host_name'     => '',
        'host_address'  => '',
        'host_phone'    => '',
    ],
];
