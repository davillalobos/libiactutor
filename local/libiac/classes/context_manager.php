<?php
/**
 * Stores and retrieves the per-user Libiac context.
 *
 * @package    local_libiac
 */

namespace local_libiac;

defined('MOODLE_INTERNAL') || die();

class context_manager {

    /**
     * Returns the stored context of a user.
     *
     * @param int $userid Moodle user id.
     * @return \stdClass|null Object with initial_interview and last_context, or null if none stored.
     */
    public static function get_user_context($userid) {
        global $DB;

        $record = $DB->get_record(
            'local_libiac_user_ctx',
            ['userid' => $userid],
            'initial_interview, last_context'
        );

        return $record ?: null;
    }

    /**
     * Inserts or updates the context of a user.
     *
     * @param int $userid Moodle user id.
     * @param string|null $initial_interview JSON or text of the first interview.
     * @param string|null $last_context JSON or text of the last conversation context.
     * @return void
     */
    public static function update_user_context($userid, $initial_interview, $last_context) {
        global $DB;

        $now = time();
        $existing = $DB->get_record('local_libiac_user_ctx', ['userid' => $userid], 'id');

        if ($existing) {
            $existing->initial_interview = $initial_interview;
            $existing->last_context = $last_context;
            $existing->timemodified = $now;
            $DB->update_record('local_libiac_user_ctx', $existing);
            return;
        }

        $DB->insert_record('local_libiac_user_ctx', (object) [
            'userid' => $userid,
            'initial_interview' => $initial_interview,
            'last_context' => $last_context,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
