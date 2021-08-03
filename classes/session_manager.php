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
 * This file contains functions to manage observation sessions
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

defined('MOODLE_INTERNAL') || die();

/**
 * mod_observation observation session management class
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_manager {
    /**
     * Updates DB to begin observation session
     * @param int $obsid observation instance ID
     * @param int $observerid user ID of the observer
     * @param int $observeeid user ID of the observee
     * @param string $tablename DB table
     * @return int ID of the session that was just started.
     */
    public static function start_session(int $obsid, int $observerid, int $observeeid, string $tablename='observation_sessions') {
        global $DB;

        $data = [
            'obs_id' => $obsid,
            'observee_id' => $observeeid,
            'observer_id' => $observerid,
            'state' => 'inprogress',
            'start_time' => time()
        ];

        return $DB->insert_record($tablename, $data, true);
    }

    /**
     * Returns information about a session (such as observation id)
     * @param int $sessionid observation session ID
     * @return array array of information about session. Does NOT return any observation point data - see get_session_data().
     */
    public static function get_session_info(int $sessionid) {
        global $DB;

        $sessiondata = $DB->get_record('observation_sessions', ['id' => $sessionid], '*', MUST_EXIST);

        return [
            'obid' => $sessiondata->obs_id,
            'ex_comment' => $sessiondata->ex_comment
        ];
    }

    /**
     * Returns the observation point data for a session, including any existing responses to the points
     * @param int $sessionid observation session id
     * @return array array of observation points
     */
    public static function get_session_data(int $sessionid) {
        // Get the details for the session.
        $sessioninfo = self::get_session_info($sessionid);
        $obid = $sessioninfo['obid'];

        // Get all points.
        $observationpoints = \mod_observation\observation_manager::get_points_and_responses($obid, $sessionid);
        return $observationpoints;
    }

    /**
     * Returns a list of incomplete points. I.e. those without a response.
     * @param int $sessionid observation sesssion ID
     * @return array array containing points with no responses.
     */
    public static function get_incomplete_points(int $sessionid) {
        $sessiondata = self::get_session_data($sessionid);
        $incompletepoints = array_filter($sessiondata, function($point) {
            return $point->response_id === null;
        });
        return $incompletepoints;
    }

    /**
     * Calculates the total grade given for an observation session.
     * @param int $sessionid observation session id
     * @return array array containing two values: total and max. Total is the total grade given, Max is the total grade available.
     */
    public static function calculate_grade(int $sessionid) {
        $sessioninfo = self::get_session_info($sessionid);
        $obid = $sessioninfo['obid'];
        $observationpoints = \mod_observation\observation_manager::get_points_and_responses($obid, $sessionid);

        $totalmaxgrade = array_reduce($observationpoints, function($carry, $item) {
            return $carry + $item->max_grade;
        }, 0);

        $totalgradegiven = array_reduce($observationpoints, function($carry, $item) {
            return $carry + $item->grade_given;
        }, 0);

        return [
            'total' => $totalgradegiven,
            'max' => $totalmaxgrade
        ];
    }

    /**
     * Saves extra comment for an observation sesssion
     * @param int $sessionid observation session id
     * @param string $extracomment extra comment
     */
    public static function save_extra_comment(int $sessionid, string $extracomment) {
        global $DB;
        $DB->update_record('observation_sessions', ['id' => $sessionid, 'ex_comment' => $extracomment]);
        return;
    }

    /**
     * Checks and finalises a session.
     * @param int $sessionid observation session id
     * @return mixed Returns true if successful, else an error message containing the points which are not complete.
     */
    public static function finish_session(int $sessionid) {
        global $DB;

        $incompletepoints = self::get_incomplete_points($sessionid);

        if (empty($incompletepoints)) {
            // Update status in DB.
            $DB->update_record('observation_sessions', [
                'id' => $sessionid,
                'state' => 'complete',
                'finish_time' => time(),
            ]);

            return true;
        }

        // Return error message with list of names of the incomplete points.
        $incompletenames = array_column($incompletepoints, 'title');
        $nameslist = json_encode($incompletenames);

        $error = get_string('sessionincomplete', 'observation', count($incompletepoints));
        $error = $error."\n".$nameslist;

        return $error;
    }

    /**
     * Cancels an observation session
     * @param int $sessionid observation session id
     */
    public static function cancel_session(int $sessionid) {
        global $DB;

        $DB->update_record('observation_sessions', ['id' => $sessionid, 'state' => 'cancelled', 'finish_time' => time()]);

        return;
    }
}