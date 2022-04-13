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
 * Renderable to render the list of observation sessions
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\viewsessions;

/**
 * Functions to view the list of observation sessions
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewsessions_display {

    /**
     * Creates a table that displays all the observation sessions for a given observation
     * @param int $observationid ID of the observation instance to get the observation sesssions from.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     */
    public static function ob_sess_table(int $observationid, \moodle_url $callbackurl) {
        $table = new \mod_observation\table\viewsessions\viewsessions_table('obsessionviewtable', $callbackurl);

        // Select the sessions, and left join the observer and observee's usernames.
        $sql = (object) [
            'fields' => '*',
            'from' => '{observation_sessions} os
             LEFT JOIN (
                    SELECT id as observer_id, username as observer_username
                    FROM {user}
                 ) as observers
             ON os.observer_id = observers.observer_id
             LEFT JOIN (
                    SELECT id as observee_id, username as observee_username
                    FROM {user}
                  ) as observees
            ON os.observee_id = observees.observee_id',
            'where' => 'obs_id = :obsid',
            'params' => ['obsid' => $observationid]
        ];
        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }
}
