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
      if (type === 'info' || type === 'file') return;
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
    if (fld.hidden || fld.dataset.type === 'info') return true;
    var type = fld.dataset.type, req = fld.dataset.required === '1';
    var empty = false, msg = 'Este dato es obligatorio.';
    var input = fld.querySelector('input:not(.other):not([type=hidden]), select, textarea');
    var val = '';

    if (type === 'checkbox' || type === 'palette') {
      var checked = $$('input[type=checkbox]:checked', fld);
      empty = checked.length === 0;
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
      empty = !fld.querySelector('input[type=file]').files.length; msg = 'Adjuntá al menos un archivo.';
    } else if (type === 'colors') {
      var h = fld.querySelector('input[type=hidden]'); empty = !h.value;
    } else if (type === 'select') {
      val = input.value; empty = val === '';
      if (val === '__otro') { var os = fld.querySelector('input.other'); if (os && !os.value.trim()) { setError(fld, 'Contanos cuál.'); return false; } }
    } else {
      val = (input.value || '').trim(); empty = val === '';
    }

    if (empty) { if (req) { setError(fld, msg); return false; } clearError(fld); return true; }

    if (type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) { setError(fld, 'Revisá el email: parece que falta algo.'); return false; }
    if (type === 'tel' && !/^[+()\d\s.\-]{6,25}$/.test(val)) { setError(fld, 'Ingresá un teléfono válido, por ejemplo +54 9 280 123 4567.'); return false; }
    if (type === 'url') {
      var u = /^https?:\/\//i.test(val) ? val : 'https://' + val;
      try { var parsed = new URL(u); if (parsed.hostname.indexOf('.') === -1) throw 0; } catch (e) { setError(fld, 'Pegá un enlace válido (que empiece con https://).'); return false; }
    }
    if (type === 'number' && isNaN(Number(val))) { setError(fld, 'Ingresá solo números.'); return false; }
    if (type === 'file') {
      var fi = fld.querySelector('input[type=file]'), maxB = (+fi.dataset.maxMb || 15) * 1048576, maxN = +fi.dataset.maxFiles || 12;
      if (fi.files.length > maxN) { setError(fld, 'Podés subir hasta ' + maxN + ' archivos por campo.'); return false; }
      for (var k = 0; k < fi.files.length; k++) if (fi.files[k].size > maxB) { setError(fld, '“' + fi.files[k].name + '” supera los ' + fi.dataset.maxMb + ' MB.'); return false; }
    }
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
  function showStep(step, silent) {
    updateVisibility();
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

  btnNext.addEventListener('click', function () { if (validateStep(current)) go(1); });
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
      if (!el.name || el.name === '_csrf' || el.name === '_t' || el.name === 'website' || el.type === 'file') return;
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
    $$('input, select, textarea').forEach(function (el) {
      if (!el.name || !(el.name in d.v) || el.type === 'file' || el.name === '_csrf' || el.name === '_t' || el.name === 'website') return;
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
  $$('[data-drop]').forEach(function (drop) {
    var input = drop.querySelector('input[type=file]'), list = drop.querySelector('.drop__list');
    function render() {
      list.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (f, i) {
        var li = document.createElement('li');
        var name = document.createElement('span'); name.textContent = f.name + ' · ' + fmt(f.size);
        var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'linklike'; rm.textContent = 'Quitar';
        rm.addEventListener('click', function () {
          var dt = new DataTransfer();
          Array.prototype.forEach.call(input.files, function (x, j) { if (j !== i) dt.items.add(x); });
          input.files = dt.files; render();
        });
        li.appendChild(name); li.appendChild(rm); list.appendChild(li);
      });
      drop.classList.toggle('has-files', input.files.length > 0);
    }
    input.addEventListener('change', render);
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
  });

  /* ---------- Colores ---------- */
  $$('[data-colors]').forEach(function (box) {
    var hid = box.querySelector('input[type=hidden]');
    function sync() {
      var vals = $$('.slot input', box).filter(function (i) { return i.dataset.set === '1'; }).map(function (i) { return i.value; });
      hid.value = vals.join(', ');
      $$('.slot', box).forEach(function (s) {
        var i = s.querySelector('input'); s.classList.toggle('is-set', i.dataset.set === '1');
        s.querySelector('.slot__dot').style.background = i.dataset.set === '1' ? i.value : '';
      });
      saveDraft();
    }
    $$('.slot input', box).forEach(function (i) { i.addEventListener('input', function () { i.dataset.set = '1'; sync(); }); });
    box.querySelector('[data-colors-clear]').addEventListener('click', function () {
      $$('.slot input', box).forEach(function (i) { delete i.dataset.set; i.value = '#ffffff'; }); sync();
    });
    sync();
  });

  /* ---------- Inicio ---------- */
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
