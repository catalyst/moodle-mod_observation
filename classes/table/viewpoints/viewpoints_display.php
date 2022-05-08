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
 * Renderable to render the list of observation points
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\viewpoints;

/**
 * Functions to view observation points
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewpoints_display {

    /**
     * Creates a table that displays all the observation points for a given observation
     * @param int $observationid ID of the observation instance to get the observation points from.
     * @param \moodle_url $callbackurl URL for action buttons in table to callback to
     * @param int $displaymode display mode for table
     */
    public static function ob_point_table(int $observationid, \moodle_url $callbackurl, int $displaymode = 0) {
        $table = new \mod_observation\table\viewpoints\viewpoints_table('obpointviewtable', $callbackurl, $displaymode);
        // Left join the res type map table to get the corresponding lang string for the response type.
        $sql = (object) [
            'fields' => 'op.*, ortm.lang_string',
            'from' => '{observation_points} op LEFT JOIN {observation_res_type_map} ortm ON op.res_type = ortm.res_type',
            'where' => 'obs_id = :obsid',
            'params' => ['obsid' => $observationid]
        ];
        $table->sql = $sql;
        return $table->out($table->pagesize, true);
    }
}
