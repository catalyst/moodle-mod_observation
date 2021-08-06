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
class timeslot_manager {
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
     * Modifies or creates a new time slot in the database
     * @param mixed $data Data to pass to database function
     * @param bool $newinstance True if new instance, else false if editing
     * @param bool $returnid True if should return id (only when $newinstance = true)
     * @param string $tablename Tablename
     * @return mixed True if successful. If $returnid is True and $newinstance is True, returns ID
     */
    public static function modify_time_slot($data, bool $newinstance = false, bool $returnid = false,
            string $tablename = 'observation_timeslots') {
        global $DB;

        $data = (object)$data;

        // TODO - add more checks.

        if (property_exists($data, 'duration')) {
            // Ensure duration is an int.
            if (!is_int($data->duration)) {
                throw new \coding_exception("Property duration must be an int.");
            }

            // Ensure maxgrade (if set) is not negative.
            if ($data->duration < 1) {
                throw new \coding_exception("Property duration cannot be below one");
            }
        }

        if ($newinstance) {
            // Insert.
            return $DB->insert_record($tablename, $data, $returnid);
        } else {
            return $DB->update_record($tablename, $data);
        }
    }

    /**
     * Gets observation point data
     * @param int $observationid ID of observation instance
     * @param int $slotid ID of the observation point
     * @param string $tablename database table name
     * @return object existing point data
     */
    public static function get_existing_slot_data(int $observationid, int $slotid,
            string $tablename = 'observation_timeslots'): object {
        global $DB;
        return $DB->get_record($tablename, ['id' => $slotid, 'obs_id' => $observationid], '*', MUST_EXIST);
    }

    /**
     * Get all observation points in an observation instance
     * @param int $observationid ID of the observation instance
     * @param string $sortby column to sort by
     * @param string $tablename database tablename
     * @return array array of database objects obtained from database
     */
    public static function get_time_slots(int $observationid, string $sortby='list_order',
            string $tablename='observation_timeslots'): array {
        global $DB;
        return $DB->get_records($tablename, ['obs_id' => $observationid], $sortby);
    }

    /**
     * Deletes observation point
     * @param int $observationid ID of the observation instance
     * @param int $slotid ID of the observation point to delete
     * @param string $tablename database table name
     */
    public static function delete_time_slot(int $observationid, int $slotid, string $tablename='observation_timeslots') {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records($tablename, ['id' => $slotid, 'obs_id' => $observationid]);

        $transaction->allow_commit();
    }
}
