<?php $nav = 'forms'; $link = abs_url('f/' . $row['slug']); ?>
<p class="crumbs"><a href="<?= h(url('admin')) ?>">Formularios</a> / Editar</p>
<div class="ahead">
  <div>
    <h1 class="atitle">Editar formulario</h1>
    <p class="asub"><?= (int) $nResponses ?> respuesta<?= $nResponses === 1 ? '' : 's' ?> recibida<?= $nResponses === 1 ? '' : 's' ?>. Los cambios no modifican las respuestas anteriores.</p>
  </div>
  <div class="ahead__actions">
    <a class="btn btn--ghost btn--sm" href="<?= h(url('admin/forms/' . $row['id'] . '/responses')) ?>">Ver respuestas</a>
    <a class="btn btn--ghost btn--sm" href="<?= h(url('f/' . $row['slug'])) ?>" target="_blank" rel="noopener">Abrir formulario ↗</a>
  </div>
</div>

<form method="post" action="<?= h(url('admin/forms/' . $row['id'] . '/edit')) ?>" id="editForm" class="editor">
  <?= csrf_field() ?>
  <textarea name="steps_json" id="stepsJson" hidden></textarea>

  <div class="stickysave no-print">
    <label class="switch"><input type="checkbox" name="active" value="1" <?= $row['active'] ? 'checked' : '' ?>><span></span> Publicado</label>
    <span class="grow"></span>
    <button class="btn btn--accent btn--sm" type="submit">Guardar cambios</button>
  </div>

  <section class="ecard">
    <h2>Datos generales</h2>
    <div class="egrid">
      <div class="field"><label class="label" for="title">Título</label><input type="text" id="title" name="title" value="<?= h($def['title']) ?>" required></div>
      <div class="field"><label class="label" for="subtitle">Subtítulo</label><input type="text" id="subtitle" name="subtitle" value="<?= h($def['subtitle']) ?>"></div>
      <div class="field field--wide"><label class="label" for="intro">Texto de bienvenida</label><textarea id="intro" name="intro" rows="3"><?= h($def['intro']) ?></textarea></div>
      <div class="field"><label class="label" for="slug">Dirección del formulario</label>
        <div class="slugbox"><span><?= h(preg_replace('#^https?://#', '', abs_url('f/'))) ?></span><input type="text" id="slug" name="slug" value="<?= h($row['slug']) ?>" pattern="[a-z0-9\-]+" required></div>
        <p class="help">Si la cambiás, el enlace anterior deja de funcionar.</p></div>
      <div class="field"><label class="label" for="icon">Ícono</label>
        <select id="icon" name="icon"><?php foreach (FORM_ICONS as $k => $l): ?><option value="<?= h($k) ?>" <?= $def['icon'] === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label class="label" for="brand">Marca en la cabecera</label>
        <select id="brand" name="brand"><option value="jema" <?= $def['brand'] === 'jema' ? 'selected' : '' ?>>Jema Imagen Creativa</option><option value="planazo" <?= $def['brand'] === 'planazo' ? 'selected' : '' ?>>Qué Planazo · por Jema</option></select></div>
      <div class="field"><label class="label" for="accent">Color de acento</label>
        <input type="color" id="accent" name="accent" value="<?= h($def['accent'] ?: '#9a513a') ?>"></div>
    </div>
  </section>

  <section class="ecard">
    <h2>Preguntas</h2>
    <p class="help">Armá los pasos y los campos. Podés mostrar un paso o una pregunta solo si respondieron algo antes (“Mostrar solo si…”).</p>
    <div id="builder" class="builder"><p class="help">Cargando editor…</p></div>
    <noscript><p class="alert">El editor necesita JavaScript activado.</p></noscript>
    <details class="adv">
      <summary>Modo avanzado (JSON)</summary>
      <p class="help">Para usuarios técnicos: podés copiar, pegar o editar la estructura completa de pasos y campos.</p>
      <textarea id="advJson" rows="12" spellcheck="false"></textarea>
      <button type="button" class="btn btn--ghost btn--sm" id="advApply">Aplicar JSON al editor</button>
    </details>
  </section>

  <section class="ecard">
    <h2>Respuestas y avisos</h2>
    <div class="egrid">
      <div class="field field--wide"><label class="label" for="notify">Avisar por email a</label>
        <textarea id="notify" name="notify" rows="2" placeholder="uno@jemaic.com, otro@jemaic.com"><?= h(implode(', ', $def['notify'])) ?></textarea>
        <p class="help">Además de los correos configurados para todo el sistema. Uno por línea o separados por coma.</p></div>
      <div class="field"><label class="label" for="client_email_field">Enviar confirmación al cliente usando el campo…</label>
        <select id="client_email_field" name="client_email_field" data-value="<?= h($def['client_email_field']) ?>"></select></div>
      <div class="field"><label class="label">Columnas en la lista de respuestas <small>(hasta 4)</small></label>
        <div id="listFields" class="checklist" data-values='<?= h(json_encode($def['list_fields'])) ?>'></div></div>
      <div class="field"><label class="label" for="submit_label">Texto del botón de envío</label><input type="text" id="submit_label" name="submit_label" value="<?= h($def['submit_label']) ?>"></div>
      <div class="field"><label class="label" for="thanks_title">Título de “gracias”</label><input type="text" id="thanks_title" name="thanks_title" value="<?= h($def['thanks_title']) ?>"></div>
      <div class="field field--wide"><label class="label" for="thanks_text">Mensaje de “gracias” <small>(también va en el email al cliente)</small></label><textarea id="thanks_text" name="thanks_text" rows="3"><?= h($def['thanks_text']) ?></textarea></div>
    </div>
  </section>

  <div class="stickysave stickysave--bottom no-print">
    <span class="grow"></span>
    <button class="btn btn--accent" type="submit">Guardar cambios</button>
  </div>
</form>

<section class="ecard ecard--danger">
  <h2>Zona delicada</h2>
  <form method="post" action="<?= h(url('admin/forms/' . $row['id'] . '/delete')) ?>" onsubmit="return confirm('¿Borrar este formulario<?= $nResponses ? ' y sus ' . $nResponses . ' respuestas' : '' ?>? No se puede deshacer.')">
    <?= csrf_field() ?>
    <?php if ($nResponses): ?>
      <p class="help">Este formulario tiene <?= (int) $nResponses ?> respuesta<?= $nResponses === 1 ? '' : 's' ?>. Descargá el Excel antes. Para confirmar, escribí <b><?= h($row['slug']) ?></b>:</p>
      <input type="text" name="confirm" placeholder="<?= h($row['slug']) ?>" autocomplete="off">
    <?php endif; ?>
    <button class="btn btn--danger btn--sm" type="submit">Borrar formulario</button>
  </form>
</section>

<script>window.__STEPS__ = <?= json_encode($def['steps'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__TYPES__ = <?= json_encode(FIELD_TYPES, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= h(asset('js/editor.js')) ?>" defer></script>
