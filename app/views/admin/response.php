<?php
$nav = 'forms';
$byStep = [];
foreach ($answers as $a) {
    if ($a['type'] === 'file') continue;
    $byStep[$a['step']][] = $a;
}
// ¿Qué respuesta identifica esta entrada?
$who = submission_title($answers, $def);
$labelOf = [];
foreach ($answers as $a) $labelOf[$a['k']] = $a['label'];

function render_answer_value(array $a): string
{
    $v = $a['value'];
    if (is_array($v) && in_array($a['type'], ['repeater', 'captions'], true)) {
        return '<ol class="replist">' . implode('', array_map(fn($x) => '<li>' . h(is_array($x) ? implode(' · ', $x) : $x) . '</li>', $v)) . '</ol>';
    }
    if (is_array($v)) {
        return '<ul class="taglist">' . implode('', array_map(fn($x) => '<li>' . h($x) . '</li>', $v)) . '</ul>';
    }
    $s = (string) $v;
    switch ($a['type']) {
        case 'email': return '<a href="mailto:' . h($s) . '">' . h($s) . '</a>';
        case 'tel':
            $digits = preg_replace('/\D+/', '', $s);
            return h($s) . ($digits ? ' <a class="wa" target="_blank" rel="noopener" href="https://wa.me/' . h($digits) . '">WhatsApp ↗</a>' : '');
        case 'url':
            $u = preg_match('#^https?://#i', $s) ? $s : 'https://' . $s;
            return '<a href="' . h($u) . '" target="_blank" rel="noopener noreferrer">' . h($s) . ' ↗</a>';
        case 'date': return h(fmt_date($s));
        case 'datetime': return h(answer_text($s, 'datetime'));
        case 'colors':
            $out = '<span class="colorrow">';
            foreach (array_map('trim', explode(',', $s)) as $c) $out .= '<span><i style="background:' . h($c) . '"></i>' . h($c) . '</span>';
            return $out . '</span>';
        default: return nl2br(h($s));
    }
}
?>
<p class="crumbs"><a href="<?= h(url('admin')) ?>">Formularios</a> / <a href="<?= h(url('admin/forms/' . $sub['form_id'] . '/responses')) ?>"><?= h($row['title'] ?? 'Formulario') ?></a> / <?= h($sub['ref']) ?></p>

<div class="ahead">
  <div>
    <h1 class="atitle"><?= h($who) ?></h1>
    <p class="asub">Código <b><?= h($sub['ref']) ?></b> · recibida el <?= h(fmt_datetime($sub['created_at'])) ?></p>
  </div>
  <div class="ahead__actions no-print">
    <?php if ($prev): ?><a class="btn btn--ghost btn--sm" href="<?= h(url('admin/responses/' . $prev)) ?>" title="Más reciente">←</a><?php endif; ?>
    <?php if ($next): ?><a class="btn btn--ghost btn--sm" href="<?= h(url('admin/responses/' . $next)) ?>" title="Más antigua">→</a><?php endif; ?>
    <?php if (($def['export'] ?? '') === 'queplanazo'): ?><a class="btn btn--accent btn--sm" href="<?= h(url('admin/responses/' . $sub['id'] . '/queplanazo.json')) ?>">Descargar datos para Qué Planazo</a><?php endif; ?>
    <button class="btn btn--ghost btn--sm" type="button" onclick="window.print()">Imprimir</button>
  </div>
</div>

<div class="detail">
  <section class="detail__main">
    <?php foreach ($byStep as $step => $list):
        $shown = array_filter($list, fn($a) => answer_text($a['value'], $a['type']) !== ''); if (!$shown) continue; ?>
      <div class="dsec">
        <h2><?= h($step) ?></h2>
        <dl>
          <?php foreach ($shown as $a): ?>
            <div class="drow"><dt><?= h($a['label']) ?></dt><dd><?= render_answer_value($a) ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
    <?php endforeach; ?>

    <?php if ($files): ?>
      <div class="dsec">
        <h2>Archivos adjuntos</h2>
        <?php foreach ($files as $field => $list): ?>
          <p class="dfiles__label"><?= h($labelOf[$field] ?? $field) ?></p>
          <ul class="dfiles">
            <?php foreach ($list as $i => $f):
                $link = url('admin/files/' . $sub['id'] . '/' . $field . '/' . $i);
                $isImg = str_starts_with((string) ($f['mime'] ?? ''), 'image/') && !str_contains((string) $f['mime'], 'svg') && !str_contains((string) $f['mime'], 'heic'); ?>
              <li>
                <?php if ($isImg): ?><a href="<?= h($link) ?>" target="_blank"><img loading="lazy" src="<?= h($link) ?>" alt="<?= h($f['name']) ?>"></a>
                <?php else: ?><a class="filechip" href="<?= h($link) ?>" target="_blank"><?= icon('file', 22) ?></a><?php endif; ?>
                <span><?= h($f['name']) ?><br><small><?= h(fmt_bytes((int) $f['size'])) ?> · <a href="<?= h($link . '?dl=1') ?>">Descargar</a></small></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <aside class="detail__side no-print">
    <form method="post" action="<?= h(url('admin/responses/' . $sub['id'] . '/update')) ?>" class="sidebox">
      <?= csrf_field() ?>
      <h3>Seguimiento</h3>
      <label class="label" for="status">Estado</label>
      <select id="status" name="status">
        <?php foreach (STATUSES as $k => $label): ?><option value="<?= h($k) ?>" <?= $sub['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
      </select>
      <label class="label" for="notes">Notas internas <small>(el cliente no las ve)</small></label>
      <textarea id="notes" name="notes" rows="6"><?= h($sub['notes']) ?></textarea>
      <button class="btn btn--accent btn--sm" type="submit">Guardar</button>
    </form>
    <form method="post" action="<?= h(url('admin/responses/' . $sub['id'] . '/delete')) ?>" class="sidebox sidebox--danger"
          onsubmit="return confirm('¿Borrar esta respuesta y sus archivos? No se puede deshacer.')">
      <?= csrf_field() ?>
      <button class="linklike danger" type="submit">Borrar respuesta</button>
    </form>
  </aside>
</div>
