<?php
/* Inicio público: no lista los formularios. Cada cliente recibe su enlace directo por WhatsApp. */
$wa = preg_replace('/\D+/', '', (string) cfg('coordinator_whatsapp', ''));
?>
<main class="wrap wrap--narrow home">
  <p class="eyebrow">Formularios</p>
  <h1 class="display">Tu formulario llega por <em>WhatsApp</em></h1>
  <p class="lead">Cada formulario se completa desde el enlace que te enviamos. Si no lo tenés o se te perdió, escribinos y te lo pasamos al toque.</p>

  <div class="thanks__actions">
    <?php if ($wa): ?>
      <a class="btn btn--wa" href="https://wa.me/<?= h($wa) ?>?text=<?= rawurlencode('Hola! Necesito el enlace del formulario para mi servicio.') ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 18) ?> Pedir mi formulario</a>
    <?php endif; ?>
    <a class="btn btn--ghost" href="https://jemaic.com">Conocer Jema</a>
  </div>
</main>
