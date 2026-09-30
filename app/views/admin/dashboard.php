<?php $nav = 'forms'; ?>
<div class="ahead">
  <div>
    <h1 class="atitle">Formularios</h1>
    <p class="asub">Todo lo que tus clientes completan, en un solo lugar.</p>
  </div>
  <form method="post" action="<?= h(url('admin/forms/new')) ?>" class="inline-new">
    <?= csrf_field() ?>
    <input type="text" name="title" placeholder="Nombre del formulario" aria-label="Nombre del nuevo formulario">
    <button class="btn btn--sm" type="submit">+ Nuevo formulario</button>
  </form>
</div>

<div class="fgrid">
  <?php foreach ($forms as $f): $d = $f['_def']; $s = $f['_stats']; $link = abs_url('f/' . $f['slug']); ?>
    <article class="fcard <?= $f['active'] ? '' : 'is-off' ?>">
      <header class="fcard__head">
        <span class="fcard__ico"><?= icon($d['icon'], 24) ?></span>
        <div>
          <h2><?= h($d['title']) ?></h2>
          <p><?= h($d['subtitle']) ?></p>
        </div>
        <span class="pill <?= $f['active'] ? 'pill--on' : 'pill--off' ?>"><?= $f['active'] ? 'Publicado' : 'Desactivado' ?></span>
      </header>
      <dl class="fcard__stats">
        <div><dt>Respuestas</dt><dd><?= (int) $s['total'] ?></dd></div>
        <div><dt>Sin revisar</dt><dd class="<?= (int) $s['nuevos'] ? 'hot' : '' ?>"><?= (int) $s['nuevos'] ?></dd></div>
        <div><dt>Última</dt><dd><?= $s['last'] ? h(date('d/m', strtotime($s['last']))) : '—' ?></dd></div>
      </dl>
      <div class="fcard__link">
        <input type="text" readonly value="<?= h($link) ?>" aria-label="Enlace del formulario" onclick="this.select()">
        <button type="button" class="btn btn--ghost btn--sm" data-copy="<?= h($link) ?>">Copiar</button>
      </div>
      <footer class="fcard__actions">
        <a class="btn btn--sm" href="<?= h(url('admin/forms/' . $f['id'] . '/responses')) ?>">Ver respuestas</a>
        <a class="btn btn--ghost btn--sm" href="<?= h(url('admin/forms/' . $f['id'] . '/edit')) ?>">Editar</a>
        <a class="btn btn--ghost btn--sm" href="<?= h(url('f/' . $f['slug'])) ?>" target="_blank" rel="noopener">Abrir ↗</a>
        <form method="post" action="<?= h(url('admin/forms/' . $f['id'] . '/toggle')) ?>"><?= csrf_field() ?>
          <button class="linklike" type="submit"><?= $f['active'] ? 'Desactivar' : 'Publicar' ?></button></form>
        <form method="post" action="<?= h(url('admin/forms/' . $f['id'] . '/duplicate')) ?>"><?= csrf_field() ?>
          <button class="linklike" type="submit">Duplicar</button></form>
      </footer>
    </article>
  <?php endforeach; ?>
</div>

<?php if ($recent): ?>
<h2 class="asec">Últimas respuestas</h2>
<div class="tablewrap">
  <table class="atable">
    <thead><tr><th>Código</th><th>Formulario</th><th>Quién</th><th>Recibida</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $r):
        $rdef = normalize_definition(json_decode($r['definition'], true) ?: []);
        $ans = json_decode($r['answers'], true) ?: []; ?>
      <tr onclick="location.href='<?= h(url('admin/responses/' . $r['id'])) ?>'">
        <td><a href="<?= h(url('admin/responses/' . $r['id'])) ?>"><b><?= h($r['ref']) ?></b></a></td>
        <td><?= h($r['form_title']) ?></td>
        <td><?= h(submission_title($ans, $rdef)) ?></td>
        <td><?= h(fmt_datetime($r['created_at'])) ?></td>
        <td><span class="badge badge--<?= h($r['status']) ?>"><?= h(STATUSES[$r['status']] ?? $r['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<script>
document.querySelectorAll('[data-copy]').forEach(function(b){b.addEventListener('click',function(){
  var t=b.dataset.copy;(navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).then(function(){b.textContent='¡Copiado!';setTimeout(function(){b.textContent='Copiar'},1500)}).catch(function(){b.previousElementSibling.select()});});});
</script>
