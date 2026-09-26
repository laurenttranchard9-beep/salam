<?php
declare(strict_types=1);

/*
 * Préparation commune aux pages publiques : contenus, illustrations,
 * coordonnées réglées dans l'espace gestion.
 */

require_once __DIR__ . '/content.php';
require_once __DIR__ . '/partials/offer-art.php';
require_once __DIR__ . '/partials/phone.php';

$offers = offers();
$cuisines = demo_cuisines();
$projects = projects();
$faq = faq();
$phone = trim((string) setting('public_phone', ''));
$publicEmail = trim((string) setting('public_email', ''));
$zone = trim((string) setting('zone', ''));
$euro = static fn (int $cents): string => number_format($cents / 100, 2, ',', ' ') . "\u{00A0}€";

/** Affiche une section de includes/sections/ avec les variables de la page. */
function section(string $name, array $vars = []): void
{
    $shared = array_filter($GLOBALS, static fn ($key): bool => $key !== 'GLOBALS' && !str_starts_with((string) $key, '_'), ARRAY_FILTER_USE_KEY);
    extract($shared, EXTR_SKIP);
    extract($vars, EXTR_OVERWRITE);
    require __DIR__ . '/sections/' . $name . '.php';
}

/**
 * Découpe un titre en mots, chacun dans un masque, pour l'animation d'apparition.
 * Le texte reste du vrai texte (lisible par les moteurs et les lecteurs d'écran).
 */
function split_words(string $text, int $start = 0): string
{
    $html = '';
    $i = $start;
    foreach (preg_split('/(\s+)/u', trim($text), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
        if (trim($part) === '') {
            $html .= ' ';
            continue;
        }
        $html .= '<span class="w"><span style="--i:' . $i++ . '">' . h($part) . '</span></span>';
    }
    return $html;
}
