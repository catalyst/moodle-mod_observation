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
 * Renderable to render the list of observation time slot notifications
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\notifications;

/**
 * Functions to view observation time slot notifications
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications_display {

    /**
     * Creates a table that displays all the observation notifications for a users timeslot registration
     * @param int $observationid ID of the observation instance to get the observation time slots and notifications from.
     * @param int $userid ID of the user to find notifications for
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     */
    public static function notifications_table(int $observationid, int $userid, \moodle_url $callbackurl) {
        $table = new \mod_observation\table\notifications\notifications_table('notificationstable', $callbackurl);

        $table->sql = (object) [
            'fields' => 'obn.id AS id, time_before, start_time',
            'from' => '{observation_notifications} obn LEFT JOIN {observation_timeslots} ot ON obn.timeslot_id = ot.id',
            'where' => 'ot.observee_id = :userid AND ot.obs_id = :obsid',
            'params' => ['userid' => $userid, 'obsid' => $observationid]
        ];

        return $table->out($table->pagesize, true);
    }
}
