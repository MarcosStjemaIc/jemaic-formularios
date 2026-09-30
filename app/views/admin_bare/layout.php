<?php /** @var string $content */ ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Panel') ?> · Panel Jema</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" type="image/png" href="<?= h(asset('brand/Favicon.png')) ?>">
<link rel="stylesheet" href="<?= h(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= h(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--bare">
<div class="atmosphere" aria-hidden="true"></div>
<div class="bare">
  <div class="bare__brand"><?= brand_html('jema') ?></div>
  <?= $content ?>
</div>
</body>
</html>
