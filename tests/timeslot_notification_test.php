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
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Unit tests for observation timeslot notifications.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslot_notification_test extends advanced_testcase {

    /**
     * Valid data for the timeslot to create notifications for.
     */
    private const VALID_DATA = [
        'start_time' => 1528656920,
        'duration' => 60,
    ];

    /**
     * Valid notification data.
     */
    const NOTIFY_DATA = [
        'interval_amount' => 1,
        'interval_multiplier' => 6,
    ];

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

        // Create two valid timeslots.
        $this->slot1id = $this->create_valid_timeslot();
        $this->slot2id = $this->create_valid_timeslot();

        // Signup each user to own timeslot.
        \mod_observation\timeslot_manager::timeslot_signup($obinstance->id, $this->slot1id, $observee->id);
        \mod_observation\timeslot_manager::timeslot_signup($obinstance->id, $this->slot2id, $observee2->id);
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
     * Test basic CRUD operations for notifications
     */
    public function test_notification_crud() {
        $this->setUser($this->observee);

        // Create notification.
        \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
            (object) self::NOTIFY_DATA);

        $notifys = \mod_observation\timeslot_manager::get_users_notifications($this->instance->id, $this->observee->id);
        $this->assertEquals(1, count($notifys));

        // Delete notification.
        \mod_observation\timeslot_manager::delete_notification($this->instance->id, $this->observee->id,
            array_values($notifys)[0]->notification_id);

        $notifys = \mod_observation\timeslot_manager::get_users_notifications($this->instance->id, $this->observee->id);
        $this->assertEquals(0, count($notifys));
    }

    /**
     * Test if user can delete a notification they didn't create
     */
    public function test_delete_not_owned() {
        $this->setUser($this->observee);

        // Create notification for observee 1.
        \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
            (object) self::NOTIFY_DATA);
        $notifys = \mod_observation\timeslot_manager::get_users_notifications($this->instance->id, $this->observee->id);
        $this->assertEquals(1, count($notifys));

        // Try and delete as observee 2 (not allowed).
        $this->expectException('moodle_exception');
        \mod_observation\timeslot_manager::delete_notification($this->instance->id, $this->observee2->id,
            array_values($notifys)[0]->notification_id);
    }

    /**
     * Tests if a user can create too many notifications than allowed
     */
    public function test_create_too_many() {
        $this->setUser($this->observee);

        // Create the allowed number.
        for ($i = 0; $i < \mod_observation\timeslot_manager::MAX_NOTIFICATIONS; $i++) {
            \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
                (object) self::NOTIFY_DATA);
        }

        // Try and create 1 more (will not create more, but no exception should be thrown).
        \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
            (object) self::NOTIFY_DATA);

        $notifys = \mod_observation\timeslot_manager::get_users_notifications($this->instance->id, $this->observee->id);
        $this->assertEquals(\mod_observation\timeslot_manager::MAX_NOTIFICATIONS, count($notifys));
    }

    /**
     * Tests invalid inputs for the interval field
     */
    public function test_invalid_interval() {
        $invalidvars = [
            '',
            array(),
            true,
            -10,
            new \StdClass,
            0.5
        ];

        foreach ($invalidvars as $var) {
            try {
                $data = (object) self::NOTIFY_DATA;
                $data->interval_amount = $var;

                \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id,
                    $this->observee->id, $data);
                $this->fail('No exception thrown for variable type "' . gettype($var) . '".');
            } catch (\coding_exception $expected) {
                // Moodle codechecker doesn't like empty catch statements.
                $a = true;
            }
        }
    }

    /**
     * Tests invalid inputs for the interval multiplier field
     */
    public function test_invalid_multiplier() {
        $invalidvars = [
            '',
            array(),
            true,
            -10,
            new \StdClass,
            0.5
        ];

        foreach ($invalidvars as $var) {
            try {
                $data = (object) self::NOTIFY_DATA;
                $data->interval_multiplier = $var;

                \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id,
                    $this->observee->id, $data);
                $this->fail('No exception thrown for variable type "' . gettype($var) . '".');
            } catch (\coding_exception $expected) {
                // Moodle codechecker doesn't like empty catch statements.
                $a = true;
            }
        }
    }

    /**
     * Tests if the notifications are deleted if a student unenrols from a timeslot
     */
    public function test_deleted_after_unenrol() {
        global $DB;

        $obid = $this->instance->id;

        // Ensure unenrol is enabled.
        $DB->update_record('observation', ['id' => $obid, 'students_self_unregister' => 1]);

        // Create notification.
        $this->setUser($this->observee);
        \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
            (object) self::NOTIFY_DATA);

        $notifications = \mod_observation\timeslot_manager::get_users_notifications($obid, $this->observee->id);
        $this->assertEquals(1, count($notifications));

        // User unenrols from timeslot.
        \mod_observation\timeslot_manager::timeslot_unenrolment($obid, $this->slot1id, $this->observee->id);

        // Ensure notifications are removed.
        $notifications = \mod_observation\timeslot_manager::get_users_notifications($obid, $this->observee->id);
        $this->assertEmpty($notifications);
    }

    public function test_deleted_after_kicked() {
        global $DB;

        $obid = $this->instance->id;

        // Ensure unenrol is enabled.
        $DB->update_record('observation', ['id' => $obid, 'students_self_unregister' => 1]);

        // Create notification.
        $this->setUser($this->observee);
        \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
            (object) self::NOTIFY_DATA);

        $notifications = \mod_observation\timeslot_manager::get_users_notifications($obid, $this->observee->id);
        $this->assertEquals(1, count($notifications));

        // User is kicked from timeslot.
        \mod_observation\timeslot_manager::remove_observee($obid, $this->slot1id, $this->observer->id);

        // Ensure notifications are removed.
        $notifications = \mod_observation\timeslot_manager::get_users_notifications($obid, $this->observee->id);
        $this->assertEmpty($notifications);
    }

    public function test_deleted_after_slot_deleted() {
        global $DB;

        $obid = $this->instance->id;

        // Ensure unenrol is enabled.
        $DB->update_record('observation', ['id' => $obid, 'students_self_unregister' => 1]);

        // Create notification.
        $this->setUser($this->observee);
        \mod_observation\timeslot_manager::create_notification($this->instance->id, $this->slot1id, $this->observee->id,
            (object) self::NOTIFY_DATA);

        $notifications = \mod_observation\timeslot_manager::get_users_notifications($obid, $this->observee->id);
        $this->assertEquals(1, count($notifications));

        // Timeslot is deleted.
        \mod_observation\timeslot_manager::delete_time_slot($obid, $this->slot1id, $this->observer->id);

        // Ensure notifications are removed.
        $notifications = \mod_observation\timeslot_manager::get_users_notifications($obid, $this->observee->id);
        $this->assertEmpty($notifications);
    }
}
