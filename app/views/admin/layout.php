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
<body class="admin">
<header class="anav">
  <a class="anav__brand" href="<?= h(url('admin')) ?>"><?= brand_html('jema') ?><span class="anav__tag">Panel</span></a>
  <nav class="anav__links">
    <a href="<?= h(url('admin')) ?>" class="<?= ($nav ?? '') === 'forms' ? 'is-on' : '' ?>">Formularios</a>
    <a href="<?= h(url('admin/users')) ?>" class="<?= ($nav ?? '') === 'users' ? 'is-on' : '' ?>">Usuarios</a>
    <a href="<?= h(url('/')) ?>" target="_blank" rel="noopener">Ver sitio ↗</a>
  </nav>
  <?php if (!empty($user)): ?>
  <form class="anav__user" method="post" action="<?= h(url('admin/logout')) ?>">
    <?= csrf_field() ?>
    <span><?= h($user['name']) ?></span>
    <button class="linklike" type="submit">Salir</button>
  </form>
  <?php endif; ?>
</header>
<main class="apage">
  <?php if (!empty($flash)): ?>
    <div class="toast toast--<?= h($flash['type']) ?>" role="status"><?= h($flash['msg']) ?></div>
  <?php endif; ?>
  <?= $content ?>
</main>
</body>
</html>
