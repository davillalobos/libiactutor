<?php
/**
 * Language strings for local_libiac.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Libiac';
$string['libiac:managecontext'] = 'Gestionar el contexto de usuario de Libiac mediante servicios web';
$string['issuer'] = 'Emisor de la aserción';
$string['issuer_desc'] = 'Debe coincidir exactamente con MOODLE_ASSERTION_ISSUER del backend de Libiac.';
$string['secret'] = 'Secreto de firma de la aserción';
$string['secret_desc'] = 'Debe coincidir con MOODLE_ASSERTION_SECRET del backend de Libiac. No lo compartas ni lo subas al repositorio. El widget de chat permanece oculto hasta definirlo.';
$string['backendurl'] = 'URL del backend de Libiac';
$string['backendurl_desc'] = 'URL base de la API de Libiac Core, consultada por el servidor de Moodle (no por el navegador). Local: http://127.0.0.1:8050. Producción: la URL HTTPS, p. ej. https://libiac.tudominio.org';
$string['connectionerror'] = 'No se pudo conectar con Libiac.';
$string['widget_open'] = 'Abrir el chat de Libiac';
$string['widget_close'] = 'Cerrar el chat';
$string['widget_title'] = 'Libiac';
$string['widget_messagelabel'] = 'Mensaje para Libiac';
$string['widget_messageplaceholder'] = 'Escribe un mensaje...';
$string['widget_send'] = 'Enviar';
$string['widget_mic_start'] = 'Grabar mensaje de voz';
$string['widget_mic_stop'] = 'Detener la grabación y enviar';
$string['widget_recording'] = 'Grabando... pulsa de nuevo el micrófono para enviar.';
$string['widget_processing'] = 'Procesando...';
$string['widget_micdenied'] = 'Se denegó el acceso al micrófono. Permítelo en el navegador para usar la voz.';
$string['widget_micunsupported'] = 'Este navegador no puede grabar audio.';
$string['widget_you'] = 'Tú';
$string['widget_assistant'] = 'Libiac';
$string['widget_error_unauthorized'] = 'Tu cuenta aún no está vinculada con Libiac. Contacta al administrador.';
$string['widget_error_toolarge'] = 'La grabación es demasiado larga. Intenta un mensaje más corto.';
$string['widget_error_unsupported'] = 'El formato de audio no es compatible.';
$string['widget_error_nospeech'] = 'No se detectó voz. Inténtalo de nuevo.';
$string['widget_error_unavailable'] = 'Libiac no está disponible en este momento. Inténtalo más tarde.';
$string['widget_error_invalid'] = 'No se pudo enviar el mensaje.';
$string['widget_greeting_failed'] = 'No pude cargar el mensaje de bienvenida, pero puedes escribirme directamente.';
