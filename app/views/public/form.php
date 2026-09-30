<?php
/** @var array $row */ /** @var array $def */ /** @var array $values */ /** @var array $errors */
$steps = $def['steps'];
?>
<main class="wrap wrap--form">
  <section class="hero">
    <p class="eyebrow"><span class="dot"></span> <?= h($def['subtitle'] ?: 'Formulario') ?></p>
    <h1 class="display"><?= title_html($def['title']) ?></h1>
    <?php if ($def['intro']): ?><p class="lead"><?= nl2br(h($def['intro'])) ?></p><?php endif; ?>
  </section>

  <div class="draft-banner" id="draftBanner" hidden>
    <span>Retomamos lo que habías completado.<?php if (array_filter(all_fields($def), fn($f) => $f['type'] === 'file')): ?> Las fotos y archivos no se guardan: si ya los habías elegido, volvé a subirlos.<?php endif; ?></span>
    <button type="button" class="linklike" id="draftReset">Empezar de nuevo</button>
  </div>

  <?php if ($errors): ?>
    <div class="alert" role="alert" id="serverErrors">
      Revisá los campos marcados en rojo para poder enviar el formulario.
      <?php if (array_intersect_key($errors, array_flip(array_column(array_filter(all_fields($def), fn($f) => $f['type'] === 'file'), 'name')))): ?>
        Si tenías archivos adjuntos, volvé a elegirlos.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form id="wizard" class="wizard" method="post" action="<?= h(url('f/' . $row['slug'])) ?>" enctype="multipart/form-data" novalidate data-slug="<?= h($row['slug']) ?>">
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <input type="hidden" name="_t" value="<?= h((string) time()) ?>">

    <div class="progress" id="progress" aria-live="polite">
      <div class="progress__bar"><span id="progressFill"></span></div>
      <div class="progress__meta"><b id="progressLabel"></b><span id="progressTitle"></span></div>
    </div>

    <?php foreach ($steps as $i => $s): ?>
      <section class="step<?= !empty($s['review']) ? ' step--review' : '' ?>" data-step="<?= $i ?>" data-title="<?= h($s['title']) ?>"<?= !empty($s['review']) ? ' data-review-step' : '' ?><?php if (!empty($s['show_if'])): ?> data-show='<?= h(json_encode($s['show_if'], JSON_UNESCAPED_UNICODE)) ?>'<?php endif; ?> hidden>
        <header class="step__head">
          <h2><?= h($s['title']) ?></h2>
          <?php if ($s['description']): ?><p><?= h($s['description']) ?></p><?php endif; ?>
        </header>
        <?php if (!empty($s['review'])): ?><div class="review" data-review aria-live="polite"></div><?php endif; ?>
        <div class="fields">
          <?php foreach ($s['fields'] as $f) echo render_field($f, $values, $errors); ?>
        </div>
      </section>
    <?php endforeach; ?>

    <nav class="actions" id="actions">
      <button type="button" class="btn btn--ghost" id="btnPrev">Atrás</button>
      <button type="button" class="btn" id="btnNext">Siguiente <?= icon('arrow', 18) ?></button>
      <button type="submit" class="btn btn--accent" id="btnSubmit" hidden><?= h($def['submit_label']) ?></button>
    </nav>
  </form>
</main>
<script src="<?= h(asset('js/form.js')) ?>" defer></script>
