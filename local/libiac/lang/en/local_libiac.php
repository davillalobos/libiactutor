<?php
/**
 * Language strings for local_libiac.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Libiac';
$string['libiac:managecontext'] = 'Manage Libiac user context via web services';
$string['issuer'] = 'Assertion issuer';
$string['issuer_desc'] = 'Must match MOODLE_ASSERTION_ISSUER configured on the Libiac backend, exactly.';
$string['secret'] = 'Assertion signing secret';
$string['secret_desc'] = 'Must match MOODLE_ASSERTION_SECRET configured on the Libiac backend. Never share or commit this value. The chat widget stays hidden until this is set.';
$string['backendurl'] = 'Libiac backend URL';
$string['backendurl_desc'] = 'Base URL of the Libiac Core API, called by the Moodle server (not by the browser). Local development: http://127.0.0.1:8050. Production: the HTTPS URL, e.g. https://libiac.yourdomain.org';
$string['connectionerror'] = 'Could not connect to Libiac.';
$string['widget_open'] = 'Open Libiac chat';
$string['widget_close'] = 'Close chat';
$string['widget_title'] = 'Libiac';
$string['widget_messagelabel'] = 'Message to Libiac';
$string['widget_messageplaceholder'] = 'Write a message...';
$string['widget_send'] = 'Send';
$string['widget_mic_start'] = 'Record voice message';
$string['widget_mic_stop'] = 'Stop recording and send';
$string['widget_recording'] = 'Recording... press the microphone again to send.';
$string['widget_processing'] = 'Processing...';
$string['widget_micdenied'] = 'Microphone access was denied. Allow it in the browser to use voice.';
$string['widget_micunsupported'] = 'This browser cannot record audio.';
$string['widget_you'] = 'You';
$string['widget_assistant'] = 'Libiac';
$string['widget_error_unauthorized'] = 'Your account is not linked to Libiac yet. Contact your administrator.';
$string['widget_error_toolarge'] = 'The recording is too long. Try a shorter message.';
$string['widget_error_unsupported'] = 'The audio format is not supported.';
$string['widget_error_nospeech'] = 'No speech was detected. Try again.';
$string['widget_error_unavailable'] = 'Libiac is not available right now. Try again later.';
$string['widget_error_invalid'] = 'The message could not be sent.';
