<?php
declare(strict_types=1);

/**
 * Maquette de téléphone avec une capture d'écran.
 * $options : class, statusBg (couleur de la barre d'état), loading, scroll (capture longue qui défile)
 */
function phone(string $image, string $alt, array $options = []): string
{
    $class = trim('phone ' . ($options['class'] ?? ''));
    $status = $options['statusBg'] ?? '#1d1512';
    $loading = $options['loading'] ?? 'lazy';
    $size = @getimagesize(APP_ROOT . '/assets/img/' . $image) ?: [720, 1558];
    $screenClass = 'phone__screen' . (!empty($options['scroll']) ? ' phone__screen--scroll' : '');
    $priority = $loading === 'eager' ? ' fetchpriority="high"' : '';

    return '<div class="' . h($class) . '">'
        . '<div class="' . $screenClass . '" style="--status:' . h($status) . '">'
        . '<div class="phone__status" aria-hidden="true"><span>9:41</span><span class="phone__icons"><i></i><i></i><i></i></span></div>'
        . '<div class="phone__view"><img src="' . h(asset('img/' . $image)) . '" alt="' . h($alt) . '" width="' . (int) $size[0] . '" height="' . (int) $size[1] . '" loading="' . h($loading) . '" decoding="async"' . $priority . '></div>'
        . '</div></div>';
}
