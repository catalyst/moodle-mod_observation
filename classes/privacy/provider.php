<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provder for mod_observation
 *
 * @package   mod_observation
 * @copyright  Catalyst IT
 * @author Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_observation
 * @copyright  Catalyst IT
 * @author Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_subsystem_link(
            'core_files',
            [],
            'privacy:metadata:core_files'
        );

        $collection->add_subsystem_link(
            'core_calendar',
            [],
            'privacy:metadata:core_calendar'
        );

        $collection->add_subsystem_link(
            'core_message',
            [],
            'privacy:metadata:core_message'
        );

        $collection->add_database_table(
            'observation_point_responses',
             [
                'grade_given' => 'privacy:metadata:observation_point_responses:grade_given',
                'response' => 'privacy:metadata:observation_point_responses:response',
                'ex_comment' => 'privacy:metadata:observation_point_responses:ex_comment',
                'timecreated' => 'privacy:metadata:observation_point_responses:timecreated',
                'timemodified' => 'privacy:metadata:observation_point_responses:timemodified'
             ],
            'privacy:metadata:observation_point_responses'
        );

        $collection->add_database_table(
            'observation_timeslots',
             [
                'observer_id' => 'privacy:metadata:observation_timeslots:observer_id',
                'observee_id' => 'privacy:metadata:observation_timeslots:observee_id',
                'start_time' => 'privacy:metadata:observation_timeslots:start_time',
                'duration' => 'privacy:metadata:observation_timeslots:duration',
             ],
            'privacy:metadata:observation_timeslots'
        );

        $collection->add_database_table(
            'observation_sessions',
             [
                'observer_id' => 'privacy:metadata:observation_sessions:observer_id',
                'observee_id' => 'privacy:metadata:observation_sessions:observee_id',
                'state' => 'privacy:metadata:observation_sessions:state',
                'start_time' => 'privacy:metadata:observation_sessions:start_time',
                'finish_time' => 'privacy:metadata:observation_sessions:finish_time',
                'ex_comment' => 'privacy:metadata:observation_sessions:ex_comment',
             ],
            'privacy:metadata:observation_sessions'
        );

        return $collection;
    }

     /**
      * Get the list of contexts that contain user information for the specified user.
      *
      * @param int $userid The user to search.
      * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
      */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $params = [
            'modulename' => 'observation',
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
        ];

        // Observation sessions.
        $sql = "SELECT c.id
            FROM {context} c
            JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
            JOIN {observation} o ON o.id = cm.instance
            JOIN {observation_sessions} os ON os.obs_id = o.id
            WHERE (os.observee_id = :userid1 OR os.observer_id = :userid2)";

        $contextlist->add_from_sql($sql, $params);

        // Timeslot registrations.
        $sql = "SELECT c.id
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {observation} o ON o.id = cm.instance
                JOIN {observation_timeslots} ot ON ot.obs_id = o.id
                WHERE (ot.observee_id = :userid1 OR ot.observer_id = :userid2)";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('observation', $context->instanceid);
        if (!$cm) {
            return;
        }

        $id = $cm->instance;

        $userlist->add_from_sql('observer_id', 'SELECT observer_id FROM {observation_timeslots} WHERE obs_id = ?', [$id]);
        $userlist->add_from_sql('observee_id', 'SELECT observee_id FROM {observation_timeslots} WHERE obs_id = ?', [$id]);

        $userlist->add_from_sql('observer_id', 'SELECT observer_id FROM {observation_sessions} WHERE obs_id = ?', [$id]);
        $userlist->add_from_sql('observee_id', 'SELECT observee_id FROM {observation_sessions} WHERE obs_id = ?', [$id]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            $data = (object) [];

            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $observation = $DB->get_record('observation', ['id' => $cm->instance]);

            // Get sessions for this course module.
            $sessions = $DB->get_records('observation_sessions', ['obs_id' => $observation->id]);

            $data->sessions = $sessions;

            // Get all point responses and the sessions themselves if the user is a part of them.
            foreach ($sessions as $session) {
                if ($session->observer_id == $userid || $session->observee_id == $userid) {
                    // Delete all responses.
                    $responses = $DB->get_records('observation_point_responses', ['obs_ses_id' => $session->id]);
                    $data->sessions[$session->id]->responses = $responses;
                }
            }

            // Get all timeslot records where the user is a part of them.
            $timeslots = $DB->get_records_select('observation_timeslots', 'observer_id = :userid1 OR observee_id = :userid2', [
                'userid1' => $userid,
                'userid2' => $userid
            ]);

            $data->timeslots = $timeslots;

            writer::with_context($context)->export_data([], $data);
        }
    }

     /**
      * Delete all user data for the specified user, in the specified contexts.
      *
      * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
      */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $observation = $DB->get_record('observation', ['id' => $cm->instance]);

            // Get sessions for this course module.
            $sessions = $DB->get_records('observation_sessions', ['obs_id' => $observation->id]);

            // Delete all point responses and the sessions themselves if the user is a part of them.
            foreach ($sessions as $session) {
                if ($session->observer_id == $userid || $session->observee_id == $userid) {
                    // Delete all responses.
                    $DB->delete_records('observation_point_responses', ['obs_ses_id' => $session->id]);

                    // Delete the session itself.
                    $DB->delete_records('observation_sessions', ['id' => $session->id]);
                }
            }

            // Delete all timeslot records where the user is a part of them.
            $DB->delete_records_select('observation_timeslots', 'observer_id = :userid1 OR observee_id = :userid2', [
                'userid1' => $userid,
                'userid2' => $userid
            ]);
        }
    }

     /**
      * Delete multiple users within a single context.
      *
      * @param approved_userlist $userlist The approved context and user information to delete information for.
      */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        $cm = get_coursemodule_from_id('observation', $context->instanceid);
        if (!$cm) {
            return;
        }

        $id = $cm->instance;

        // Get the sessions for this course module.
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $id]);

        // Go through each user and delete their data.
        $userids = $userlist->get_userids();

        foreach ($sessions as $session) {
            // If the observer or observee in this session is one of the users we want to delete the data for.
            if (in_array($session->observer_id, $userids) || in_array($session->observee_id, $userids)) {
                // Delete all responses.
                $DB->delete_records('observation_point_responses', ['obs_ses_id' => $session->id]);

                // Delete the session itself.
                $DB->delete_records('observation_sessions', ['id' => $session->id]);
            }
        }

        foreach ($userids as $userid) {
            // Delete all timeslot records where the user is a part of them.
            $DB->delete_records_select('observation_timeslots', 'observer_id = :userid1 OR observee_id = :userid2', [
                'userid1' => $userid,
                'userid2' => $userid
            ]);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context (\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('observation', $context->instanceid);
        if (!$cm) {
            return;
        }

        $id = $cm->instance;

        // Get all sessions for this module.
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $id]);

        // Delete all responses to points in each of these sessions.
        foreach ($sessions as $session) {
            $DB->delete_records('observation_point_responses', ['obs_ses_id' => $session->id]);
        }

        // Delete the sessions themselves (because of column ex_comment).
        $DB->delete_records('observation_sessions', ['obs_id' => $id]);

        // Delete any timeslot records for this context.
        $DB->delete_records('observation_timeslots', ['obs_id' => $id]);
    }
}
