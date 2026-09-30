<h1 class="bare__title">Creá tu usuario administrador</h1>
<p class="bare__lead">Es el primer ingreso: este usuario podrá crear formularios, ver respuestas y sumar a otras personas del equipo.</p>
<form class="bare__form" method="post" action="<?= h(url('admin/setup')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="key" value="<?= h($key) ?>">
  <?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php endif; ?>
  <div class="field"><label class="label" for="name">Tu nombre</label>
    <input type="text" id="name" name="name" value="<?= h($v['name']) ?>" required autofocus></div>
  <div class="field"><label class="label" for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= h($v['email']) ?>" autocomplete="username" required></div>
  <div class="field"><label class="label" for="password">Contraseña <small>(mínimo 10 caracteres)</small></label>
    <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required></div>
  <div class="field"><label class="label" for="password2">Repetí la contraseña</label>
    <input type="password" id="password2" name="password2" autocomplete="new-password" minlength="10" required></div>
  <button class="btn btn--accent" type="submit">Crear usuario</button>
</form>
