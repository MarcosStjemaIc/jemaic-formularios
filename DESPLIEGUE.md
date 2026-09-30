# Puesta en marcha en Hostinger — formularios.jemaic.com

Tiempo estimado: 20–30 minutos. No hace falta saber programar, alcanza con seguir los pasos en orden.

## Qué necesita tu plan
- **PHP 8.1 o superior** (Hostinger trae 8.2/8.3; se elige en hPanel → *Avanzado → Configuración de PHP*).
- **Una base de datos MySQL** (todos los planes Premium/Business y los Cloud la incluyen).
- Extensiones habituales de PHP: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl` (vienen activadas por defecto).

> Si tu plan fuera solo "Website Builder" (sin PHP), avisame: hay que pasar a un plan de hosting web o usar otra opción.

---

## 1. Crear el subdominio
1. hPanel → **Dominios → Subdominios**.
2. Escribí `formularios` en el dominio `jemaic.com` y creá el subdominio.
3. Hostinger crea una carpeta para él, normalmente `public_html/formularios` (o `domains/jemaic.com/public_html/formularios`). Tomá nota de cuál es.
4. Activá el **SSL gratuito** (hPanel → *Seguridad → SSL*). Tarda unos minutos. La app fuerza HTTPS.

## 2. Crear la base de datos
1. hPanel → **Bases de datos → Administración**.
2. Creá una base nueva. Hostinger te da tres datos: **nombre**, **usuario** y **contraseña** (los tres suelen empezar con `u123456789_`). Guardalos.
3. El servidor casi siempre es `localhost`.

Las tablas se crean solas la primera vez que se abre la aplicación.

## 3. Crear el correo que envía los avisos
1. hPanel → **Correos electrónicos** → crear `formularios@jemaic.com` (o el que prefieras) con una contraseña.
2. Datos SMTP de Hostinger: servidor `smtp.hostinger.com`, puerto `465`, seguridad `ssl`.

## 4. Subir los archivos
1. Subí el archivo `formularios-jema.zip` en el **Administrador de archivos** dentro de la carpeta del subdominio y usá *Extraer*.
2. Comprobá que en esa carpeta queden directamente `index.php`, `.htaccess`, `app/`, `assets/`, `storage/`, etc. (no una carpeta extra adentro). Si el `.htaccess` no se ve, activá "Mostrar archivos ocultos" en el administrador.

## 5. Configurar
1. En esa misma carpeta, copiá `config.sample.php` con el nombre **`config.php`** y editalo:
   - `base_url`: `https://formularios.jemaic.com`
   - `setup_key`: una frase larga y única (la vas a usar una sola vez, paso 6).
   - `db`: `driver` = `mysql`, y `name`, `user`, `pass` con los datos del paso 2.
   - `mail`: `from_email`, `smtp.user` y `smtp.pass` del paso 3.
   - `mail.notify`: los correos del equipo que deben recibir el aviso de cada respuesta, por ejemplo `['info@jemaic.com']`.
   - `coordinator_whatsapp`: número con código de país, solo dígitos (ya viene cargado el de Marcos; cambialo si corresponde).
   - Dejá `debug` en `false`.
2. La carpeta `storage/` (y `storage/uploads`, `storage/logs`) tiene que poder escribirse: permisos **755** (o 775). Si al abrir la app aparece un error de escritura, subí a 775.

## 6. Crear tu usuario administrador
1. Entrá a: `https://formularios.jemaic.com/admin/setup?key=LA_CLAVE_QUE_PUSISTE`
2. Cargá nombre, email y una contraseña de al menos 10 caracteres.
3. Ese enlace deja de servir en cuanto existe un usuario. Después, para sumar gente del equipo: **Panel → Usuarios**.
4. Ingresá desde `https://formularios.jemaic.com/admin`.

Los 4 formularios ya vienen cargados la primera vez que se abre la app:
`/f/informacion-previa`, `/f/pre-sesion`, `/f/fotoclip` y `/f/invitacion-digital`.

## 7. Probar todo (5 minutos)
1. Abrí `https://formularios.jemaic.com` y completá un formulario con tu propio email en el campo de correo.
2. Deberías recibir: el **aviso al equipo** (a los correos de `mail.notify`) y la **confirmación al cliente**.
3. En el panel: *Ver respuestas → abrir la respuesta → cambiar estado / agregar nota interna → Exportar CSV*.
4. Si el correo no llega: revisá `storage/logs/error.log` (dice el motivo exacto: usuario/clave, puerto, etc.). Probá el puerto `587` con `secure` = `tls`, o `driver` = `mail` como alternativa.

## 8. Cómo compartir los formularios
Cada tarjeta del panel tiene un botón **Copiar** con el enlace. Podés pegarlo en WhatsApp, Instagram o tus PDFs de presupuesto.
La parte final de la dirección (por ejemplo `/f/fotoclip`) se puede cambiar desde *Editar*, pero el enlace anterior deja de funcionar.

---

## Uso diario
- **Editar preguntas**: Panel → Editar. Podés cambiar textos, reordenar, agregar/quitar pasos y campos, y hacer que una pregunta aparezca solo si respondieron algo antes. Los cambios **no alteran** las respuestas ya recibidas.
- **Nuevo formulario**: escribí el nombre en el panel y "+ Nuevo formulario"; o **Duplicar** uno existente para partir de él.
- **Desactivar**: un formulario desactivado muestra un cartel de "no disponible" sin borrar nada.
- **Respuestas**: filtro por estado (Nuevo / En curso / Listo / Archivado), búsqueda, notas internas, descarga de los archivos que subió el cliente y exportación a CSV (abre bien en Excel y Google Sheets).

## Seguridad (ya incluida)
Protección CSRF, anti-spam (campo trampa, tiempo mínimo, límite por IP), contraseñas cifradas, bloqueo tras intentos fallidos de login, archivos subidos fuera del alcance público (solo se ven con sesión iniciada), y `config.php`, `app/` y `storage/` bloqueados por `.htaccess`.

Recomendado: hacer una copia de la base de datos y de `storage/uploads` de vez en cuando (hPanel → *Copias de seguridad*).

## Actualizar o mover
- Para reinstalar sobre otra base de datos borrá `storage/.schema_ok`.
- No pises `config.php` ni `storage/` al subir una versión nueva.

## Problemas frecuentes
| Síntoma | Solución |
|---|---|
| Error 500 al abrir | Revisá la versión de PHP (≥ 8.1) y que `config.php` exista y no tenga errores de sintaxis (comas, comillas). |
| "No se pudo conectar a la base" | Repetí nombre/usuario/clave del paso 2; el host casi siempre es `localhost`. |
| 404 en `/f/...` | Falta el `.htaccess` (archivo oculto) en la carpeta del subdominio. |
| No suben fotos grandes | Subí `upload_max_filesize` y `post_max_size` en hPanel → *Configuración de PHP*. |
| No llegan los emails | Mirá `storage/logs/error.log` y revisá la carpeta de spam; probá puerto 587/tls. |
