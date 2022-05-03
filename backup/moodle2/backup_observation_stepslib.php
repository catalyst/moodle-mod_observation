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
 * This file contains the backup structure code for mod_observation
 *
 * @package     mod_observation
 * @copyright   Catalyst IT Australia
 * @author      Matthew Hilton
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to backup one observation activity
 */
class backup_observation_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define structure of backup
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $observation = new backup_nested_element('observation', ['id'], [
            'name',
            'intro',
            'timemodified',
            'observer_ins',
            'observer_ins_f',
            'observee_ins',
            'observee_ins_f',
            'students_self_unregister',
            'marking_type'
        ]);

        $notifications = new backup_nested_element('notifications');

        $notification = new backup_nested_element('notification', ['id'], [
            'timeslot_id',
            'time_before'
        ]);

        $points = new backup_nested_element('points');

        $point = new backup_nested_element('point', ['id'], [
            'obs_id',
            'title',
            'list_order',
            'ins',
            'ins_f',
            'max_grade',
            'res_type',
            'file_size'
        ]);

        $responses = new backup_nested_element('responses');

        $response = new backup_nested_element('response', ['id'], [
            'obs_pt_id',
            'obs_ses_id',
            'grade_given',
            'response',
            'ex_comment',
            'timecreated',
            'timemodified'
        ]);

        $sessions = new backup_nested_element('sessions');

        $session = new backup_nested_element('session', ['id'], [
            'obs_id',
            'observee_id',
            'observer_id',
            'state',
            'start_time',
            'finish_time',
            'ex_comment'
        ]);

        $timeslots = new backup_nested_element('timeslots');

        $timeslot = new backup_nested_element('timeslot', ['id'], [
            'obs_id',
            'start_time',
            'duration',
            'observer_id',
            'observee_id',
            'observer_event_id',
            'observee_event_id'
        ]);

        // Timeslots tree.
        $observation->add_child($timeslots);
        $timeslots->add_child($timeslot);
        $timeslot->add_child($notifications);
        $notifications->add_child($notification);

        // Points tree.
        $observation->add_child($points);
        $points->add_child($point);

        // Sessions tree.
        $observation->add_child($sessions);
        $sessions->add_child($session);

        // Responses tree.
        $session->add_child($responses);
        $responses->add_child($response);

        // Set table sources.
        $observation->set_source_table('observation', ['id' => backup::VAR_ACTIVITYID]);
        $point->set_source_table('observation_points', ['obs_id' => backup::VAR_PARENTID]);

        // These are only possible if user info is included.
        if ($userinfo || PHPUNIT_TEST) {
            $response->set_source_table('observation_point_responses', ['obs_ses_id' => backup::VAR_PARENTID]);
            $timeslot->set_source_table('observation_timeslots', ['obs_id' => backup::VAR_PARENTID]);
            $notification->set_source_table('observation_notifications', ['timeslot_id' => backup::VAR_PARENTID]);
            $session->set_source_table('observation_sessions', ['obs_id' => backup::VAR_PARENTID]);
        }

        // User ID annotations.
        $timeslot->annotate_ids('user', 'observee_id');
        $timeslot->annotate_ids('user', 'observer_id');

        $session->annotate_ids('user', 'observee_id');
        $session->annotate_ids('user', 'observer_id');

        // Annotate files.
        $response->annotate_files('mod_observation', 'response', 'id');

        return $this->prepare_activity_structure($observation);
    }
}
