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
 * Renderable to render the list of observation time slots
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\timeslots;

/**
 * Functions to view observation time slots
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslots_display {

    /**
     * Display mode with editing buttons
     * @param const int
     */
    const DISPLAY_MODE_EDITING = 0;

    /**
     * Display mode with signup buttons
     * @param const int
     */
    const DISPLAY_MODE_SIGNUP = 1;

    /**
     * Display mode with start session buttons
     * @param const int
     */
    const DISPLAY_MODE_UPCOMING = 2;

    /**
     * Display mode with action buttons for teachers.
     * @param const int
     */
    const DISPLAY_MODE_OBSERVER_ASSIGNED = 3;

    /**
     * Display mode with action buttons for students.
     * @param const int
     */
    const DISPLAY_MODE_OBSERVEE_REGISTERED = 4;

    /**
     * Common SQL for timeslot tables
     * @param const int
     */
    private const COMMON_SQL = [
        'fields' => "ot.*,
                    CONCAT(u.firstname, ' ', u.lastname) as observer_fullname,
                    u.email as observer_email,
                    CONCAT(o.firstname, ' ', o.lastname) as observee_fullname,
                    o.email as observee_email",
        'from' => '{observation_timeslots} ot
                    LEFT JOIN {user} u ON ot.observer_id = u.id
                    LEFT JOIN {user} o on ot.observee_id = o.id',
        'where' => 'obs_id = :obsid'
    ];

    /**
     * Creates a table that displays all the observation time slots for a given observation
     * @param int $observationid ID of the observation instance to get the observation time slots from.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     * @param int $displaymode display mode for table
     */
    public static function timeslots_table(int $observationid, \moodle_url $callbackurl, int $displaymode) {
        $table = new \mod_observation\table\timeslots\timeslots_table('slotviewtable', $callbackurl, $displaymode);

        $sql = (object) self::COMMON_SQL;

        $sql->params['obsid'] = $observationid;

        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }

    /**
     * Creates a table that displays all the observation timeslots assigned to a person as an observer, with optional time filter.
     * @param int $observationid ID of the observation instance to get the observation time slots from.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     * @param int $displaymode display mode for table
     * @param int $userid ID of the user who is an observer for a given timeslot
     * @param int $timefilter filter for the start time column, if 0 no filter is applied. Positive integers only.
     */
    public static function assigned_timeslots_table(int $observationid, \moodle_url $callbackurl, int $displaymode, int $userid,
        int $timefilter = 0) {

        $table = new \mod_observation\table\timeslots\timeslots_table('slotviewtable', $callbackurl, $displaymode);

        // Optional time filtering SQL query.
        if ($timefilter < 0) {
            throw new \coding_exception("Time filter cannot be negative.");
        }

        $sql = (object) self::COMMON_SQL;
        $sql->params['obsid'] = $observationid;

        // Add observer/observee ID filter.
        $sql->where .= ' AND :userid IN (observee_id, observer_id)';
        $sql->params['userid'] = $userid;

        if ($timefilter !== 0) {
            // Add timefilter filter.

            $sql->where .= ' AND start_time < :timefilter';
            $sql->params['timefilter'] = time() + $timefilter;
        }

        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }
}
