<?php
/**
 * Knowledge library of a course: upload, list and delete the material the Libiac tutor answers from.
 *
 * @package    local_libiac
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php'); // Moodle's \curl class lives here.

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/libiac:manageknowledge', $context);

$PAGE->set_url(new moodle_url('/local/libiac/knowledge.php', ['id' => $course->id]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('knowledgelibrary', 'local_libiac'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('knowledgelibrary', 'local_libiac'));

if (!\local_libiac\widget::is_configured()) {
    echo $OUTPUT->notification(get_string('knowledge_notconfigured', 'local_libiac'), \core\output\notification::NOTIFY_WARNING);
} else {
    $page = new \local_libiac\output\knowledge_page((int) $course->id, $course->fullname);
    echo $OUTPUT->render_from_template('local_libiac/knowledge_page', $page->export_for_template($OUTPUT));
    $PAGE->requires->js_call_amd('local_libiac/knowledge_manager', 'init', [[
        'ajaxurl' => (new moodle_url('/local/libiac/knowledge_ajax.php'))->out(false),
        'courseid' => (int) $course->id,
        'sesskey' => sesskey(),
        'strings' => \local_libiac\output\knowledge_page::js_strings(),
    ]]);
}

echo $OUTPUT->footer();
