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
 * Query: action=chat|voice, courseid, sesskey.
 * chat  body: JSON {"message": "..."}  -> {"message": "..."}
 * voice body: raw WAV audio            -> {transcript, response_text, audio_base64, audio_format}
 *
 * @package    local_libiac
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

const LOCAL_LIBIAC_MAX_AUDIO_BYTES = 10 * 1024 * 1024; // Same limit as the backend (VOICE_MAX_AUDIO_BYTES).
const LOCAL_LIBIAC_MAX_MESSAGE_CHARS = 4000;

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
    $body = json_encode(['message' => $message]);
    $contenttype = 'application/json';
} else if ($action === 'voice') {
    if ($raw === '') {
        local_libiac_fail('invalid', 400);
    }
    if (strlen($raw) > LOCAL_LIBIAC_MAX_AUDIO_BYTES) {
        local_libiac_fail('toolarge', 413);
    }
    $path = '/voice/turn';
    $body = $raw;
    $contenttype = 'application/octet-stream';
} else {
    local_libiac_fail('invalid', 400);
}

// 300 seconds: the backend's MOODLE_ASSERTION_MAX_LIFETIME (see assertion_signer).
$assertion = \local_libiac\assertion_signer::build_and_sign(
    $issuer, $secret, (string) $USER->id, (string) $course->id, 300
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
local_libiac_respond(200, [
    'transcript' => (string) ($response['transcript'] ?? ''),
    'response_text' => (string) ($response['response_text'] ?? ''),
    'audio_base64' => isset($response['audio_base64']) ? (string) $response['audio_base64'] : null,
    'audio_format' => isset($response['audio_format']) ? (string) $response['audio_format'] : null,
]);
