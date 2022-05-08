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
 * Defines DB upgrade behaviour
 *
 * @package mod_observation
 * @copyright Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Updates database tables when version changes.
  * @param int $oldversion Old version number
  */
function xmldb_observation_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021052523) {

        // Define table observation_notifications to be created.
        $table = new xmldb_table('observation_notifications');

        // Adding fields to table observation_notifications.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timeslot_id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time_before', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table observation_notifications.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('timeslot_id', XMLDB_KEY_FOREIGN, ['timeslot_id'], 'observation_timeslots', ['id']);

        // Conditionally launch create table for observation_notifications.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Observation savepoint reached.
        upgrade_mod_savepoint(true, 2021052523, 'observation');
    }

    if ($oldversion < 2021052531) {
        // Add two columns to observation_points table.
        $table2 = new xmldb_table('observation_points');
        $field1 = new xmldb_field('file_size', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        if (!$dbman->field_exists($table2, $field1)) {
            $dbman->add_field($table2, $field1);
        }

        // Add evidence response type to table.
        if (empty($DB->get_record('observation_res_type_map', ['res_type' => 2]))) {
            $DB->insert_record('observation_res_type_map', ['res_type' => 2, 'lang_string' => 'evidencetype']);
        }

        // Observation savepoint reached.
        upgrade_mod_savepoint(true, 2021052531, 'observation');
    }

    if ($oldversion < 2021052533) {
        $storage = get_file_storage();

        // Get all files for mod_observation.
        $filerecords = $DB->get_records('files', ['component' => 'mod_observation']);
        $storage = get_file_storage();

        foreach ($filerecords as $file) {
            // Find files using the old filearea naming scheme.
            if ($file->filearea != 'response') {
                // Decode the $pointid and $sessionid.
                $pointid = (int)str_replace('response', '', $file->filearea);
                $sessionid = (int)$file->itemid;

                // Get the response  that this file is for.
                $pointresponse = $DB->get_record('observation_point_responses',
                    ['obs_pt_id' => $pointid, 'obs_ses_id' => $sessionid]);
                $newfileitemid = $pointresponse->id;

                // Move to the new file area and record using response ID as the 'item' id.
                $changes = [
                    'filearea' => 'response',
                    'itemid' => $newfileitemid
                ];
                $migratedfile = $storage->create_file_from_storedfile($changes, $file->id);

                // Update the response to use the new file item ID.
                $DB->update_record('observation_point_responses', ['id' => $pointresponse->id, 'response' => $newfileitemid]);
            }
        }

        // Observation savepoint reached.
        upgrade_mod_savepoint(true, 2021052533, 'observation');
    }

    return true;
}
