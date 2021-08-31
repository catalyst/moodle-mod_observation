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
 * Unit tests for the observation timeslot joining.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Endurer Solutions Team
 * @author Jack Kepper <Jack@Kepper.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Unit tests for the observation timeslot joining.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Endurer Solutions Team
 * @author Jack Kepper <Jack@Kepper.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_slot_joining_test extends advanced_testcase {

    /**
     * First valid data point to use for testing.
     */
    private const VALID_DATA1 = [
        'start_time' => 1528656920,
        'duration' => 60,
    ];
    /**
     * Second valid data point to use for testing.
     */
    private const VALID_DATA2 = [
        'start_time' => 1628656920,
        'duration' => 20,
    ];

    /**
     * Set up for tests. Creates course, activity and adds three basic user roles to it.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $obgenerator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $obinstance = $obgenerator->create_instance(['course' => $course->id]);

        // Create three users with roles:
        // coordinator (editing teacher), observer (teacher), observee (student).
        $coordinator = $this->getDataGenerator()->create_user();
        $observer = $this->getDataGenerator()->create_user();
        $observee = $this->getDataGenerator()->create_user();
        $observee2 = $this->getDataGenerator()->create_user();

        // Enrol all users to course with their roles.
        $this->getDataGenerator()->enrol_user($coordinator->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($observer->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($observee->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($observee2->id, $course->id, 'student');

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->coordinator = $coordinator;
        $this->observer = $observer;
        $this->observee = $observee;
        $this->observee2 = $observee2;

        $this->setUser($this->observee);
    }

    /**
     * Creates a valid timeslot using the data created in the setUp() function.
     */
    private function create_valid_timeslot1() {
        $obid = $this->instance->id;
        $data = self::VALID_DATA1;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        return $data;
    }

    /**
     * Creates a valid timeslot using the data created in the setUp() function.
     */
    private function create_valid_timeslot2() {
        $obid = $this->instance->id;
        $data = self::VALID_DATA2;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        return $data;
    }

    /**
     * Tests basic joining functions.
     */
    public function test_joining_function () {
        $obid = $this->instance->id;
        $data1 = $this->create_valid_timeslot1();
        $data2 = $this->create_valid_timeslot2();

        $timeslotid1 = \mod_observation\timeslot_manager::modify_time_slot($data1, true);
        $timeslotid2 = \mod_observation\timeslot_manager::modify_time_slot($data2, true);

        // First observee joining.
        $jointedimeslot1 = \mod_observation\timeslot_manager::timeslot_signup($obid, $timeslotid1, $this->observee->id);

        // Testing second user trying to join timeslot
        
    }
}