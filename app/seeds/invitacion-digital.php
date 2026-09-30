<?php
require_once __DIR__ . '/_helpers.php';

$paletas = O([
    ['Romántico', 'Romántico', '', ['#E5C3A6', '#9E6D5B', '#2B2B2B']],
    ['Minimal', 'Minimal', '', ['#FFFFFF', '#EDEDED', '#222222']],
    ['Marino', 'Marino', '', ['#0B2545', '#134074', '#8DA9C4']],
    ['Pastel', 'Pastel', '', ['#FAD4D8', '#D8E2DC', '#FFE5D9']],
    ['Clásico dorado', 'Clásico dorado', '', ['#D4AF37', '#2E2E2E', '#FFFFFF']],
]);

return [
    'title' => 'Invitación digital',
    'subtitle' => 'Qué Planazo · por Jema',
    'brand' => 'planazo',
    'icon' => 'card',
    'accent' => '#9a513a',
    'intro' => 'Armemos tu invitación. Contanos los datos de tu evento, tus fotos, tus colores y tu historia: nosotros la diseñamos. Todos los datos son opcionales salvo los marcados; si algo no aplica, dejalo en blanco o aclaralo en los comentarios.',
    'submit_label' => 'Enviar datos de mi invitación',
    'thanks_title' => '¡Listo! Ya podemos armar tu invitación',
    'thanks_text' => "Recibimos los datos de tu invitación. Los plazos corren desde que recibimos tus fotos y los datos del evento. Si algo falta, te escribimos por WhatsApp.",
    'client_email_field' => 'email',
    'list_fields' => ['homenajeados', 'plan', 'dia'],
    'steps' => [
        [
            'id' => 'plan', 'title' => 'Tu invitación', 'description' => 'Elegí el plan y el estilo que más te gusta de la colección.',
            'fields' => [
                F('email', 'email', 'Correo electrónico', ['required' => true, 'layout' => 'half']),
                F('text', 'contacto', 'Tu nombre', ['required' => true, 'layout' => 'half']),
                F('radio', 'plan', 'Plan', ['required' => true, 'cards' => true, 'options' => O([
                    ['digital', 'Invitación digital · $45.000', 'Portada, cuenta regresiva, ubicación, confirmación de asistencia, dress code, regalos, cronograma y galería. Entrega en 48 hs hábiles.'],
                    ['interactiva', 'Experiencia interactiva · $70.000', 'Todo lo de la Invitación digital, más trivia personalizada y cápsula de mensajes de tus invitados. Entrega en 72 hs hábiles.'],
                ])]),
                F('radio', 'categoria', 'Tipo de celebración', ['required' => true, 'options' => O(['XV años', 'Bodas', 'Egresados', 'Evento especial'])]),
                F('text', 'muestra', '¿Qué diseño de la colección te gustó?', ['help' => 'Escribí el nombre de la muestra en queplanazo.jemaic.com (por ejemplo “Valentina” o “Sí, con vos. Siempre.”), o contanos una idea propia.']),
                F('radio', 'mascota', 'Compañía para tu invitación', ['help' => 'Un pequeño cómplice que pasea con tus invitados (se puede prender o apagar).', 'other' => true,
                    'options' => O(['Sin compañía', 'Perrito', 'Gatito', 'Ballenita austral'])]),
            ],
        ],
        [
            'id' => 'evento', 'title' => 'El evento', 'description' => 'Los datos que van en la portada y en la información de tu invitación.',
            'fields' => [
                F('text', 'homenajeados', 'Nombre/s del/los homenajead@/s', ['required' => true]),
                F('select', 'tipo_evento', 'Tipo de evento', ['other' => true, 'options' => O(['Cumpleaños', '15 años', 'Boda', 'Egreso', 'Aniversario']), 'layout' => 'half']),
                F('date', 'dia', 'Día del evento', ['required' => true, 'layout' => 'half']),
                F('time', 'hora', 'Hora del evento', ['required' => true, 'layout' => 'half']),
                F('text', 'salon', 'Nombre del salón', ['layout' => 'half']),
                F('text', 'ubicacion', 'Ubicación del evento', ['help' => 'Dirección, ciudad, punto de referencia.']),
                F('url', 'maps', 'Link de Google Maps', ['help' => 'Opcional. Pegá un link de Google Maps para la ubicación.']),
                F('textarea', 'ceremonia', '¿La ceremonia es en otro lugar o momento?', ['help' => 'Si aplica: dirección, horario y fecha.', 'rows' => 2]),
                F('radio', 'dress_code', 'Código de vestimenta', ['cards' => true, 'options' => O(['Elegante', 'Elegante sport', 'Otro / no aplica'])]),
                F('textarea', 'cronograma', 'Cronograma del evento', ['help' => 'Opcional. Hora y actividad, una por línea (recepción, cena, baile…).', 'rows' => 4]),
                F('textarea', 'info_importante', 'Información importante para tus invitados', ['help' => 'Opcional. Por ejemplo: estacionamiento, niños, cómo llegar.', 'rows' => 3]),
            ],
        ],
        [
            'id' => 'invitados', 'title' => 'Confirmación y contacto', 'description' => 'Cómo confirman tus invitados y cómo te encontramos.',
            'fields' => [
                F('checkbox', 'rsvp', '¿Qué querés preguntarle a tus invitados al confirmar?', ['options' => O([
                    'Asistencia y cantidad de personas', 'Menú', 'Alergias o restricciones alimentarias', 'Canción favorita para la playlist',
                ])]),
                F('textarea', 'menu_opciones', 'Opciones de menú', ['help' => 'Una por línea (ej: Clásico, Vegetariano, Celíaco).', 'rows' => 3, 'show_if' => ['field' => 'rsvp', 'equals' => 'Menú']]),
                F('tel', 'wa_confirmacion', 'WhatsApp para CONFIRMACIONES', ['required' => true, 'help' => 'Número al que llegan las confirmaciones.', 'placeholder' => '+54 9 280 xxxx xxxx', 'layout' => 'half']),
                F('tel', 'wa_datos', 'WhatsApp para DATOS IMPORTANTES y MÚSICA', ['help' => 'Número de contacto alternativo.', 'layout' => 'half']),
                F('text', 'instagram', 'Instagram del/los agasajad@/s', ['help' => 'Usuario sin @ o link al perfil.']),
            ],
        ],
        [
            'id' => 'fotos', 'title' => 'Fotos y música', 'description' => 'Las imágenes y la canción que cuentan tu historia.',
            'fields' => [
                INFO('info_album', 'Si preferís cargar las fotos elegidas en un álbum de Google Fotos, pedíselo a la coordinadora por WhatsApp: 280 434-3587.', 'Álbum de fotos'),
                F('file', 'fotos', 'Subí tus fotos', ['help' => 'Podés subir varias imágenes a la vez.', 'accept' => 'image/*']),
                F('url', 'album', 'Link a carpeta o álbum (Google Fotos, Drive u otro)', ['help' => 'Si ya tenés un álbum listo con TODAS las imágenes organizadas.']),
                F('textarea', 'comentarios_imagenes', 'Comentarios sobre las imágenes', ['help' => 'Opcional. Aclarar si alguna imagen NO va, orden sugerido, etc.', 'rows' => 2]),
                F('text', 'cancion', 'Canción de fondo', ['help' => 'Tema y artista, o un link.', 'layout' => 'half']),
                F('url', 'spotify', 'Lista de Spotify', ['help' => 'Pegá el link de la playlist colaborativa.', 'layout' => 'half']),
            ],
        ],
        [
            'id' => 'textos', 'title' => 'Textos y regalos', 'description' => 'Las palabras de tu invitación.',
            'fields' => [
                F('textarea', 'frase_whatsapp', 'Frase de invitación para WhatsApp', ['help' => 'Texto corto para enviar por WhatsApp junto con la portada.', 'rows' => 2]),
                F('textarea', 'frase_tarjeta', 'Frase de invitación para la tarjeta', ['help' => 'Breve o extensa: es el texto que va dentro de la invitación.', 'rows' => 4]),
                INFO('info_regalos', 'Completá lo que corresponda. Podés usar uno o varios métodos. Todo es opcional.', 'Regalos'),
                F('url', 'regalo_mp', 'Link de regalo económico (Mercado Pago)'),
                F('text', 'regalo_alias', 'Alias', ['layout' => 'half']),
                F('text', 'regalo_cbu', 'CBU / CVU', ['layout' => 'half', 'help' => 'Solo si querés que figure en la invitación.']),
            ],
        ],
        [
            'id' => 'interactiva', 'title' => 'Experiencia interactiva', 'description' => 'Trivia y cápsula de mensajes.',
            'show_if' => ['field' => 'plan', 'equals' => 'interactiva'],
            'fields' => [
                F('textarea', 'trivia', 'Preguntas para la trivia', ['rows' => 8, 'help' => 'Preguntas divertidas sobre los protagonistas. Idealmente 5 a 10: escribí cada pregunta con su respuesta correcta y 2 o 3 opciones incorrectas.']),
                F('textarea', 'capsula', 'Mensaje de bienvenida para la cápsula', ['rows' => 3, 'help' => 'Opcional. Tus invitados dejan mensajes que se desbloquean el día de la celebración.']),
            ],
        ],
        [
            'id' => 'estilo', 'title' => 'Estilo visual', 'description' => 'Colores, motivos y referencias para diseñar a tu medida.',
            'fields' => [
                F('palette', 'paletas', 'Paletas sugeridas', ['help' => 'Elegí alguna como guía (podés combinar).', 'options' => $paletas]),
                F('colors', 'colores', 'Colores principales', ['help' => 'Elegí 2 principales y 1 para detalles.']),
                F('file', 'fotos_paleta', 'Fotos con la paleta de colores deseada', ['help' => 'Opcional.', 'accept' => 'image/*']),
                F('text', 'motivos', 'Motivos / iconografía', ['help' => 'Ej: coronas, mariposas, flores, olivo, estrellas, minimal…']),
                F('textarea', 'estetica', 'Estética y referencias', ['help' => 'Opcional. Links a Pinterest / Instagram o palabras clave del estilo que te gusta.', 'rows' => 3]),
            ],
        ],
        [
            'id' => 'cierre', 'title' => 'Plazos y comentarios', 'description' => 'Último paso.',
            'fields' => [
                F('date', 'fecha_publicacion', 'Fecha objetivo de publicación', ['help' => 'Opcional. Una fecha tentativa para difundir la invitación.']),
                INFO('info_plazos', 'Los plazos corren desde que recibimos tus fotos y los datos del evento: 48 hs hábiles para la Invitación digital y 72 hs hábiles para la Experiencia interactiva. Si necesitás fast-track, avisanos en los comentarios.', 'Importante'),
                F('textarea', 'comentarios', 'Comentarios finales / secciones que NO van', ['help' => 'Aclarar si alguna sección no aplica o preferís omitirla.', 'rows' => 3]),
            ],
        ],
    ],
];
