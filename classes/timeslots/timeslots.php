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
 * @copyright  2021 Endurer Solutions Team
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\timeslots;

defined('MOODLE_INTERNAL') || die;

/**
 * Functions to view observation time slots
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslots {

    /**
     * Display mode with editing buttons
     * @param const editing parameter for action button
     */
    const DISPLAY_MODE_EDITING = 0;

    /**
     * Display mode with signup buttons
     * @param const signup parameter for action button
     */
    const DISPLAY_MODE_SIGNUP = 1;

    /**
     * Display mode with start session buttons
     * @param const upcoming display mode
     */
    const DISPLAY_MODE_UPCOMING = 2;


    /**
     * Creates a table that displays all the observation time slots for a given observation
     * @param int $observationid ID of the observation instance to get the observation time slots from.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     * @param int $displaymode display mode for table
     * @param int $timefilter filter for the start time column, if 0 no filter is applied. Positive integers only.
     */
    public static function timeslots_table(int $observationid, \moodle_url $callbackurl, int $displaymode, int $timefilter = 0) {
        $table = new \mod_observation\timeslots\timeslots_table('slotviewtable', $callbackurl, $displaymode, $timefilter);

        // Optional time filtering SQL query.
        if ($timefilter < 0) {
            throw new \coding_exception("Time filter cannot be negative.");
        }

        $sql = (object) [
            'fields' => "ot.*,
                        CONCAT(u.firstname, ' ', u.lastname) as observer_fullname,
                        u.email as observer_email,
                        CONCAT(o.firstname, ' ', o.lastname) as observee_fullname",
            'from' => '{observation_timeslots} ot
                        LEFT JOIN {user} u ON ot.observer_id = u.id
                        LEFT JOIN {user} o on ot.observee_id = o.id',
            'where' => 'obs_id = :obsid',
            'params' => ['obsid' => $observationid]
        ];

        if ($timefilter !== 0) {
            $sql->where = 'obs_id = :obsid AND start_time < :timefilter';
            $sql->params = ['obsid' => $observationid, 'timefilter' => time() + $timefilter];
        }

        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }

    /**
     * Creates a table that displays all the observation time slots for a given observation for the logged in user
     * @param int $observationid ID of the observation instance to get the observation time slots from.
     * @param int $observerid ID of the user to filter the timeslots displayed by.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     */
    public static function assigned_timeslots_table(int $observationid, int $userid, \moodle_url $callbackurl) {
        $table = new \mod_observation\timeslots\timeslots_table('slotviewtable', $callbackurl);
        $sql = (object) [
            'fields' => "op.*, CONCAT(u.firstname, ' ', u.lastname) as observer_fullname, u.email as observer_email",
            'from' => '{observation_timeslots} op LEFT JOIN {user} u ON op.observer_id = u.id',
            'where' => 'obs_id = :obsid AND observer_id = :userid', // Add OR observee_id = :userid here when students can select timeslots.
            'params' => ['obsid' => $observationid, 'userid' => $userid]
        ];
        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }
}
