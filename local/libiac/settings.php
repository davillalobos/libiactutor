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

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_description(
            'local_libiac/connectiontest',
            get_string('diag_title', 'local_libiac'),
            html_writer::tag('p', get_string('diag_intro', 'local_libiac'), ['class' => 'text-muted']) .
            html_writer::tag('button', get_string('diag_button', 'local_libiac'),
                ['type' => 'button', 'class' => 'btn btn-secondary', 'data-action' => 'test-connection']) .
            html_writer::div('', 'mt-3', ['data-region' => 'diagnostics', 'aria-live' => 'polite'])
        ));
        $strings = [];
        foreach (['diag_button', 'diag_testing', 'diag_server', 'diag_database', 'diag_llm', 'diag_latency', 'diag_state_ok', 'diag_state_degraded', 'diag_state_error', 'diag_unreachable', 'diag_badsignature', 'diag_forbidden', 'diag_servererror', 'diag_signature_ok', 'diag_notconfigured'] as $key) {
            $strings[$key] = get_string($key, 'local_libiac');
        }
        $PAGE->requires->js_call_amd('local_libiac/connection_test', 'init', [[
            'ajaxurl' => (new moodle_url('/local/libiac/knowledge_ajax.php'))->out(false),
            'courseid' => (int) SITEID,
            'sesskey' => sesskey(),
            'strings' => $strings,
        ]]);
    }
}
