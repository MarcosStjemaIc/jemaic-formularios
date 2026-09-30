<?php
require_once __DIR__ . '/_helpers.php';

$fotos70 = O(['Hasta 40', '41 a 55', '56 a 70']);

return [
    'title' => 'Fotoclip',
    'subtitle' => 'Tus fotos, animadas y con música',
    'icon' => 'film',
    'accent' => '',
    'intro' => 'Elegí el modelo de Fotoclip y completá la información. Importante: el álbum/material debe estar COMPLETO 5 días hábiles antes del evento para lograr una edición satisfactoria.',
    'submit_label' => 'Enviar pedido de Fotoclip',
    'thanks_title' => '¡Gracias! Recibimos tu pedido de Fotoclip',
    'thanks_text' => "Ya tenemos los datos de tu Fotoclip. Recordá que el álbum de fotos debe estar completo 5 días hábiles antes del evento. Si tenés dudas, escribinos por WhatsApp.",
    'client_email_field' => 'email',
    'list_fields' => ['responsable', 'nombre_evento', 'fecha_evento', 'modelo'],
    'steps' => [
        [
            'id' => 'datos', 'title' => 'Datos del evento', 'description' => 'Datos básicos para coordinar tiempos y entregas.',
            'fields' => [
                F('email', 'email', 'Correo electrónico', ['required' => true]),
                F('text', 'responsable', 'Nombre y apellido del/la responsable', ['required' => true]),
                F('tel', 'telefono', 'Teléfono (WhatsApp)', ['required' => true, 'placeholder' => '+54 9 280 xxxx xxxx', 'layout' => 'half']),
                F('date', 'fecha_evento', 'Fecha del evento', ['required' => true, 'help' => 'La usamos para verificar el plazo mínimo de 5 días hábiles.', 'layout' => 'half',
                    'rule' => ['type' => 'business_days_from_today', 'days' => 5, 'message' => 'Tu evento es en menos de 5 días hábiles. Podés enviar el pedido igual, pero la edición puede tener recargos: consultalo con la coordinadora.']]),
                F('text', 'nombre_evento', 'Nombre del evento / quinceañera / novios / institución', ['required' => true]),
            ],
        ],
        [
            'id' => 'modelo', 'title' => 'Elegí tu modelo', 'description' => 'Cada modelo tiene requisitos y contenidos específicos.',
            'fields' => [
                F('radio', 'modelo', 'Modelo deseado', ['required' => true, 'cards' => true, 'options' => O([
                    ['clasico', 'Fotoclip clásico', 'Hasta 70 fotos + 3 canciones. Ideal para eventos generales.'],
                    ['historia', 'Historia de vida', 'Hasta 70 fotos + 3 canciones + texto emotivo para locución.'],
                    ['biografia', 'Biografía express', 'Hasta 50 fotos + 2 canciones + memes/efemérides + texto divertido para locución.'],
                ])]),
            ],
        ],
        [
            'id' => 'clasico', 'title' => 'Fotoclip clásico', 'description' => 'Hasta 70 fotos + 3 canciones.',
            'show_if' => ['field' => 'modelo', 'equals' => 'clasico'],
            'fields' => [
                F('radio', 'cl_cantidad', 'Cantidad de fotos (máx. 70)', ['required' => true, 'options' => $fotos70]),
                F('text', 'cl_cancion1', 'Canción 1 (link o título)', ['required' => true]),
                F('text', 'cl_cancion2', 'Canción 2 (link o título)'),
                F('text', 'cl_cancion3', 'Canción 3 (link o título)'),
                F('textarea', 'cl_obs_musica', 'Observaciones sobre la música', ['help' => 'Opcional.', 'rows' => 2]),
                F('text', 'cl_texto_inicio', 'Texto para el INICIO del video', ['help' => 'Opcional. Frase breve, dedicatoria o presentación.']),
                F('text', 'cl_texto_final', 'Texto para el FINAL del video', ['help' => 'Opcional. Cierre, agradecimiento o frase especial.']),
            ],
        ],
        [
            'id' => 'historia', 'title' => 'Historia de vida', 'description' => 'Hasta 70 fotos + 3 canciones + texto para locución.',
            'show_if' => ['field' => 'modelo', 'equals' => 'historia'],
            'fields' => [
                F('radio', 'hv_cantidad', 'Cantidad de fotos (máx. 70)', ['required' => true, 'options' => $fotos70]),
                F('text', 'hv_cancion1', 'Canción 1 (link o título)', ['required' => true]),
                F('text', 'hv_cancion2', 'Canción 2 (link o título)'),
                F('text', 'hv_cancion3', 'Canción 3 (link o título)'),
                F('textarea', 'hv_obs_musica', 'Observaciones sobre la música', ['help' => 'Opcional.', 'rows' => 2]),
                F('textarea', 'hv_texto', 'Texto de tu historia de vida para locución', ['required' => true, 'rows' => 9, 'help' => 'Contanos la historia o palabras emotivas para el locutor. Ideal: 250–500 palabras.']),
                F('textarea', 'hv_inicio_final', '¿Alguna idea para el INICIO y/o FINAL?', ['help' => 'Opcional. Frases de apertura o cierre que te gustaría incluir.', 'rows' => 3]),
            ],
        ],
        [
            'id' => 'biografia', 'title' => 'Biografía express', 'description' => 'Hasta 50 fotos + 2 canciones + texto divertido.',
            'show_if' => ['field' => 'modelo', 'equals' => 'biografia'],
            'fields' => [
                F('radio', 'be_cantidad', 'Cantidad de fotos (máx. 50)', ['required' => true, 'options' => O(['Hasta 30', '31 a 40', '41 a 50'])]),
                F('text', 'be_estilo_musical', '¿Qué estilo musical querés?', ['help' => 'Podés mencionar artistas, ritmos o climas.']),
                F('text', 'be_cancion1', 'Canción 1 (link o título)', ['required' => true]),
                F('text', 'be_cancion2', 'Canción 2 (link o título)'),
                F('text', 'be_no_canciones', 'Canciones que NO querés', ['help' => 'Opcional.']),
                F('textarea', 'be_obs_musica', 'Observaciones sobre la música', ['help' => 'Opcional.', 'rows' => 2]),
                F('textarea', 'be_texto', 'Texto para locución (tono divertido)', ['required' => true, 'rows' => 7, 'help' => 'Anécdotas graciosas, datos curiosos, apodos, etc. Ideal: 150–350 palabras.']),
                F('file', 'be_archivo_texto', 'Adjuntar el texto para locución', ['help' => 'Opcional: si lo tenés escrito en un documento.']),
                F('textarea', 'be_memes', 'Memes / referencias humorísticas', ['help' => 'Opcional. Listá referencias, chistes internos, hashtags, etc.', 'rows' => 3]),
                F('textarea', 'be_efemerides', 'Efemérides / fechas especiales', ['help' => 'Opcional. Nacimiento, logros, momentos clave.', 'rows' => 3]),
                F('textarea', 'be_inicio_final', 'Ideas para INICIO / FINAL', ['help' => 'Opcional.', 'rows' => 2]),
            ],
        ],
        [
            'id' => 'entrega', 'title' => 'Estilo y entrega', 'description' => 'Datos comunes de entrega y estilo.',
            'fields' => [
                F('text', 'colores_fiesta', 'Tonos / colores de la fiesta', ['placeholder' => 'Ej: dorado y blanco; rosa pastel; azul marino…']),
                F('url', 'album_link', 'Link al ÁLBUM DE GOOGLE FOTOS provisto por la coordinadora', ['required' => true, 'help' => 'Solicitalo por WhatsApp a la coordinadora: 280 434-3587.']),
                F('textarea', 'links_extra', 'Links adicionales', ['help' => 'Opcional. Pinterest, Drive, YouTube o Google Fotos con material extra.', 'rows' => 2]),
                F('checkbox', 'confirma_plazo', 'Confirmación de plazo', ['options' => O(['Lo solicito con un plazo mínimo de 5 días hábiles previos a la fecha del evento.']),
                    'help' => 'El álbum/material debe estar COMPLETO, sin excepción, 5 días hábiles antes del evento. Si no se cumple, la edición puede verse afectada o requerir recargos/reprogramación.']),
                F('checkbox', 'mas_de_70', '¿Necesitás más de 70 fotos?', ['options' => O(['Quiero más de 70 fotos']),
                    'help' => 'Si necesitás más de 70 fotos, consultá presupuesto según la cantidad de fotos extra a animar.']),
            ],
        ],
        [
            'id' => 'confirmacion', 'title' => 'Confirmación y condiciones', 'description' => 'Último paso.',
            'fields' => [
                F('checkbox', 'condiciones', 'Acepto las condiciones', ['required' => true, 'help' => 'Debés aceptar para continuar.', 'options' => O([
                    'Confirmo que el álbum/material estará completo 5 días hábiles antes del evento',
                    'Autorizo el uso de fragmentos en redes de Jema Ic (opcional)',
                    'Acepto términos y condiciones de entrega',
                ])]),
                F('textarea', 'comentarios', 'Comentarios finales', ['help' => 'Opcional. Cualquier detalle extra que quieras sumar.', 'rows' => 3]),
            ],
        ],
    ],
];
