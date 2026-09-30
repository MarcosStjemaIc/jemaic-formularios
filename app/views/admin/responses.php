<?php $nav = 'forms'; $base = url('admin/forms/' . $row['id'] . '/responses'); ?>
<p class="crumbs"><a href="<?= h(url('admin')) ?>">Formularios</a> / <?= h($row['title']) ?></p>
<div class="ahead">
  <div>
    <h1 class="atitle">Respuestas · <?= h($row['title']) ?></h1>
    <p class="asub"><?= (int) $counts[''] ?> en total</p>
  </div>
  <div class="ahead__actions">
    <a class="btn btn--ghost btn--sm" href="<?= h(url('admin/forms/' . $row['id'] . '/edit')) ?>">Editar formulario</a>
    <a class="btn btn--sm" href="<?= h(url('admin/forms/' . $row['id'] . '/export.csv')) ?>">Descargar Excel (CSV)</a>
  </div>
</div>

<div class="filters">
  <div class="tabs">
    <a href="<?= h($base . ($qs !== '' ? '?q=' . rawurlencode($qs) : '')) ?>" class="<?= $status === '' ? 'is-on' : '' ?>">Todas <span><?= (int) $counts[''] ?></span></a>
    <?php foreach (STATUSES as $k => $label): ?>
      <a href="<?= h($base . '?status=' . $k . ($qs !== '' ? '&q=' . rawurlencode($qs) : '')) ?>" class="<?= $status === $k ? 'is-on' : '' ?>"><?= h($label) ?> <span><?= (int) ($counts[$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
  </div>
  <form method="get" action="<?= h($base) ?>" class="search">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= h($status) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= h($qs) ?>" placeholder="Buscar por nombre, teléfono, fecha…" aria-label="Buscar">
    <button class="btn btn--sm" type="submit">Buscar</button>
  </form>
</div>

<?php if (!$rows): ?>
  <div class="empty">
    <b><?= $qs !== '' || $status !== '' ? 'No hay respuestas con ese filtro.' : 'Todavía no hay respuestas.' ?></b>
    <span><?= $qs !== '' || $status !== '' ? '<a href="' . h($base) . '">Quitar filtros</a>' : 'Compartí el enlace del formulario para empezar a recibirlas.' ?></span>
  </div>
<?php else: ?>
<div class="tablewrap">
  <table class="atable">
    <thead><tr>
      <th>Código</th><th>Recibida</th>
      <?php foreach ($cols as $c): ?><th><?= h($c['label']) ?></th><?php endforeach; ?>
      <th>Estado</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $ans = json_decode($r['answers'], true) ?: [];
        $by = [];
        foreach ($ans as $a) $by[$a['k']] = $a; ?>
      <tr onclick="location.href='<?= h(url('admin/responses/' . $r['id'])) ?>'">
        <td><a href="<?= h(url('admin/responses/' . $r['id'])) ?>"><b><?= h($r['ref']) ?></b></a><?= $r['files'] ? ' <span title="Tiene archivos" class="clip">📎</span>' : '' ?></td>
        <td><?= h(fmt_datetime($r['created_at'])) ?></td>
        <?php foreach ($cols as $c): $a = $by[$c['name']] ?? null; ?>
          <td><?= $a ? h(mb_strimwidth(answer_text($a['value'], $a['type']), 0, 60, '…')) : '' ?></td>
        <?php endforeach; ?>
        <td><span class="badge badge--<?= h($r['status']) ?>"><?= h(STATUSES[$r['status']] ?? $r['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Páginas">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="<?= $p === $page ? 'is-on' : '' ?>" href="<?= h($base . '?' . http_build_query(array_filter(['status' => $status, 'q' => $qs, 'page' => $p > 1 ? $p : null]))) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
<?php endif; ?>
