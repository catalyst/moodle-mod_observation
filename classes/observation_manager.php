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

use coding_exception;

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
     * @param mixed $data Data to pass to database function
     * @param bool $newinstance True if new instance, else false if editing
     * @param bool $returnid True if should return id (only when $newinstance = true)
     * @param string $tablename Tablename
     * @return mixed True if successful. If $returnid is True and $newinstance is True, returns ID
     */
    public static function modify_observation_point($data, bool $newinstance = false, bool $returnid = false,
        string $tablename = 'observation_points') {

        $data = (object)$data;

        global $DB;

        // Ensure maxgrade (if set) is not negative.
        if (property_exists($data, 'max_grade') && $data->max_grade < 0) {
            throw new coding_exception("Property max_grade cannot be negative");
        }

        if (property_exists($data, 'res_type')) {
            // Get record, MUST_EXIST is passed so will except if res_type is invalid.
            $DB->get_record('observation_res_type_map', ['res_type' => $data->res_type], '*', MUST_EXIST);
        }

        if ($newinstance) {
            // Get the max observation point list_order to place this new one after it.
            $ordering = $DB->get_record($tablename, ['obs_id' => $data->obs_id], 'MAX(list_order)');

            $maxordering = 0;
            if ($ordering !== null && isset($ordering->max)) {
                $maxordering = $ordering->max;
            }

            // Update $data ordering property to be the max_ordering + 1 (place at end of list).
            $data->list_order = $maxordering + 1;

            // Insert.
            return $DB->insert_record($tablename, $data, $returnid);
        } else {
            return $DB->update_record($tablename, $data);
        }
    }

    /**
     * Gets observation point data
     * @param int $observationid ID of observation instance
     * @param int $pointid ID of the observation point
     * @param string $tablename database table name
     * @return object existing point data
     */
    public static function get_existing_point_data(int $observationid, int $pointid,
        string $tablename = 'observation_points'): object {

        global $DB;
        return $DB->get_record($tablename, ['id' => $pointid, 'obs_id' => $observationid], '*', MUST_EXIST);
    }

    /**
     * Get all observation points in an observation instance
     * @param int $observationid ID of the observation instance
     * @param string $sortby column to sort by
     * @param string $tablename database tablename
     * @return array array of database objects obtained from database
     */
    public static function get_observation_points(int $observationid, string $sortby='list_order',
        string $tablename='observation_points'): array {

        global $DB;
        return $DB->get_records($tablename, ['obs_id' => $observationid], $sortby);
    }

    /**
     * Deletes observation point
     * @param int $observationid ID of the observation instance
     * @param int $obpointid ID of the observation point to delete
     * @param string $tablename database table name
     */
    public static function delete_observation_point(int $observationid, int $obpointid, string $tablename='observation_points') {
        global $DB;
        // To ensure ordering stays intact, should move all those points with a higher order than this one down by 1.
        $currentpoint = self::get_existing_point_data($observationid, $obpointid, $tablename);

        // Get those with a higher list ordering than this one.
        $pointsabove = $DB->get_records_select(
            $tablename,
            "obs_id = :obsid AND list_order > :listorder",
            [
                'obsid' => $observationid,
                'listorder' => $currentpoint->list_order
            ]
        );

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records($tablename, ['id' => $obpointid, 'obs_id' => $observationid]);

        // Shuffle those above down.
        foreach ($pointsabove as $pointabove) {
            $DB->update_record(
                $tablename,
                [
                    'id' => $pointabove->id,
                    'list_order' => $pointabove->list_order - 1
                ]
            );
        }

        $transaction->allow_commit();
    }

    /**
     * Reorders an observation point in relation to the other observation points in this observation instance
     * @param int $observationid ID of the observation instance
     * @param int $obpointid ID of the observation point to reorder
     * @param int $direction direction to reorder the point in, must be -1 or 1.
     * @param string $tablename database table name
     */
    public static function reorder_observation_point(int $observationid, int $obpointid, int $direction,
        string $tablename='observation_points') {

        if ($direction != 1 && $direction != -1) {
            throw new coding_exception("direction must be -1 or 1.");
        }

        global $DB;
        // First get the ordering of the current point.
        $currentpoint = self::get_existing_point_data($observationid, $obpointid, $tablename);

        // Also get the min and max orderings (the bounds).
        $orderbounds = $DB->get_record(
            $tablename,
            ['obs_id' => $observationid],
            'min(list_order), max(list_order)',
            MUST_EXIST
        );
        $newordering = $currentpoint->list_order + $direction;
        // Is currently the minimum - do nothing.
        if ($newordering < $orderbounds->min) {
            return;
        }

        // Is currently the maximum - do nothing.
        if ($newordering > $orderbounds->max) {
            return;
        }

        // Else ordering is valid, get the ID of the point that currently has this ordering.
        $pointtoswap = $DB->get_record(
            $tablename,
            ['obs_id' => $observationid, 'list_order' => $newordering],
            'id',
            MUST_EXIST
        );

        // Swap the orderings in a DB transaction.
        $transaction = $DB->start_delegated_transaction();
        $DB->update_record($tablename,
            [
                'id' => $currentpoint->id,
                'list_order' => $newordering
            ]);
        $DB->update_record($tablename,
            [
                'id' => $pointtoswap->id,
                'list_order' => $currentpoint->list_order
            ]);
        $transaction->allow_commit();
    }
}
