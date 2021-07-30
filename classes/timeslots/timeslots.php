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
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\timeslots;

defined('MOODLE_INTERNAL') || die;

/**
 * Functions to view observation time slots
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslots {

    /**
     * Creates a table that displays all the observation time slots for a given observation
     * @param int $observationid ID of the observation instance to get the observation time slots from.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     */
    public static function timeslots_table(int $observationid, \moodle_url $callbackurl) {
        $table = new \mod_observation\timeslots\timeslots_table('slotviewtable', $callbackurl);
        $sql = (object) [
            'fields' => 'op.*, ortm.lang_string',
            'from' => '{observation_timeslots} op LEFT JOIN {observation_res_type_map} ortm ON ortm.res_type = ortm.res_type',
            'where' => 'obs_id = :obsid',
            'params' => ['obsid' => $observationid]
        ];
        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }
}
