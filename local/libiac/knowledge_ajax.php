<?php
/**
 * Server-side proxy between the knowledge library page and the Libiac backend.
 *
 * The browser never talks to the backend and never sees the signing secret: it calls this script
 * (Moodle session + sesskey + capability check), which signs a fresh assertion for the current
 * user and course — including their roles and the manageknowledge capability, evaluated here by
 * Moodle — and sends it to /api/v1/knowledge/documents in X-Moodle-Assertion headers.
 *
 * Query: action=list|upload|delete|getsettings|savesettings|getanalytics|checkhealth, courseid, sesskey.
 *   list   GET                                -> {"documents": [...]}
 *   upload POST multipart: file, access_level -> {"document": {...}}
 *   delete POST id=<uuid>                     -> {"ok": true}
 *   getsettings  GET                          -> {"settings": {...}}      (backend: GET  /api/v1/courses/{id}/settings)
 *   savesettings POST pedagogical_mode, depth_level, formative_assessment (0|1)
 *                                             -> {"settings": {...}}      (backend: PUT  /api/v1/courses/{id}/settings)
 *   getanalytics GET                          -> {"analytics": {...}}     (backend: GET  /api/v1/courses/{id}/analytics)
 *   checkhealth  GET                          -> {"health": {...}}        (backend: GET  /api/v1/system/status; always HTTP 200, the
 *                                                state says what happened: ok|degraded|error|unreachable|badsignature|forbidden)
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

/** The course settings the page needs, as plain values. */
function local_libiac_kb_settings($settings): array {
    $settings = is_array($settings) ? $settings : [];
    return [
        'pedagogical_mode' => (string) ($settings['pedagogical_mode'] ?? 'mixed'),
        'depth_level' => (string) ($settings['depth_level'] ?? 'intermediate'),
        'formative_assessment_enabled' => !isset($settings['formative_assessment_enabled'])
            || !empty($settings['formative_assessment_enabled']),
    ];
}

/** A list of {concept, students, rate}: whitelisted fields, bounded numbers, plain strings. */
function local_libiac_kb_concepts($items): array {
    $out = [];
    foreach (is_array($items) ? array_slice($items, 0, 10) : [] as $item) {
        $item = is_array($item) ? $item : [];
        $out[] = [
            'concept' => mb_substr(trim(strip_tags((string) ($item['concept'] ?? ''))), 0, 200),
            'students' => max(0, (int) ($item['students'] ?? 0)),
            'rate' => max(0.0, min(1.0, (float) ($item['rate'] ?? 0))),
        ];
    }
    return $out;
}

/**
 * The analytics the dashboard draws. Only aggregates reach the browser (the backend sends no student
 * data either); every field is re-cast here so nothing unexpected is ever forwarded.
 */
function local_libiac_kb_analytics($data): array {
    $data = is_array($data) ? $data : [];
    $q = is_array($data['qualitative'] ?? null) ? $data['qualitative'] : [];
    $n = is_array($data['quantitative'] ?? null) ? $data['quantitative'] : [];
    $activities = is_array($q['formative_activities'] ?? null) ? $q['formative_activities'] : [];
    $bytype = [];
    foreach (is_array($activities['by_type'] ?? null) ? $activities['by_type'] : [] as $row) {
        $type = (string) ($row['type'] ?? '');
        if (in_array($type, ['razonamiento', 'caso_practico', 'deteccion_errores', 'otra'], true)) {
            $bytype[] = ['type' => $type, 'count' => max(0, (int) ($row['count'] ?? 0))];
        }
    }
    return [
        'qualitative' => [
            'available' => !empty($q['available']),
            'students_with_evidence' => max(0, (int) ($q['students_with_evidence'] ?? 0)),
            'struggling_concepts' => local_libiac_kb_concepts($q['struggling_concepts'] ?? []),
            'comprehended_concepts' => local_libiac_kb_concepts($q['comprehended_concepts'] ?? []),
            'formative_activities' => ['total' => max(0, (int) ($activities['total'] ?? 0)), 'by_type' => $bytype],
        ],
        'quantitative' => [
            'window_days' => max(0, (int) ($n['window_days'] ?? 0)),
            'students_in_cohort' => max(0, (int) ($n['students_in_cohort'] ?? 0)),
            'active_students' => max(0, (int) ($n['active_students'] ?? 0)),
            'active_rate' => max(0.0, min(1.0, (float) ($n['active_rate'] ?? 0))),
            'onboarding_completed' => max(0, (int) ($n['onboarding_completed'] ?? 0)),
            'onboarding_rate' => max(0.0, min(1.0, (float) ($n['onboarding_rate'] ?? 0))),
            'messages_in_window' => max(0, (int) ($n['messages_in_window'] ?? 0)),
        ],
    ];
}

/** A component status as plain, whitelisted values. */
function local_libiac_kb_component($component): array {
    $component = is_array($component) ? $component : [];
    $state = (string) ($component['status'] ?? '');
    return [
        'status' => in_array($state, ['ok', 'degraded', 'error'], true) ? $state : 'error',
        'message' => mb_substr(trim(strip_tags((string) ($component['message'] ?? ''))), 0, 200),
    ];
}

/**
 * The connection diagnostic. Distinguishes what the administrator can act on: the server does not
 * answer (wrong URL / network), answers 401 (issuer or secret do not match), or answers with the
 * component states.
 */
function local_libiac_kb_health(string $backendurl, array $assertion): array {
    $started = microtime(true);
    try {
        $result = \local_libiac\libiac_client::request('GET', $backendurl, $assertion, '/api/v1/system/status', null, null, 30);
    } catch (\Exception $e) {
        return ['state' => 'unreachable'];
    }
    $roundtrip = (int) round((microtime(true) - $started) * 1000);
    if ($result['status'] === 401) {
        return ['state' => 'badsignature', 'roundtrip_ms' => $roundtrip];
    }
    if ($result['status'] === 403) {
        return ['state' => 'forbidden', 'roundtrip_ms' => $roundtrip];
    }
    if ($result['status'] < 200 || $result['status'] >= 300) {
        return ['state' => 'error', 'roundtrip_ms' => $roundtrip];
    }
    $body = $result['body'];
    $state = (string) ($body['status'] ?? '');
    $components = is_array($body['components'] ?? null) ? $body['components'] : [];
    return [
        'state' => in_array($state, ['ok', 'degraded', 'error'], true) ? $state : 'error',
        'roundtrip_ms' => $roundtrip,
        'latency_ms' => max(0, (int) ($body['latency_ms'] ?? 0)),
        'components' => [
            'database' => local_libiac_kb_component($components['database'] ?? []),
            'llm_provider' => local_libiac_kb_component($components['llm_provider'] ?? []),
        ],
    ];
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

if ($action === 'checkhealth') {
    local_libiac_kb_respond(200, ['health' => local_libiac_kb_health($backendurl, $assertion)]);
}

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
    } else if ($action === 'getsettings') {
        $result = \local_libiac\libiac_client::request(
            'GET', $backendurl, $assertion, '/api/v1/courses/' . rawurlencode((string) $course->id) . '/settings'
        );
    } else if ($action === 'getanalytics') {
        $result = \local_libiac\libiac_client::request(
            'GET', $backendurl, $assertion, '/api/v1/courses/' . rawurlencode((string) $course->id) . '/analytics'
        );
    } else if ($action === 'savesettings') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            local_libiac_kb_fail('invalid', 405);
        }
        $mode = required_param('pedagogical_mode', PARAM_ALPHA);
        $depth = required_param('depth_level', PARAM_ALPHA);
        if (!in_array($mode, ['reactive', 'proactive', 'mixed'], true)
                || !in_array($depth, ['basic', 'intermediate', 'advanced'], true)) {
            local_libiac_kb_fail('invalid', 400);
        }
        $result = \local_libiac\libiac_client::request(
            'PUT', $backendurl, $assertion, '/api/v1/courses/' . rawurlencode((string) $course->id) . '/settings',
            json_encode([
                'pedagogical_mode' => $mode,
                'depth_level' => $depth,
                'formative_assessment_enabled' => optional_param('formative_assessment', 0, PARAM_INT) === 1,
            ]),
            'application/json'
        );
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

if ($action === 'getsettings' || $action === 'savesettings') {
    local_libiac_kb_respond(200, ['settings' => local_libiac_kb_settings($result['body'])]);
}
if ($action === 'getanalytics') {
    local_libiac_kb_respond(200, ['analytics' => local_libiac_kb_analytics($result['body'])]);
}
if ($action === 'list') {
    local_libiac_kb_respond(200, ['documents' => array_map('local_libiac_kb_document', array_values($result['body']))]);
}
if ($action === 'upload') {
    local_libiac_kb_respond(200, ['document' => local_libiac_kb_document($result['body'])]);
}
local_libiac_kb_respond(200, ['ok' => true]);
