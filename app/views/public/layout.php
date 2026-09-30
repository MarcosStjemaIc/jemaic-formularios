<?php /** @var string $content */ ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($pageTitle ?? cfg('app_name', 'Jema')) ?> · <?= h(cfg('app_name', 'Jema')) ?></title>
<meta name="robots" content="noindex">
<meta name="theme-color" content="#f0efed">
<?php
  // Vista previa al compartir el enlace (WhatsApp, Instagram, etc.)
  $ogTitle = ($pageTitle ?? 'Formularios') . ' · ' . cfg('app_name', 'Jema');
  $ogDesc  = $pageDesc ?? 'Completalo desde el celular en pocos minutos.';
  $ogImg   = abs_url('assets/' . (($brand ?? 'jema') === 'planazo' ? 'og-planazo.png' : 'og-jema.png'));
?>
<meta name="description" content="<?= h($ogDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="es_AR">
<meta property="og:site_name" content="<?= h(cfg('app_name', 'Jema')) ?>">
<meta property="og:title" content="<?= h($ogTitle) ?>">
<meta property="og:description" content="<?= h($ogDesc) ?>">
<meta property="og:url" content="<?= h(abs_url(ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'))) ?>">
<meta property="og:image" content="<?= h($ogImg) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="<?= h(asset('brand/Favicon.png')) ?>">
<link rel="stylesheet" href="<?= h(asset('css/app.css')) ?>">
<?php if (!empty($accent) && !in_array(strtolower($accent), ['#9a513a', '#d34e18'], true)): ?><style>:root{--accent:<?= h($accent) ?>}</style><?php endif; ?>
</head>
<body class="<?= h($bodyClass ?? '') ?>">
<div class="atmosphere" aria-hidden="true"></div>
<header class="top">
  <a class="top__brand" href="<?= h(url('/')) ?>" aria-label="Inicio"><?= brand_html($brand ?? 'jema') ?></a>
  <?php if (!empty($topRight)): ?><span class="top__right"><?= $topRight ?></span><?php endif; ?>
</header>
<?= $content ?>
<footer class="foot">
  <span><?= h(cfg('app_name', 'Jema')) ?> · Puerto Madryn, Chubut</span>
  <span>Tus datos se usan solo para organizar tu servicio.</span>
</footer>
</body>
</html>
