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
 * @copyright 2021 Endurer Solutions Team
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

    if ($oldversion < 2021052524) {
        // Add two columns to observation_points table.
        $table2 = new xmldb_table('observation_points');
        $field1 = new xmldb_field('file_size', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $field2 = new xmldb_field('num_files', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        if (!$dbman->field_exists($table2, $field1)) {
            $dbman->add_field($table2, $field1);
        }
        if (!$dbman->field_exists($table2, $field2)) {
            $dbman->add_field($table2, $field2);
        }

        // Observation savepoint reached.
        upgrade_mod_savepoint(true, 2021052524, 'observation');
    }

    return true;
}
