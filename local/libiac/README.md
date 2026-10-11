# local_libiac

Plugin local de Moodle para Libiac. Unifica:

1. **Widget de chat flotante** (texto + micrófono) inyectado en el footer de todas las páginas.
2. **Web Services** para leer/guardar la entrevista inicial y el último contexto del usuario (`local_libiac_get_context`, `local_libiac_update_context`).

## Instalación
1. Copiar `local/libiac/` a `<moodle>/local/libiac/` (si estaba instalado el bloque `block_libiac`, ya no hace falta).
2. Administración del sitio > Notificaciones > actualizar la base de datos (hacer backup antes).
3. Purgar cachés (Administración > Desarrollo > Purgar todas las cachés) para cargar el CSS/JS.

## Configuración del widget
Administración del sitio > Plugins > Plugins locales > **Libiac**:

| Campo | Valor |
|---|---|
| Assertion issuer | Igual a `MOODLE_ASSERTION_ISSUER` del backend |
| Assertion signing secret | Igual a `MOODLE_ASSERTION_SECRET` del backend |
| Libiac backend URL | `http://127.0.0.1:8050` en local; URL HTTPS en producción |

El widget **no aparece** hasta que el secreto está definido, ni para invitados ni en layouts embebidos/popup.

**Moodle bloquea por defecto las peticiones del servidor a `127.0.0.1`.** En local, quitar `127.0.0.1` de *Administración > Seguridad > Seguridad HTTP > cURL blocked hosts* y añadir el puerto `8050` a *cURL allowed ports*.

## Cómo funciona
- El navegador **no habla con el backend** ni ve el secreto: el JS (`amd/src/widget.js`) envía a `/local/libiac/ajax.php` (sesión de Moodle + sesskey). Ese script firma una assertion fresca (300 s) del usuario/curso y la reenvía a `POST /chat` o `POST /voice/turn` en las cabeceras `X-Moodle-Assertion` y `X-Moodle-Assertion-Signature`.
- Contexto de la página: con cada mensaje (texto o voz) el widget envía el título, la ruta y el texto visible de la zona principal de la página (hasta 6000 caracteres, sin `sesskey`); `ajax.php` lo limita y añade el id y nombre del curso desde Moodle. El backend lo inyecta en el prompt del tutor como datos, no como instrucciones.
- Saludo: la primera vez que se abre el chat en una página, el widget pide a `/chat/greeting` (vía `ajax.php`, acción `greeting`) el mensaje de bienvenida; la respuesta es `{greeting, has_history, messages}`: un estudiante nuevo recibe el saludo (e inicia la entrevista de onboarding); uno con conversación previa recibe sus últimos mensajes para redibujarlos. Si falla (red, 500, modelo no disponible) el widget muestra un aviso amable y deja el campo de texto listo; la siguiente apertura reintenta, y el detalle queda en `console.error`.
- Voz: `MediaRecorder` graba; el navegador convierte a WAV 16 kHz mono (el STT de Gemini no acepta webm) y se envía como JSON (`audio_base64` + contexto de la página) a `/voice/turn`; la respuesta `audio_base64` se reproduce automáticamente.
- Inyección: `lib.php` (`local_libiac_before_footer`, Moodle 4.0–4.2) y `db/hooks.php` (`before_footer_html_generation`, 4.3+); `classes/widget.php` evita cargarlo dos veces.
- `amd/build/widget.min.js` es una copia del fuente con el nombre del módulo (sin minificar). Para regenerarlo con las herramientas de Moodle: `npx grunt amd --root=local/libiac`.

## Requisito en el backend
El backend (rama `feat/moodle-principal-adapter` del repo libiac) acepta la assertion en `/chat` y `/voice/turn`, con `MOODLE_ASSERTION_ISSUER` y `MOODLE_ASSERTION_SECRET` iguales a los de este plugin. Con `MOODLE_AUTO_PROVISION=true` (por defecto) crea el usuario de Libiac y su `ExternalIdentity` (proveedor `moodle`) la primera vez que un usuario escribe en el chat; con `false`, el usuario debe vincularse antes.

## Biblioteca de conocimiento (docentes)
Capacidad `local/libiac:manageknowledge` (contexto de curso; por defecto `editingteacher`, `teacher` y `manager`). Quien la tiene ve **"Biblioteca LibIAC"** en la navegación del curso (`/local/libiac/knowledge.php?id=<curso>`): sube PDF/TXT (clasificación *accesible para estudiantes* o *reservado para docente*), ve la tabla con estado, chunks y fecha, y elimina documentos. Los que están `pending`/`processing` se refrescan solos cada 3 s.

En la misma pantalla, **Configuración pedagógica del tutor**: modalidad (Reactivo / Proactivo / Mixto, predeterminado), profundidad (Básica / Intermedia / Avanzada) y la casilla de ejercicios y evaluaciones formativas. Se leen con `getsettings` y se guardan con `savesettings` (PUT a `/api/v1/courses/{id}/settings`, firmado con HMAC en el servidor).

Las llamadas pasan por `knowledge_ajax.php` (sesión de Moodle + sesskey + capacidad): el servidor firma `X-Moodle-Assertion` con el **rol y la capacidad evaluados por Moodle** para ese curso y lo envía a `/api/v1/knowledge/documents`. El backend solo acepta a ese docente para **ese curso**. El material `teacher_only` nunca llega al tutor que responde a estudiantes.

## Web Services
Ver los pasos de habilitación de servicios web, token y capability `local/libiac:managecontext` (rol del usuario externo `libiac_api`).

## Rollback
- **Antes de actualizar la BD:** borrar la carpeta `<moodle>/local/libiac/`.
- **Ya instalado:** Administración > Plugins > Resumen de plugins > Libiac > Desinstalar (elimina `local_libiac_user_ctx`), o `php admin/cli/uninstall_plugins.php --plugins=local_libiac --run`, y borrar la carpeta.
- **Solo ocultar el widget:** vaciar el *Assertion signing secret*.
- **Código:** `git checkout master` y borrar la rama.

## Dashboard de Seguimiento Pedagógico (0.8.0)

En la misma página **Biblioteca LibIAC** (solo quien tiene `local/libiac:manageknowledge`) hay un dashboard del curso: tarjetas de *Estudiantes activos*, *Onboarding completado* y *Actividades formativas realizadas*; la lista priorizada **Conceptos a reforzar** (barras) y la tabla **Conceptos dominados**. Los indicadores **cualitativos** (evidencia que el tutor registró) se muestran separados de los **cuantitativos** (conteos de uso).

`knowledge_ajax.php?action=getanalytics` firma la aserción en el servidor y consulta `GET /api/v1/courses/{id}/analytics`; reenvía solo agregados con campos y números re-validados, y el navegador los dibuja con `textContent` (sin HTML inyectable). No viajan nombres ni mensajes de estudiantes. Con menos de 3 estudiantes el backend oculta el detalle por concepto (`ANALYTICS_MIN_COHORT`).

## Probar conexión y diagnóstico (0.9.0)

En **Administración del sitio > Plugins > Plugins locales > LibIAC** (junto a *emisor*, *clave secreta* y *URL del backend*) hay un botón **Probar conexión**. Llama a `knowledge_ajax.php?action=checkhealth`, que envía al backend una solicitud firmada con HMAC a `GET /api/v1/system/status` y muestra con insignias verde / amarillo / rojo: servidor FastAPI y tiempo de respuesta, base de datos PostgreSQL + pgvector y proveedor LLM / RAG. Si la firma no coincide el backend responde 401 y el panel lo explica (emisor o clave secreta distintos). La prueba usa los valores **guardados**: guarde antes de probar.

Los ajustes conservan sus nombres (`local_libiac/backendurl` = URL del backend, `local_libiac/secret` = clave secreta HMAC) para no romper instalaciones existentes.
