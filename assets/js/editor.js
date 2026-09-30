/* Editor visual de formularios (panel) — sin dependencias */
(function () {
  'use strict';
  var TYPES = window.__TYPES__ || {};
  var CHOICE = ['select', 'radio', 'checkbox', 'palette'];
  var TEXTLIKE = ['text', 'textarea', 'email', 'tel', 'url', 'number'];
  var steps = window.__STEPS__ || [];
  var root = document.getElementById('builder');
  var form = document.getElementById('editForm');
  var jsonBox = document.getElementById('stepsJson');
  var adv = document.getElementById('advJson');
  if (!root || !form) return;

  /* ---------- Utilidades ---------- */
  function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function slug(s) {
    return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
  }
  function allNames() { var n = {}; steps.forEach(function (s) { (s.fields || []).forEach(function (f) { n[f.name] = 1; }); }); return n; }
  function uniqueName(base) {
    var used = allNames(), name = base, i = 2;
    while (used[name]) name = base + '_' + i++;
    return name;
  }
  function findField(name) {
    for (var i = 0; i < steps.length; i++) for (var j = 0; j < steps[i].fields.length; j++) if (steps[i].fields[j].name === name) return steps[i].fields[j];
    return null;
  }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function clean(o) {
    return JSON.parse(JSON.stringify(o, function (k, v) { return k.charAt(0) === '_' ? undefined : v; }));
  }

  /* ---------- Opciones <-> texto ---------- */
  function optsToText(f) {
    return (f.options || []).map(function (o) {
      var parts = [o.label];
      var colors = (o.colors || []).join(',');
      if (o.desc || colors) parts.push(o.desc || '');
      if (colors) parts.push(colors);
      return parts.join(' | ');
    }).join('\n');
  }
  function textToOpts(text, prev) {
    var byLabel = {};
    (prev || []).forEach(function (o) { byLabel[o.label] = o; });
    var out = [], seen = {};
    text.split(/\r?\n/).forEach(function (line) {
      line = line.trim(); if (!line) return;
      var p = line.split('|').map(function (x) { return x.trim(); });
      var label = p[0]; if (!label) return;
      var old = byLabel[label];
      var o = { value: old ? old.value : label, label: label };
      if (p[1]) o.desc = p[1];
      if (p[2]) o.colors = p[2].split(',').map(function (c) { return c.trim(); }).filter(function (c) { return /^#[0-9a-fA-F]{6}$/.test(c); });
      if (seen[o.value]) return; seen[o.value] = 1;
      out.push(o);
    });
    return out;
  }

  /* ---------- "Mostrar solo si" ---------- */
  function condHtml(obj, path, selfName) {
    var c = obj.show_if || null;
    var opts = '<option value="">Siempre visible</option>';
    steps.forEach(function (s) {
      (s.fields || []).forEach(function (f) {
        if (f.type === 'info' || f.type === 'file' || f.name === selfName) return;
        opts += '<option value="' + esc(f.name) + '"' + (c && c.field === f.name ? ' selected' : '') + '>' + esc(f.label || f.name) + '</option>';
      });
    });
    var html = '<div class="cond"><span>Mostrar</span><select data-cond="' + path + '" data-part="field">' + opts + '</select>';
    if (c && c.field) {
      var op = c.not_empty ? 'not_empty' : (c['in'] ? 'in' : 'equals');
      html += '<select data-cond="' + path + '" data-part="op">' +
        '<option value="equals"' + (op === 'equals' ? ' selected' : '') + '>si es igual a</option>' +
        '<option value="in"' + (op === 'in' ? ' selected' : '') + '>si es alguno de</option>' +
        '<option value="not_empty"' + (op === 'not_empty' ? ' selected' : '') + '>si tiene respuesta</option></select>';
      if (op === 'equals') {
        var src = findField(c.field);
        if (src && src.options && src.options.length) {
          html += '<select data-cond="' + path + '" data-part="value"><option value="">Elegí…</option>' + src.options.map(function (o) {
            return '<option value="' + esc(o.value) + '"' + (String(c.equals) === String(o.value) ? ' selected' : '') + '>' + esc(o.label) + '</option>';
          }).join('') + '</select>';
        } else {
          html += '<input type="text" data-cond="' + path + '" data-part="value" value="' + esc(c.equals == null ? '' : c.equals) + '" placeholder="valor">';
        }
      } else if (op === 'in') {
        html += '<input type="text" data-cond="' + path + '" data-part="value" value="' + esc((c['in'] || []).join(', ')) + '" placeholder="valor1, valor2">';
      }
    }
    return html + '</div>';
  }

  /* ---------- Render ---------- */
  function fieldHtml(f, si, fi, total) {
    var p = 's' + si + '.f' + fi;
    var open = f._open;
    var typeOpts = Object.keys(TYPES).map(function (k) { return '<option value="' + k + '"' + (f.type === k ? ' selected' : '') + '>' + esc(TYPES[k]) + '</option>'; }).join('');
    var isInfo = f.type === 'info';
    var head = '<div class="fld__head">' +
      '<button type="button" class="fld__toggle" data-act="toggle" data-s="' + si + '" data-f="' + fi + '" aria-expanded="' + (!!open) + '">' +
      '<span class="fld__type">' + esc(TYPES[f.type] || f.type) + '</span>' +
      '<span class="fld__label" data-title="' + p + '">' + esc(f.label || (isInfo ? (f.help || '').slice(0, 50) : '') || '(sin título)') + '</span>' +
      (f.required ? '<span class="fld__req">obligatorio</span>' : '') +
      (f.show_if ? '<span class="fld__cond">condicional</span>' : '') +
      '</button><span class="fld__tools">' +
      '<button type="button" title="Subir" data-act="field-up" data-s="' + si + '" data-f="' + fi + '"' + (fi === 0 ? ' disabled' : '') + '>↑</button>' +
      '<button type="button" title="Bajar" data-act="field-down" data-s="' + si + '" data-f="' + fi + '"' + (fi === total - 1 ? ' disabled' : '') + '>↓</button>' +
      '<button type="button" title="Duplicar" data-act="field-dup" data-s="' + si + '" data-f="' + fi + '">⧉</button>' +
      '<button type="button" title="Quitar" class="danger" data-act="field-del" data-s="' + si + '" data-f="' + fi + '">✕</button></span></div>';
    if (!open) return '<li class="fld">' + head + '</li>';

    var body = '<div class="fld__body">';
    body += '<div class="egrid egrid--tight">';
    body += '<div class="field"><label class="label">Tipo</label><select data-bind="' + p + '.type" data-rerender="1">' + typeOpts + '</select></div>';
    if (isInfo) {
      body += '<div class="field"><label class="label">Título (opcional)</label><input type="text" data-bind="' + p + '.label" value="' + esc(f.label) + '"></div>';
      body += '<div class="field field--wide"><label class="label">Texto</label><textarea rows="3" data-bind="' + p + '.help">' + esc(f.help) + '</textarea></div>';
    } else {
      body += '<div class="field"><label class="label">Pregunta</label><input type="text" data-bind="' + p + '.label" value="' + esc(f.label) + '"></div>';
      body += '<div class="field field--wide"><label class="label">Ayuda debajo de la pregunta</label><input type="text" data-bind="' + p + '.help" value="' + esc(f.help) + '"></div>';
      if (TEXTLIKE.indexOf(f.type) !== -1 && f.type !== 'textarea' || f.type === 'textarea') {
        body += '<div class="field"><label class="label">Texto de ejemplo</label><input type="text" data-bind="' + p + '.placeholder" value="' + esc(f.placeholder) + '"></div>';
      }
      if (f.type === 'textarea') body += '<div class="field"><label class="label">Alto (líneas)</label><input type="number" min="2" max="20" data-bind="' + p + '.rows" value="' + esc(f.rows || 4) + '"></div>';
      if (f.type === 'file') body += '<div class="field"><label class="label">Tipo de archivos</label><select data-bind="' + p + '.accept"><option value="">Cualquiera permitido</option><option value="image/*"' + (f.accept === 'image/*' ? ' selected' : '') + '>Solo imágenes</option></select></div>';
      if (CHOICE.indexOf(f.type) !== -1) {
        body += '<div class="field field--wide"><label class="label">Opciones <small>(una por línea' + (f.type === 'palette' ? ': Nombre | descripción | #color1,#color2,#color3' : '; opcional “Nombre | descripción”') + ')</small></label>' +
          '<textarea rows="5" data-opts="' + p + '">' + esc(optsToText(f)) + '</textarea></div>';
        body += '<div class="field field--wide checks">' +
          (f.type !== 'palette' ? '<label class="chk"><input type="checkbox" data-bind="' + p + '.other"' + (f.other ? ' checked' : '') + '> Permitir “Otro” con texto libre</label>' : '') +
          (f.type === 'radio' ? '<label class="chk"><input type="checkbox" data-bind="' + p + '.cards"' + (f.cards ? ' checked' : '') + '> Mostrar como tarjetas grandes</label>' : '') + '</div>';
      }
      body += '<div class="field field--wide checks">' +
        '<label class="chk"><input type="checkbox" data-bind="' + p + '.required"' + (f.required ? ' checked' : '') + '> Obligatorio</label>' +
        (['text', 'email', 'tel', 'url', 'date', 'time', 'number', 'select'].indexOf(f.type) !== -1 ? '<label class="chk"><input type="checkbox" data-bind="' + p + '.layout" data-half="1"' + (f.layout === 'half' ? ' checked' : '') + '> Ocupar media línea (en pantallas grandes)</label>' : '') + '</div>';
      if (f.rule) body += '<div class="field field--wide"><p class="help">Este campo tiene un aviso automático de fechas configurado (se conserva). Se edita en modo avanzado.</p></div>';
    }
    body += '<div class="field field--wide">' + condHtml(f, p, f.name) + '</div>';
    body += '<div class="field field--wide"><p class="help">ID interno: <code>' + esc(f.name) + '</code></p></div>';
    body += '</div></div>';
    return '<li class="fld is-open">' + head + body + '</li>';
  }

  function stepHtml(s, si) {
    var p = 's' + si;
    var fields = (s.fields || []).map(function (f, fi) { return fieldHtml(f, si, fi, s.fields.length); }).join('');
    var typeAdd = Object.keys(TYPES).map(function (k) { return '<option value="' + k + '">' + esc(TYPES[k]) + '</option>'; }).join('');
    return '<div class="stp">' +
      '<div class="stp__head"><span class="stp__num">Paso ' + (si + 1) + '</span>' +
      '<div class="stp__tools">' +
      '<button type="button" title="Subir paso" data-act="step-up" data-s="' + si + '"' + (si === 0 ? ' disabled' : '') + '>↑</button>' +
      '<button type="button" title="Bajar paso" data-act="step-down" data-s="' + si + '"' + (si === steps.length - 1 ? ' disabled' : '') + '>↓</button>' +
      '<button type="button" title="Quitar paso" class="danger" data-act="step-del" data-s="' + si + '">Quitar paso</button></div></div>' +
      '<div class="egrid egrid--tight">' +
      '<div class="field"><label class="label">Título del paso</label><input type="text" data-bind="' + p + '.title" value="' + esc(s.title) + '"></div>' +
      '<div class="field"><label class="label">Descripción</label><input type="text" data-bind="' + p + '.description" value="' + esc(s.description) + '"></div>' +
      '<div class="field field--wide">' + condHtml(s, p, '') + '</div></div>' +
      '<ul class="flds">' + fields + '</ul>' +
      '<div class="addrow"><select data-newtype="' + si + '">' + typeAdd + '</select><button type="button" class="btn btn--ghost btn--sm" data-act="field-add" data-s="' + si + '">+ Agregar campo</button></div></div>';
  }

  function render() {
    root.innerHTML = steps.map(stepHtml).join('') + '<button type="button" class="btn btn--ghost btn--sm addstep" data-act="step-add">+ Agregar paso</button>';
    refreshMeta();
    adv.value = JSON.stringify(clean(steps), null, 2);
  }

  /* ---------- Ajustes que dependen de los campos ---------- */
  function refreshMeta() {
    var sel = document.getElementById('client_email_field');
    var list = document.getElementById('listFields');
    var curClient = sel.options.length ? sel.value : sel.dataset.value;
    var curList = list.querySelector('input') ? Array.prototype.map.call(list.querySelectorAll('input:checked'), function (i) { return i.value; }) : JSON.parse(list.dataset.values || '[]');
    var html = '<option value="">No enviar confirmación</option>';
    var checks = '';
    steps.forEach(function (s) {
      (s.fields || []).forEach(function (f) {
        if (f.type === 'email') html += '<option value="' + esc(f.name) + '"' + (curClient === f.name ? ' selected' : '') + '>' + esc(f.label || f.name) + '</option>';
        if (f.type !== 'info' && f.type !== 'file') checks += '<label class="chk"><input type="checkbox" name="list_fields[]" value="' + esc(f.name) + '"' + (curList.indexOf(f.name) !== -1 ? ' checked' : '') + '> ' + esc(f.label || f.name) + '</label>';
      });
    });
    sel.innerHTML = html;
    list.innerHTML = checks;
  }
  var metaTimer = null;
  function refreshMetaSoon() { clearTimeout(metaTimer); metaTimer = setTimeout(function () { refreshMeta(); adv.value = JSON.stringify(clean(steps), null, 2); }, 400); }

  /* ---------- Acceso a datos por ruta ("s0.f2.label") ---------- */
  function target(path) {
    var m = /^s(\d+)(?:\.f(\d+))?\.(\w+)$/.exec(path); if (!m) return null;
    var s = steps[+m[1]]; if (!s) return null;
    var obj = m[2] !== undefined ? s.fields[+m[2]] : s;
    return obj ? { obj: obj, key: m[3] } : null;
  }

  function defaultsFor(f) {
    if (CHOICE.indexOf(f.type) !== -1 && !f.options) f.options = [{ value: 'Opción 1', label: 'Opción 1' }, { value: 'Opción 2', label: 'Opción 2' }];
    if (f.type === 'radio' || f.type === 'select' || f.type === 'checkbox' || f.type === 'palette') { if (!f.options) f.options = []; }
    return f;
  }

  /* ---------- Eventos ---------- */
  root.addEventListener('input', function (e) {
    var el = e.target;
    if (el.dataset.opts) {
      var t = target(el.dataset.opts + '.x'); // dummy key
      var m = /^s(\d+)\.f(\d+)$/.exec(el.dataset.opts); var f = steps[+m[1]].fields[+m[2]];
      f.options = textToOpts(el.value, f.options); refreshMetaSoon(); return;
    }
    if (el.dataset.bind && el.type !== 'checkbox' && el.tagName !== 'SELECT') {
      var tg = target(el.dataset.bind); if (!tg) return;
      tg.obj[tg.key] = el.type === 'number' ? (el.value === '' ? '' : Number(el.value)) : el.value;
      var title = root.querySelector('[data-title="' + el.dataset.bind.replace(/\.(label|help)$/, '') + '"]');
      if (title && /\.(label)$/.test(el.dataset.bind)) title.textContent = el.value || '(sin título)';
      refreshMetaSoon();
    }
    if (el.dataset.cond && el.dataset.part === 'value') applyCond(el);
  });

  root.addEventListener('change', function (e) {
    var el = e.target;
    if (el.dataset.cond) { applyCond(el); return; }
    if (el.dataset.newtype !== undefined) return;
    if (el.dataset.bind) {
      var tg = target(el.dataset.bind); if (!tg) return;
      if (el.type === 'checkbox') tg.obj[tg.key] = el.dataset.half ? (el.checked ? 'half' : '') : el.checked;
      else tg.obj[tg.key] = el.value;
      if (el.dataset.rerender) { defaultsFor(tg.obj); render(); } else { refreshMetaSoon(); }
    }
  });

  function applyCond(el) {
    var path = el.dataset.cond, tg = target(path + '.x'); // "s0" o "s0.f1" + clave ficticia
    var m = /^s(\d+)(?:\.f(\d+))?$/.exec(path); var s = steps[+m[1]]; var obj = m[2] !== undefined ? s.fields[+m[2]] : s;
    var part = el.dataset.part, c = obj.show_if || {};
    if (part === 'field') {
      if (!el.value) { delete obj.show_if; } else { obj.show_if = { field: el.value, equals: '' }; }
      render(); return;
    }
    if (part === 'op') {
      var f = c.field; obj.show_if = { field: f };
      if (el.value === 'not_empty') obj.show_if.not_empty = true; else if (el.value === 'in') obj.show_if['in'] = []; else obj.show_if.equals = '';
      render(); return;
    }
    if (part === 'value') {
      if (c['in'] !== undefined) c['in'] = el.value.split(',').map(function (x) { return x.trim(); }).filter(Boolean); else c.equals = el.value;
      obj.show_if = c;
    }
  }

  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]'); if (!b) return;
    var act = b.dataset.act, si = +b.dataset.s, fi = +b.dataset.f;
    var s = steps[si];
    function swap(arr, i, j) { if (j < 0 || j >= arr.length) return; var t = arr[i]; arr[i] = arr[j]; arr[j] = t; }
    switch (act) {
      case 'toggle': s.fields[fi]._open = !s.fields[fi]._open; break;
      case 'field-up': swap(s.fields, fi, fi - 1); break;
      case 'field-down': swap(s.fields, fi, fi + 1); break;
      case 'field-dup': var c = clone(clean(s.fields[fi])); c.name = uniqueName(c.name); c.label = (c.label || '') + ' (copia)'; c._open = true; s.fields.splice(fi + 1, 0, c); break;
      case 'field-del':
        if (!confirm('¿Quitar este campo? Las respuestas anteriores lo conservan igual.')) return;
        s.fields.splice(fi, 1); break;
      case 'field-add':
        var type = root.querySelector('[data-newtype="' + si + '"]').value;
        var f = defaultsFor({ type: type, name: uniqueName('campo'), label: type === 'info' ? '' : 'Nueva pregunta', required: false, help: type === 'info' ? 'Texto informativo' : '', placeholder: '', _open: true });
        s.fields.push(f); break;
      case 'step-up': swap(steps, si, si - 1); break;
      case 'step-down': swap(steps, si, si + 1); break;
      case 'step-del':
        if (!confirm('¿Quitar este paso y todos sus campos?')) return;
        steps.splice(si, 1); break;
      case 'step-add': steps.push({ id: 'paso-' + (steps.length + 1) + '-' + Math.random().toString(36).slice(2, 5), title: 'Nuevo paso', description: '', fields: [] }); break;
    }
    render();
  });

  /* ---------- Modo avanzado ---------- */
  document.getElementById('advApply').addEventListener('click', function () {
    try {
      var v = JSON.parse(adv.value);
      if (!Array.isArray(v)) throw new Error('Tiene que ser una lista de pasos.');
      steps = v.map(function (s) { s.fields = (s.fields || []).map(defaultsFor); return s; });
      render();
    } catch (err) { alert('El JSON no es válido: ' + err.message); }
  });

  /* ---------- Guardar ---------- */
  form.addEventListener('submit', function (e) {
    var problems = [];
    steps.forEach(function (s, i) {
      if (!s.fields || !s.fields.length) problems.push('El paso ' + (i + 1) + ' no tiene campos.');
      (s.fields || []).forEach(function (f) {
        if (f.type !== 'info' && !String(f.label || '').trim()) problems.push('Hay un campo sin pregunta en el paso ' + (i + 1) + '.');
        if (CHOICE.indexOf(f.type) !== -1 && (!f.options || !f.options.length)) problems.push('“' + (f.label || f.name) + '” necesita al menos una opción.');
        if (f.show_if && f.show_if.field && !findField(f.show_if.field)) problems.push('“' + (f.label || f.name) + '” depende de un campo que ya no existe.');
      });
    });
    if (problems.length) { e.preventDefault(); alert('Revisá antes de guardar:\n\n• ' + problems.slice(0, 6).join('\n• ')); return; }
    jsonBox.value = JSON.stringify(clean(steps));
    var checked = form.querySelectorAll('#listFields input:checked');
    if (checked.length > 4) { e.preventDefault(); alert('Elegí hasta 4 columnas para la lista de respuestas.'); }
  });

  // Aviso si hay cambios sin guardar
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('change', function () { dirty = true; });
  root.addEventListener('click', function () { dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  render();
  dirty = false;
})();
