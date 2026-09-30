<main class="wrap wrap--narrow home">
  <p class="eyebrow"><span class="dot"></span> Formularios</p>
  <h1 class="display">¿Qué <em>necesitás</em> contarnos?</h1>
  <p class="lead">Elegí el formulario que corresponde a tu servicio. Se completa desde el celular y podés retomarlo más tarde: guardamos tu avance en este dispositivo.</p>

  <?php if (!$forms): ?>
    <p class="note"><strong>Todavía no hay formularios activos.</strong></p>
  <?php endif; ?>

  <ul class="cards">
    <?php foreach ($forms as $f): $d = $f['_def']; ?>
      <li>
        <a class="card-link" href="<?= h(url('f/' . $f['slug'])) ?>">
          <span class="card-link__ico"><?= icon($d['icon'], 30) ?></span>
          <span class="card-link__body">
            <b><?= h($d['title']) ?></b>
            <small><?= h($d['subtitle']) ?></small>
          </span>
          <span class="card-link__go"><?= icon('arrow', 20) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</main>
