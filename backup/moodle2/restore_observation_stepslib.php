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
 * This file contains the restore structure code for mod_observation
 *
 * @package     mod_observation
 * @copyright   Catalyst IT Australia
 * @author      Matthew Hilton
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one observation activity
 */
class restore_observation_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure of restore
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $paths = [];

        $paths[] = new restore_path_element('observation', '/activity/observation');
        $paths[] = new restore_path_element('point', '/activity/observation/points/point');

        $userinfo = $this->get_setting_value('userinfo');

        if ($userinfo || PHPUNIT_TEST) {
            $paths[] = new restore_path_element('timeslot', '/activity/observation/timeslots/timeslot');
            $paths[] = new restore_path_element('notification',
                '/activity/observation/timeslots/timeslot/notifications/notification');
            $paths[] = new restore_path_element('session', '/activity/observation/sessions/session');
            $paths[] = new restore_path_element('response', '/activity/observation/sessions/session/responses/response');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process observation (activity) itself
     * @param array $data data from backup
     */
    protected function process_observation($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('observation', $data);

        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process notitifications
     * @param array $data data from backup
     */
    protected function process_notification($data) {
        global $DB;
        $data = (object)$data;
        $oldnotificationid = $data->id;
        unset($data->id);

        $data->timeslot_id = $this->get_new_parentid('timeslot');
        $newnotificationid = $DB->insert_record('observation_notifications', $data);
        $this->set_mapping('notification', $oldnotificationid, $newnotificationid);
    }

    /**
     * Process observation point responses
     * @param array $data data from backup
     */
    protected function process_response($data) {
        global $DB;
        $data = (object)$data;

        $oldresponseid = $data->id;
        unset($data->id);
        $data->obs_pt_id = $this->get_mappingid('point', $data->obs_pt_id);
        $data->obs_ses_id = $this->get_new_parentid('session');

        $newresponseid = $DB->insert_record('observation_point_responses', $data);
        $this->set_mapping('response', $oldresponseid, $newresponseid);
    }

    /**
     * Process observation sessions
     * @param array $data data from backup
     */
    protected function process_session($data) {
        global $DB;
        $data = (object)$data;
        $oldsessionid = $data->id;
        unset($data->id);

        $data->obs_id = $this->get_new_parentid('observation');
        $data->observer_id = $this->get_mappingid('user', $data->observer_id, -1);
        $data->observee_id = $this->get_mappingid('user', $data->observee_id, -1);

        $newsessionid = $DB->insert_record('observation_sessions', $data);
        $this->set_mapping('session', $oldsessionid, $newsessionid);
    }

    /**
     * Process timeslots
     * @param array $data data from backup
     */
    protected function process_timeslot($data) {
        global $DB;
        $data = (object)$data;

        $oldtimeslotid = $data->id;
        unset($data->id);
        $data->obs_id = $this->get_new_parentid('observation');
        $data->observer_id = $this->get_mappingid('user', $data->observer_id, -1);
        $data->observee_id = $this->get_mappingid('user', $data->observee_id, null);

        $newtimeslotid = $DB->insert_record('observation_timeslots', $data);
        $this->set_mapping('timeslot', $oldtimeslotid, $newtimeslotid);
    }

    /**
     * Process observation points
     * @param array $data data from backup
     */
    protected function process_point($data) {
        global $DB;
        $data = (object)$data;

        $oldpointid = $data->id;
        unset($data->id);
        $data->obs_id = $this->get_new_parentid('observation');

        $newpointid = $DB->insert_record('observation_points', $data);
        $this->set_mapping('point', $oldpointid, $newpointid);
    }

    /**
     * Run after executing, process file mappings.
     */
    protected function after_execute() {
        global $DB;

        // Get all files for responses from their fileareas.
        $this->add_related_files('mod_observation', 'response', null);

        // Remap the observation point responses to the restored files.
        $contextid = $this->get_task()->get_contextid();

        $fs = get_file_storage();

        // Get all the files in this context that were just restored.
        $restoredfiles = $DB->get_records('files', ['component' => 'mod_observation', 'contextid' => $contextid]);

        foreach ($restoredfiles as $file) {
            // If the item ID has a response mapping, update it's item ID and the response to the new file object.
            $olditemid = $file->itemid;
            $itemidmapping = $this->get_mapping('response', $olditemid, null);
            $newitemid = !empty($itemidmapping) ? $itemidmapping->newitemid : null;

            if (!empty($newitemid) && $file->filename != '.') {
                // Move the file by creating a copy with the correct item ID and deleting the old one.
                $newfilerecord = $file;
                $newfilerecord->itemid = $newitemid;

                $fs->create_file_from_storedfile($newfilerecord, $file->id);
                $fs->delete_area_files($contextid, 'mod_observation', 'response', $olditemid);

                // Update the point respose to point to the new file.
                $DB->update_record('observation_point_responses', ['id' => $newitemid, 'response' => $newitemid]);
            }
        }
    }
}
