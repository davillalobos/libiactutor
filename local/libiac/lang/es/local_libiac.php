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
$string['libiac:manageknowledge'] = 'Gestionar la biblioteca de conocimiento de Libiac de un curso';
$string['knowledgelibrary'] = 'Biblioteca LibIAC';
$string['knowledge_notconfigured'] = 'Libiac aún no está configurado. Un administrador debe definir emisor, secreto y URL del backend en la configuración del plugin.';
$string['knowledge_intro'] = 'Sube el material del curso (PDF o texto) con el que responderá el tutor de Libiac. Cada archivo se indexa en segundo plano.';
$string['knowledge_file'] = 'Archivo';
$string['knowledge_file_help'] = 'PDF, TXT o MD, hasta 20 MB. Los PDF escaneados sin texto no se pueden indexar.';
$string['knowledge_classification'] = 'Clasificación pedagógica';
$string['knowledge_student'] = 'Accesible para estudiantes';
$string['knowledge_teacher'] = 'Reservado para docente (solucionarios / exámenes)';
$string['knowledge_upload'] = 'Subir e indexar';
$string['knowledge_uploading'] = 'Subiendo...';
$string['knowledge_uploaded'] = 'Archivo subido. Se inició la indexación.';
$string['knowledge_nofile'] = 'Elige primero un archivo.';
$string['knowledge_documents'] = 'Documentos';
$string['knowledge_empty'] = 'Aún no hay documentos cargados.';
$string['knowledge_delete'] = 'Eliminar';
$string['knowledge_confirmdelete'] = '¿Eliminar este documento y su contenido indexado?';
$string['knowledge_deleted'] = 'Documento eliminado.';
$string['col_name'] = 'Nombre';
$string['col_classification'] = 'Clasificación';
$string['col_date'] = 'Fecha';
$string['col_chunks'] = 'Chunks';
$string['col_status'] = 'Estado';
$string['status_pending'] = 'pendiente';
$string['status_processing'] = 'procesando';
$string['status_indexed'] = 'indexado';
$string['status_error'] = 'error';
$string['knowledge_error_forbidden'] = 'No tienes permiso para gestionar la biblioteca de este curso.';
$string['knowledge_error_toolarge'] = 'El archivo es demasiado grande (máximo 20 MB).';
$string['knowledge_error_unsupported'] = 'Solo se admiten archivos PDF y de texto.';
$string['knowledge_error_unavailable'] = 'Libiac no está disponible en este momento. Inténtalo más tarde.';
$string['knowledge_error_invalid'] = 'No se pudo procesar la solicitud.';
$string['knowledge_error_notfound'] = 'El documento ya no existe.';
