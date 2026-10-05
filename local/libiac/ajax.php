<?php
/**
 * Server-side proxy between the chat widget and the Libiac backend.
 *
 * The browser never talks to the backend and never sees the signing secret: it
 * posts here (Moodle session + sesskey), and this script signs a fresh assertion
 * for the current user/course and forwards the request to POST /chat or
 * POST /voice/turn. Backend failures are mapped to generic error keys — internal
 * details never reach the browser.
 *
 * Query: action=chat|voice|greeting, courseid, sesskey.
 * chat  body: JSON {"message": "...", "page_context": {...}}        -> {"message": "..."}
 * voice body: JSON {"audio_base64": "<WAV>", "page_context": {...}}  -> {transcript, response_text, audio_base64, audio_format}
 *
 * greeting body: JSON {"page_context": {...}}                      -> {"message": "...", "greeted": bool}
 *   (welcome for a student with no previous conversation; empty message otherwise)
 *
 * page_context (title, url, visible text of the page the user is on) comes from the
 * browser and is length-limited here; the course id and name always come from Moodle.
 *
 * @package    local_libiac
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

const LOCAL_LIBIAC_MAX_AUDIO_BYTES = 10 * 1024 * 1024; // Same limit as the backend (VOICE_MAX_AUDIO_BYTES).
const LOCAL_LIBIAC_MAX_MESSAGE_CHARS = 4000;
const LOCAL_LIBIAC_PAGE_FIELD_LIMITS = ['page_title' => 255, 'page_url' => 1000, 'page_text' => 8000];

/**
 * Keeps only the known page fields, as length-limited strings, and adds the trusted
 * course id/name from Moodle itself.
 */
function local_libiac_page_context($input, stdClass $course): array {
    $context = ['course_id' => (string) $course->id, 'course_name' => mb_substr(strip_tags(format_string($course->fullname, true, ['escape' => false])), 0, 255)];
    if (is_array($input)) {
        foreach (LOCAL_LIBIAC_PAGE_FIELD_LIMITS as $field => $limit) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $context[$field] = mb_substr($input[$field], 0, $limit);
            }
        }
    }
    return $context;
}

function local_libiac_respond(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    die();
}

function local_libiac_fail(string $key, int $status): void {
    local_libiac_respond($status, ['error' => $key]);
}

$courseid = required_param('courseid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);
require_sesskey();

if (isguestuser()) {
    local_libiac_fail('unauthorized', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    local_libiac_fail('invalid', 405);
}
if (!\local_libiac\widget::is_configured()) {
    local_libiac_fail('unavailable', 503);
}

$issuer = get_config('local_libiac', 'issuer');
$secret = get_config('local_libiac', 'secret');
$backendurl = get_config('local_libiac', 'backendurl');

$raw = (string) file_get_contents('php://input');

if ($action === 'chat') {
    $data = json_decode($raw, true);
    $message = is_array($data) && isset($data['message']) && is_string($data['message'])
        ? trim($data['message']) : '';
    if ($message === '' || mb_strlen($message) > LOCAL_LIBIAC_MAX_MESSAGE_CHARS) {
        local_libiac_fail('invalid', 400);
    }
    $path = '/chat';
    $body = json_encode([
        'message' => $message,
        'page_context' => local_libiac_page_context($data['page_context'] ?? null, $course),
    ]);
    $contenttype = 'application/json';
} else if ($action === 'voice') {
    $data = json_decode($raw, true);
    $audio = is_array($data) && isset($data['audio_base64']) && is_string($data['audio_base64'])
        ? $data['audio_base64'] : '';
    if ($audio === '') {
        local_libiac_fail('invalid', 400);
    }
    if (strlen($audio) * 3 / 4 > LOCAL_LIBIAC_MAX_AUDIO_BYTES) {
        local_libiac_fail('toolarge', 413);
    }
    $path = '/voice/turn';
    $body = json_encode([
        'audio_base64' => $audio,
        'page_context' => local_libiac_page_context($data['page_context'] ?? null, $course),
    ]);
    $contenttype = 'application/json';
} else if ($action === 'greeting') {
    $data = json_decode($raw, true);
    $path = '/chat/greeting';
    $body = json_encode([
        'page_context' => local_libiac_page_context(is_array($data) ? ($data['page_context'] ?? null) : null, $course),
    ]);
    $contenttype = 'application/json';
} else {
    local_libiac_fail('invalid', 400);
}

// 300 seconds: the backend's MOODLE_ASSERTION_MAX_LIFETIME (see assertion_signer).
$assertion = \local_libiac\assertion_signer::build_and_sign(
    $issuer, $secret, (string) $USER->id, (string) $course->id, 300,
    mb_substr(trim(strip_tags(fullname($USER))), 0, 255)
);

try {
    $result = \local_libiac\libiac_client::forward($backendurl, $assertion, $path, $body, $contenttype);
} catch (\Exception $e) {
    local_libiac_fail('unavailable', 502);
}

$errors = [
    400 => ['invalid', 400],
    401 => ['unauthorized', 401],
    403 => ['unauthorized', 403],
    413 => ['toolarge', 413],
    415 => ['unsupported', 415],
    422 => ['nospeech', 422],
];
if ($result['status'] < 200 || $result['status'] >= 300) {
    [$key, $status] = $errors[$result['status']] ?? ['unavailable', 502];
    local_libiac_fail($key, $status);
}

$response = $result['body'];
if ($action === 'chat') {
    local_libiac_respond(200, ['message' => (string) ($response['message'] ?? '')]);
}
if ($action === 'greeting') {
    local_libiac_respond(200, [
        'message' => (string) ($response['message'] ?? ''),
        'greeted' => !empty($response['greeted']),
    ]);
}
local_libiac_respond(200, [
    'transcript' => (string) ($response['transcript'] ?? ''),
    'response_text' => (string) ($response['response_text'] ?? ''),
    'audio_base64' => isset($response['audio_base64']) ? (string) $response['audio_base64'] : null,
    'audio_format' => isset($response['audio_format']) ? (string) $response['audio_format'] : null,
]);
