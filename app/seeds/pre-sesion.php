<?php
require_once __DIR__ . '/_helpers.php';

$sino = O(['Sí', 'No']);

return [
    'title' => 'Pre-sesión fotográfica',
    'subtitle' => '15 años & Bodas',
    'icon' => 'heart',
    'accent' => '',
    'intro' => 'Organicemos tu pre-sesión: contanos tu estilo, la locación, el vestuario y las referencias que te gustan. Importante: la pre-sesión debe realizarse al menos 15 días antes del evento principal.',
    'submit_label' => 'Enviar pre-sesión',
    'thanks_title' => '¡Gracias! Ya podemos empezar a planear tu pre-sesión',
    'thanks_text' => "Recibimos tus preferencias de estilo, locación y vestuario. Vamos a armar una propuesta y te escribimos para confirmar fecha y horario.",
    'client_email_field' => 'email',
    'list_fields' => ['nombre', 'fecha_evento', 'fecha_presesion'],
    'steps' => [
        [
            'id' => 'datos', 'title' => 'Datos generales', 'description' => 'Datos básicos para coordinar la pre-sesión.',
            'fields' => [
                F('email', 'email', 'Correo electrónico', ['required' => true]),
                F('text', 'nombre', 'Nombre y apellido de quien contrata / protagonista', ['required' => true]),
                F('tel', 'telefono', 'Teléfono de contacto (WhatsApp)', ['required' => true, 'placeholder' => '+54 9 280 xxxx xxxx', 'layout' => 'half']),
                F('tel', 'telefono_alt', 'WhatsApp alternativo', ['help' => 'Podés sumar otro teléfono de contacto (opcional).', 'layout' => 'half']),
                F('date', 'fecha_evento', 'Fecha del evento principal', ['required' => true, 'help' => 'Para asegurar la pre-sesión al menos 15 días antes.', 'layout' => 'half']),
                F('date', 'fecha_presesion', 'Fecha tentativa de la pre-sesión', ['required' => true, 'help' => 'Sugerimos atardecer si es exterior.', 'layout' => 'half',
                    'rule' => ['type' => 'days_before', 'ref' => 'fecha_evento', 'days' => 15, 'message' => 'Ojo: la pre-sesión debería hacerse al menos 15 días antes del evento. Igual podés enviarla y lo coordinamos.']]),
                F('text', 'locacion', 'Ciudad y/o locación deseada', ['required' => true, 'placeholder' => 'Playa, campo, urbano, estudio; o un lugar específico']),
                F('radio', 'recomendar_locaciones', '¿Querés que te recomendemos locaciones?', ['required' => true, 'options' => O([['Sí, recomienden opciones', 'Sí, recomienden opciones'], ['No, ya tengo un lugar definido', 'No, ya tengo un lugar definido']])]),
            ],
        ],
        [
            'id' => 'luz', 'title' => 'Horario y luz', 'description' => 'Elegí el momento del día y recursos adicionales.',
            'fields' => [
                F('checkbox', 'momento_dia', 'Preferencias de momento del día', ['help' => 'Podés elegir más de una.', 'options' => O([['Amanecer', 'Amanecer'], ['Tarde / atardecer', 'Tarde / atardecer'], ['Noche con luces / ciudad', 'Noche con luces / ciudad'], ['Interior o estudio', 'Interior o estudio']])]),
                F('radio', 'drone', '¿Querés incluir tomas con drone?', ['required' => true, 'options' => O([['Sí, incluir drone', 'Sí, incluir drone'], ['No, solo fotos a tierra', 'No, solo fotos a tierra']])]),
                F('radio', 'prueba_maquillaje', '¿Coordinamos el mismo día de la prueba de peinado/maquillaje?', ['required' => true, 'options' => O(['Sí', 'No', 'A evaluar'])]),
            ],
        ],
        [
            'id' => 'vestuario', 'title' => 'Vestuario y estética', 'description' => 'Contanos estilos, colores y si necesitás recomendaciones.',
            'fields' => [
                F('radio', 'cambios_ropa', '¿Cuántos cambios de ropa querés usar?', ['required' => true, 'options' => O(['1', '2', '3 o más'])]),
                F('textarea', 'vestuario_desc', 'Describí brevemente los estilos/colores de cada vestuario', ['placeholder' => 'Ej: elegante claro, urbano negro, pastel…', 'rows' => 3]),
                F('radio', 'recomendar_combinaciones', '¿Querés que recomendemos combinaciones o accesorios?', ['options' => O([['Sí, por favor', 'Sí, por favor'], ['No, ya lo tengo definido', 'No, ya lo tengo definido']])]),
                F('textarea', 'elementos_personales', '¿Querés incluir elementos personales o simbólicos?', ['placeholder' => 'Cartas, guitarra, flores, libro, mascota…', 'rows' => 3]),
            ],
        ],
        [
            'id' => 'estilo', 'title' => 'Estilo visual y referencias', 'description' => 'Tu estilo y tus referencias nos alinean expectativas.',
            'fields' => [
                F('checkbox', 'estilos', 'Estilos que te representan', ['options' => O(['Romántico y natural', 'Urbano / moderno', 'Elegante y cinematográfico', 'Divertido y espontáneo', 'Clásico'])]),
                F('checkbox', 'edicion', 'Tipo de edición de fotos que más te gusta', ['required' => true, 'help' => 'Podés marcar varias.', 'other' => true,
                    'options' => O([
                        ['Natural / clean', 'Natural / clean', 'Piel real, colores fieles'],
                        ['Cinemática', 'Cinemática', 'Contraste marcado, negros profundos'],
                        ['Cálida', 'Cálida', 'Tonos dorados, piel cálida'],
                        ['Pastel / mate', 'Pastel / mate', 'Suave, desaturado'],
                        ['Blanco y negro clásico', 'Blanco y negro clásico'],
                        ['Vibrante / detalle alto', 'Vibrante / detalle alto'],
                        ['Grain / look analógico', 'Grain / look analógico'],
                    ])]),
                F('textarea', 'links_referencias', 'Links a referencias (Pinterest / Instagram / TikTok)', ['help' => 'Pegá enlaces a tableros o publicaciones que te gusten.', 'rows' => 3]),
                F('url', 'carpeta_link', 'Link a carpeta de Drive o álbum de Google Fotos', ['help' => 'Opcional: donde subiste referencias o imágenes para la pre-sesión.']),
                F('file', 'fotos_inspiracion', 'Subí tus fotos de inspiración', ['help' => 'Opcional. Podés subir varias imágenes a la vez.', 'accept' => 'image/*']),
            ],
        ],
        [
            'id' => 'produccion', 'title' => 'Producción y clima', 'description' => 'Acompañantes, proveedores y plan B en caso de mal tiempo.',
            'fields' => [
                F('text', 'acompanantes', '¿Quiénes te acompañarán ese día?', ['help' => 'Amigos, familia, pareja, coordinadora… (opcional)']),
                F('radio', 'maquilladora', '¿Vas a contar con maquilladora/peinadora durante la sesión?', ['options' => O(['Sí', 'No', 'A definir'])]),
                F('checkbox', 'recomendaciones_proveedores', '¿Querés recomendaciones de proveedores?', ['options' => O(['Makeup / peinado', 'Flores', 'Vestuario / styling', 'Accesorios'])]),
                F('radio', 'mal_clima', 'En caso de mal clima, preferís…', ['required' => true, 'options' => O(['Reprogramar', 'Hacerla en interior / estudio'])]),
                F('text', 'fechas_alternativas', 'Fechas alternativas disponibles', ['help' => 'Opcional. Indicá otras fechas posibles para reprogramar.']),
            ],
        ],
        [
            'id' => 'entrega', 'title' => 'Redes y entrega', 'description' => 'Contenido para redes y cómo recibís el material.',
            'fields' => [
                F('checkbox', 'contenido_vertical', '¿Te gustaría que creemos contenido vertical (reels) y/o detrás de escena?', ['help' => 'Opcional.', 'options' => O(['Reel resumen', 'Clips verticales cortos', 'Behind the scenes para historias'])]),
                F('radio', 'autoriza_redes', '¿Autorizás a Jema Imagen Creativa a usar algunas tomas en sus redes?', ['required' => true, 'options' => $sino]),
                INFO('info_entrega', 'La entrega se realiza mediante Google Fotos, en un álbum privado con enlace. Podés solicitar una selección curada para redes.', 'Entrega del material'),
                F('email', 'email_gmail', 'Correo Gmail para compartir el álbum de Google Fotos', ['required' => true, 'placeholder' => 'tunombre@gmail.com']),
                F('radio', 'seleccion_curada', '¿Querés también una selección curada para publicar en redes?', ['options' => O([['Sí, selección curada además del álbum completo', 'Sí, selección curada además del álbum completo'], ['No, solo álbum completo', 'No, solo álbum completo']])]),
            ],
        ],
        [
            'id' => 'confirmacion', 'title' => 'Confirmación', 'description' => 'Leé y aceptá las condiciones.',
            'fields' => [
                F('checkbox', 'confirmaciones', 'Confirmaciones', ['required' => true, 'help' => 'Marcá las que correspondan (todas recomendadas).', 'options' => O([
                    'Confirmo que la pre-sesión se realizará al menos 15 días antes del evento',
                    'Entiendo que el material final se conservará online por 90 días',
                    'Acepto los términos de entrega y uso de imagen',
                ])]),
            ],
        ],
    ],
];
