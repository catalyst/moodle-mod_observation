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
 * Unit tests for timeslots.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Unit tests for timeslots.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author     Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslots_test extends advanced_testcase {

    /**
     * Valid data point to use for testing.
     */
    private const VALID_DATA = [
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
        $observer2 = $this->getDataGenerator()->create_user();
        $observee = $this->getDataGenerator()->create_user();

        // Enrol all users to course with their roles.
        $this->getDataGenerator()->enrol_user($coordinator->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($observer->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($observer2->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($observee->id, $course->id, 'student');

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->coordinator = $coordinator;
        $this->observer = $observer;
        $this->observer2 = $observer2;
        $this->observee = $observee;

        $this->setUser($this->coordinator);
    }

    /**
     * Creates a valid timeslot using the data created in the setUp() function.
     */
    private function create_valid_timeslot() {
        $obid = $this->instance->id;
        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['observee_id'] = $this->observee->id;
        $data['obs_id'] = $obid;

        return $data;
    }

    /**
     * Tests basic CRUD actions for timeslots using valid data.
     */
    public function test_valid_crud () {
        // Test create.
        $obid = $this->instance->id;
        $data = $this->create_valid_timeslot();

        $timeslotid = \mod_observation\timeslot_manager::modify_time_slot($data);

        // Test read.
        $thistimeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
        $this->assertEquals($thistimeslot->id, $timeslotid);

        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $this->assertTrue(in_array($timeslotid, array_column($alltimeslots, 'id')));

        // Ensure calendar event created for observer.
        $this->assertNotNull($thistimeslot->observer_event_id);
        // Ensure calendar event created for observee.
        $this->assertNotNull($thistimeslot->observee_event_id);

        // Load timeslot for observer.
        $event = \calendar_event::load($thistimeslot->observer_event_id);
        // Note calendar events store duration in seconds.
        $this->assertEquals($data['duration'] * MINSECS, $event->timeduration);
        $this->assertEquals($data['start_time'], $event->timestart);

        // Load timeslot for observee.
        $event = \calendar_event::load($thistimeslot->observee_event_id);
        // Note calendar events store duration in seconds.
        $this->assertEquals($data['duration'] * MINSECS, $event->timeduration);
        $this->assertEquals($data['start_time'], $event->timestart);

        // Test edit.
        $editedslot = $data;
        $editedslot['duration'] = 50;
        $editedslot['start_time'] += 200;
        $editedslot['id'] = $timeslotid;
        $editedslot['observer_id'] = $this->observer2->id;

        \mod_observation\timeslot_manager::modify_time_slot($editedslot);
        $thistimeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
        $this->assertEquals($editedslot['duration'], $thistimeslot->duration);
        $this->assertEquals($editedslot['start_time'], $thistimeslot->start_time);

        // Ensure calendar event edited for observer.
        $event = \calendar_event::load($thistimeslot->observer_event_id);
        // Note calendar events store duration in seconds.
        $this->assertEquals($thistimeslot->duration * MINSECS, $event->timeduration);
        $this->assertEquals($thistimeslot->start_time, $event->timestart);
        $this->assertEquals($thistimeslot->observer_id, $event->userid);

        // Ensure calendar event edited for observee.
        $event = \calendar_event::load($thistimeslot->observee_event_id);
        // Note calendar events store duration in seconds.
        $this->assertEquals($thistimeslot->duration * MINSECS, $event->timeduration);
        $this->assertEquals($thistimeslot->start_time, $event->timestart);
        $this->assertEquals($thistimeslot->observee_id, $event->userid);

        // Test delete.
        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        \mod_observation\timeslot_manager::delete_time_slot($obid, $timeslotid, $this->coordinator->id);
        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $this->assertEmpty($alltimeslots);

        // Ensure message was sent on timeslot deletion.
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));

        // Ensure the message comes from the coordinators email (the one who actioned the removal).
        $this->assertEquals($this->coordinator->id, $messages[0]->useridfrom);
        $this->assertEquals($this->observee->id, $messages[0]->useridto);

        // Ensure calendar event deleted for observer.
        $this->expectException('dml_exception');
        $event = \calendar_event::load($thistimeslot->observer_event_id);

        // Ensure calendar event deleted for observee.
        $this->expectException('dml_exception');
        $event = \calendar_event::load($thistimeslot->observee_event_id);
    }

    /**
     * Tests a negative duration.
     */
    public function test_negative_duration() {
        $data = $this->create_valid_timeslot();
        $data['duration'] = -1;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    /**
     * Tests a non-int duration.
     */
    public function test_float_duration() {
        $data = $this->create_valid_timeslot();
        $data['duration'] = 20.5;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    /**
     * Tests a string start time
     */
    public function test_string_start_time() {
        $data = $this->create_valid_timeslot();
        $data['start_time'] = 'Wed 5th July 10am';

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    /**
     * Tests a negative start time
     */
    public function test_negative_start_time() {
        $data = $this->create_valid_timeslot();
        $data['start_time'] = -1000;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    /**
     * Creates data for valid interval timeslot
     */
    private function create_valid_interval_timeslot() {
        $data = new stdClass();

        $data->interval_amount = 30;
        // In form the multiplier is a string, so simulate that here.
        $data->interval_multiplier = (string) MINSECS;
        $data->start_time = time();
        $data->interval_end = $data->start_time + HOURSECS * 1;
        $data->id = $this->instance->id;
        $data->duration = 10;
        $data->observer_id = $this->observer->id;

        return $data;
    }

    /**
     * Tests valid creation of interval timeslot
     */
    public function test_interval() {
        $data = $this->create_valid_interval_timeslot();

        \mod_observation\timeslot_manager::create_timeslots_by_interval($data);

        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($this->instance->id);

        $this->assertEquals(2, count($alltimeslots));
    }

    /**
     * Tests invalid interval amount
     */
    public function test_interval_invalid_amount() {
        $data = $this->create_valid_interval_timeslot();
        $data->interval_amount = -10;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::create_timeslots_by_interval($data);
    }

    /**
     * Tests invalid interval multiplier
     */
    public function test_interval_invalid_multiplier() {
        $data = $this->create_valid_interval_timeslot();
        $data->interval_multiplier = "-2.5";

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::create_timeslots_by_interval($data);
    }

    /**
     * Tests interval end time before start time
     */
    public function test_interval_end_before_start() {
        $data = $this->create_valid_interval_timeslot();
        $data->interval_end = $data->start_time - 20;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::create_timeslots_by_interval($data);
    }

    /**
     * Tests interval invalid end time
     */
    public function test_interval_invalid_end() {
        $data = $this->create_valid_interval_timeslot();
        $data->interval_end = 0;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::create_timeslots_by_interval($data);
    }

    /**
     * Tests coordinator kicking an observee from a timeslot.
     */
    /**
     * Tests the basic case when randomly assigning students
     * to timeslots.
     */
    public function test_random_assign_single_user() {
        $obid = $this->instance->id;

        // Currently 1 observee created in setUp.
        $notsignedup = \mod_observation\timeslot_manager::randomly_assign_students($obid);
        $this->assertEquals(1, count($notsignedup));

        // Create timeslot.
        $data = $this->create_valid_timeslot();
        unset($data['observee_id']);
        \mod_observation\timeslot_manager::modify_time_slot($data);

        $notsignedup = \mod_observation\timeslot_manager::randomly_assign_students($obid);
        $this->assertEquals(0, count($notsignedup));
    }

    /**
     * Tests that if there are not enough slots, the users who
     * where not signed up to a timeslot are returned.
     */
    public function test_random_assign_not_enough_slots() {
        $obid = $this->instance->id;

        // Create an additional user (to make 2 in total), but only a single timeslots.
        $observee2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($observee2->id, $this->course->id, 'student');

        $data = $this->create_valid_timeslot();
        unset($data['observee_id']);
        \mod_observation\timeslot_manager::modify_time_slot($data);

        $notsignedup = \mod_observation\timeslot_manager::randomly_assign_students($obid);
        $this->assertEquals(1, count($notsignedup));
    }

    /**
     * Tests if there are more empty slots than users,
     * that a user is not assigned to more than a single slot.
     */
    public function test_random_assign_excessive_slots() {
        $obid = $this->instance->id;

        // Create 5 slots.
        for ($i = 0; $i < 5; $i++) {
            $data = $this->create_valid_timeslot();
            unset($data['observee_id']);
            \mod_observation\timeslot_manager::modify_time_slot($data);
        }

        // Assign the single user created in setUp.
        $notsignedup = \mod_observation\timeslot_manager::randomly_assign_students($obid);
        $this->assertEquals(0, count($notsignedup));

        // Repeat again (should not re-assign the student assigned before).
        $notsignedup = \mod_observation\timeslot_manager::randomly_assign_students($obid);
        $this->assertEquals(0, count($notsignedup));

        $slots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $observees = array_column($slots, 'observee_id');
        $observees = array_filter($observees); // Filter nulls.

        $this->assertEquals(1, count($observees));
        $this->assertEquals(array_values($observees)[0], $this->observee->id);
    }

    public function test_kick_observee() {
        $obid = $this->instance->id;

        $data = $this->create_valid_timeslot();
        $data['observee_id'] = null;
        $slotid = \mod_observation\timeslot_manager::modify_time_slot($data);

        \mod_observation\timeslot_manager::timeslot_signup($obid, $slotid, $this->observee->id);

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        // Kick from timeslot, ensure they are removed and cancellation message is sent.
        \mod_observation\timeslot_manager::remove_observee($obid, $slotid, $this->coordinator->id);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));

        // Ensure the message comes from the coordinators email (the one who actioned the removal).
        $this->assertEquals($this->coordinator->id, $messages[0]->useridfrom);
        $this->assertEquals($this->observee->id, $messages[0]->useridto);

        $timeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $slotid);
        $this->assertEmpty($timeslot->observee_id);

        // Try to kick them again (should throw exception, as there should be no observee to kick).
        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::remove_observee($obid, $slotid, $this->coordinator->id);
    }
}
