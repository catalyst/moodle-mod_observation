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
 * Generates data in the DB right after tables created (install.xml).
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Function run on install of the database.
  * Genereates response type mappings
  */
function xmldb_observation_install() {
    global $DB;

    // Generate res_type mappings.
    $tablename = 'observation_res_type_map';
    $mappings = [
        ['res_type' => 0, 'lang_string' => 'textinputtype'],
        ['res_type' => 1, 'lang_string' => 'passfailtype'],
        ['res_type' => 2, 'lang_string' => 'evidencetype']
    ];
    $DB->insert_records($tablename, $mappings);
}
