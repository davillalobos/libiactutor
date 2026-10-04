<?php
/**
 * External (web service) API for local_libiac.
 *
 * @package    local_libiac
 */

namespace local_libiac;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

class external extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function get_context_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Moodle user id'),
        ]);
    }

    /**
     * Returns the stored context of a user.
     *
     * @param int $userid Moodle user id.
     * @return array
     */
    public static function get_context($userid) {
        $params = self::validate_parameters(self::get_context_parameters(), ['userid' => $userid]);
        self::validate_user($params['userid']);

        $record = context_manager::get_user_context($params['userid']);

        return [
            'initial_interview' => $record->initial_interview ?? '',
            'last_context' => $record->last_context ?? '',
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_context_returns() {
        return new external_single_structure([
            'initial_interview' => new external_value(PARAM_RAW, 'Initial interview data'),
            'last_context' => new external_value(PARAM_RAW, 'Last conversation context'),
        ]);
    }

    /**
     * @return external_function_parameters
     */
    public static function update_context_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Moodle user id'),
            'initial_interview' => new external_value(PARAM_RAW, 'Initial interview data'),
            'last_context' => new external_value(PARAM_RAW, 'Last conversation context'),
        ]);
    }

    /**
     * Stores the context of a user.
     *
     * @param int $userid Moodle user id.
     * @param string $initial_interview JSON or text of the first interview.
     * @param string $last_context JSON or text of the last conversation context.
     * @return bool
     */
    public static function update_context($userid, $initial_interview, $last_context) {
        $params = self::validate_parameters(self::update_context_parameters(), [
            'userid' => $userid,
            'initial_interview' => $initial_interview,
            'last_context' => $last_context,
        ]);
        self::validate_user($params['userid']);

        context_manager::update_user_context(
            $params['userid'],
            $params['initial_interview'],
            $params['last_context']
        );

        return true;
    }

    /**
     * @return external_value
     */
    public static function update_context_returns() {
        return new external_value(PARAM_BOOL, 'True on success');
    }

    /**
     * Checks system context, capability and that the target user exists.
     *
     * @param int $userid Moodle user id.
     * @return void
     */
    private static function validate_user($userid) {
        global $DB;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/libiac:managecontext', $context);

        if (!$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new \invalid_parameter_exception('Unknown user id');
        }
    }
}
