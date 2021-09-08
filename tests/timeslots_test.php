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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for timeslots.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Endurer Solutions Team
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

        $timeslotid = \mod_observation\timeslot_manager::modify_time_slot($data, true);

        // Test read.
        $thistimeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
        $this->assertEquals($thistimeslot->id, $timeslotid);

        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $this->assertContainsEquals($timeslotid, array_column($alltimeslots, 'id'));

        // Ensure calendar event created.
        $this->assertNotNull($thistimeslot->observer_event_id);

        $event = \calendar_event::load($thistimeslot->observer_event_id);
        // Note calendar events store duration in seconds.
        $this->assertEquals($data['duration'] * MINSECS, $event->timeduration);
        $this->assertEquals($data['start_time'], $event->timestart);

        // Test edit.
        $editedslot = $data;
        $editedslot['duration'] = 50;
        $editedslot['start_time'] += 200;
        $editedslot['id'] = $timeslotid;
        $editedslot['observer_id'] = $this->observer2->id;

        \mod_observation\timeslot_manager::modify_time_slot($editedslot, false);
        $thistimeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
        $this->assertEquals($editedslot['duration'], $thistimeslot->duration);
        $this->assertEquals($editedslot['start_time'], $thistimeslot->start_time);

        // Ensure calendar event edited.
        $event = \calendar_event::load($thistimeslot->observer_event_id);
        // Note calendar events store duration in seconds.
        $this->assertEquals($thistimeslot->duration * MINSECS, $event->timeduration);
        $this->assertEquals($thistimeslot->start_time, $event->timestart);
        $this->assertEquals($thistimeslot->observer_id, $event->userid);

        // Test delete.
        \mod_observation\timeslot_manager::delete_time_slot($obid, $timeslotid);
        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $this->assertEmpty($alltimeslots);

        // Ensure calendar event deleted.
        $this->expectException('dml_exception');
        $event = \calendar_event::load($thistimeslot->observer_event_id);
    }

    /**
     * Tests a negative duration.
     */
    public function test_negative_duration() {
        $data = $this->create_valid_timeslot();
        $data['duration'] = -1;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true);
    }

    /**
     * Tests a non-int duration.
     */
    public function test_float_duration() {
        $data = $this->create_valid_timeslot();
        $data['duration'] = 20.5;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true);
    }

    /**
     * Tests a string start time
     */
    public function test_string_start_time() {
        $data = $this->create_valid_timeslot();
        $data['start_time'] = 'Wed 5th July 10am';

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true);
    }

    /**
     * Tests a negative start time
     */
    public function test_negative_start_time() {
        $data = $this->create_valid_timeslot();
        $data['start_time'] = -1000;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true);
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
}
