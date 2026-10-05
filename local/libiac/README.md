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
- Voz: `MediaRecorder` graba; el navegador convierte a WAV 16 kHz mono (el STT de Gemini no acepta webm) y la respuesta `audio_base64` se reproduce automáticamente.
- Inyección: `lib.php` (`local_libiac_before_footer`, Moodle 4.0–4.2) y `db/hooks.php` (`before_footer_html_generation`, 4.3+); `classes/widget.php` evita cargarlo dos veces.
- `amd/build/widget.min.js` es una copia del fuente con el nombre del módulo (sin minificar). Para regenerarlo con las herramientas de Moodle: `npx grunt amd --root=local/libiac`.

## Requisito en el backend (pendiente)
El backend debe aceptar la assertion de Moodle en `/chat` y `/voice/turn`. Hoy `authenticate_principal` rechaza todo (fail-closed), por lo que el widget mostrará "Tu cuenta aún no está vinculada con Libiac" (401) hasta que se habilite ese adaptador. Además el usuario necesita un `ExternalIdentity` con proveedor `moodle`.

## Web Services
Ver los pasos de habilitación de servicios web, token y capability `local/libiac:managecontext` (rol del usuario externo `libiac_api`).

## Rollback
- **Antes de actualizar la BD:** borrar la carpeta `<moodle>/local/libiac/`.
- **Ya instalado:** Administración > Plugins > Resumen de plugins > Libiac > Desinstalar (elimina `local_libiac_user_ctx`), o `php admin/cli/uninstall_plugins.php --plugins=local_libiac --run`, y borrar la carpeta.
- **Solo ocultar el widget:** vaciar el *Assertion signing secret*.
- **Código:** `git checkout master` y borrar la rama.
