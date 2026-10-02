<?php
/**
 * Invitación digital · Qué Planazo (v2)
 * Las claves y opciones coinciden con el editor de queplanazo.jemaic.com
 * para poder crear el borrador de la invitación automáticamente (ver planazo_export()).
 */
require_once __DIR__ . '/_helpers.php';

$QP = 'https://queplanazo.jemaic.com/assets/';
$JEMA = ['jema', 'Que lo elija Jema'];
$on = fn(string $sec) => ['field' => 'sec_' . $sec, 'equals' => 'Sí'];
$img = function (array $list, string $dir, string $ext) use ($QP): array {
    $out = [];
    foreach ($list as [$v, $l]) $out[] = ['value' => $v, 'label' => $l] + ($v === 'jema' || $v === 'none' ? [] : ['img' => $QP . $dir . $v . $ext]);
    return $out;
};
$IMG_EXT = ['jpg', 'jpeg', 'png', 'webp'];

$themes = [$JEMA, ['starlight', 'Noche estrellada'], ['blossom', 'Jardín de sueños'], ['pearl', 'Aura perlada'], ['lunar', 'Luna en primer plano'],
    ['paper', 'Papel & recuerdos'], ['muse', 'Tu canción favorita'], ['editorial', 'Amor editorial'], ['coastal', 'Promesa junto al mar'],
    ['terracotta', 'Sí, bajo el sol'], ['vows', 'Solo nosotros'], ['vellum', 'Carta de amor'], ['linen', 'Entre líneas'],
    ['varsity', 'La última lista'], ['neon', 'Modo prom'], ['sunset', 'Atardecer de promo'], ['afterhours', 'After · la gran noche'],
    ['encore', 'Una última canción'], ['orbit', 'Órbita 2027'], ['fiesta', 'Club celebración'], ['gala', 'Noche de gala'],
    ['garden', 'Brindis en el jardín'], ['companions', 'Nuestros cómplices'], ['nightclub', 'Club de medianoche'], ['pawprints', 'Amigos de cuatro patas']];

$looks = [
    ['value' => 'jema', 'label' => 'Que lo elija Jema'],
    ['value' => 'romantica', 'label' => 'Romántica · rosa & plata', 'colors' => ['#8a5a6e', '#e9a9bb', '#ffffff']],
    ['value' => 'lavanda', 'label' => 'Jardín de lavanda', 'colors' => ['#4f4466', '#b9a6d6', '#fbf9fd']],
    ['value' => 'marfil', 'label' => 'Clásica marfil & oro', 'colors' => ['#3b3025', '#c2a15e', '#fbf7ef']],
    ['value' => 'princesa', 'label' => 'Princesa celeste', 'colors' => ['#2e4a6b', '#9cc3e6', '#f6fbff']],
    ['value' => 'estrellas', 'label' => 'Noche de estrellas', 'colors' => ['#f3eefc', '#c9b8f0', '#171a2e']],
    ['value' => 'terracota', 'label' => 'Boho terracota', 'colors' => ['#6b3a2a', '#c98a62', '#fbf3e8']],
    ['value' => 'minimal', 'label' => 'Minimal editorial', 'colors' => ['#1f1f1f', '#b5a58c', '#ffffff']],
    ['value' => 'mar', 'label' => 'Mar & arena', 'colors' => ['#23495a', '#8fbfcc', '#f7fbfb']],
    ['value' => 'vintage', 'label' => 'Carta vintage rosa', 'colors' => ['#5e4a4a', '#d4a5a5', '#fbf3ee']],
    ['value' => 'prom', 'label' => 'Prom eléctrica', 'colors' => ['#f5efff', '#d8ff79', '#241636']],
    ['value' => 'propio', 'label' => 'Prefiero elegir mis colores'],
];

$backgrounds = [$JEMA, ['none', 'Sin decoración'], ['butterflies', 'Mariposas y flores'], ['silverdust', 'Destellos plateados'], ['almond', 'Flores de almendro'],
    ['stars', 'Polvo de estrellas'], ['silk', 'Seda en movimiento'], ['moonlight', 'Luz de luna'], ['cotton', 'Papel de algodón'],
    ['royal', 'Corona de seda'], ['vowlines', 'Lazos & promesas'], ['pawtrail', 'Huellitas'], ['discoglow', 'Disco & melodía']];

$pick = fn(array $pairs) => array_map(fn($p) => ['value' => $p[0], 'label' => $p[1]], $pairs);

return [
    'version' => 4,
    'export' => 'queplanazo',
    'title' => 'Invitación digital',
    'subtitle' => 'Qué Planazo · por Jema',
    'brand' => 'planazo',
    'icon' => 'card',
    'accent' => '',
    'intro' => 'Con estas respuestas armamos tu invitación. Va por pasos: cada sección de la invitación tiene un interruptor “Quiero esta sección”; si no la querés, apagala y listo. Tu avance se guarda en este celular.',
    'submit_label' => 'Enviar mi invitación',
    'thanks_title' => '¡Listo! Ya tenemos todo para tu invitación',
    'thanks_text' => 'Recibimos los datos de tu invitación. Empezamos a diseñarla y te escribimos por WhatsApp si nos falta algo.',
    'client_email_field' => 'host_email',
    'list_fields' => ['title', 'host_name'],
    'steps' => [
        [
            'id' => 'contacto', 'title' => 'Tus datos', 'description' => 'Para hablar con vos. No aparecen en la invitación.',
            'fields' => [
                F('text', 'host_name', 'Tu nombre (o el de la familia)', ['required' => true, 'max' => 120, 'placeholder' => 'Ej.: Familia de Lucía']),
                F('email', 'host_email', 'Tu email', ['required' => true, 'max' => 255, 'help' => 'Con este email vas a entrar a ver tus invitados.']),
                F('tel', 'host_phone', 'WhatsApp para recibir las confirmaciones', ['required' => true, 'normalize' => 'digits', 'max' => 25, 'placeholder' => '+54 9 280 412 3456', 'help' => 'Con código de país. Podés escribirlo como quieras: lo acomodamos solo.']),
            ],
        ],
        [
            'id' => 'celebracion', 'title' => 'La celebración', 'description' => 'Lo básico de tu evento.',
            'fields' => [
                F('radio', 'category', '¿Qué celebran?', ['required' => true, 'options' => $pick([['quince', 'XV años'], ['boda', 'Boda'], ['egresados', 'Egresados'], ['evento', 'Evento especial']])]),
                F('text', 'title', 'Nombre que va en la invitación', ['required' => true, 'max' => 120, 'placeholder' => 'Ej.: Lucía · Ana & Nico · Promo 2027']),
                F('datetime', 'starts_at', 'Fecha y hora del evento', ['required' => true, 'layout' => 'half', 'help' => 'Hora de Argentina.']),
                F('datetime', 'deadline', 'Fecha límite para confirmar asistencia', ['required' => true, 'layout' => 'half', 'help' => 'Tiene que ser antes del evento. Te sugerimos 10 días antes.',
                    'rule' => ['type' => 'before', 'ref' => 'starts_at', 'suggest_days' => 10, 'message' => 'La fecha límite tiene que ser antes del evento.']]),
            ],
        ],
        [
            'id' => 'diseno', 'title' => 'Diseño de portada', 'description' => 'Elegí la portada que más te guste. Si no sabés, dejá “Que lo elija Jema”.',
            'fields' => [
                ['type' => 'info', 'name' => 'info_coleccion', 'label' => '¿Querés ver los diseños en acción?', 'help' => 'Mirá la colección completa antes de elegir.', 'link' => 'https://queplanazo.jemaic.com/#coleccion', 'link_label' => 'Ver la colección'],
                F('radio', 'theme', 'Diseño de portada', ['default' => 'jema', 'img_shape' => 'tall', 'help' => 'Cómo se acomodan los textos y la foto. Tocá la que más te guste.', 'options' => array_map(fn($o) => isset($o['img']) ? ['img' => '/assets/covers/' . $o['value'] . '.webp'] + $o : $o, $img($themes, 'sample-covers/', '-small.webp'))]),
            ],
        ],
        [
            'id' => 'colores', 'title' => 'Los colores', 'description' => 'Elegí una combinación lista o armá la tuya con tus tres colores.',
            'fields' => [
                F('radio', 'look', '¿Qué colores querés para tu invitación?', ['required' => true, 'help' => 'Tocá una opción para seguir. Con “Prefiero elegir mis colores” elegís vos la letra, el acento y el fondo.', 'options' => $looks]),
                F('colors', 'colors', 'Tus colores', ['required' => true, 'need' => 3, 'hex' => true, 'slots' => ['Letra', 'Acento', 'Fondo'],
                    'help' => 'Tocá cada círculo para elegir en la rueda de colores, o pegá el código (HEX como #e9a9bb o RGB como 233,169,187). Abajo ves cómo quedan.',
                    'show_if' => ['field' => 'look', 'equals' => 'propio']]),
            ],
        ],
        [
            'id' => 'detalles', 'title' => 'Letras y detalles', 'description' => 'Todo es opcional: si no sabés, dejá “Que lo elija Jema” y nosotros lo elegimos por vos.',
            'fields' => [
                F('radio', 'font_titles', 'Letra de los títulos', ['default' => 'jema', 'options' => $pick([$JEMA, ['script', 'Cursiva elegante'], ['script_soft', 'Cursiva romántica'], ['fine_serif', 'Fina clásica'], ['editorial', 'Editorial'], ['modern', 'Moderna'], ['theme', 'Como el diseño']])]),
                F('radio', 'font_texts', 'Letra de los textos', ['default' => 'jema', 'options' => $pick([$JEMA, ['fine_sans', 'Fina y clara'], ['fine_serif', 'Fina clásica'], ['modern', 'Moderna'], ['classic', 'Clásica'], ['theme', 'Como el diseño']])]),
                F('radio', 'motif_style', 'Detalle o adorno', ['default' => 'jema', 'options' => $pick([$JEMA, ['none', 'Sin adornos'], ['butterflies', 'Mariposas'], ['flowers', 'Flores delicadas'], ['moon', 'Luna'], ['stars', 'Estrellas'], ['crown', 'Corona'], ['rings', 'Anillos'], ['cat', 'Gatito'], ['dog', 'Perrito'], ['music', 'Música'], ['disco', 'Disco']])]),
                F('radio', 'metal_style', 'Detalles que brillan', ['default' => 'jema', 'options' => $pick([$JEMA, ['none', 'Sin brillo'], ['silver', 'Plateado'], ['gold', 'Dorado'], ['rose', 'Oro rosa']])]),
                F('radio', 'background_style', 'Fondo decorativo suave', ['default' => 'jema', 'img_shape' => 'square', 'options' => $img($backgrounds, 'backgrounds/', '.svg')]),
                F('file', 'background_photo', '¿Querés una foto suave de fondo detrás de toda la invitación?', ['max_files' => 1, 'ext' => $IMG_EXT, 'max_mb' => 12, 'compress' => true, 'file_word' => 'una foto', 'help' => 'Opcional.']),
                F('radio', 'mascot_style', 'Mascota virtual que acompaña a los invitados', ['default' => 'jema', 'options' => $pick([$JEMA, ['none', 'Sin mascota'], ['whale', 'Ballenita austral'], ['dog', 'Perrito'], ['cat', 'Gatito']])]),
            ],
        ],
        [
            'id' => 'portada', 'title' => 'Portada', 'description' => 'Lo primero que ven tus invitados.',
            'fields' => [
                F('file', 'cover_photo', 'Foto de portada', ['required' => true, 'max_files' => 1, 'ext' => $IMG_EXT, 'max_mb' => 12, 'compress' => true, 'link_alt' => true, 'file_word' => 'una foto',
                    'help' => 'Mejor vertical y bien iluminada. También podés pegar un link de Drive, Google Fotos o WeTransfer.']),
                F('text', 'greeting', 'Frase pequeña de apertura', ['required' => true, 'max' => 120, 'placeholder' => 'Ej.: Te invito a celebrar mis XV']),
                F('text', 'tagline', 'Frase principal', ['required' => true, 'max' => 160, 'placeholder' => 'Ej.: Una noche mágica para celebrar juntos']),
            ],
        ],
        [
            'id' => 'countdown', 'title' => 'Cuenta regresiva', 'description' => 'Un reloj con los días que faltan. Usa la fecha que pusiste antes.',
            'fields' => [
                F('switch', 'sec_countdown', 'Quiero esta sección'),
                F('radio', 'countdown_style', 'Estilo del reloj', ['required' => true, 'show_if' => $on('countdown'), 'options' => $pick([['glass', 'Cristal'], ['editorial', 'Editorial'], ['rings', 'Órbitas']])]),
            ],
        ],
        [
            'id' => 'historia', 'title' => 'Historia o mensaje', 'description' => 'Unas palabras para tus invitados.',
            'fields' => [
                F('switch', 'sec_intro', 'Quiero esta sección'),
                F('textarea', 'intro', 'Tu mensaje o historia para los invitados', ['required' => true, 'max' => 1500, 'rows' => 6, 'show_if' => $on('intro'),
                    'placeholder' => 'Ej.: Hay noches que se sueñan durante años. Esta es la mía, y quiero vivirla con vos.']),
            ],
        ],
        [
            'id' => 'galeria', 'title' => 'Galería de fotos', 'description' => 'Pueden ser verticales o apaisadas: la galería respeta su forma.',
            'fields' => [
                F('switch', 'sec_gallery', 'Quiero esta sección'),
                F('text', 'gallery_title', 'Título de la galería', ['max' => 100, 'placeholder' => 'Pedacitos de nuestra historia', 'help' => 'Opcional. Si lo dejás vacío usamos “Pedacitos de nuestra historia”.', 'show_if' => $on('gallery')]),
                F('file', 'gallery_photos', 'Tus fotos', ['required' => true, 'max_files' => 12, 'ext' => $IMG_EXT, 'max_mb' => 12, 'compress' => true, 'link_alt' => true, 'captions' => true, 'caption_max' => 160,
                    'file_word_plural' => 'tus fotos', 'link_placeholder' => 'Link a una carpeta (Drive, Google Fotos, WeTransfer)',
                    'help' => 'Hasta 12 fotos. Podés sumarles un texto corto a cada una (opcional).', 'show_if' => $on('gallery')]),
            ],
        ],
        [
            'id' => 'lugar', 'title' => 'Lugar, horarios y vestimenta', 'description' => 'Dónde y cuándo pasa cada cosa.',
            'fields' => [
                F('switch', 'sec_details', 'Quiero esta sección'),
                F('text', 'venue', 'Nombre del lugar', ['required' => true, 'max' => 160, 'placeholder' => 'Ej.: Salón Las Camelias', 'show_if' => $on('details')]),
                F('text', 'address', 'Dirección (calle, número y ciudad)', ['required' => true, 'max' => 240, 'map' => true, 'placeholder' => 'Ej.: Av. Roca 123, Puerto Madryn, Chubut', 'show_if' => $on('details')]),
                F('text', 'dress', 'Vestimenta', ['max' => 300, 'help' => 'Opcional.', 'placeholder' => 'Ej.: Elegante · el rosa queda reservado para la quinceañera', 'show_if' => $on('details')]),
                ['type' => 'repeater', 'name' => 'schedule', 'label' => 'Momentos del evento', 'required' => true, 'help' => 'Hasta 6. Ej.: 21:30 Recepción · 23:00 Vals · 00:00 Torta.',
                    'row_label' => 'Momento', 'add_label' => 'Agregar momento', 'min_rows' => 1, 'max_rows' => 6, 'show_if' => $on('details'),
                    'fields' => [
                        ['name' => 'time', 'label' => 'Hora', 'type' => 'time', 'required' => true],
                        ['name' => 'label', 'label' => 'Qué pasa', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'Recepción'],
                    ]],
            ],
        ],
        [
            'id' => 'trivia', 'title' => 'Trivia', 'description' => 'Preguntas divertidas sobre quien se celebra. De 1 a 5.',
            'fields' => [
                F('switch', 'sec_trivia', 'Quiero esta sección'),
                ['type' => 'repeater', 'name' => 'trivia', 'label' => 'Preguntas', 'required' => true, 'help' => 'Escribí la pregunta, tres opciones y marcá cuál es la correcta.',
                    'row_label' => 'Pregunta', 'add_label' => 'Agregar pregunta', 'min_rows' => 1, 'max_rows' => 5, 'show_if' => $on('trivia'),
                    'fields' => [
                        ['name' => 'question', 'label' => 'Pregunta', 'type' => 'text', 'required' => true, 'max' => 220, 'wide' => true, 'placeholder' => '¿Cuál es mi color favorito?'],
                        ['name' => 'a', 'label' => 'Opción A', 'type' => 'text', 'required' => true, 'max' => 120, 'wide' => true],
                        ['name' => 'b', 'label' => 'Opción B', 'type' => 'text', 'required' => true, 'max' => 120, 'wide' => true],
                        ['name' => 'c', 'label' => 'Opción C', 'type' => 'text', 'required' => true, 'max' => 120, 'wide' => true],
                        ['name' => 'correct', 'label' => '¿Cuál es la correcta?', 'short' => 'Correcta', 'type' => 'select', 'required' => true, 'wide' => true,
                            'options' => [['value' => '0', 'label' => 'A'], ['value' => '1', 'label' => 'B'], ['value' => '2', 'label' => 'C']]],
                    ]],
            ],
        ],
        [
            'id' => 'capsula', 'title' => 'Cápsula de mensajes', 'description' => 'Tus invitados te dejan mensajes que se abren recién el día del evento.',
            'fields' => [
                F('switch', 'sec_capsule', 'Quiero esta sección', ['help' => 'No hay que completar nada más.']),
            ],
        ],
        [
            'id' => 'extras', 'title' => 'Música y regalos', 'description' => 'Todo opcional dentro de esta sección.',
            'fields' => [
                F('switch', 'sec_extras', 'Quiero esta sección'),
                F('url', 'music_url', 'Link de tu playlist o canción', ['max' => 500, 'placeholder' => 'https://open.spotify.com/…', 'help' => 'Spotify, YouTube Music, etc.', 'show_if' => $on('extras')]),
                F('file', 'audio', '¿Querés música sonando en la invitación? Subí la canción', ['max_files' => 1, 'ext' => ['mp3', 'm4a'], 'max_mb' => 12, 'file_word' => 'la canción', 'show_if' => $on('extras')]),
                F('text', 'audio_title', 'Nombre de la canción', ['max' => 100, 'placeholder' => 'Ej.: A thousand years', 'show_if' => $on('extras')]),
                F('textarea', 'gift', 'Regalos: alias o CBU, o un mensaje', ['max' => 500, 'rows' => 3, 'placeholder' => 'Ej.: Tu presencia es el mejor regalo 💝 Alias: lucia.xv', 'show_if' => $on('extras')]),
            ],
        ],
        [
            'id' => 'rsvp', 'title' => 'Confirmación de asistencia', 'description' => 'Las confirmaciones te llegan al WhatsApp que pusiste al principio.',
            'fields' => [
                F('switch', 'sec_rsvp', 'Quiero esta sección'),
                F('checkbox', 'menus', '¿Qué opciones de menú van a tener?', ['required' => true, 'min' => 1, 'max_count' => 8, 'show_if' => $on('rsvp'),
                    'options' => O(['Clásico', 'Vegetariano', 'Vegano', 'Sin gluten', 'Infantil', 'Otro'])]),
            ],
        ],
        [
            'id' => 'revision', 'title' => 'Revisá y enviá', 'description' => 'Fijate que esté todo bien. Con “Editar” volvés a cualquier paso.', 'review' => true,
            'fields' => [
                F('consent', 'autorizacion', 'Revisé los datos y autorizo a Jema a usar estas fotos en mi invitación', ['required' => true]),
            ],
        ],
    ],
];
