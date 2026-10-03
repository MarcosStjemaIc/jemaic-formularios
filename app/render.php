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
    return '<span class="brand"><span class="brand__main">Jema</span><small>Imagen creativa</small></span>';
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
    if ($cur === null && isset($f['default'])) $cur = $f['default'];
    $req = !empty($f['required']);
    $attrs = ' data-name="' . h($name) . '" data-type="' . h($type) . '"' . ($req ? ' data-required="1"' : '');
    if (!empty($f['show_if'])) $attrs .= " data-show='" . h(json_encode($f['show_if'], JSON_UNESCAPED_UNICODE)) . "'";
    if (!empty($f['rule'])) $attrs .= " data-rule='" . h(json_encode($f['rule'], JSON_UNESCAPED_UNICODE)) . "'";
    if (!empty($f['min'])) $attrs .= ' data-min="' . (int) $f['min'] . '"';
    if (!empty($f['need'])) $attrs .= ' data-need="' . (int) $f['need'] . '"';
    if (($f['normalize'] ?? '') === 'digits') $attrs .= ' data-digits="1"';
    $cls = 'field field--' . $type . (($f['layout'] ?? '') === 'half' ? ' field--half' : '') . ($err ? ' has-error' : '');

    if ($type === 'info') {
        $title = $f['label'] !== '' ? '<strong>' . h($f['label']) . '</strong>' : '';
        $lnk = !empty($f['link']) && preg_match('#^https://#', (string) $f['link']) ? '<p><a class="note__link" href="' . h($f['link']) . '" target="_blank" rel="noopener">' . h($f['link_label'] ?? 'Ver') . ' ↗</a></p>' : '';
        return '<aside class="' . $cls . ' note"' . $attrs . '>' . $title . '<p>' . nl2br(h($f['help'] ?? '')) . '</p>' . $lnk . '</aside>';
    }

    $label = h($f['label']) . ($req ? '<span class="req" title="Obligatorio"> *</span>' : '');
    $help = !empty($f['help']) ? '<p class="help" id="' . $id . '_help">' . nl2br(h($f['help'])) . '</p>' : '';
    $control = '';
    $ph = !empty($f['placeholder']) ? ' placeholder="' . h($f['placeholder']) . '"' : '';
    $reqAttr = $req ? ' aria-required="true"' : '';
    $desc = !empty($f['help']) ? ' aria-describedby="' . $id . '_help"' : '';
    $maxA = !empty($f['max']) ? ' maxlength="' . (int) $f['max'] . '" data-max="' . (int) $f['max'] . '"' : '';
    $counter = !empty($f['max']) ? '<span class="count" aria-live="polite" hidden></span>' : '';

    switch ($type) {
        case 'text': case 'email': case 'tel': case 'url': case 'date': case 'time': case 'number': case 'datetime':
            $extra = $maxA;
            if ($type === 'datetime') $type = 'datetime-local';
            if ($type === 'tel') $extra .= ' inputmode="tel" autocomplete="tel"';
            if ($type === 'email') $extra .= ' inputmode="email" autocomplete="email" autocapitalize="off"';
            if ($type === 'url') { $type = 'text'; $extra .= ' inputmode="url" autocapitalize="off"'; }
            if ($type === 'number') $extra .= ' inputmode="numeric" min="' . h($f['min'] ?? 0) . '"';
            $control = '<input type="' . $type . '" id="' . $id . '" name="f[' . h($name) . ']" value="' . h(is_array($cur) ? '' : $cur) . '"' . $ph . $extra . $reqAttr . $desc . '>' . $counter;
            if (!empty($f['map'])) $control .= '<div class="mapbox" data-map hidden><iframe title="Mapa" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><small>¿Es este el lugar? Si no, agregá la ciudad o más detalles a la dirección.</small></div>';
            break;
        case 'textarea':
            $control = '<textarea id="' . $id . '" name="f[' . h($name) . ']" rows="' . (int) ($f['rows'] ?? 4) . '"' . $ph . $maxA . $reqAttr . $desc . '>' . h(is_array($cur) ? '' : $cur) . '</textarea>' . $counter;
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
            $hasImg = (bool) array_filter($f['options'], fn($o) => !empty($o['img']));
            $hasDemo = (bool) array_filter($f['options'], fn($o) => !empty($o['demo']));
            $control = '<div class="choices' . $cards . ($type === 'palette' ? ' choices--palette' : '') . ($hasImg ? ' choices--images' . (!empty($f['img_shape']) ? ' choices--' . h($f['img_shape']) : '') : '') . ($hasDemo ? ' choices--demo' : '') . '" role="' . ($multi ? 'group' : 'radiogroup') . '"' . $desc . '>';
            foreach ($f['options'] as $o) {
                $val = (string) $o['value'];
                $checked = is_selected($sel, $val) ? ' checked' : '';
                $sw = '';
                if (!empty($o['colors'])) {
                    $sw = '<span class="swatches">';
                    foreach ($o['colors'] as $c) $sw .= '<i style="background:' . h($c) . '"></i>';
                    $sw .= '</span>';
                }
                $img = !empty($o['img']) ? '<span class="choice__img"><img loading="lazy" decoding="async" src="' . h($o['img']) . '" alt=""></span>' : ($hasImg ? '<span class="choice__img choice__img--none">' . icon('sparkle', 26) . '</span>' : '');
                $demo = '';
                if (!empty($o['demo'])) {
                    [$kind, $what] = explode(':', (string) $o['demo'], 2);
                    if ($kind === 'clock') {
                        $demo = '<span class="choice__demo demo-clock demo-clock--' . h($what) . '" aria-hidden="true"><span><b>185</b><i>DÍAS</i></span><span><b>01</b><i>HORAS</i></span><span><b>16</b><i>MIN</i></span></span>';
                    } elseif ($kind === 'font') {
                        $demo = '<span class="choice__demo demo-font demo-font--' . h(strtolower($what)) . '" aria-hidden="true">' . h($f['demo_text'] ?? 'Lucía & Nico') . '</span>';
                    }
                }
                $control .= '<label class="choice"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="f[' . h($name) . ']' . ($multi ? '[]' : '') . '" value="' . h($val) . '"' . $checked . '>'
                    . '<span class="choice__box">' . $img . $demo . $sw . '<b>' . h($o['label']) . '</b>' . (!empty($o['desc']) ? '<small>' . h($o['desc']) . '</small>' : '') . '</span></label>';
            }
            if (!empty($f['other'])) {
                $checked = ($sel === '__otro' || (is_array($sel) && $otherTxt !== '') || $otherTxt !== '') ? ' checked' : '';
                $control .= '<label class="choice"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="f[' . h($name) . ']' . ($multi ? '[]' : '') . '" value="__otro"' . $checked . '>'
                    . '<span class="choice__box"><b>Otro</b></span></label>';
                $control .= '<input class="other" type="text" name="f[' . h($name) . '__otro]" value="' . h($otherTxt) . '" placeholder="Contanos cuál" data-other-for="' . h($name) . '" hidden>';
            }
            $control .= '</div>';
            break;
        case 'switch':
            $on = $cur === null || $cur === '' || $cur === 'Sí' || $cur === '1';
            $control = '<label class="switchq"><input type="hidden" name="f[' . h($name) . ']" value="0" data-nodraft><input type="checkbox" id="' . $id . '" name="f[' . h($name) . ']" value="1"' . ($on ? ' checked' : '') . '>'
                . '<span class="switchq__track" aria-hidden="true"></span><span class="switchq__text"><b>' . h($f['label']) . '</b>'
                . '<small class="switchq__on">Incluida · completá los datos de abajo</small><small class="switchq__off">No va en tu invitación</small>'
                . (!empty($f['help']) ? '<small class="switchq__help">' . h($f['help']) . '</small>' : '') . '</span></label>';
            return '<div class="' . $cls . '"' . $attrs . '>' . $control . '</div>';
        case 'repeater':
            $rows = is_array($cur) && $cur ? array_values($cur) : [];
            $minRows = max(1, (int) ($f['min_rows'] ?? 1));
            while (count($rows) < $minRows) $rows[] = [];
            $rowHtml = function ($r, $i) use ($f, $name) {
                $o = '<div class="rep__row"><div class="rep__head"><b>' . h($f['row_label'] ?? 'Fila') . ' <span class="rep__n">' . ($i + 1) . '</span></b>'
                    . '<button type="button" class="linklike rep__del" data-rep-del>Quitar</button></div><div class="rep__fields">';
                foreach ($f['fields'] as $sf) {
                    $nm = 'f[' . h($name) . '][' . $i . '][' . h($sf['name']) . ']';
                    $sv = (string) ($r[$sf['name']] ?? '');
                    $mx = !empty($sf['max']) ? ' maxlength="' . (int) $sf['max'] . '" data-max="' . (int) $sf['max'] . '"' : '';
                    $ph = !empty($sf['placeholder']) ? ' placeholder="' . h($sf['placeholder']) . '"' : '';
                    $o .= '<label class="rep__f rep__f--' . h($sf['type']) . (!empty($sf['wide']) ? ' rep__f--wide' : '') . '" data-sub="' . h($sf['name']) . '"' . ($sf['required'] ? ' data-required="1"' : '') . '><span>' . h($sf['label']) . '</span>';
                    if ($sf['type'] === 'select') {
                        $o .= '<select name="' . $nm . '" data-rep-name="' . h($sf['name']) . '"><option value="">Elegí…</option>';
                        foreach ($sf['options'] as $op) $o .= '<option value="' . h($op['value']) . '"' . ((string) $op['value'] === $sv ? ' selected' : '') . '>' . h($op['label']) . '</option>';
                        $o .= '</select>';
                    } elseif ($sf['type'] === 'textarea') {
                        $o .= '<textarea rows="2" name="' . $nm . '" data-rep-name="' . h($sf['name']) . '"' . $mx . $ph . '>' . h($sv) . '</textarea>';
                    } else {
                        $o .= '<input type="' . ($sf['type'] === 'time' ? 'time' : 'text') . '" name="' . $nm . '" data-rep-name="' . h($sf['name']) . '" value="' . h($sv) . '"' . $mx . $ph . '>';
                    }
                    $o .= '</label>';
                }
                return $o . '</div></div>';
            };
            $control = '<div class="rep" data-rep data-max-rows="' . (int) ($f['max_rows'] ?? 10) . '" data-min-rows="' . $minRows . '">';
            foreach ($rows as $i => $r) $control .= $rowHtml($r, $i);
            $control .= '<template>' . $rowHtml([], 0) . '</template>';
            $control .= '<button type="button" class="btn btn--ghost btn--sm rep__add" data-rep-add>+ ' . h($f['add_label'] ?? 'Agregar') . '</button></div>';
            break;
        case 'consent':
            $checked = ($cur === 'Sí') ? ' checked' : '';
            $control = '<label class="consent"><input type="checkbox" id="' . $id . '" name="f[' . h($name) . ']" value="1"' . $checked . '><span class="consent__box"></span>'
                . '<span class="consent__text"><b>' . h($f['label']) . ($req ? '<span class="req"> *</span>' : '') . '</b>' . (!empty($f['help']) ? '<small>' . h($f['help']) . '</small>' : '') . '</span></label>';
            return '<div class="' . $cls . '"' . $attrs . '>' . $control . '<p class="err" role="alert">' . h($err) . '</p></div>';
        case 'file':
            $mb = (int) ($f['max_mb'] ?? 0) ?: (int) cfg('uploads.max_file_mb', 15);
            $nMax = (int) ($f['max_files'] ?? 0) ?: (int) cfg('uploads.max_files_per_field', 12);
            $exts = (array) ($f['ext'] ?? []);
            $accept = $exts ? ' accept="' . h(implode(',', array_map(fn($e) => '.' . $e, $exts)) . (array_intersect($exts, ['jpg', 'jpeg', 'png', 'webp']) ? ',image/jpeg,image/png,image/webp' : '') . (array_intersect($exts, ['mp3', 'm4a']) ? ',audio/mpeg,audio/mp4,audio/x-m4a' : '')) . '"'
                : (!empty($f['accept']) ? ' accept="' . h($f['accept']) . '"' : '');
            $what = $nMax === 1 ? ($f['file_word'] ?? 'un archivo') : ($f['file_word_plural'] ?? 'archivos');
            $imgOnly = $exts && !array_diff($exts, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'gif']);
            $noun = $imgOnly ? 'fotos' : 'archivos';
            $limit = $nMax > 1 ? '<p class="droplimit"><b>' . $nMax . '</b><span><strong>Máximo ' . $nMax . ' ' . $noun . '</strong><small>Admitimos hasta ' . $nMax . ' ' . $noun . ' en esta sección.</small></span><em data-count>0 de ' . $nMax . '</em></p>' : '';
            $control = $limit . '<div class="drop" data-drop data-noun="' . ($imgOnly ? 'foto' : ($exts && !array_diff($exts, ['mp3', 'm4a']) ? 'cancion' : 'archivo')) . '"' . (!empty($f['captions']) ? ' data-captions="' . h($name) . '" data-caption-max="' . (int) ($f['caption_max'] ?? 160) . '"' : '') . (!empty($f['compress']) ? ' data-compress="1"' : '') . '><input type="file" id="' . $id . '" name="files[' . h($name) . '][]"' . ($nMax > 1 ? ' multiple' : '') . $accept . ' data-max-mb="' . $mb . '" data-max-files="' . $nMax . '">'
                . '<span class="drop__cta">' . icon('upload', 22) . '<b>Tocá para elegir ' . h($what) . '</b><small>' . ($nMax > 1 ? 'hasta ' . $nMax . ' · ' : '') . ($exts ? strtoupper(implode(', ', array_diff($exts, ['jpeg']))) . ' · ' : '') . 'hasta ' . $mb . ' MB cada uno</small></span>'
                . '<p class="drop__status" role="status" aria-live="polite" hidden></p><ul class="drop__list"></ul></div>';
            if (!empty($f['link_alt'])) {
                $lv = (string) ($values[$name . '__link'] ?? '');
                $control .= '<div class="linkalt"><span class="linkalt__or">o pegá un link</span><input type="text" inputmode="url" autocapitalize="off" name="f[' . h($name) . '__link]" value="' . h($lv) . '" placeholder="' . h($f['link_placeholder'] ?? 'Link de Drive, Google Fotos o WeTransfer') . '" maxlength="500"></div>';
            }
            break;
        case 'colors':
            $ctl = '<div class="colors" data-colors>';
            $have = $cur && !is_array($cur) ? array_map('trim', explode(',', (string) $cur)) : [];
            $names = array_values((array) ($f['slots'] ?? ['Principal', 'Secundario', 'Detalles']));
            $hex = !empty($f['hex']);
            if ($hex) $ctl = '<div class="colors colors--hex" data-colors>';
            for ($i = 0; $i < min(3, count($names)); $i++) {
                $v = $have[$i] ?? '';
                $ctl .= '<div class="slotwrap"><label class="slot' . ($v ? ' is-set' : '') . '"><input type="color" value="' . h($v ?: '#ffffff') . '"' . ($v ? ' data-set="1"' : '') . ' aria-label="' . h($names[$i]) . '"><span class="slot__dot"></span><span class="slot__name">' . h($names[$i]) . '</span></label>'
                    . ($hex ? '<input type="text" class="slot__hex" value="' . h($v) . '" placeholder="#e9a9bb o rgb(233,169,187)" aria-label="Código de ' . h($names[$i]) . '" autocapitalize="off" spellcheck="false">' : '') . '</div>';
            }
            $ctl .= '<button type="button" class="linklike" data-colors-clear>Borrar</button>';
            $ctl .= '<input type="hidden" name="f[' . h($name) . ']" value="' . h(is_array($cur) ? '' : $cur) . '"></div>';
            if ($hex) $ctl .= '<div class="cprev" data-cprev aria-hidden="true"><span class="cprev__eyebrow">Te invito a celebrar</span><b class="cprev__title">Lucía</b><span class="cprev__text">Una noche mágica para celebrar juntos</span><span class="cprev__btn">Confirmar asistencia</span></div>';
            $control = $ctl;
            break;
    }

    $group = ['radio', 'checkbox', 'palette', 'colors', 'file', 'repeater'];
    $for = in_array($type, $group, true) ? '' : ' for="' . $id . '"';
    $labelTag = in_array($type, $group, true)
        ? '<span class="label" id="' . $id . '_label">' . $label . '</span>' : '<label class="label"' . $for . '>' . $label . '</label>';
    $warn = !empty($f['rule']) ? '<p class="warn" hidden>' . h($f['rule']['message'] ?? '') . '</p>' : '';
    return '<div class="' . $cls . '"' . $attrs . '>' . $labelTag . $help . $control . $warn . '<p class="err" role="alert">' . h($err) . '</p></div>';
}
