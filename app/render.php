<?php
declare(strict_types=1);

/** Marca de la cabecera según el formulario. */
function brand_html(string $brand = 'jema'): string
{
    foreach (['logo.svg', 'logo.png', 'logo.webp'] as $f) {
        if (is_file(APP_ROOT . '/assets/' . $f) && $brand !== 'planazo') {
            return '<img class="brand__logo" src="' . h(asset($f)) . '" alt="' . h(cfg('app_name', 'Jema')) . '">';
        }
    }
    if ($brand === 'planazo') {
        return '<span class="brand brand--planazo"><span class="brand__main">qué<b>planazo!</b></span><small>POR JEMA</small></span>';
    }
    return '<span class="brand"><span class="brand__main"><b>jema</b></span><small>IMAGEN CREATIVA</small></span>';
}

/** Título con la última palabra en cursiva de color (eco del sitio queplanazo). */
function title_html(string $title): string
{
    $t = trim($title);
    $pos = mb_strrpos($t, ' ');
    if ($pos === false) return '<em>' . h($t) . '</em>';
    return h(mb_substr($t, 0, $pos)) . ' <em>' . h(mb_substr($t, $pos + 1)) . '</em>';
}

function field_value(array $values, string $name)
{
    return $values[$name] ?? null;
}

function is_selected($current, string $value): bool
{
    if (is_array($current)) return in_array($value, array_map('strval', $current), true);
    return (string) $current === $value;
}

/** Devuelve [seleccionadas_normales, texto_otro] separando "Otro: xxx". */
function split_other($current): array
{
    $other = '';
    if (is_array($current)) {
        $keep = [];
        foreach ($current as $c) {
            if (str_starts_with((string) $c, 'Otro: ')) $other = substr((string) $c, 6);
            else $keep[] = $c;
        }
        return [$keep, $other];
    }
    if (is_string($current) && str_starts_with($current, 'Otro: ')) return ['__otro', substr($current, 6)];
    return [$current, ''];
}

function render_field(array $f, array $values, array $errors): string
{
    $name = $f['name'];
    $type = $f['type'];
    $id = 'f_' . $name;
    $err = $errors[$name] ?? '';
    $cur = field_value($values, $name);
    $req = !empty($f['required']);
    $attrs = ' data-name="' . h($name) . '" data-type="' . h($type) . '"' . ($req ? ' data-required="1"' : '');
    if (!empty($f['show_if'])) $attrs .= " data-show='" . h(json_encode($f['show_if'], JSON_UNESCAPED_UNICODE)) . "'";
    if (!empty($f['rule'])) $attrs .= " data-rule='" . h(json_encode($f['rule'], JSON_UNESCAPED_UNICODE)) . "'";
    $cls = 'field field--' . $type . (($f['layout'] ?? '') === 'half' ? ' field--half' : '') . ($err ? ' has-error' : '');

    if ($type === 'info') {
        $title = $f['label'] !== '' ? '<strong>' . h($f['label']) . '</strong>' : '';
        return '<aside class="' . $cls . ' note"' . $attrs . '>' . $title . '<p>' . nl2br(h($f['help'] ?? '')) . '</p></aside>';
    }

    $label = h($f['label']) . ($req ? '<span class="req" title="Obligatorio"> *</span>' : '');
    $help = !empty($f['help']) ? '<p class="help" id="' . $id . '_help">' . nl2br(h($f['help'])) . '</p>' : '';
    $control = '';
    $ph = !empty($f['placeholder']) ? ' placeholder="' . h($f['placeholder']) . '"' : '';
    $reqAttr = $req ? ' aria-required="true"' : '';
    $desc = !empty($f['help']) ? ' aria-describedby="' . $id . '_help"' : '';

    switch ($type) {
        case 'text': case 'email': case 'tel': case 'url': case 'date': case 'time': case 'number':
            $extra = '';
            if ($type === 'tel') $extra = ' inputmode="tel" autocomplete="tel"';
            if ($type === 'email') $extra = ' inputmode="email" autocomplete="email" autocapitalize="off"';
            if ($type === 'url') { $type = 'text'; $extra = ' inputmode="url" autocapitalize="off"'; }
            if ($type === 'number') $extra = ' inputmode="numeric" min="' . h($f['min'] ?? 0) . '"';
            $control = '<input type="' . $type . '" id="' . $id . '" name="f[' . h($name) . ']" value="' . h(is_array($cur) ? '' : $cur) . '"' . $ph . $extra . $reqAttr . $desc . '>';
            break;
        case 'textarea':
            $control = '<textarea id="' . $id . '" name="f[' . h($name) . ']" rows="' . (int) ($f['rows'] ?? 4) . '"' . $ph . $reqAttr . $desc . '>' . h(is_array($cur) ? '' : $cur) . '</textarea>';
            break;
        case 'select':
            [$sel, $otherTxt] = split_other($cur);
            $control = '<select id="' . $id . '" name="f[' . h($name) . ']"' . $reqAttr . $desc . '><option value="">Elegí una opción…</option>';
            foreach ($f['options'] as $o) $control .= '<option value="' . h($o['value']) . '"' . (is_selected($sel, (string) $o['value']) ? ' selected' : '') . '>' . h($o['label']) . '</option>';
            if (!empty($f['other'])) $control .= '<option value="__otro"' . ($sel === '__otro' ? ' selected' : '') . '>Otro…</option>';
            $control .= '</select>';
            if (!empty($f['other'])) $control .= '<input class="other" type="text" name="f[' . h($name) . '__otro]" value="' . h($otherTxt) . '" placeholder="Contanos cuál" data-other-for="' . h($name) . '" hidden>';
            break;
        case 'radio': case 'checkbox': case 'palette':
            $multi = $type !== 'radio';
            [$sel, $otherTxt] = split_other($cur);
            $cards = !empty($f['cards']) ? ' choices--cards' : '';
            $control = '<div class="choices' . $cards . ($type === 'palette' ? ' choices--palette' : '') . '" role="' . ($multi ? 'group' : 'radiogroup') . '"' . $desc . '>';
            foreach ($f['options'] as $o) {
                $val = (string) $o['value'];
                $checked = is_selected($sel, $val) ? ' checked' : '';
                $sw = '';
                if (!empty($o['colors'])) {
                    $sw = '<span class="swatches">';
                    foreach ($o['colors'] as $c) $sw .= '<i style="background:' . h($c) . '"></i>';
                    $sw .= '</span>';
                }
                $control .= '<label class="choice"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="f[' . h($name) . ']' . ($multi ? '[]' : '') . '" value="' . h($val) . '"' . $checked . '>'
                    . '<span class="choice__box">' . $sw . '<b>' . h($o['label']) . '</b>' . (!empty($o['desc']) ? '<small>' . h($o['desc']) . '</small>' : '') . '</span></label>';
            }
            if (!empty($f['other'])) {
                $checked = ($sel === '__otro' || (is_array($sel) && $otherTxt !== '') || $otherTxt !== '') ? ' checked' : '';
                $control .= '<label class="choice"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="f[' . h($name) . ']' . ($multi ? '[]' : '') . '" value="__otro"' . $checked . '>'
                    . '<span class="choice__box"><b>Otro</b></span></label>';
                $control .= '<input class="other" type="text" name="f[' . h($name) . '__otro]" value="' . h($otherTxt) . '" placeholder="Contanos cuál" data-other-for="' . h($name) . '" hidden>';
            }
            $control .= '</div>';
            break;
        case 'consent':
            $checked = ($cur === 'Sí') ? ' checked' : '';
            $control = '<label class="consent"><input type="checkbox" id="' . $id . '" name="f[' . h($name) . ']" value="1"' . $checked . '><span class="consent__box"></span>'
                . '<span class="consent__text"><b>' . h($f['label']) . ($req ? '<span class="req"> *</span>' : '') . '</b>' . (!empty($f['help']) ? '<small>' . h($f['help']) . '</small>' : '') . '</span></label>';
            return '<div class="' . $cls . '"' . $attrs . '>' . $control . '<p class="err" role="alert">' . h($err) . '</p></div>';
        case 'file':
            $accept = !empty($f['accept']) ? ' accept="' . h($f['accept']) . '"' : '';
            $control = '<div class="drop" data-drop><input type="file" id="' . $id . '" name="files[' . h($name) . '][]" multiple' . $accept . ' data-max-mb="' . (int) cfg('uploads.max_file_mb', 15) . '" data-max-files="' . (int) cfg('uploads.max_files_per_field', 12) . '">'
                . '<span class="drop__cta">' . icon('upload', 22) . '<b>Tocá para elegir archivos</b><small>o arrastralos hasta acá · hasta ' . (int) cfg('uploads.max_file_mb', 15) . ' MB cada uno</small></span>'
                . '<ul class="drop__list" aria-live="polite"></ul></div>';
            break;
        case 'colors':
            $ctl = '<div class="colors" data-colors>';
            $have = $cur && !is_array($cur) ? array_map('trim', explode(',', (string) $cur)) : [];
            $names = ['Principal', 'Secundario', 'Detalles'];
            for ($i = 0; $i < 3; $i++) {
                $v = $have[$i] ?? '';
                $ctl .= '<label class="slot' . ($v ? ' is-set' : '') . '"><input type="color" value="' . h($v ?: '#ffffff') . '"' . ($v ? ' data-set="1"' : '') . '><span class="slot__dot"></span><span class="slot__name">' . $names[$i] . '</span></label>';
            }
            $ctl .= '<button type="button" class="linklike" data-colors-clear>Borrar</button>';
            $ctl .= '<input type="hidden" name="f[' . h($name) . ']" value="' . h(is_array($cur) ? '' : $cur) . '"></div>';
            $control = $ctl;
            break;
    }

    $for = in_array($type, ['radio', 'checkbox', 'palette', 'colors', 'file'], true) ? '' : ' for="' . $id . '"';
    $labelTag = in_array($type, ['radio', 'checkbox', 'palette', 'colors', 'file'], true)
        ? '<span class="label" id="' . $id . '_label">' . $label . '</span>' : '<label class="label"' . $for . '>' . $label . '</label>';
    $warn = !empty($f['rule']) ? '<p class="warn" hidden>' . h($f['rule']['message'] ?? '') . '</p>' : '';
    return '<div class="' . $cls . '"' . $attrs . '>' . $labelTag . $help . $control . $warn . '<p class="err" role="alert">' . h($err) . '</p></div>';
}
