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
 * Unit tests for the observation session class.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jack Kepper <Jack@Kepper.net>, Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Unit tests for unenrollment functions.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author     Jack Kepper <Jack@Kepper.net>, Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unenrol_test extends advanced_testcase {

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

        $this->slotid = $this->create_valid_timeslot();

        $this->setUser($this->observee);
    }
    /**
     * Creates a valid timeslot using the data created in the setUp() function.
     */
    private function create_valid_timeslot() {
        $obid = $this->instance->id;
        $data = self::VALID_DATA;
        $data['observer_id'] = $this->observer->id;
        $data['obs_id'] = $obid;

        return \mod_observation\timeslot_manager::modify_time_slot($data);
    }

    public function test_unenrolling_enabled() {
        global $DB;

        // Creating test.
        $obid = $this->instance->id;

        // Ensure unenrolment is enabled.
        $DB->update_record('observation', ['id' => $obid, 'students_self_unregister' => 1]);

        // Observee 1 joins timeslot and creates notification.
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slotid, $this->observee->id);

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        // Observee 1 unenrols.
        \mod_observation\timeslot_manager::timeslot_unenrolment($obid, $this->slotid, $this->observee->id);
        $timeslot = \mod_observation\timeslot_manager::get_existing_slot_data($obid, $this->slotid);

        // Ensure they get an email from themselves.
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
        $this->assertEquals($this->observee->id, $messages[0]->useridfrom);
        $this->assertEquals($this->observee->id, $messages[0]->useridto);

        // Ensure observee is removed from timeslot as well as calendar event.
        $this->assertEquals(null, $timeslot->observee_id);
        $this->assertEquals(null, $timeslot->observee_event_id);
    }

    public function test_unenrolling_disabled() {
        global $DB;

        // Creating test.
        $obid = $this->instance->id;

        // Ensure unenrolment is disabled.
        $DB->update_record('observation', ['id' => $obid, 'students_self_unregister' => 0]);

        // Observee 1 joins timeslot.
        \mod_observation\timeslot_manager::timeslot_signup($obid, $this->slotid, $this->observee->id);

        // Observee 1 unenrols.
        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::timeslot_unenrolment($obid, $this->slotid, $this->observee->id);
    }

    public function test_unenrolling_empty() {
        global $DB;

        // Ensure unenrolment is enabled.
        $DB->update_record('observation', ['id' => $this->instance->id, 'students_self_unregister' => 1]);

        // Remove observee from timeslot.
        $DB->update_record('observation_timeslots', ['id' => $this->slotid, 'observee_id' => null]);

        // Try to unenrol.
        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::timeslot_unenrolment($this->instance->id, $this->slotid, $this->observee->id);
    }

    public function test_unenrolling_not_own() {
        global $DB;

        // Ensure unenrolment is enabled.
        $DB->update_record('observation', ['id' => $this->instance->id, 'students_self_unregister' => 1]);

        // Observee 1 joins timeslot.
        \mod_observation\timeslot_manager::timeslot_signup($this->instance->id, $this->slotid, $this->observee->id);

        // Try to unenrol using a different user.
        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::timeslot_unenrolment($this->instance->id, $this->slotid, $this->observee2->id);
    }
}
