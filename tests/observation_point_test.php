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
 * Unit tests for the observation point manager class.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the observation point manager class.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Endurer Solutions Team
 * @author     Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_point_test extends advanced_testcase {
    /**
     * Set up for tests. Creates course, activity and adds three basic user roles to it.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);

        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $obgenerator = $this->getDataGenerator()->get_plugin_generator('mod_observation');
        $obinstance = $obgenerator->create_instance([
            'course' => $course->id
        ]);

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
     * Tests CRUD operations for observation point with expected data.
     */
    public function test_crud_expected () {
        // Create point.
        $validdata = [
            'obs_id' => $this->instance->id,
            'title' => 'point1',
            'ins' => '<p dir="ltr" style="text-align: left;">text1<br></p>',
            'ins_f' => 1,
            'max_grade' => 5,
            'res_type' => 0,
        ];

        // No data yet.
        $this->assertEmpty(
            \mod_observation\observation_manager::get_observation_points($this->instance->id)
        );

        $this->assertTrue(
            \mod_observation\observation_manager::modify_observation_point($validdata, true));
        
        // Unset DB generated values to compare to the original data.
        $returndata = \mod_observation\observation_manager::get_observation_points($this->instance->id);
        foreach($returndata as $point){
            unset($point->id);
            unset($point->list_order);
        }

        // Ensure contains point just added.
        $this->assertContainsEquals((object)$validdata, $returndata);

        // Re-get points to get ID.
        $returndata = \mod_observation\observation_manager::get_observation_points($this->instance->id);
        $returnedpoint = array_values($returndata)[0];

        // Confirm can get individual point data.
        $singlepointdata = \mod_observation\observation_manager::get_existing_point_data($this->instance->id, $returnedpoint->id);
        
        $this->assertEquals($returnedpoint, $singlepointdata);

        // Edit point data using returned value as a base.
        $editeddata = clone $returnedpoint;
        $editeddata->title = 'point2';
        $editeddata->max_grade = 10;
        $editeddata->ins = '<p dir="ltr" style="text-align: left;">text2<br></p>';

        // Modify point.
        $this->assertTrue(
            \mod_observation\observation_manager::modify_observation_point($editeddata, false));
        
        // Re get data and confirm changed.
        $returndata = \mod_observation\observation_manager::get_observation_points($this->instance->id);
        $returnedpoint = array_values($returndata)[0];

        $this->assertContainsEquals((object)$editeddata, $returndata);
        $this->assertNotContainsEquals((object)$validdata, $returndata);

        // Delete point.
        \mod_observation\observation_manager::delete_observation_point($this->instance->id, $returnedpoint->id);

        // Confirm point deleted.
        $returndata = \mod_observation\observation_manager::get_observation_points($this->instance->id);
        
        $this->assertEmpty($returndata);
        
        // Cannot access point as no longer exists (throws exception).
        $this->expectException('dml_exception');
        \mod_observation\observation_manager::get_existing_point_data($this->instance->id, $returnedpoint->id);
    }

    /**
     * Tests the ordering logic for observation points.
     */
    public function test_ordering_single () {
        
        $validdata = [
            'obs_id' => $this->instance->id,
            'title' => 'point1',
            'ins' => '<p dir="ltr" style="text-align: left;">text1<br></p>',
            'ins_f' => 1,
            'max_grade' => 5,
            'res_type' => 0,
        ];

        // Create point and get data.
        \mod_observation\observation_manager::modify_observation_point($validdata, true);
        $returndata = \mod_observation\observation_manager::get_observation_points($this->instance->id);
        $returnedpoint = array_values($returndata)[0];

        // Ensure ordering is 1.
        $this->assertEquals(1, $returnedpoint->list_order);

        // Attempt to move up/down.
        //TODO.
    }
}