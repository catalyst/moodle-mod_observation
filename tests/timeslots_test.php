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
        $observee = $this->getDataGenerator()->create_user();

        // Enrol all users to course with their roles.
        $this->getDataGenerator()->enrol_user($coordinator->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($observer->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($observee->id, $course->id, 'student');

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->coordinator = $coordinator;
        $this->observer = $observer;
        $this->observee = $observee;
    }

    /**
     * Tests basic CRUD actions for timeslots using valid data.
     */
    public function test_valid_crud () {
        $obid = $this->instance->id;

        // Test create.
        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        $timeslotid = \mod_observation\timeslot_manager::modify_time_slot($data, true, true);

        // Test read.
        $thistimeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
        $this->assertEquals($thistimeslot->id, $timeslotid);

        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $this->assertContainsEquals($timeslotid, array_column($alltimeslots, 'id'));

        // Test edit.
        $editedslot = $data;
        $editedslot['duration'] = 50;
        $editedslot['start_time'] += 200;
        $editedslot['id'] = $timeslotid;

        \mod_observation\timeslot_manager::modify_time_slot($editedslot, false, false);
        $thistimeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
        $this->assertEquals($editedslot['duration'], $thistimeslot->duration);
        $this->assertEquals($editedslot['start_time'], $thistimeslot->start_time);

        // Test delete.
        \mod_observation\timeslot_manager::delete_time_slot($obid, $timeslotid);
        $alltimeslots = \mod_observation\timeslot_manager::get_time_slots($obid);
        $this->assertEmpty($alltimeslots);

        $this->expectException('dml_exception');
        \mod_observation\timeslot_manager::get_existing_slot_data($obid, $timeslotid);
    }

    /**
     * Tests a negative duration.
     */
    public function test_negative_duration() {
        $obid = $this->instance->id;

        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;
        $data['duration'] = -1;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true, true);
    }

    /**
     * Tests a non-int duration.
     */
    public function test_float_duration() {
        $obid = $this->instance->id;

        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;
        $data['duration'] = 20.5;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true, true);
    }

    /**
     * Tests a string start time
     */
    public function test_string_start_time() {
        $obid = $this->instance->id;

        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        $data['start_time'] = 'Wed 5th July 10am';

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true, true);
    }

    /**
     * Tests a negative start time
     */
    public function test_negative_start_time() {
        $obid = $this->instance->id;

        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        $data['start_time'] = -1000;

        $this->expectException('coding_exception');
        \mod_observation\timeslot_manager::modify_time_slot($data, true, true);
    }
}
