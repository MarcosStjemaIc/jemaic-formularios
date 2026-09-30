/* Formulario por pasos — sin dependencias */
(function () {
  'use strict';
  var form = document.getElementById('wizard');
  if (!form) return;

  var slug = form.dataset.slug;
  var KEY = 'jema:draft:' + slug;
  var steps = Array.prototype.slice.call(form.querySelectorAll('.step'));
  var btnPrev = document.getElementById('btnPrev');
  var btnNext = document.getElementById('btnNext');
  var btnSubmit = document.getElementById('btnSubmit');
  var fill = document.getElementById('progressFill');
  var pLabel = document.getElementById('progressLabel');
  var pTitle = document.getElementById('progressTitle');
  var banner = document.getElementById('draftBanner');
  var hasServerErrors = !!document.getElementById('serverErrors');
  var current = null;

  function $$(sel, root) { return Array.prototype.slice.call((root || form).querySelectorAll(sel)); }

  /* ---------- Valores y condiciones ---------- */
  function getValues() {
    var v = {};
    $$('.field[data-name]').forEach(function (fld) {
      var name = fld.dataset.name, type = fld.dataset.type;
      if (type === 'info' || type === 'file' || type === 'repeater') return;
      if (type === 'switch') { v[name] = fld.querySelector('input[type=checkbox]').checked ? 'Sí' : 'No'; return; }
      if (type === 'checkbox' || type === 'palette') {
        v[name] = $$('input[type=checkbox]:checked', fld).map(function (i) { return i.value; });
      } else if (type === 'radio') {
        var r = fld.querySelector('input[type=radio]:checked'); v[name] = r ? r.value : '';
      } else if (type === 'consent') {
        v[name] = fld.querySelector('input[type=checkbox]').checked ? 'Sí' : '';
      } else {
        var el = fld.querySelector('input:not([type=hidden]):not(.other), select, textarea, input[type=hidden]');
        v[name] = el ? el.value : '';
      }
    });
    return v;
  }

  function evalCond(cond, vals) {
    if (!cond || !cond.field) return true;
    var raw = vals[cond.field];
    var list = Array.isArray(raw) ? raw.map(String) : (raw === undefined || raw === '' ? [] : [String(raw)]);
    if (cond.not_empty) return list.length > 0;
    var wanted = cond.in ? cond.in.map(String) : (cond.equals !== undefined ? [String(cond.equals)] : []);
    if (!wanted.length) return true;
    return list.some(function (x) { return wanted.indexOf(x) !== -1; });
  }

  function parseShow(el) {
    if (!el.dataset.show) return null;
    try { return JSON.parse(el.dataset.show); } catch (e) { return null; }
  }

  function updateVisibility() {
    var vals = getValues();
    $$('[data-show]').forEach(function (el) {
      var show = evalCond(parseShow(el), vals);
      if (el.classList.contains('step')) { el.dataset.off = show ? '' : '1'; return; }
      el.hidden = !show;
      $$('input, select, textarea', el).forEach(function (i) { i.disabled = !show; });
      if (!show) clearError(el);
    });
    // "Otro" abre su campo de texto
    $$('.field').forEach(function (fld) {
      var other = fld.querySelector('input.other');
      if (!other) return;
      var on = false;
      var sel = fld.querySelector('select');
      if (sel) on = sel.value === '__otro';
      else on = !!fld.querySelector('input[value="__otro"]:checked');
      other.hidden = !on;
      if (!on) other.value = other.value; // se conserva por si vuelve a elegirlo
    });
    updateRules(vals);
  }

  function visibleSteps() { return steps.filter(function (s) { return s.dataset.off !== '1'; }); }

  /* ---------- Avisos suaves (fechas) ---------- */
  function parseDate(s) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; }
  function businessDaysBetween(from, to) {
    var n = 0, d = new Date(from.getTime());
    while (d < to) { d.setDate(d.getDate() + 1); var w = d.getDay(); if (w !== 0 && w !== 6) n++; }
    return n;
  }
  function updateRules(vals) {
    $$('.field[data-rule]').forEach(function (fld) {
      var rule; try { rule = JSON.parse(fld.dataset.rule); } catch (e) { return; }
      var warn = fld.querySelector('.warn'); if (!warn) return;
      var d = parseDate(vals[fld.dataset.name]); var show = false;
      if (d) {
        if (rule.type === 'days_before') {
          var ref = parseDate(vals[rule.ref]);
          if (ref) show = Math.round((ref - d) / 86400000) < rule.days;
        } else if (rule.type === 'business_days_from_today') {
          var t = new Date(); t.setHours(0, 0, 0, 0);
          show = d >= t && businessDaysBetween(t, d) < rule.days;
        }
      }
      warn.hidden = !show;
    });
  }

  /* ---------- Validación ---------- */
  function setError(fld, msg) {
    fld.classList.add('has-error');
    var e = fld.querySelector('.err'); if (e) e.textContent = msg;
  }
  function clearError(fld) {
    fld.classList.remove('has-error');
    var e = fld.querySelector('.err'); if (e) e.textContent = '';
  }

  function validateField(fld) {
    if (fld.hidden || fld.dataset.type === 'info' || fld.dataset.type === 'switch') return true;
    var type = fld.dataset.type, req = fld.dataset.required === '1';
    if (type === 'repeater') return validateRepeater(fld, req);
    var empty = false, msg = 'Este dato es obligatorio.';
    var input = fld.querySelector('input:not(.other):not([type=hidden]), select, textarea');
    var val = '';

    if (type === 'checkbox' || type === 'palette') {
      var checked = $$('input[type=checkbox]:checked', fld);
      empty = checked.length === 0;
      if (!empty && fld.dataset.min && checked.length < +fld.dataset.min) { setError(fld, 'Elegí al menos ' + fld.dataset.min + '.'); return false; }
      if (!empty) {
        var other = fld.querySelector('input.other');
        if (other && checked.some(function (c) { return c.value === '__otro'; }) && !other.value.trim()) { setError(fld, 'Contanos cuál.'); return false; }
      }
    } else if (type === 'radio') {
      var r = fld.querySelector('input[type=radio]:checked'); empty = !r;
      if (r && r.value === '__otro') { var o = fld.querySelector('input.other'); if (o && !o.value.trim()) { setError(fld, 'Contanos cuál.'); return false; } }
    } else if (type === 'consent') {
      empty = !fld.querySelector('input[type=checkbox]').checked; msg = 'Necesitamos que aceptes para continuar.';
    } else if (type === 'file') {
      var lk = fld.querySelector('.linkalt input');
      empty = !fld.querySelector('input[type=file]').files.length && !(lk && lk.value.trim());
      msg = lk ? 'Subí una foto o pegá un link.' : 'Adjuntá al menos un archivo.';
      if (lk && lk.value.trim()) {
        var lu = /^https?:\/\//i.test(lk.value.trim()) ? lk.value.trim() : 'https://' + lk.value.trim();
        try { var lp = new URL(lu); if (lp.hostname.indexOf('.') === -1) throw 0; } catch (e) { setError(fld, 'El link no parece válido: copialo completo desde Drive, Google Fotos o WeTransfer.'); return false; }
      }
    } else if (type === 'colors') {
      var h = fld.querySelector('input[type=hidden]'); empty = !h.value;
      if (!empty && fld.dataset.need && h.value.split(',').length < +fld.dataset.need) { if (req) { setError(fld, 'Elegí los ' + fld.dataset.need + ' colores.'); return false; } }
    } else if (type === 'select') {
      val = input.value; empty = val === '';
      if (val === '__otro') { var os = fld.querySelector('input.other'); if (os && !os.value.trim()) { setError(fld, 'Contanos cuál.'); return false; } }
    } else {
      val = (input.value || '').trim(); empty = val === '';
    }

    if (empty) { if (req) { setError(fld, msg); return false; } clearError(fld); return true; }

    if (type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) { setError(fld, 'Revisá el email: parece que falta algo.'); return false; }
    if (type === 'tel' && fld.dataset.digits) { var dg = val.replace(/\D+/g, ''); if (dg.length < 8 || dg.length > 15) { setError(fld, 'Escribí el número con código de país, por ejemplo +54 9 280 412 3456.'); return false; } }
    else if (type === 'tel' && !/^[+()\d\s.\-]{6,25}$/.test(val)) { setError(fld, 'Ingresá un teléfono válido, por ejemplo +54 9 280 123 4567.'); return false; }
    if (type === 'datetime') {
      if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(val)) { setError(fld, 'Elegí la fecha y la hora.'); return false; }
      var rl = ruleOf(fld);
      if (rl && rl.type === 'before') {
        var refv = (getValues()[rl.ref] || '').slice(0, 16);
        if (refv && val.slice(0, 16) >= refv) { setError(fld, rl.message || 'Tiene que ser antes de la fecha del evento.'); return false; }
      }
    }
    if (type === 'url') {
      var u = /^https?:\/\//i.test(val) ? val : 'https://' + val;
      try { var parsed = new URL(u); if (parsed.hostname.indexOf('.') === -1) throw 0; } catch (e) { setError(fld, 'Pegá un enlace válido (que empiece con https://).'); return false; }
    }
    if (type === 'number' && isNaN(Number(val))) { setError(fld, 'Ingresá solo números.'); return false; }
    if (type === 'file') {
      var fi = fld.querySelector('input[type=file]'), maxB = (+fi.dataset.maxMb || 15) * 1048576, maxN = +fi.dataset.maxFiles || 12;
      if (fi.files.length > maxN) { setError(fld, 'Podés subir hasta ' + maxN + ' archivos por campo.'); return false; }
      for (var k = 0; k < fi.files.length; k++) if (fi.files[k].size > maxB) { setError(fld, '“' + fi.files[k].name + '” supera los ' + fi.dataset.maxMb + ' MB.'); return false; }
      var acc = (fi.getAttribute('accept') || '').split(',').filter(function (a) { return a.charAt(0) === '.'; });
      if (acc.length) for (var q = 0; q < fi.files.length; q++) {
        var ext = '.' + (fi.files[q].name.split('.').pop() || '').toLowerCase();
        if (acc.indexOf(ext) === -1) { setError(fld, 'El formato de “' + fi.files[q].name + '” no está permitido (usá ' + acc.join(', ').toUpperCase().replace(/\./g, '') + ').'); return false; }
      }
    }
    clearError(fld); return true;
  }

  function ruleOf(fld) { try { return fld.dataset.rule ? JSON.parse(fld.dataset.rule) : null; } catch (e) { return null; } }

  function validateRepeater(fld, req) {
    var rows = $$('.rep__row', fld.querySelector('.rep')), done = 0, msg = null;
    rows.forEach(function (row, i) {
      var subs = $$('[data-sub]', row), filled = subs.filter(function (s) { var el = s.querySelector('input,select,textarea'); return el && el.value.trim() !== ''; });
      if (!filled.length) return;
      var missing = subs.filter(function (s) { var el = s.querySelector('input,select,textarea'); return s.dataset.required === '1' && (!el || el.value.trim() === ''); });
      if (missing.length && !msg) msg = 'En la fila ' + (i + 1) + ' falta “' + missing[0].querySelector('span').textContent + '”.';
      else if (!missing.length) done++;
    });
    if (msg) { setError(fld, msg); return false; }
    var need = Math.max(1, +(fld.querySelector('.rep').dataset.minRows || 1));
    if (req && done < need) { setError(fld, need > 1 ? 'Completá al menos ' + need + '.' : 'Completá al menos una fila.'); return false; }
    clearError(fld); return true;
  }

  function validateStep(step) {
    var first = null;
    $$('.field[data-name]', step).forEach(function (fld) {
      if (!validateField(fld) && !first) first = fld;
    });
    if (first) {
      var focusable = first.querySelector('input:not([type=hidden]):not(.other), select, textarea');
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (focusable && focusable.type !== 'file') { try { focusable.focus({ preventScroll: true }); } catch (e) {} }
    }
    return !first;
  }

  /* ---------- Navegación ---------- */
  var backToReview = null;
  function showStep(step, silent) {
    updateVisibility();
    if (step && step.hasAttribute('data-review-step')) buildReview(step);
    var vis = visibleSteps();
    if (vis.indexOf(step) === -1) step = vis[0];
    steps.forEach(function (s) { s.hidden = s !== step; });
    current = step;
    var idx = vis.indexOf(step), n = vis.length;
    pLabel.textContent = 'Paso ' + (idx + 1) + ' de ' + n;
    pTitle.textContent = step.dataset.title;
    fill.style.width = Math.round(((idx + 1) / n) * 100) + '%';
    btnPrev.hidden = idx === 0;
    btnNext.hidden = idx === n - 1;
    btnSubmit.hidden = idx !== n - 1;
    if (backToReview && step !== backToReview) { btnNext.hidden = false; btnNext.firstChild.nodeValue = 'Volver a la revisión '; }
    else { backToReview = step === backToReview ? null : backToReview; btnNext.firstChild.nodeValue = 'Siguiente '; }
    if (!silent) {
      var top = document.getElementById('progress');
      window.scrollTo({ top: Math.max(0, top.getBoundingClientRect().top + window.pageYOffset - 12), behavior: 'smooth' });
    }
    saveDraft();
  }

  function go(delta) {
    var vis = visibleSteps(), i = vis.indexOf(current) + delta;
    if (i < 0 || i >= vis.length) return;
    showStep(vis[i]);
  }

  btnNext.addEventListener('click', function () {
    if (!validateStep(current)) return;
    if (backToReview) { var r = backToReview; backToReview = null; showStep(r); return; }
    go(1);
  });
  btnPrev.addEventListener('click', function () { go(-1); });

  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && !['submit', 'button'].includes(e.target.type)) {
      e.preventDefault();
      if (!btnNext.hidden) btnNext.click();
    }
  });

  form.addEventListener('submit', function (e) {
    updateVisibility();
    var bad = null;
    visibleSteps().forEach(function (s) { if (!bad) { var ok = true; $$('.field[data-name]', s).forEach(function (f) { if (!validateField(f)) ok = false; }); if (!ok) bad = s; } });
    if (bad) {
      e.preventDefault();
      showStep(bad, true);
      validateStep(bad);
      return;
    }
    btnSubmit.disabled = true; btnSubmit.textContent = 'Enviando…';
    // Al enviar bien, el servidor redirige a "gracias" y ahí se limpia el borrador.
  });

  /* ---------- Borrador (localStorage) ---------- */
  var saveTimer = null;
  function serialize() {
    var data = {};
    $$('input, select, textarea').forEach(function (el) {
      if (!el.name || el.name === '_csrf' || el.name === '_t' || el.name === 'website' || el.type === 'file' || el.hasAttribute('data-nodraft') || el.closest('template')) return;
      if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; return; }
      if (el.type === 'checkbox') { (data[el.name] = data[el.name] || []); if (el.checked) data[el.name].push(el.value); return; }
      data[el.name] = el.value;
    });
    return data;
  }
  function saveDraft() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      try {
        localStorage.setItem(KEY, JSON.stringify({ v: serialize(), step: steps.indexOf(current), ts: Date.now() }));
      } catch (e) {}
    }, 300);
  }
  function restoreDraft() {
    var raw; try { raw = localStorage.getItem(KEY); } catch (e) { return null; }
    if (!raw) return null;
    var d; try { d = JSON.parse(raw); } catch (e) { return null; }
    if (!d || !d.v || Date.now() - d.ts > 30 * 86400000) return null;
    var any = false;
    // filas repetibles: crear las que hagan falta antes de rellenar
    $$('[data-rep]').forEach(function (rep) {
      var fname = rep.closest('.field').dataset.name, max = -1, re = new RegExp('^f\\[' + fname + '\\]\\[(\\d+)\\]');
      Object.keys(d.v).forEach(function (k) { var m = re.exec(k); if (m) max = Math.max(max, +m[1]); });
      while ($$('.rep__row', rep).length < max + 1) addRow(rep);
    });
    // epígrafes de fotos: se guardan pero no las fotos
    $$('input, select, textarea').forEach(function (el) {
      if (!el.name || !(el.name in d.v) || el.type === 'file' || el.name === '_csrf' || el.name === '_t' || el.name === 'website' || el.hasAttribute('data-nodraft')) return;
      var v = d.v[el.name];
      if (el.type === 'radio') { el.checked = (el.value === v); if (el.checked) any = true; }
      else if (el.type === 'checkbox') { el.checked = Array.isArray(v) && v.indexOf(el.value) !== -1; if (el.checked) any = true; }
      else if (v !== '' && v !== el.value) { el.value = v; any = true; }
    });
    // colores: reconstruir estado visual
    $$('[data-colors]').forEach(function (box) {
      var hid = box.querySelector('input[type=hidden]'); var parts = hid.value ? hid.value.split(',').map(function (x) { return x.trim(); }) : [];
      $$('.slot', box).forEach(function (slot, i) {
        var inp = slot.querySelector('input'); if (parts[i]) { inp.value = parts[i]; inp.dataset.set = '1'; slot.classList.add('is-set'); } else { delete inp.dataset.set; slot.classList.remove('is-set'); }
      });
    });
    return any ? d : null;
  }

  form.addEventListener('input', saveDraft);
  form.addEventListener('change', function (e) {
    updateVisibility(); saveDraft();
    var fld = e.target.closest && e.target.closest('.field');
    if (fld && fld.classList.contains('has-error')) validateField(fld);
  });
  document.getElementById('draftReset').addEventListener('click', function () {
    try { localStorage.removeItem(KEY); } catch (e) {}
    location.reload();
  });

  /* ---------- Archivos ---------- */
  function fmt(n) { return n < 1048576 ? Math.max(1, Math.round(n / 1024)) + ' KB' : (n / 1048576).toFixed(1) + ' MB'; }
  /* Achica fotos grandes en el celular antes de subirlas (más rápido y sin fallar por peso). */
  function shrink(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 900 * 1024 || !window.createImageBitmap) return resolve(file);
      createImageBitmap(file).then(function (bmp) {
        var MAX = 2400, sc = Math.min(1, MAX / Math.max(bmp.width, bmp.height));
        var c = document.createElement('canvas'); c.width = Math.round(bmp.width * sc); c.height = Math.round(bmp.height * sc);
        var ctx = c.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height); ctx.drawImage(bmp, 0, 0, c.width, c.height);
        c.toBlob(function (b) {
          if (!b || b.size >= file.size) return resolve(file);
          resolve(new File([b], file.name.replace(/\.(png|webp|jpe?g)$/i, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', 0.86);
      }).catch(function () { resolve(file); });
    });
  }
  $$('[data-drop]').forEach(function (drop) {
    var input = drop.querySelector('input[type=file]'), list = drop.querySelector('.drop__list');
    var capName = drop.dataset.captions, capMax = +drop.dataset.captionMax || 160, caps = [], thumbs = [];
    function render() {
      list.innerHTML = '';
      thumbs.forEach(function (u) { URL.revokeObjectURL(u); }); thumbs = [];
      Array.prototype.forEach.call(input.files, function (f, i) {
        var li = document.createElement('li');
        if (/^image\//.test(f.type)) { var im = document.createElement('img'); im.className = 'drop__thumb'; im.alt = ''; im.src = URL.createObjectURL(f); thumbs.push(im.src); li.appendChild(im); }
        var body = document.createElement('div'); body.className = 'drop__item';
        var name = document.createElement('span'); name.textContent = f.name + ' · ' + fmt(f.size); body.appendChild(name);
        if (capName) {
          var cap = document.createElement('input'); cap.type = 'text'; cap.name = 'f[' + capName + '__cap][]'; cap.maxLength = capMax;
          cap.placeholder = 'Texto para esta foto (opcional)'; cap.value = caps[i] || ''; cap.setAttribute('data-nodraft', '');
          cap.addEventListener('input', function () { caps[i] = cap.value; });
          body.appendChild(cap);
        }
        li.appendChild(body);
        var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'linklike'; rm.textContent = 'Quitar';
        rm.addEventListener('click', function () {
          var dt = new DataTransfer();
          Array.prototype.forEach.call(input.files, function (x, j) { if (j !== i) dt.items.add(x); });
          caps.splice(i, 1);
          input.files = dt.files; render();
          var fl = drop.closest('.field'); if (fl.classList.contains('has-error')) validateField(fl);
        });
        li.appendChild(rm); list.appendChild(li);
      });
      drop.classList.toggle('has-files', input.files.length > 0);
    }
    var prevFiles = [];
    input.addEventListener('change', function () {
      var picked = Array.prototype.slice.call(input.files), max = +input.dataset.maxFiles || 12;
      // Si ya había fotos y el campo admite varias, se suman a las anteriores.
      var all = (input.multiple ? prevFiles.concat(picked) : picked).slice(0, max);
      var cta = drop.querySelector('.drop__cta b'), old = cta.textContent;
      var work = drop.dataset.compress ? Promise.all(all.map(shrink)) : Promise.resolve(all);
      if (drop.dataset.compress) { cta.textContent = 'Preparando fotos…'; }
      work.then(function (files) {
        var dt = new DataTransfer(); files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files; prevFiles = files; cta.textContent = old; render();
        var fl = drop.closest('.field'); validateField(fl);
      });
    });
    input.addEventListener('click', function () { prevFiles = Array.prototype.slice.call(input.files); });
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
  });

  /* ---------- Colores ---------- */
  function toHex(txt) {
    txt = (txt || '').trim().toLowerCase();
    var m = /^#?([0-9a-f]{6})$/.exec(txt); if (m) return '#' + m[1];
    m = /^#?([0-9a-f])([0-9a-f])([0-9a-f])$/.exec(txt); if (m) return '#' + m[1] + m[1] + m[2] + m[2] + m[3] + m[3];
    m = /^rgba?\(?\s*(\d{1,3})\s*[, ]\s*(\d{1,3})\s*[, ]\s*(\d{1,3})/.exec(txt);
    if (m && +m[1] < 256 && +m[2] < 256 && +m[3] < 256) return '#' + [m[1], m[2], m[3]].map(function (x) { return (+x).toString(16).padStart(2, '0'); }).join('');
    return null;
  }
  $$('[data-colors]').forEach(function (box) {
    var hid = box.querySelector('input[type=hidden]'), prev = box.parentNode.querySelector('[data-cprev]');
    var pickers = $$('.slot input[type=color]', box);
    function sync() {
      // Se guardan en orden y sin huecos solo si están todos; si falta uno, se guardan los elegidos.
      var vals = pickers.filter(function (i) { return i.dataset.set === '1'; }).map(function (i) { return i.value; });
      hid.value = vals.join(', ');
      pickers.forEach(function (i) {
        var s = i.closest('.slot'); s.classList.toggle('is-set', i.dataset.set === '1');
        s.querySelector('.slot__dot').style.background = i.dataset.set === '1' ? i.value : '';
        var hx = i.closest('.slotwrap') && i.closest('.slotwrap').querySelector('.slot__hex');
        if (hx && document.activeElement !== hx) hx.value = i.dataset.set === '1' ? i.value : '';
      });
      if (prev) {
        var c = pickers.map(function (i) { return i.dataset.set === '1' ? i.value : ''; });
        prev.style.setProperty('--c1', c[0] || '#3b3025'); prev.style.setProperty('--c2', c[1] || '#c2a15e'); prev.style.setProperty('--c3', c[2] || '#fbf7ef');
      }
      saveDraft();
    }
    pickers.forEach(function (i) {
      i.addEventListener('input', function () { i.dataset.set = '1'; sync(); });
      var hx = i.closest('.slotwrap') && i.closest('.slotwrap').querySelector('.slot__hex');
      if (hx) hx.addEventListener('input', function () {
        var v = toHex(hx.value); hx.classList.toggle('is-bad', !!hx.value.trim() && !v);
        if (v) { i.value = v; i.dataset.set = '1'; sync(); }
      });
    });
    box.querySelector('[data-colors-clear]').addEventListener('click', function () {
      pickers.forEach(function (i) { delete i.dataset.set; i.value = '#ffffff'; }); sync();
    });
    sync();
  });

  /* ---------- Filas repetibles (horarios, trivia) ---------- */
  function renumber(rep) {
    var fname = rep.closest('.field').dataset.name;
    $$('.rep__row', rep).forEach(function (row, i) {
      row.querySelector('.rep__n').textContent = i + 1;
      $$('[data-rep-name]', row).forEach(function (el) { el.name = 'f[' + fname + '][' + i + '][' + el.dataset.repName + ']'; });
    });
    var n = $$('.rep__row', rep).length;
    rep.querySelector('[data-rep-add]').hidden = n >= +rep.dataset.maxRows;
    $$('[data-rep-del]', rep).forEach(function (b) { b.hidden = n <= +rep.dataset.minRows; });
  }
  function addRow(rep) {
    var tpl = rep.querySelector('template'), node = tpl.content.firstElementChild.cloneNode(true);
    rep.insertBefore(node, rep.querySelector('[data-rep-add]'));
    renumber(rep); return node;
  }
  $$('[data-rep]').forEach(function (rep) {
    rep.addEventListener('click', function (e) {
      if (e.target.closest('[data-rep-add]')) { var r = addRow(rep); var f = r.querySelector('input,select,textarea'); if (f) f.focus(); saveDraft(); }
      var del = e.target.closest('[data-rep-del]');
      if (del) { del.closest('.rep__row').remove(); renumber(rep); saveDraft(); }
    });
    renumber(rep);
  });

  /* ---------- Contador de caracteres ---------- */
  function updateCount(el) {
    var c = el.parentNode.querySelector('.count'); if (!c) return;
    var max = +el.dataset.max, len = el.value.length, left = max - len;
    c.hidden = left > Math.max(15, Math.round(max * 0.2));
    c.textContent = left <= 0 ? 'Llegaste al máximo (' + max + ')' : 'Te quedan ' + left + ' caracteres';
    c.classList.toggle('is-full', left <= 0);
  }
  var revTimer = null;
  form.addEventListener('input', function (e) {
    if (e.target.dataset && e.target.dataset.max) updateCount(e.target);
    var fld = e.target.closest && e.target.closest('.field');
    if (fld && fld.classList.contains('has-error')) { clearTimeout(revTimer); revTimer = setTimeout(function () { validateField(fld); }, 250); }
  });

  /* ---------- Fecha límite sugerida (10 días antes) ---------- */
  $$('.field[data-rule]').forEach(function (fld) {
    var rl = ruleOf(fld); if (!rl || rl.type !== 'before' || !rl.suggest_days) return;
    var ref = form.querySelector('.field[data-name="' + rl.ref + '"] input'), mine = fld.querySelector('input');
    if (!ref || !mine) return;
    ref.addEventListener('change', function () {
      if (mine.value || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(ref.value)) return;
      var d = new Date(ref.value); d.setDate(d.getDate() - rl.suggest_days);
      var p = function (n) { return String(n).padStart(2, '0'); };
      mine.value = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
      var w = fld.querySelector('.warn'); if (w) { w.hidden = false; w.textContent = 'Te sugerimos 10 días antes del evento. Podés cambiarla.'; }
      saveDraft();
    });
  });

  /* ---------- Mapa para confirmar la dirección ---------- */
  $$('[data-map]').forEach(function (box) {
    var inp = box.parentNode.querySelector('input'), frame = box.querySelector('iframe'), t = null, last = '';
    function upd() {
      var q = inp.value.trim(); var venue = form.querySelector('.field[data-name="venue"] input');
      if (q.length < 6) { box.hidden = true; return; }
      var full = (venue && venue.value.trim() ? venue.value.trim() + ', ' : '') + q;
      if (full === last) return; last = full;
      frame.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(full) + '&z=15&output=embed';
      box.hidden = false;
    }
    inp.addEventListener('input', function () { clearTimeout(t); t = setTimeout(upd, 900); });
    inp.addEventListener('change', upd);
    if (inp.value) upd();
  });

  /* ---------- Revisión final ---------- */
  function fieldSummary(fld) {
    var type = fld.dataset.type, label = (fld.querySelector('.label, .switchq__text b, .consent__text b') || {}).textContent || '';
    label = label.replace(/\s*\*$/, '');
    var val = '';
    if (type === 'info' || type === 'consent') return null;
    if (type === 'switch') return null;
    if (type === 'radio') { var r = fld.querySelector('input:checked'); val = r ? (r.value === '__otro' ? 'Otro: ' + (fld.querySelector('input.other') || {}).value : r.closest('.choice').querySelector('b').textContent) : ''; }
    else if (type === 'checkbox' || type === 'palette') val = $$('input[type=checkbox]:checked', fld).map(function (c) { return c.value === '__otro' ? 'Otro: ' + (fld.querySelector('input.other') || {}).value : c.closest('.choice').querySelector('b').textContent; }).join(', ');
    else if (type === 'file') {
      var fi = fld.querySelector('input[type=file]'), lk = fld.querySelector('.linkalt input');
      var names = Array.prototype.map.call(fi.files, function (f) { return f.name; });
      val = names.length ? names.length + (names.length === 1 ? ' archivo' : ' archivos') : '';
      if (lk && lk.value.trim()) val += (val ? ' · ' : '') + 'Link: ' + lk.value.trim();
    }
    else if (type === 'colors') val = fld.querySelector('input[type=hidden]').value;
    else if (type === 'repeater') val = $$('.rep__row', fld).map(function (row) { return $$('input,select,textarea', row).map(function (el) { return el.tagName === 'SELECT' ? (el.value !== '' ? 'Correcta: ' + el.options[el.selectedIndex].text : '') : el.value.trim(); }).filter(Boolean).join(' · '); }).filter(Boolean).join('\n');
    else if (type === 'datetime') { var dv = fld.querySelector('input').value; var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}:\d{2})/.exec(dv); val = m ? m[3] + '/' + m[2] + '/' + m[1] + ' · ' + m[4] + ' hs' : dv; }
    else { var el = fld.querySelector('input:not([type=hidden]):not(.other), select, textarea'); val = el ? (el.tagName === 'SELECT' && el.value ? el.options[el.selectedIndex].text : el.value.trim()) : ''; }
    return { label: label, value: val };
  }
  function buildReview(reviewStep) {
    var box = reviewStep.querySelector('[data-review]'); if (!box) return;
    updateVisibility();
    box.innerHTML = '';
    visibleSteps().forEach(function (st) {
      if (st === reviewStep) return;
      var sw = st.querySelector('.field[data-type=switch]');
      var off = sw && !sw.querySelector('input[type=checkbox]').checked;
      var card = document.createElement('div'); card.className = 'review__card' + (off ? ' is-off' : '');
      var head = document.createElement('div'); head.className = 'review__head';
      var h = document.createElement('b'); h.textContent = st.dataset.title; head.appendChild(h);
      var ed = document.createElement('button'); ed.type = 'button'; ed.className = 'btn btn--ghost btn--sm'; ed.textContent = 'Editar';
      ed.addEventListener('click', function () { backToReview = reviewStep; showStep(st); });
      head.appendChild(ed); card.appendChild(head);
      var dl = document.createElement('dl');
      if (off) { var p = document.createElement('p'); p.className = 'review__off'; p.textContent = 'No va en tu invitación.'; card.appendChild(p); }
      else $$('.field[data-name]', st).forEach(function (fld) {
        if (fld.hidden) return; var s = fieldSummary(fld); if (!s || !s.value) return;
        var dt = document.createElement('dt'); dt.textContent = s.label; var dd = document.createElement('dd'); dd.textContent = s.value;
        dl.appendChild(dt); dl.appendChild(dd);
      });
      if (dl.childNodes.length) card.appendChild(dl);
      box.appendChild(card);
    });
  }

  /* ---------- Inicio ---------- */
  $$('[data-max]').forEach(updateCount);
  var restored = hasServerErrors ? null : restoreDraft();
  updateVisibility();
  var start = steps[0];
  if (hasServerErrors) {
    var badField = form.querySelector('.field.has-error');
    if (badField) start = badField.closest('.step');
  } else if (restored) {
    banner.hidden = false;
    var vis = visibleSteps();
    start = steps[restored.step] && vis.indexOf(steps[restored.step]) !== -1 ? steps[restored.step] : vis[0];
  }
  showStep(start, true);
  if (hasServerErrors) { var e0 = form.querySelector('.field.has-error'); if (e0) setTimeout(function () { e0.scrollIntoView({ block: 'center' }); }, 60); }
})();
