<?php
require_once __DIR__ . '/_helpers.php';

return [
    'title' => 'Información previa al evento',
    'subtitle' => 'Fotografía y filmación',
    'icon' => 'camera',
    'accent' => '#9a513a',
    'intro' => 'Completá los detalles de tu evento para que podamos coordinar la fotografía y la filmación con precisión. Te lleva unos 5 minutos.',
    'submit_label' => 'Enviar información',
    'thanks_title' => '¡Gracias! Ya tenemos la información de tu evento',
    'thanks_text' => "Recibimos los datos de tu evento. Vamos a revisarlos y, si algo necesita ajuste, te escribimos por WhatsApp.\n\nNos vemos pronto.",
    'client_email_field' => 'email_gmail',
    'list_fields' => ['nombre', 'fecha_evento', 'salon_ciudad'],
    'steps' => [
        [
            'id' => 'datos', 'title' => 'Datos generales', 'description' => 'Datos básicos para coordinar.',
            'fields' => [
                F('text', 'nombre', 'Nombre y apellido de quien contrata / protagonista/s', ['required' => true]),
                F('tel', 'telefono', 'Teléfono de contacto (WhatsApp)', ['required' => true, 'placeholder' => '+54 9 280 xxxx xxxx', 'layout' => 'half']),
                F('tel', 'telefono_alt', 'WhatsApp alternativo', ['help' => 'Podés sumar otro teléfono de contacto (opcional).', 'placeholder' => '+54 9 280 xxxx xxxx', 'layout' => 'half']),
                F('date', 'fecha_evento', 'Fecha del evento', ['required' => true, 'layout' => 'half']),
                F('select', 'tipo_evento', 'Tipo de evento', ['options' => O(['15 años', 'Boda', 'Aniversario', 'Cumpleaños', 'Egreso']), 'other' => true, 'layout' => 'half']),
                F('text', 'salon_ciudad', 'Salón y ciudad', ['required' => true, 'placeholder' => 'Ej: Salón Las Toninas, Puerto Madryn']),
            ],
        ],
        [
            'id' => 'fotos', 'title' => 'Fotos que no pueden faltar', 'description' => 'Fotos y/o imágenes que no pueden faltar en tu evento.',
            'fields' => [
                F('url', 'pinterest', 'Link de Pinterest con fotos o ideas para tu evento', ['placeholder' => 'Pegar enlace…', 'help' => 'Opcional.']),
                F('textarea', 'fotos_imprescindibles', 'Fotos o momentos que no pueden faltar', ['placeholder' => 'Familiares, grupos de amigos, momentos, detalles de decoración…', 'help' => 'Opcional.']),
                F('textarea', 'fotos_previas', '¿Dónde les/te gustaría las fotos antes de entrar al salón?', ['required' => true, 'rows' => 3]),
                F('textarea', 'lugares_alternativos', 'En caso de mal clima, indicá lugares alternativos en interiores', ['rows' => 3]),
                F('textarea', 'pedido_extra', 'Pedido extra / sugerencias', ['rows' => 3]),
                INFO('info_retiro', "Si contrataste video/fotoclip: pasar a retirar un pendrive por Jema un día antes del evento.\nSi contrataste cuadro/atril: retirarlo un día antes por el local.", 'Recordatorio'),
            ],
        ],
        [
            'id' => 'logistica', 'title' => 'Horarios y logística', 'description' => 'Para llegar puntuales y con todo listo.',
            'fields' => [
                F('text', 'punto_encuentro', 'Punto de encuentro con el equipo de Jema Ic', ['required' => true]),
                F('time', 'hora_inicio', 'Hora de inicio del servicio', ['required' => true, 'layout' => 'half']),
                F('time', 'hora_fin', 'Hora de finalización estimada', ['required' => true, 'layout' => 'half']),
                F('radio', 'comida', '¿Habrá comida o bebida disponible para el equipo de Jema?', ['required' => true, 'options' => O([['Sí, incluido en el servicio', 'Sí, incluido en el servicio'], ['No, Jema llevará sus viáticos', 'No, Jema llevará sus viáticos']])]),
                F('consent', 'espacio_notebook', 'De acuerdo', ['help' => 'En caso de edición en vivo/SDE o impresión de fotos souvenir, solicitamos un espacio en el salón para notebook e impresora.']),
                F('number', 'fotos_souvenir', '¿Cuántas fotos souvenir les gustaría?', ['help' => 'Opcional.', 'min' => 0, 'layout' => 'half']),
            ],
        ],
        [
            'id' => 'musica', 'title' => 'Música', 'description' => 'La banda sonora de tu recuerdo.',
            'fields' => [
                F('textarea', 'canciones', 'Artistas y canciones que les/te gustan (5 canciones)', ['required' => true, 'help' => 'Las usamos en la edición posterior.', 'rows' => 4]),
                F('text', 'cancion_resumen', 'Una canción para el video resumen del evento', ['help' => 'Opcional.']),
            ],
        ],
        [
            'id' => 'redes', 'title' => 'Contenido para redes', 'description' => 'Servicios opcionales para Instagram, TikTok y Stories.',
            'fields' => [
                F('radio', 'filmacion_vertical', '¿Contrataste filmación vertical (Content Creator)?', ['required' => true, 'options' => O([['Sí', 'Sí, contraté filmación vertical'], ['No', 'No, no contraté filmación vertical']])]),
                F('checkbox', 'contenido_redes', '¿Qué contenido querés?', ['options' => O(['Reel resumen', 'Clips verticales cortos', 'Behind the scenes para historias']), 'show_if' => ['field' => 'filmacion_vertical', 'equals' => 'Sí']]),
                F('radio', 'autoriza_redes', '¿Autorizás a Jema Imagen Creativa a usar algunas tomas en sus redes?', ['required' => true, 'options' => O(['Sí', 'No'])]),
                F('text', 'redes_usuario', 'Nombre de tus redes sociales', ['placeholder' => '@usuario', 'show_if' => ['field' => 'autoriza_redes', 'equals' => 'Sí']]),
            ],
        ],
        [
            'id' => 'entrega', 'title' => 'Entrega y confirmación', 'description' => 'Cómo te hacemos llegar el material.',
            'fields' => [
                INFO('info_entrega', 'La entrega se realiza mediante Google Fotos, en un álbum privado con enlace. Podés solicitar una selección curada para redes.', 'Entrega del material'),
                F('email', 'email_gmail', 'Correo Gmail para compartir el álbum de Google Fotos', ['required' => true, 'placeholder' => 'tunombre@gmail.com']),
                F('checkbox', 'confirmaciones', 'Confirmaciones', ['required' => true, 'help' => 'Marcá las que correspondan (todas recomendadas).', 'options' => O(['Entiendo que el material final se conservará online por 90 días', 'Acepto los términos de entrega y uso de imagen'])]),
            ],
        ],
    ],
];
