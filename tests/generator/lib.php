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
 * mod_observation data generator
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Observation module data generator class
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_observation_generator extends testing_module_generator {

    /**
     * Default timeslot test data
     */
    const DEFAULT_TIMESLOT_DATA = [
        'start_time' => 9999999999,
        'duration' => 60,
    ];

    /**
     * Default observation point data
     */
    const DEFAULT_POINT_DATA = [
        'title' => 'point1',
        'ins' => '<p dir="ltr" style="text-align: left;">text1<br></p>',
        'ins_f' => 1,
        'max_grade' => 5,
        'res_type' => 0,
        'file_size' => null,
    ];

    /**
     * Default observation point response data
     */
    const DEFAULT_RESPONSE_DATA = [
        'grade_given' => self::DEFAULT_POINT_DATA['max_grade'],
        'response' => 'test',
        'ex_comment' => '',
    ];

    /**
     * Default notification data
     */
    const DEFAULT_NOTIFICATION_DATA = [
        'interval_amount' => 1,
        'interval_multiplier' => HOURSECS,
    ];

    /**
     * Creates instance of observation for testing.
     * @param array|stdClass $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        // Default editor values.
        $defaulteditorvalues = array(
            'text' => null,
            'format' => null,
        );

        // Default observation activity settings.
        $defaultobservationsettings = array(
            'observerins_editor' => $defaulteditorvalues,
            'observeeins_editor' => $defaulteditorvalues,
            'students_self_unregister' => 0,
        );

        // Set defaults if not already set.
        foreach ($defaultobservationsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create timeslot
     * @param int $obid ID of observation activity
     * @param int $observerid ID of observer to be assigned to timeslot created
     * @param array $override Data to override over default data
     */
    public function create_timeslot($obid, $observerid, $override = []) {
        // Merge, overriding default if values are given.
        $data = array_merge(self::DEFAULT_TIMESLOT_DATA, $override);

        $data['observer_id'] = $observerid;
        $data['obs_id'] = $obid;

        return \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    /**
     * Create observation point
     * @param int $obid ID of observation activity
     * @param array $override Data to override over default data
     */
    public function create_observation_point($obid, $override = []) {
        $data = array_merge(self::DEFAULT_POINT_DATA, $override);

        $data['obs_id'] = $obid;

        return \mod_observation\observation_manager::modify_observation_point($data, true);
    }

    /**
     * Create observation point response
     * @param int $pointid ID of observation point to respond to
     * @param int $sessionid ID of session to respond to
     * @param array $override Data to override over default data
     */
    public function create_observation_point_response($pointid, $sessionid, $override = []) {
        $data = array_merge(self::DEFAULT_RESPONSE_DATA, $override);

        return \mod_observation\observation_manager::submit_point_response($sessionid, $pointid, $data);
    }

    /**
     * Create observation timeslot notification
     * @param int $obid ID of the observation instance
     * @param int $timeslotid ID of observation timeslot
     * @param int $userid ID of user to create notification for
     * @param array $override Data to override over default data
     */
    public function create_observation_notification($obid, $timeslotid, $userid, $override = []) {
        $data = array_merge(self::DEFAULT_NOTIFICATION_DATA, $override);

        return \mod_observation\timeslot_manager::create_notification($obid, $timeslotid, $userid, (object)$data);
    }
}
