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
 * This file contains functions to get various observation data objects
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

defined('MOODLE_INTERNAL') || die();

/**
 * mod_observation observation management class
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_manager {
    
    /**
     * Modifies or creates a new observation point in the database
     */
    public static function modify_observation_point($data, bool $newinstance=false, string $tablename = 'observation_points'): bool {
        global $DB;

        if($newinstance){
            // Get current observation points to work out ordering value.
            $params = array(
                'obsid' => $data['obs_id'],
            );
            $current_points = $DB->get_records_select($tablename, 'obs_id = :obsid', $params, '', 'id, list_order');
            
            // Decompose to only list of the orderings and get max
            $current_orderings = array_column($current_points, 'list_order');
            $max_ordering = max($current_orderings);
            
            // Update $data ordering property to be the max_ordering + 1 (place at end of list)
            $data['list_order'] = $max_ordering + 1;
            
            // Insert.
            return $DB->insert_record($tablename, $data, false);
        } else {
            return $DB->update_record($tablename, $data);
        }
    }

    public static function get_existing_point_data(int $observationid, int $pointid, string $tablename = 'observation_points') {
        global $DB;
        return $DB->get_record($tablename, ['id' => $pointid, 'obs_id' => $observationid], '*', MUST_EXIST);
    }
}