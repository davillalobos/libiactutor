<?php
namespace local_libiac;

defined('MOODLE_INTERNAL') || die();

/**
 * Loads the floating chat widget on every page of a logged-in user.
 *
 * Called from the footer callbacks (lib.php for Moodle < 4.3, hook_callbacks.php
 * for 4.3+). The widget DOM is built by the AMD module; this class only decides
 * whether to load it and hands it its configuration.
 */
class widget {

    /** Page layouts where a floating widget makes no sense. */
    private const EXCLUDED_LAYOUTS = ['embedded', 'popup', 'print', 'redirect', 'maintenance', 'frametop'];

    /** @var bool Guards against both the legacy callback and the hook firing. */
    private static $injected = false;

    public static function is_configured(): bool {
        return get_config('local_libiac', 'issuer') && get_config('local_libiac', 'secret')
            && get_config('local_libiac', 'backendurl');
    }

    public static function inject(): void {
        global $PAGE, $COURSE;

        if (self::$injected || CLI_SCRIPT || (defined('AJAX_SCRIPT') && AJAX_SCRIPT)) {
            return;
        }
        if (!isloggedin() || isguestuser() || in_array($PAGE->pagelayout, self::EXCLUDED_LAYOUTS, true)) {
            return;
        }
        if (!self::is_configured()) {
            return;
        }
        self::$injected = true;

        $keys = ['open', 'close', 'title', 'messagelabel', 'messageplaceholder', 'send', 'mic_start', 'mic_stop',
            'recording', 'processing', 'micdenied', 'micunsupported', 'you', 'assistant', 'error_unauthorized',
            'error_toolarge', 'error_unsupported', 'error_nospeech', 'error_unavailable', 'error_invalid'];
        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = get_string('widget_' . $key, 'local_libiac');
        }

        $PAGE->requires->js_call_amd('local_libiac/widget', 'init', [[
            'ajaxurl' => (new \moodle_url('/local/libiac/ajax.php'))->out(false),
            'courseid' => (int) $COURSE->id,
            'sesskey' => sesskey(),
            'strings' => $strings,
        ]]);
    }
}
