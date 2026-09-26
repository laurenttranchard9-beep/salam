<?php
/** @var array $page title, description, path, (robots), (bodyClass) */
$canonical = absolute_url($page['path'] ?? '');
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($page['title']) ?></title>
<meta name="description" content="<?= h($page['description']) ?>">
<?php if (!empty($page['robots'])): ?>
<meta name="robots" content="<?= h($page['robots']) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= h($canonical) ?>">
<meta name="theme-color" content="#e8472c">
<link rel="icon" href="<?= h(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= h(url('assets/img/apple-touch-icon.png')) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<meta property="og:site_name" content="<?= h(site_name()) ?>">
<meta property="og:title" content="<?= h($page['title']) ?>">
<meta property="og:description" content="<?= h($page['description']) ?>">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta property="og:image" content="<?= h(absolute_url('assets/img/og.jpg')) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<link rel="preload" href="<?= h(url('assets/fonts/bricolage-grotesque.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= h(url('assets/fonts/unbounded.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= h(asset('css/site.css')) ?>">
<?php
// Ordre important (scripts « defer ») : mesure d'audience, défilement doux, GSAP si la page en a besoin, puis le site
$scripts = array_merge(['js/consent.js', 'js/vendor/lenis.min.js'], $page['vendor'] ?? [], ['js/motion.js', 'js/site.js'], $page['scripts'] ?? []);
foreach ($scripts as $script): ?>
<script src="<?= h(asset($script)) ?>" defer></script>
<?php endforeach; ?>
<?php if (!empty($page['jsonld'])): ?>
<script type="application/ld+json"><?= json_encode($page['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
</head>
<body class="<?= h($page['bodyClass'] ?? '') ?>" data-base="<?= h(base_path()) ?>">
<?php require __DIR__ . '/icons.php'; ?>
<a class="skip" href="#contenu">Aller au contenu</a>
