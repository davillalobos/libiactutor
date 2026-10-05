<?php
/**
 * Hook registrations for local_libiac (Moodle 4.3+; older versions use lib.php).
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => [\local_libiac\hook_callbacks::class, 'before_footer_html_generation'],
    ],
];
