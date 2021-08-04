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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the observation session manager class.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  2021 Endurer Solutions Team
 * @author     Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_session_test extends advanced_testcase {

    /**
     * Valid data point to use for testing.
     */
    private const VALID_POINT_DATA = [
        'title' => 'point1',
        'ins' => '<p dir="ltr" style="text-align: left;">text1<br></p>',
        'ins_f' => 1,
        'max_grade' => 5,
        'res_type' => 0,
    ];

    /**
     * Valid response to an observation point
     */
    private const VALID_RESPONSE = [
        'grade_given' => 1,
        'response' => 'test response',
        'ex_comment' => 'extra comment'
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

        // Create two observation points.
        $data = self::VALID_POINT_DATA;
        $data['obs_id'] = $obinstance->id;

        $pointid1 = \mod_observation\observation_manager::modify_observation_point($data, true, true);
        $pointid2 = \mod_observation\observation_manager::modify_observation_point($data, true, true);

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->coordinator = $coordinator;
        $this->observer = $observer;
        $this->observee = $observee;

        $this->pointid1 = $pointid1;
        $this->pointid2 = $pointid2;
    }

    /**
     * Creates a valid session.
     */
    private function create_session() {
        $obid = $this->instance->id;
        $oberid = $this->observer->id;
        $obeeid = $this->observee->id;

        return \mod_observation\session_manager::start_session($obid, $oberid, $obeeid);
    }

    public function test_valid_session_flow() {
        $obid = $this->instance->id;
        $sessionid = $this->create_session();

        $this->assertIsInt($sessionid);

        // Get session info.
        $sessioninfo = \mod_observation\session_manager::get_session_info($sessionid);
        $this->assertEquals($obid, $sessioninfo['obid']);
        $this->assertEquals('inprogress', $sessioninfo['state']);

        // Get session data.
        $sessiondata = \mod_observation\session_manager::get_session_data($sessionid);

        // Ensure the observation points are returned.
        $sessiondataids = array_column($sessiondata, 'point_id');

        $this->assertContainsEquals($this->pointid1, $sessiondataids);
        $this->assertContainsEquals($this->pointid2, $sessiondataids);

        // Get incomplete points (should be both of them.).
        $incomplete = \mod_observation\session_manager::get_incomplete_points($sessionid);
        $incompleteids = array_column($incomplete, 'point_id');

        $this->assertContainsEquals($this->pointid1, $incompleteids);
        $this->assertContainsEquals($this->pointid2, $incompleteids);

        // Current grade should be zero.
        $currentgrade = \mod_observation\session_manager::calculate_grade($sessionid);

        $this->assertEquals(0, $currentgrade['total']);
        $this->assertEquals(10, $currentgrade['max']);

        $response = (object)self::VALID_RESPONSE;

        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid1, $response);

        // Get incomplete points (should only be the second).
        $incomplete = \mod_observation\session_manager::get_incomplete_points($sessionid);
        $incompleteids = array_column($incomplete, 'point_id');

        $this->assertNotContainsEquals($this->pointid1, $incompleteids);
        $this->assertContainsEquals($this->pointid2, $incompleteids);

        // Current grade should be 1.
        $currentgrade = \mod_observation\session_manager::calculate_grade($sessionid);
        $this->assertEquals(1, $currentgrade['total']);

        // Submit response to second point.
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid2, $response);

        // Get incomplete points (should now be empty).
        $incomplete = \mod_observation\session_manager::get_incomplete_points($sessionid);
        $this->assertEmpty($incomplete);

        // Current grade should be 2.
        $currentgrade = \mod_observation\session_manager::calculate_grade($sessionid);
        $this->assertEquals(2, $currentgrade['total']);

        // Save an extra comment.
        $extracomment = "Extra comment";
        \mod_observation\session_manager::save_extra_comment($sessionid, $extracomment);

        $sessioninfo = \mod_observation\session_manager::get_session_info($sessionid);
        $this->assertEquals($extracomment, $sessioninfo['ex_comment']);

        // Finalise session.
        $status = \mod_observation\session_manager::finish_session($sessionid);
        $this->assertTrue($status);

        $sessioninfo = \mod_observation\session_manager::get_session_info($sessionid);
        $this->assertEquals('complete', $sessioninfo['state']);
    }

    public function test_cancel_session() {
        $sessionid = $this->create_session();
        \mod_observation\session_manager::cancel_session($sessionid);

        $sessioninfo = \mod_observation\session_manager::get_session_info($sessionid);
        $this->assertEquals('cancelled', $sessioninfo['state']);
    }

    public function test_submit_invalid_grade() {
        $sessionid = $this->create_session();

        $response = (object)self::VALID_RESPONSE;
        $response->grade_given = -1;

        $this->expectException('coding_exception');
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid1, $response);
    }

    public function test_submit_high_grade() {
        $sessionid = $this->create_session();

        $response = (object)self::VALID_RESPONSE;
        $response->grade_given = 1000;

        $this->expectException('coding_exception');
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid1, $response);
    }

    public function test_finish_nonexistent_session() {
        $sessionid = $this->create_session();

        $this->expectException('coding_exception');
        \mod_observation\session_manager::finish_session($sessionid + 1);
    }

    public function test_submit_no_response() {
        $sessionid = $this->create_session();

        $response = (object)self::VALID_RESPONSE;
        $response->response = null;

        // Try and start session with users who do not exist.
        $this->expectException('coding_exception');
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid1, $response);
    }


}
