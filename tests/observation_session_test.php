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
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Unit tests for the observation session manager class.
 *
 * @package    mod_observation
 * @category   test
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
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
        'grade_given' => 3,
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
        $observee2 = $this->getDataGenerator()->create_user();

        // Enrol all users to course with their roles.
        $this->getDataGenerator()->enrol_user($coordinator->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($observer->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($observee->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($observee2->id, $course->id, 'student');

        // Create two observation points.
        $data = self::VALID_POINT_DATA;
        $data['obs_id'] = $obinstance->id;

        $pointid1 = \mod_observation\observation_manager::modify_observation_point($data, true);
        $pointid2 = \mod_observation\observation_manager::modify_observation_point($data, true);

        // Add data to the context.
        $this->course = $course;
        $this->instance = $obinstance;

        $this->coordinator = $coordinator;
        $this->observer = $observer;
        $this->observee = $observee;
        $this->observee2 = $observee2;

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

        $this->assertTrue(is_int($sessionid));

        // Get session info.
        $sessioninfo = \mod_observation\session_manager::get_session_info($sessionid);
        $this->assertEquals($obid, $sessioninfo['obid']);
        $this->assertEquals('inprogress', $sessioninfo['state']);

        // Get session data.
        $session = \mod_observation\session_manager::get_session_data($sessionid);
        $sessiondata = $session['data'];

        // Ensure the observation points are returned.
        $sessiondataids = array_column($sessiondata, 'point_id');

        $this->assertTrue(in_array($this->pointid1, $sessiondataids));
        $this->assertTrue(in_array($this->pointid2, $sessiondataids));

        // Get incomplete points (should be both of them.).
        $incomplete = \mod_observation\session_manager::get_incomplete_points($sessionid);
        $incompleteids = array_column($incomplete, 'point_id');

        $this->assertTrue(in_array($this->pointid1, $incompleteids));
        $this->assertTrue(in_array($this->pointid1, $incompleteids));

        // Current grade should be zero.
        $currentgrade = \mod_observation\session_manager::calculate_grade($sessionid);

        $this->assertEquals(0, $currentgrade['total']);
        $this->assertEquals(10, $currentgrade['max']);

        $response = (object)self::VALID_RESPONSE;

        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid1, $response);

        // Get incomplete points (should only be the second).
        $incomplete = \mod_observation\session_manager::get_incomplete_points($sessionid);
        $incompleteids = array_column($incomplete, 'point_id');

        $this->assertFalse(in_array($this->pointid1, $incompleteids));
        $this->assertTrue(in_array($this->pointid2, $incompleteids));

        // Current grade should be 3.
        $currentgrade = \mod_observation\session_manager::calculate_grade($sessionid);
        $this->assertEquals(3, $currentgrade['total']);

        // Submit response to second point.
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid2, $response);

        // Get incomplete points (should now be empty).
        $incomplete = \mod_observation\session_manager::get_incomplete_points($sessionid);
        $this->assertEmpty($incomplete);

        // Current grade should be 6.
        $currentgrade = \mod_observation\session_manager::calculate_grade($sessionid);
        $this->assertEquals(6, $currentgrade['total']);

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

        $this->expectException('moodle_exception');
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


    /**
     * Tests a particular edge case where the observation points are
     * modified before a session is submitted, causing the grade given to be invalid.
     */
    public function test_modified_before_submit() {
        // Start a session as normal.
        $obid = $this->instance->id;
        $sessionid = $this->create_session();

        // Submit valid data to the two points created in setup.
        $response = (object)self::VALID_RESPONSE;
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid1, $response);
        \mod_observation\observation_manager::submit_point_response($sessionid, $this->pointid2, $response);

        $responses = \mod_observation\observation_manager::get_points_and_responses($obid, $sessionid);

        // Ensure max grades and grades given are set.
        $this->assertEquals([5, 5], array_column($responses, 'max_grade'));
        $this->assertEquals([3, 3], array_column($responses, 'grade_given'));

        // Modify the first observation point to have a lower max_grade than the grade given previously.
        $lowermaxresponse = $response;
        $lowermaxresponse->max_grade = 1;
        $lowermaxresponse->id = $this->pointid1;
        \mod_observation\observation_manager::modify_observation_point($lowermaxresponse);

        // Ensure the max grades are now set correctly.
        $responses = \mod_observation\observation_manager::get_points_and_responses($obid, $sessionid);
        $this->assertEquals([1, 5], array_column($responses, 'max_grade'));

        // Try and submit a session.
        $this->expectException('moodle_exception');
        \mod_observation\session_manager::finish_session($sessionid);
    }

    /**
     * Tests to ensure the lockout is working properly.
     */
    public function test_start_session_lockout() {
        $obid = $this->instance->id;
        $sessionid = $this->create_session();

        // Try to start another one quickly, should throw exception.
        $this->expectException('moodle_exception');
        $this->create_session();
    }

    /**
     * Tests to ensure lockout DOES NOT lockout when using different observees.
     */
    public function test_start_session_lockout_neg() {
        $obid = $this->instance->id;
        $oberid = $this->observer->id;

        // Should not throw any exceptions.
        \mod_observation\session_manager::start_session($obid, $oberid, $this->observee->id);
        \mod_observation\session_manager::start_session($obid, $oberid, $this->observee2->id);
    }
}
