<?php
namespace local_libiac\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Data for the knowledge library page (template local_libiac/knowledge_page).
 *
 * @package    local_libiac
 */
class knowledge_page implements \renderable, \templatable {

    /** @var int */
    private $courseid;

    /** @var string */
    private $coursename;

    public function __construct(int $courseid, string $coursename) {
        $this->courseid = $courseid;
        $this->coursename = $coursename;
    }

    public function export_for_template(\renderer_base $output): array {
        return [
            'courseid' => $this->courseid,
            'coursename' => format_string($this->coursename),
        ];
    }

    /** Strings the JavaScript needs (status labels, messages, errors). */
    public static function js_strings(): array {
        $keys = ['status_pending', 'status_processing', 'status_indexed', 'status_error',
            'knowledge_student', 'knowledge_teacher', 'knowledge_delete', 'knowledge_confirmdelete',
            'knowledge_uploading', 'knowledge_uploaded', 'knowledge_deleted', 'knowledge_nofile',
            'knowledge_error_forbidden', 'knowledge_error_toolarge', 'knowledge_error_unsupported',
            'knowledge_error_unavailable', 'knowledge_error_invalid', 'knowledge_error_notfound',
            'settings_saving', 'settings_saved'];
        $keys = array_merge($keys, ['analytics_loading', 'analytics_active', 'analytics_active_detail', 'analytics_onboarding', 'analytics_onboarding_detail', 'analytics_activities', 'analytics_activities_detail', 'analytics_activity_razonamiento', 'analytics_activity_caso_practico', 'analytics_activity_deteccion_errores', 'analytics_activity_otra', 'analytics_comprehension', 'analytics_usage', 'analytics_messages', 'analytics_struggling', 'analytics_mastered', 'analytics_col_concept', 'analytics_col_students', 'analytics_col_share', 'analytics_none', 'analytics_hidden', 'analytics_error_forbidden']);
        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = get_string($key, 'local_libiac');
        }
        return $strings;
    }
}
