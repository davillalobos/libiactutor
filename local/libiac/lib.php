<?php
/**
 * Library callbacks for local_libiac.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Legacy footer callback (Moodle 4.0-4.2; on 4.3+ db/hooks.php is used instead).
 */
function local_libiac_before_footer() {
    \local_libiac\widget::inject();
}
