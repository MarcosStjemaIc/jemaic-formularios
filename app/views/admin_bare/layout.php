<?php /** @var string $content */ ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Panel') ?> · Panel Jema</title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= h(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--bare">
<div class="bare">
  <div class="bare__brand"><?= brand_html('jema') ?></div>
  <?= $content ?>
</div>
</body>
</html>
