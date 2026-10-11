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
$string['widget_greeting_failed'] = 'I could not load the welcome message, but you can write to me directly.';
$string['libiac:manageknowledge'] = 'Manage the Libiac knowledge library of a course';
$string['knowledgelibrary'] = 'Libiac library';
$string['knowledge_notconfigured'] = 'Libiac is not configured yet. An administrator must set the issuer, secret and backend URL in the plugin settings.';
$string['knowledge_intro'] = 'Upload the course material (PDF or text) the Libiac tutor will answer from. Each file is indexed in the background.';
$string['knowledge_file'] = 'File';
$string['knowledge_file_help'] = 'PDF, TXT or MD, up to 20 MB. Scanned PDFs without text cannot be indexed.';
$string['knowledge_classification'] = 'Pedagogical classification';
$string['knowledge_student'] = 'Accessible to students';
$string['knowledge_teacher'] = 'Reserved for teachers (answer keys / exams)';
$string['knowledge_upload'] = 'Upload and index';
$string['knowledge_uploading'] = 'Uploading...';
$string['knowledge_uploaded'] = 'File uploaded. Indexing started.';
$string['knowledge_nofile'] = 'Choose a file first.';
$string['knowledge_documents'] = 'Documents';
$string['knowledge_empty'] = 'No documents uploaded yet.';
$string['knowledge_delete'] = 'Delete';
$string['knowledge_confirmdelete'] = 'Delete this document and its indexed content?';
$string['knowledge_deleted'] = 'Document deleted.';
$string['col_name'] = 'Name';
$string['col_classification'] = 'Classification';
$string['col_date'] = 'Date';
$string['col_chunks'] = 'Chunks';
$string['col_status'] = 'Status';
$string['status_pending'] = 'pending';
$string['status_processing'] = 'processing';
$string['status_indexed'] = 'indexed';
$string['status_error'] = 'error';
$string['knowledge_error_forbidden'] = 'You are not allowed to manage the library of this course.';
$string['knowledge_error_toolarge'] = 'The file is too large (maximum 20 MB).';
$string['knowledge_error_unsupported'] = 'Only PDF and text files are supported.';
$string['knowledge_error_unavailable'] = 'Libiac is not available right now. Try again later.';
$string['knowledge_error_invalid'] = 'The request could not be processed.';
$string['knowledge_error_notfound'] = 'The document no longer exists.';
