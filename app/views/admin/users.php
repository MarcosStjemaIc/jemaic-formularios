<?php $nav = 'users'; ?>
<div class="ahead"><div><h1 class="atitle">Usuarios</h1><p class="asub">Quién puede entrar a este panel.</p></div></div>

<div class="tablewrap">
  <table class="atable atable--plain">
    <thead><tr><th>Nombre</th><th>Email</th><th>Último ingreso</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><b><?= h($u['name']) ?></b></td><td><?= h($u['email']) ?></td>
        <td><?= $u['last_login'] ? h(fmt_datetime($u['last_login'])) : '—' ?></td>
        <td class="ta-r">
          <?php if ((int) $u['id'] !== (int) $user['id']): ?>
          <form method="post" action="<?= h(url('admin/users/' . $u['id'] . '/delete')) ?>" onsubmit="return confirm('¿Quitar a <?= h(addslashes($u['name'])) ?> del panel?')"><?= csrf_field() ?>
            <button class="linklike danger" type="submit">Quitar</button></form>
          <?php else: ?><small>(vos)</small><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="twocol">
  <form method="post" action="<?= h(url('admin/users')) ?>" class="sidebox">
    <?= csrf_field() ?>
    <h3>Sumar a alguien del equipo</h3>
    <label class="label" for="u_name">Nombre</label><input type="text" id="u_name" name="name" required>
    <label class="label" for="u_email">Email</label><input type="email" id="u_email" name="email" required>
    <label class="label" for="u_pass">Contraseña inicial <small>(mín. 10 caracteres)</small></label><input type="password" id="u_pass" name="password" minlength="10" autocomplete="new-password" required>
    <button class="btn btn--sm" type="submit">Crear usuario</button>
  </form>
  <form method="post" action="<?= h(url('admin/password')) ?>" class="sidebox">
    <?= csrf_field() ?>
    <h3>Cambiar mi contraseña</h3>
    <label class="label" for="p_cur">Contraseña actual</label><input type="password" id="p_cur" name="current" autocomplete="current-password" required>
    <label class="label" for="p_new">Nueva contraseña</label><input type="password" id="p_new" name="new" minlength="10" autocomplete="new-password" required>
    <label class="label" for="p_new2">Repetir nueva</label><input type="password" id="p_new2" name="new2" minlength="10" autocomplete="new-password" required>
    <button class="btn btn--sm" type="submit">Cambiar</button>
  </form>
</div>
