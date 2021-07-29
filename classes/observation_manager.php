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
     * Gets observation, course and coursemodule from course module ID
     * @param int $cmid Course module ID
     * @param string $tablename database table name
     * @return list List containing the observation instance, course and coursemodule (in that order)
     */
    public static function get_observation_course_cm_from_cmid(int $cmid, string $tablename = 'observation') {
        global $DB;
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, $tablename);
        $observationid = $cm->instance;
        if (!$observation = $DB->get_record($tablename, ['id' => $observationid])) {
            throw new \moodle_exception('moduleinstancedoesnotexist');
        }
        return [$observation, $course, $cm];
    }

    /**
     * Gets observation, course and coursemodule from observation instance ID
     * @param int $obid Observation instance ID
     * @param string $tablename Database table name
     * @return list List containing the observation instance, course and coursemodule (in that order)
     */
    public static function get_observation_course_cm_from_obid(int $obid, string $tablename = 'observation') {
        global $DB;
        if (!$cm = get_coursemodule_from_instance($tablename, $obid)) {
            throw new \moodle_exception('invalidcoursemodule');
        }
        list($course, $cm) = get_course_and_cm_from_cmid($cm->id, 'observation');
        if (!$observation = $DB->get_record($tablename, ['id' => $obid])) {
            throw new \moodle_exception('moduleinstancedoesnotexist');
        }
        return [$observation, $course, $cm];
    }

    /**
     * Modifies an instance of an observation by either creating a new one or updating existing one.
     * Note that when updating an instance, an ID must be passed in the $data param array.
     * @param mixed $data Data to be passed to the update or create DB function
     * @param bool $newinstance If true creates new instance, else updates instance.
     * @param string $tablename Name of database table to operate on.
     * @return int If param $newinstance is true, returns ID of new instance. Else returns 1 if updated successfully, else 0.
     */
    public static function modify_instance($data, bool $newinstance = false, string $tablename = 'observation'): int {
        global $DB;

        // Editor data need to be checked to ensure empty strings are not added.
        if ($data['observer_ins'] === "") {
            $data['observer_ins'] = null;
            $data['observer_ins_f'] = null;
        }

        if ($data['observee_ins'] === "") {
            $data['observee_ins'] = null;
            $data['observee_ins_f'] = null;
        }

        if ($newinstance) {
            return $DB->insert_record($tablename, $data);
        } else {
            return (int)$DB->update_record($tablename, $data);
        }
    }

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
        global $DB;

        $data = (object)$data;

        if (property_exists($data, 'max_grade')) {
            // Ensure maxgrade (if set) is an int.
            if (!is_int($data->max_grade)) {
                throw new \coding_exception("Property max_grade must be an int.");
            }

            // Ensure maxgrade (if set) is not negative.
            if ($data->max_grade < 0) {
                throw new \coding_exception("Property max_grade cannot be negative");
            }
        }

        if (property_exists($data, 'res_type')) {
            // Get record, MUST_EXIST is passed so will except if res_type is invalid.
            $DB->get_record('observation_res_type_map', ['res_type' => $data->res_type], '*', MUST_EXIST);
        }

        if ($newinstance) {
            // Get the max observation point list_order to place this new one after it.
            // Get all the points to get the bounds.
            $allpoints = self::get_observation_points($data->obs_id);
            $alllistorders = array_column($allpoints, 'list_order');

            // Default to zero, update to max if a point already exists.
            $maxordering = 0;
            if (count($alllistorders) != 0) {
                $maxordering = max($alllistorders);
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
    
    public static function get_points_responses(int $observationid, int $sessionid, string $sortby='list_order')
    {
        global $DB;

        $sql = '
        SELECT *, pts.id as point_id FROM mdl_observation_points as pts 
        LEFT JOIN 
        (SELECT *, id as resp_id FROM mdl_observation_point_responses) as resp 
        ON pts.id = resp.obs_pt_id 
        WHERE pts.obs_id = :observationid AND (resp.obs_ses_id IS NULL OR resp.obs_ses_id = :sessionid);
        ';

        return $DB->get_records_sql($sql, ['observationid' => $observationid, 'sessionid' => $sessionid]);
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
     * @param int $direction direction and magnitude to reorder the point in
     * @param string $tablename database table name
     */
    public static function reorder_observation_point(int $observationid, int $obpointid, int $direction,
            string $tablename='observation_points') {
        global $DB;

        if (!is_int($direction) || $direction == 0) {
            throw new \coding_exception("direction must be an integer that is not zero");
        }

        // First get the ordering of the current point.
        $targetpoint = self::get_existing_point_data($observationid, $obpointid, $tablename);

        // Get all the points to get the bounds.
        $allpoints = self::get_observation_points($observationid);
        $alllistorders = array_column($allpoints, 'list_order');

        if (count($alllistorders) === 0) {
            throw new \coding_exception('No list orderings found for this observation instance, but expected at least one.');
        }

        $newordering = $targetpoint->list_order + $direction;

        // Is currently the minimum - do nothing.
        if ($newordering < min($alllistorders)) {
            return;
        }

        // Is currently the maximum - do nothing.
        if ($newordering > max($alllistorders)) {
            return;
        }

        // Ordering is valid, so get the points that are affected by this reordering.
        $affectedpoints = array_filter($allpoints, function($elem) use($newordering, $direction, $targetpoint) {
            // Don't include the item being reordered.
            if ($elem->id === $targetpoint->id) {
                return false;
            }

            // If shifting down, filter those 'above'.
            if ($direction < 0) {
                return $elem->list_order >= $newordering && $elem->list_order < $targetpoint->list_order;
            }
            // If shifting up, filter those 'below'.
            if ($direction > 0) {
                return $elem->list_order <= $newordering && $elem->list_order > $targetpoint->list_order;
            }

            return false;
        });

        $transaction = $DB->start_delegated_transaction();

        // Give the target point the new ordering.
        $DB->update_record($tablename,
        [
            'id' => $targetpoint->id,
            'list_order' => $newordering
        ]);

        // Reduce the direction to a unit vector (e.g. 5 -> 1 and -5 -> -1).
        $reductionamount = intdiv($direction, abs($direction));

        array_walk($affectedpoints, function($elem) use($DB, $tablename, $reductionamount) {
            $DB->update_record($tablename,
            [
                'id' => $elem->id,
                'list_order' => $elem->list_order - $reductionamount
            ]);
        });

        $transaction->allow_commit();
    }

    public static function submit_point_response($response, $tablename = "observation_point_responses") {
        global $DB;
        // TODO verify and check data

        if($response['ex_comment'] == ''){
            $response['ex_comment'] = NULL;
        }

        // TODO check if exists already, and update if does
        $DB->insert_record($tablename, $response);
        return;
    }
}
