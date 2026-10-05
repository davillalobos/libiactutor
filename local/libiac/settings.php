<?php
/**
 * Admin settings for local_libiac. The defaults are placeholders, never real
 * credentials: the chat widget stays off until the secret is set.
 *
 * @package    local_libiac
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_libiac', get_string('pluginname', 'local_libiac'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_libiac/issuer',
        get_string('issuer', 'local_libiac'),
        get_string('issuer_desc', 'local_libiac'),
        'https://your-moodle.example.org',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_libiac/secret',
        get_string('secret', 'local_libiac'),
        get_string('secret_desc', 'local_libiac'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_libiac/backendurl',
        get_string('backendurl', 'local_libiac'),
        get_string('backendurl_desc', 'local_libiac'),
        'http://127.0.0.1:8050',
        PARAM_URL
    ));
}
