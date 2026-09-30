<?php /** @var string $content */ ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($pageTitle ?? cfg('app_name', 'Jema')) ?> · <?= h(cfg('app_name', 'Jema')) ?></title>
<meta name="robots" content="noindex">
<meta name="theme-color" content="#f8f5ee">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(asset('css/app.css')) ?>">
<?php if (!empty($accent)): ?><style>:root{--accent:<?= h($accent) ?>}</style><?php endif; ?>
</head>
<body class="<?= h($bodyClass ?? '') ?>">
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
