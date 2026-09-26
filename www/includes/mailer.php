<?php
declare(strict_types=1);

/*
 * Email de notification à chaque nouveau message, avec la fonction mail() de PHP
 * (disponible chez la plupart des hébergeurs Apache mutualisés).
 */

function mail_sender(): string
{
    $configured = trim((string) config('mail_from', ''));
    if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
        return $configured;
    }
    $host = strtolower((string) parse_url(site_origin(), PHP_URL_HOST));
    $host = preg_replace('/^www\./', '', $host) ?: 'localhost';
    return 'no-reply@' . $host;
}

function send_mail(string $to, string $subject, string $body, string $replyTo = ''): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return false;
    }
    $from = mail_sender();
    $headers = [
        'From: ' . mb_encode_mimeheader(site_name(), 'UTF-8', 'B') . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: ' . site_name(),
    ];
    // Répondre directement au visiteur depuis sa messagerie
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL) && !preg_match('/[\r\n]/', $replyTo)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $params = filter_var($from, FILTER_VALIDATE_EMAIL) ? '-f' . $from : '';

    try {
        return @mail($to, $encodedSubject, $body, implode("\r\n", $headers), $params);
    } catch (Throwable $e) {
        error_log('Email non envoyé : ' . $e->getMessage());
        return false;
    }
}

function send_new_message_notification(int $id, array $m): bool
{
    if (setting('notify_enabled', '1') !== '1') {
        return false;
    }
    $to = trim((string) setting('notify_email', ''));
    $offers = offers();
    $options = contact_options();
    $chosen = array_map(static fn (string $k): string => $options[$k] ?? $k, json_decode($m['options'] ?: '[]', true) ?: []);

    $lines = [
        'Nouvelle demande reçue sur ' . site_name() . ' :',
        '',
        'Nom : ' . $m['name'],
        'Établissement : ' . ($m['business'] ?: '—'),
        'Email : ' . $m['email'],
        'Téléphone : ' . ($m['phone'] ?: '—') . ($m['callback'] ? ' (souhaite être rappelé·e)' : ''),
        "Type d'établissement : " . ($m['business_type'] ?: '—'),
        'Taille de la carte : ' . ($m['menu_size'] ?: '—'),
        'Formule : ' . ($offers[$m['offer']]['name'] ?? 'Pas encore décidé'),
        'Options : ' . ($chosen ? implode(', ', $chosen) : '—'),
        '',
        'Message :',
        $m['message'],
        '',
        '—',
        'Voir la demande dans l\'espace gestion :',
        absolute_url('admin/?p=message&id=' . $id),
        '',
        'Répondez simplement à cet email pour écrire à ' . $m['name'] . '.',
    ];
    $subject = 'Nouvelle demande : ' . $m['name'] . ($m['business'] !== '' ? ' · ' . $m['business'] : '');
    return send_mail($to, $subject, implode("\n", $lines), $m['email']);
}
