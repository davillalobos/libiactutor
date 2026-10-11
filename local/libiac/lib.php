<?php
/**
 * Library callbacks for local_libiac.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds "Biblioteca LibIAC" to the course navigation for users who can manage the course's knowledge.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_libiac_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('local/libiac:manageknowledge', $context)) {
        return;
    }
    $navigation->add(
        get_string('knowledgelibrary', 'local_libiac'),
        new moodle_url('/local/libiac/knowledge.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_libiac_knowledge',
        new pix_icon('i/folder', '')
    );
}

/**
 * Legacy footer callback (Moodle 4.0-4.2; on 4.3+ db/hooks.php is used instead).
 */
function local_libiac_before_footer() {
    \local_libiac\widget::inject();
}
