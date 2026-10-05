<?php
namespace local_libiac;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks (Moodle 4.3+).
 */
class hook_callbacks {

    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        widget::inject();
    }
}
