<?php
// Ayudantes para escribir formularios de forma compacta.

if (!function_exists('F')) {
    /** F('text', 'nombre', 'Etiqueta', ['required' => true, ...]) */
    function F(string $type, string $name, string $label, array $o = []): array
    {
        return ['type' => $type, 'name' => $name, 'label' => $label] + $o;
    }

    /** O(['Sí', 'No']) o O([['valor', 'Etiqueta', 'Descripción'], ...]) */
    function O(array $list): array
    {
        $out = [];
        foreach ($list as $x) {
            if (is_array($x)) {
                $item = ['value' => $x[0], 'label' => $x[1] ?? $x[0]];
                if (!empty($x[2])) $item['desc'] = $x[2];
                if (!empty($x[3])) $item['colors'] = $x[3];
                $out[] = $item;
            } else {
                $out[] = ['value' => $x, 'label' => $x];
            }
        }
        return $out;
    }

    function INFO(string $name, string $text, string $title = ''): array
    {
        return ['type' => 'info', 'name' => $name, 'label' => $title, 'help' => $text];
    }
}
