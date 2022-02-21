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
 * Unit tests for the block_community implementation of the privacy API.
 *
 * @package   mod_observation
 * @copyright  Catalyst IT
 * @author Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;
use \mod_observation\privacy\provider;

/**
 * Unit tests for the mod_observation implementation of the privacy API.
 *
 * @copyright  Catalyst IT
 * @author Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_observation_privacy_testcase extends \core_privacy\tests\provider_testcase {
    /**
     * Set up for tests.
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
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_observation');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(6, $itemcollection);
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $this->setUser($this->observee);

        // Enrol user into timeslot.
        $tsdata = [
            'start_time' => time(),
            'duration' => 5,
            'obs_id' => $this->instance->id,
            'observer_id' => $this->observer->id,
            'observee_id' => $this->observee->id
        ];

        \mod_observation\timeslot_manager::modify_time_slot($tsdata);

        // Start a session for this timeslot with this user.
        \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $this->observee->id);

        $contextlist = provider::get_contexts_for_userid($this->observee->id);
        $contexts = $contextlist->get_contexts();

        // Should only be a single context.
        $this->assertCount(1, $contexts);
    }

    /**
     * Test that only users within a course context are fetched.
     */
    public function test_get_users_in_context() {
        // Create an unrelated third user.
        $unrelated = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($unrelated->id, $this->course->id, 'student');

        // Enrol user into timeslot.
        $tsdata = [
            'start_time' => time(),
            'duration' => 5,
            'obs_id' => $this->instance->id,
            'observer_id' => $this->observer->id,
            'observee_id' => $this->observee->id
        ];

        \mod_observation\timeslot_manager::modify_time_slot($tsdata);

        // Start a session for this timeslot with this user.
        \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $this->observee->id);

        // Ensure the two users are returned.
        $context = \context_module::instance($this->instance->cmid);
        $userlist = new \core_privacy\local\request\userlist($context, 'mod_observation');
        provider::get_users_in_context($userlist);

        // Two users - observer and observee (should not include third unrelated user!).
        $this->assertCount(2, $userlist);
    }

    /**
     * Ensure that user data for specific users is deleted from a specified context.
     */
    public function test_delete_data_for_users() {
        global $DB;

        $unrelated = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($unrelated->id, $this->course->id, 'student');

        // Enrol user into timeslot.
        $tsdata = [
            'start_time' => time(),
            'duration' => 5,
            'obs_id' => $this->instance->id,
            'observer_id' => $this->observer->id,
            'observee_id' => $this->observee->id
        ];

        \mod_observation\timeslot_manager::modify_time_slot($tsdata);

        // Start a session for this timeslot with this user.
        $sessid = \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $this->observee->id);

        // Start a session with an unrelated user.
        $sess2id = \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $unrelated->id);

        // Create an observation point.
        $obpoint = \mod_observation\observation_manager::modify_observation_point((object)[
            'obs_id' => $this->instance->id,
            'title' => 'point1',
            'max_grade' => 5,
            'res_type' => \mod_observation\observation_manager::INPUT_TEXT
        ], true);

        // Make a response to the session.
        \mod_observation\observation_manager::submit_point_response($sessid, $obpoint, (object)[
            'grade_given' => 5,
            'response' => 'response text',
            'ex_comment' => 'extra comment'
        ]);

        // Ensure they have been created properly.
        $timeslots = $DB->get_records('observation_timeslots', ['obs_id' => $this->instance->id]);
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $this->instance->id]);
        $responses = $DB->get_records('observation_point_responses', ['obs_ses_id' => $sessid]);

        $this->assertCount(1, $timeslots);
        $this->assertCount(2, $sessions);
        $this->assertCount(1, $responses);

        // Try to delete the observees user data.
        $context = \context_module::instance($this->instance->cmid);
        $approveduserlist = new \core_privacy\local\request\approved_userlist($context, 'mod_observation', [$this->observee->id]);
        provider::delete_data_for_users($approveduserlist);

        // Unrelated users data should be unaffected.
        $timeslots = $DB->get_records('observation_timeslots', ['obs_id' => $this->instance->id]);
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $this->instance->id]);
        $responses = $DB->get_records('observation_point_responses', ['obs_ses_id' => $sessid]);

        $this->assertCount(0, $timeslots);
        $this->assertCount(1, $sessions);
        $this->assertCount(0, $responses);
    }

    /**
     * Ensure that all user data is deleted for a specific context.
     */
    public function test_delete_data_for_user() {
        global $DB;

        // Enrol user into timeslot.
        $tsdata = [
            'start_time' => time(),
            'duration' => 5,
            'obs_id' => $this->instance->id,
            'observer_id' => $this->observer->id,
            'observee_id' => $this->observee->id
        ];

        \mod_observation\timeslot_manager::modify_time_slot($tsdata);

        // Start a session for this timeslot with this user.
        $sessid = \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $this->observee->id);

        // Create an observation point.
        $obpoint = \mod_observation\observation_manager::modify_observation_point((object)[
            'obs_id' => $this->instance->id,
            'title' => 'point1',
            'max_grade' => 5,
            'res_type' => \mod_observation\observation_manager::INPUT_TEXT
        ], true);

        // Make a response to the session.
        \mod_observation\observation_manager::submit_point_response($sessid, $obpoint, (object)[
            'grade_given' => 5,
            'response' => 'response text',
            'ex_comment' => 'extra comment'
        ]);

        // Ensure they have been created properly.
        $timeslots = $DB->get_records('observation_timeslots', ['obs_id' => $this->instance->id]);
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $this->instance->id]);
        $responses = $DB->get_records('observation_point_responses', ['obs_ses_id' => $sessid]);

        $this->assertCount(1, $timeslots);
        $this->assertCount(1, $sessions);
        $this->assertCount(1, $responses);

        // Delete the users data in this context.
        $context = \context_module::instance($this->instance->cmid);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($this->observee->id),
            'mod-observation',
            [$context->id]
        );
        provider::delete_data_for_user($approvedcontextlist);

        // Ensure they have been deleted properly.
        $timeslots = $DB->get_records('observation_timeslots', ['obs_id' => $this->instance->id]);
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $this->instance->id]);
        $responses = $DB->get_records('observation_point_responses', ['obs_ses_id' => $sessid]);

        $this->assertCount(0, $timeslots);
        $this->assertCount(0, $sessions);
        $this->assertCount(0, $responses);
    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Setup an observation point.
        $obpoint = \mod_observation\observation_manager::modify_observation_point((object)[
            'obs_id' => $this->instance->id,
            'title' => 'point1',
            'max_grade' => 5,
            'res_type' => \mod_observation\observation_manager::INPUT_TEXT
        ], true);

        // Create additional user.
        $additionaluser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($additionaluser->id, $this->course->id, 'student');

        $users = [$additionaluser->id, $this->observee->id];

        // Generate some data for all of the users.
        foreach ($users as $userid) {
            // Signup to timeslot.
            $tsdata = [
                'start_time' => time(),
                'duration' => 5,
                'obs_id' => $this->instance->id,
                'observer_id' => $this->observer->id,
                'observee_id' => $userid
            ];

            \mod_observation\timeslot_manager::modify_time_slot($tsdata);

            // Start session for user..
            $sessid = \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $userid);

            // Make a response to the session.
            \mod_observation\observation_manager::submit_point_response($sessid, $obpoint, (object)[
                'grade_given' => 5,
                'response' => 'response text',
                'ex_comment' => 'extra comment'
            ]);
        }

        // Ensure data has been created properly.
        $timeslots = $DB->get_records('observation_timeslots', ['obs_id' => $this->instance->id]);
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $this->instance->id]);
        $responses = $DB->get_records('observation_point_responses');

        $this->assertCount(1 * count($users), $timeslots);
        $this->assertCount(1 * count($users), $sessions);
        $this->assertCount(1 * count($users), $responses);

        // Delete the data for all users in the context.
        $context = \context_module::instance($this->instance->cmid);
        provider::delete_data_for_all_users_in_context($context);

        // Ensure data has been deleted properly.
        $timeslots = $DB->get_records('observation_timeslots', ['obs_id' => $this->instance->id]);
        $sessions = $DB->get_records('observation_sessions', ['obs_id' => $this->instance->id]);
        $responses = $DB->get_records('observation_point_responses');

        $this->assertCount(0, $timeslots);
        $this->assertCount(0, $sessions);
        $this->assertCount(0, $responses);
    }

    public function test_export_user_data() {
        // Enrol user into timeslot.
        $tsdata = [
            'start_time' => time(),
            'duration' => 5,
            'obs_id' => $this->instance->id,
            'observer_id' => $this->observer->id,
            'observee_id' => $this->observee->id
        ];

        \mod_observation\timeslot_manager::modify_time_slot($tsdata);

        // Start a session for this timeslot with this user.
        $sessid = \mod_observation\session_manager::start_session($this->instance->id, $this->observer->id, $this->observee->id);

        // Create an observation point.
        $obpoint = \mod_observation\observation_manager::modify_observation_point((object)[
            'obs_id' => $this->instance->id,
            'title' => 'point1',
            'max_grade' => 5,
            'res_type' => \mod_observation\observation_manager::INPUT_TEXT
        ], true);

        // Make a response to the session to the observation point.
        \mod_observation\observation_manager::submit_point_response($sessid, $obpoint, (object)[
            'grade_given' => 5,
            'response' => 'response text',
            'ex_comment' => 'extra comment'
        ]);

        // Export the user data for this user.
        $cmcontext = context_module::instance($this->instance->cmid);
        $writer = writer::with_context($cmcontext);
        $contextlist = new approved_contextlist($this->observee, 'mod_observation' , [$cmcontext->id]);

        $this->assertCount(1, $contextlist);
        $this->assertFalse($writer->has_any_data());

        // Export user data to context.
        provider::export_user_data($contextlist);

        // Get exported user data from the context.
        $data = writer::with_context($cmcontext)->get_data();

        // Ensure the data is all there.
        $this->assertCount(1, $data->timeslots);
        $this->assertCount(1, $data->sessions);
        $this->assertCount(1, array_values($data->sessions)[0]->responses);
    }
}
