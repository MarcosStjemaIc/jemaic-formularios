<main class="wrap wrap--narrow thanks">
  <div class="thanks__mark"><?= icon('check', 34) ?></div>
  <h1 class="display"><?= title_html($def['thanks_title']) ?></h1>
  <p class="lead"><?= nl2br(h($def['thanks_text'])) ?></p>
  <?php if ($ref): ?>
    <p class="ref">Tu código de seguimiento: <b><?= h($ref) ?></b></p>
  <?php endif; ?>
  <div class="thanks__actions">
    <?php $wa = preg_replace('/\D+/', '', (string) cfg('coordinator_whatsapp', '')); if ($wa): ?>
      <a class="btn btn--wa" href="https://wa.me/<?= h($wa) ?>?text=<?= rawurlencode('Hola! Acabo de completar el formulario "' . $def['title'] . '"' . ($ref ? ' (código ' . $ref . ')' : '') . '.') ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 18) ?> Escribir a <?= h(cfg('coordinator_name', 'la coordinadora')) ?></a>
    <?php endif; ?>
    <a class="btn btn--ghost" href="<?= h(url('/')) ?>">Volver al inicio</a>
  </div>
</main>
<script>try{localStorage.removeItem('jema:draft:<?= h($row['slug']) ?>')}catch(e){}</script>
