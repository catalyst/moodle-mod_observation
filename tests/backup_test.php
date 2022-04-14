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
 * Unit tests for observation backups.
 *
 * @package     mod_observation
 * @category    test
 * @copyright   Catalyst IT Australia
 * @author      Matthew Hilton
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_test extends advanced_testcase {
    /**
     * Set up for tests. Creates course, activity and adds three basic user roles to it.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        // Create course and activity.
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $obgenerator = $generator->get_plugin_generator('mod_observation');
        $obinstance = $obgenerator->create_instance(['course' => $course->id]);

        // Create two observees and a single observer.
        $observer = $generator->create_user();
        $observee = $generator->create_user();

        // Enrol all users to course with their roles.
        $generator->enrol_user($observer->id, $course->id, 'teacher');
        $generator->enrol_user($observee->id, $course->id, 'student');

        // Create a timeslot and signup observee to the slot.
        $timeslotid = $obgenerator->create_timeslot($obinstance->id, $observer->id);

        // Create an observation point.
        $point = $obgenerator->create_observation_point($obinstance->id);

        // Start a session with this user.
        $sessid = \mod_observation\session_manager::start_session($obinstance->id, $observer->id, $observee->id);

        // Respond to the observation point.
        $obgenerator->create_observation_point_response($point, $sessid);

        // Create a notification for the observees timeslot.
        $obgenerator->create_observation_notification($obinstance->id, $timeslotid, $observee->id);

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->observer = $observer;
        $this->observee = $observee;
    }

    public function test_backup_and_restore() {
        global $DB;

        $this->setAdminUser();
        $cm = get_coursemodule_from_id('observation', $this->instance->cmid, 0, false, MUST_EXIST);

        // Duplicate module by backing up and restoring.
        $newcminfo = duplicate_module($this->course, $cm);

        // Ensure timeslots were restored.
        $originaltimeslots = \mod_observation\timeslot_manager::get_time_slots($this->instance->id);
        $restoredtimeslots = \mod_observation\timeslot_manager::get_time_slots($newcminfo->instance);

        // Pick an attribute to confirm correct restore, as IDs will be different.
        $this->assertEquals(array_column($originaltimeslots, 'start_time'),
            array_column($restoredtimeslots, 'start_time'), "\$canonicalize = true");
        $this->assertEquals(1, count($restoredtimeslots));

        // Ensure observation points were restored.
        $originalpoints = \mod_observation\observation_manager::get_observation_points($this->instance->id);
        $restoredpoints = \mod_observation\observation_manager::get_observation_points($newcminfo->instance);

        // Pick an attribute to confirm correct restore, as IDs will be different.
        $this->assertEquals(array_column($originalpoints, 'title'), array_column($restoredpoints, 'title'),
            "\$canonicalize = true");
        $this->assertEquals(1, count($restoredpoints));

        // Ensure sessions were restored.
        $originalsessions = \mod_observation\session_manager::get_sessions($this->instance->id);
        $restoredsessions = \mod_observation\session_manager::get_sessions($newcminfo->instance);

        // Pick an attribute to confirm correct restore, as IDs will be different.
        $this->assertEquals(array_column($originalsessions, 'start_time'), array_column($restoredsessions, 'start_time'),
            "\$canonicalize = true");
        $this->assertEquals(1, count($restoredsessions));

        // Ensure responses were restored.
        $originalresponses = \mod_observation\session_manager::get_session_data(array_values($originalsessions)[0]->id);
        $restoredresponses = \mod_observation\session_manager::get_session_data(array_values($restoredsessions)[0]->id);

        // Ensure timeslots were restored.
        $this->assertEquals(array_column(array_values($originalresponses['data']), 'response'),
            array_column(array_values($restoredresponses['data']), 'response'), "\$canonicalize = true");
        $this->assertEquals(1, count($originalresponses['data']));

        // Ensure timeslot notifications were restored.
        $originalnotifications = $DB->get_records('observation_notifications',
            ['timeslot_id' => array_column($originaltimeslots, 'id')[0]]);
        $restorednotifications = $DB->get_records('observation_notifications',
            ['timeslot_id' => array_column($restoredtimeslots, 'id')[0]]);

        // Ensure timeslots were restored properly.
        $this->assertEquals(array_column($originalnotifications, 'time_before'),
            array_column($restorednotifications, 'time_before'), "\$canonicalize = true");
        $this->assertEquals(1, count($originalnotifications));
        $this->assertEquals(1, count($restorednotifications));
    }
}
