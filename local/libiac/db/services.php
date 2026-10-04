<?php
/**
 * Web service definitions for local_libiac.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_libiac_get_context' => [
        'classname'    => 'local_libiac\external',
        'methodname'   => 'get_context',
        'description'  => 'Returns the initial interview and last conversation context of a user.',
        'type'         => 'read',
        'capabilities' => 'local/libiac:managecontext',
    ],
    'local_libiac_update_context' => [
        'classname'    => 'local_libiac\external',
        'methodname'   => 'update_context',
        'description'  => 'Stores the initial interview and last conversation context of a user.',
        'type'         => 'write',
        'capabilities' => 'local/libiac:managecontext',
    ],
];

$services = [
    'LibIAC Integration' => [
        'functions'       => ['local_libiac_get_context', 'local_libiac_update_context'],
        'restrictedusers' => 1,
        'enabled'         => 1,
        'shortname'       => 'libiac_integration',
    ],
];
