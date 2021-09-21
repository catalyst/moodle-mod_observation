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
        $table = new xmldb_table('observation_notifications');

        // Table fields.
        $id = new xmldb_field('id', XMLDB_TYPE_INTEGER, 20, XMLDB_UNSIGNED, true, true);
        $timeslotid = new xmldb_field('timeslot_id', XMLDB_TYPE_INTEGER, 20, XMLDB_UNSIGNED, true, false);
        $notify = new xmldb_field('time_before', XMLDB_TYPE_INTEGER, 20, XMLDB_UNSIGNED, true, false);

        // Table keys.
        $idpkey = new xmldb_key('id', XMLDB_KEY_PRIMARY, ['id']);
        $timeslotidfkey = new xmldb_key('timeslot_id', XMLDB_KEY_FOREIGN, ['timeslot_id'], 'observation_timeslots', 'id');

        $table->addField($id);
        $table->addField($timeslotid);
        $table->addField($notify);

        $table->addKey($idpkey);
        $table->addKey($timeslotidfkey);

        $dbman->create_table($table);

        upgrade_mod_savepoint(true, 2021052523, 'observation');
    }

    return true;
}
