<h1 class="bare__title">Ingresar al panel</h1>
<form class="bare__form" method="post" action="<?= h(url('admin/login')) ?>">
  <?= csrf_field() ?>
  <?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php endif; ?>
  <div class="field"><label class="label" for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="username" required autofocus></div>
  <div class="field"><label class="label" for="password">Contraseña</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required></div>
  <button class="btn btn--accent" type="submit">Ingresar</button>
</form>
