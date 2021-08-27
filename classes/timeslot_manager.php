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
 * This file contains functions to manage timeslots for the observation assessment activity.
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * mod_observation observation timeslot management class
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslot_manager {

    /**
     * Modifies or creates a new time slot in the database
     * @param mixed $data Data to pass to database function
     * @param bool $newinstance True if new instance, else false if editing
     * @param string $tablename Tablename
     * @return mixed True if updating and successful or ID if inserting and successful.
     */
    public static function modify_time_slot($data, bool $newinstance = false,
            string $tablename = 'observation_timeslots') {
        global $DB;

        $data = (object)$data;

        if (property_exists($data, 'duration')) {
            if (!is_int($data->duration)) {
                throw new \coding_exception("Property duration must be an int.");
            }

            if ($data->duration < 1) {
                throw new \coding_exception("Property duration cannot be below one");
            }
        }

        if (property_exists($data, 'start_time')) {
            if (!is_int($data->start_time) || $data->start_time < 0) {
                throw new \coding_exception("Start time must be an integer in Unix Epoch Format.");
            }
        }

        if ($newinstance) {
            $slotid = $DB->insert_record($tablename, $data, true);
            self::update_timeslot_calendar_events($data->obs_id, $slotid);
            return $slotid;
        } else {

            $dbreturn = $DB->update_record($tablename, $data);
            self::update_timeslot_calendar_events($data->obs_id, $data->id);
            return $dbreturn;
        }
    }

    /**
     * Gets observation timeslot data
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
     * Get all observation timeslots in an observation instance
     * @param int $observationid ID of the observation instance
     * @param string $sortby column to sort by
     * @param string $tablename database tablename
     * @return array array of database objects obtained from database
     */
    public static function get_time_slots(int $observationid, string $sortby='',
            string $tablename='observation_timeslots'): array {
        global $DB;
        return $DB->get_records($tablename, ['obs_id' => $observationid], $sortby);
    }

    /**
     * Deletes timeslot
     * @param int $observationid ID of the observation instance
     * @param int $slotid ID of the observation point to delete
     * @param string $tablename database table name
     */
    public static function delete_time_slot(int $observationid, int $slotid, string $tablename='observation_timeslots') {
        global $DB;

        // Get record to get the calendar event ID.
        $timeslot = self::get_existing_slot_data($observationid, $slotid);

        if (isset($timeslot->observer_event_id)) {
            $event = \calendar_event::load($timeslot->observer_event_id);
            $event->delete();
        }

        $DB->delete_records($tablename, ['id' => $slotid, 'obs_id' => $observationid]);
    }

    /**
     * Updates the calendar events for a particular timeslot.
     * @param int $observationid ID of the observation instance
     * @param int $slotid ID of the timeslot to update events for
     */
    public static function update_timeslot_calendar_events(int $observationid, int $slotid) {
        global $DB;

        $timeslot = self::get_existing_slot_data($observationid, $slotid);
        list($observation, $c, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($observationid);

        // If observer is assigned to timeslot, update observer calendar entry for observer.
        if (isset($timeslot->observer_id)) {
            // If there is no event ID for the observer, create new.
            if (!isset($timeslot->observer_event_id)) {
                $event = self::create_event($cm, $observation, $timeslot, $timeslot->observer_id);

                $eventobj = \calendar_event::create($event, false);
                if ($eventobj === false) {
                    throw new \moodle_exception("Could not create event for the observer for the timeslot.");
                }

                // Update timeslot to add event ID.
                $DB->update_record('observation_timeslots', ['id' => $slotid, 'observer_event_id' => $eventobj->id]);
            } else {
                // Else event ID exists for observer, so update the details.
                $event = \calendar_event::load($timeslot->observer_event_id);
                $newdata = self::update_event($event, $observation, $timeslot, $timeslot->observer_id);
                $event->update($newdata, false);
            }
        }
    }

    /**
     * Add event properties that are updateable.
     * @param object $event current event object to update
     * @param object $observation observation instance from DB
     * @param object $slotdata time slot data from DB
     * @param object $userid user to assign to the updated event
     */
    private static function update_event($event, $observation, $slotdata, $userid) {
        $event->name = get_string('markingsession', 'observation', $observation->name);
        $event->description = get_string('assignedmarkingsession', 'observation');
        $event->timestart = $slotdata->start_time;
        $event->timesort = $slotdata->start_time;
        $event->timeduration = $slotdata->duration * MINSECS;
        $event->userid = $userid;

        return $event;
    }

    /**
     * Creates an event object for the given parameters.
     * @param object $cm Course module instance
     * @param object $observation observation instance from DB
     * @param object $slotdata time slot data from DB
     * @param int $userid ID of user to assign this event to
     */
    private static function create_event($cm, $observation, $slotdata, $userid) {
        // The properties below never change and are only set on event creation.
        $event = new \stdClass();
        $event->eventtype = 'observation';
        $event->type = CALENDAR_EVENT_TYPE_STANDARD;
        $event->format = FORMAT_HTML;
        $event->courseid = $cm->course;
        $event->groupid = 0;
        $event->modulename = 'observation';
        $event->instance = $cm->instance;
        $event->visible = instance_is_visible('observation', $cm);

        // Run update function to add data that does change.
        $event = self::update_event($event, $observation, $slotdata, $userid);

        return $event;
    }

    /**
     * Signs users up to timeslots after check if the class is taken, and the user isnt already
     * signed up for another class.
     * @param int $observationid is the obseravtion slot ID
     * @param int $slotid is the observation timeslot ID
     * @param int $userid is the user ID
     */
    public static function timeslot_signup(int $observationid, int $slotid, int $userid) {

        // Query the timeslot to find the signup status.
        $timeslot = self::get_existing_slot_data($observationid, $slotid);
        $signedupslot = self::get_registered_timeslot($observationid, $userid);

        if ($timeslot->observee_id !== null) {
            throw new moodle_exception("Could not signup to timeslot. Timeslot already taken.");
        } else if ($signedupslot !== false) {
            throw new moodle_exception("Could not signup to timeslot. You have already signed up for a timeslot.");
        }

        // Allow signup.
        $dbdata = [
            'id' => $slotid,
            'observee_id' => $userid,
            'obs_id' => $observationid
        ];

        self::modify_time_slot($dbdata, false);
    }

    /**
     * Determines timeslot signed up to, or false if not signed up (within a single observation instance)
     * @param int $observationid is the Observation ID
     * @param int $userid is the User ID
     */
    public static function get_registered_timeslot(int $observationid, int $userid) {
        global $DB;
        return $DB->get_record("observation_timeslots", ['obs_id' => $observationid, 'observee_id' => $userid], '*');
    }
}
