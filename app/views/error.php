<main class="wrap wrap--narrow thanks">
  <h1 class="display"><?= h($title ?? 'Ups') ?></h1>
  <p class="lead"><?= h($msg ?? '') ?></p>
  <div class="thanks__actions"><a class="btn btn--ghost" href="<?= h(url('/')) ?>">Volver al inicio</a></div>
</main>
