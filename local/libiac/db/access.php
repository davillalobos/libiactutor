<?php
/**
 * Capability definitions for local_libiac.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Who may manage a course's knowledge library (upload / list / delete material for the tutor).
    'local/libiac:manageknowledge' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],

    'local/libiac:managecontext' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [],
    ],
];
