<?php
/**
 * Server-side proxy between the knowledge library page and the Libiac backend.
 *
 * The browser never talks to the backend and never sees the signing secret: it calls this script
 * (Moodle session + sesskey + capability check), which signs a fresh assertion for the current
 * user and course — including their roles and the manageknowledge capability, evaluated here by
 * Moodle — and sends it to /api/v1/knowledge/documents in X-Moodle-Assertion headers.
 *
 * Query: action=list|upload|delete, courseid, sesskey.
 *   list   GET                                -> {"documents": [...]}
 *   upload POST multipart: file, access_level -> {"document": {...}}
 *   delete POST id=<uuid>                     -> {"ok": true}
 *
 * @package    local_libiac
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

const LOCAL_LIBIAC_KB_MAX_BYTES = 20 * 1024 * 1024; // Same limit as the backend.
const LOCAL_LIBIAC_KB_CAPABILITY = 'local/libiac:manageknowledge';

function local_libiac_kb_respond(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    die();
}

function local_libiac_kb_fail(string $key, int $status): void {
    local_libiac_kb_respond($status, ['error' => $key]);
}

/** Only the fields the page needs, as plain values. */
function local_libiac_kb_document($doc): array {
    $doc = is_array($doc) ? $doc : [];
    return [
        'id' => (string) ($doc['id'] ?? ''),
        'filename' => (string) ($doc['filename'] ?? ''),
        'access_level' => (string) ($doc['access_level'] ?? ''),
        'status' => (string) ($doc['status'] ?? ''),
        'chunk_count' => (int) ($doc['chunk_count'] ?? 0),
        'error_message' => isset($doc['error_message']) ? (string) $doc['error_message'] : null,
        'created_at' => (string) ($doc['created_at'] ?? ''),
    ];
}

$courseid = required_param('courseid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);
require_sesskey();
$context = context_course::instance($course->id);
if (isguestuser() || !has_capability(LOCAL_LIBIAC_KB_CAPABILITY, $context)) {
    local_libiac_kb_fail('forbidden', 403);
}
if (!\local_libiac\widget::is_configured()) {
    local_libiac_kb_fail('unavailable', 503);
}

$roles = [];
foreach (get_user_roles($context, $USER->id, true) as $assignment) {
    $roles[$assignment->shortname] = true;
}
$assertion = \local_libiac\assertion_signer::build_and_sign(
    get_config('local_libiac', 'issuer'),
    get_config('local_libiac', 'secret'),
    (string) $USER->id,
    (string) $course->id,
    300,
    mb_substr(trim(strip_tags(fullname($USER))), 0, 255),
    implode(',', array_keys($roles)),
    LOCAL_LIBIAC_KB_CAPABILITY
);
$backendurl = get_config('local_libiac', 'backendurl');
$base = '/api/v1/knowledge/documents';

try {
    if ($action === 'list') {
        $result = \local_libiac\libiac_client::request(
            'GET', $backendurl, $assertion, $base . '?external_course_id=' . rawurlencode((string) $course->id)
        );
    } else if ($action === 'upload') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            local_libiac_kb_fail('invalid', 405);
        }
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
            local_libiac_kb_fail(($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE ? 'toolarge' : 'invalid', 400);
        }
        if ($file['size'] > LOCAL_LIBIAC_KB_MAX_BYTES) {
            local_libiac_kb_fail('toolarge', 413);
        }
        $access = optional_param('access_level', 'student_accessible', PARAM_ALPHAEXT);
        if (!in_array($access, ['student_accessible', 'teacher_only'], true)) {
            local_libiac_kb_fail('invalid', 400);
        }
        $result = \local_libiac\libiac_client::request(
            'POST', $backendurl, $assertion, $base,
            [
                'file' => new \CURLFile($file['tmp_name'], 'application/octet-stream', clean_param($file['name'], PARAM_FILE)),
                'external_course_id' => (string) $course->id,
                'access_level' => $access,
            ]
        );
    } else if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            local_libiac_kb_fail('invalid', 405);
        }
        $id = required_param('id', PARAM_ALPHANUMEXT);
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            local_libiac_kb_fail('invalid', 400);
        }
        $result = \local_libiac\libiac_client::request('DELETE', $backendurl, $assertion, $base . '/' . $id);
    } else {
        local_libiac_kb_fail('invalid', 400);
    }
} catch (\Exception $e) {
    local_libiac_kb_fail('unavailable', 502);
}

$errors = [
    400 => ['invalid', 400],
    401 => ['forbidden', 403],
    403 => ['forbidden', 403],
    404 => ['notfound', 404],
    413 => ['toolarge', 413],
    415 => ['unsupported', 415],
    422 => ['invalid', 422],
];
if ($result['status'] < 200 || $result['status'] >= 300) {
    [$key, $status] = $errors[$result['status']] ?? ['unavailable', 502];
    local_libiac_kb_fail($key, $status);
}

if ($action === 'list') {
    local_libiac_kb_respond(200, ['documents' => array_map('local_libiac_kb_document', array_values($result['body']))]);
}
if ($action === 'upload') {
    local_libiac_kb_respond(200, ['document' => local_libiac_kb_document($result['body'])]);
}
local_libiac_kb_respond(200, ['ok' => true]);
