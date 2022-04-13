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
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jack Kepper <Jack@Kepper.net>, Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Unit tests for the observation timeslot joining.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jack Kepper <Jack@Kepper.net>, Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslot_joining_test extends advanced_testcase {

    /**
     * First valid data point to use for testing.
     */
    private const VALID_DATA = [
        'start_time' => 1528656920,
        'duration' => 60,
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

        // Create two observees and a single observer.
        $observer = $this->getDataGenerator()->create_user();
        $observee = $this->getDataGenerator()->create_user();
        $observee2 = $this->getDataGenerator()->create_user();

        // Enrol all users to course with their roles.
        $this->getDataGenerator()->enrol_user($observer->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($observee->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($observee2->id, $course->id, 'student');

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->observer = $observer;
        $this->observee = $observee;
        $this->observee2 = $observee2;

        $this->slot1id = $this->create_valid_timeslot();
        $this->slot2id = $this->create_valid_timeslot();

        $this->setUser($this->observee);
    }

    /**
     * Creates a valid timeslot and returns its ID.
     */
    private function create_valid_timeslot() {
        $obid = $this->instance->id;
        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        return \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    /**
     * Tests basic joining functions.
     */
    public function test_joining_function () {
        $obid = $this->instance->id;

        // Observee 1 joins timeslot 1, Observee 2 joins timeslot 2.
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot1id, $this->observee->id);
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot2id, $this->observee2->id);

        $timeslot1 = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $this->slot1id);
        $timeslot2 = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $this->slot2id);

        $this->assertEquals($this->observee->id, $timeslot1->observee_id);
        $this->assertEquals($this->observee2->id, $timeslot2->observee_id);
    }

    /**
     * Tests if students are not allowed to join multiple timeslots.
     */
    public function test_double_joining () {
        $obid = $this->instance->id;

        // Observee 1 tries to join both timeslots 1 and 2 (not allowed).
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot1id, $this->observee->id);

        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot2id, $this->observee->id);
    }

    /**
     * Tests if two students are not allowed to join same timeslot.
     */
    public function test_join_filled () {
        $obid = $this->instance->id;

        // Observee 1 joins timeslot 1.
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot1id, $this->observee->id);

        // Observee 2 tries to join timeslot 1.
        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot1id, $this->observee2->id);
    }

     /**
      * Tests notifications on timeslot signup
      */
    public function test_signup_notification() {
        $obid = $this->instance->id;

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slot1id, $this->observee->id);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
    }
}
