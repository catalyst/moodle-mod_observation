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
 * This file contains functions to get various observation data objects
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Gets observation, course and coursemodule from course module ID
  * @param int $cmid Course module ID
  * @param string $tablename database table name
  * @return list List containing the observation instance, course and coursemodule (in that order)
  */
function get_observation_course_cm_from_cmid(int $cmid, string $tablename='observation') {
    global $DB;
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, $tablename);
    $observationid = $cm->instance;
    if (!$observation = $DB->get_record($tablename, array('id' => $observationid))) {
        throw new moodle_exception('moduleinstancedoesnotexist');
    }
    return list($observation, $course, $cm) = array($observation, $course, $cm);
}

 /**
  * Gets observation, course and coursemodule from observation instance ID
  * @param int $obid Observation instance ID
  * @param string $tablename Database table name
  * @return list List containing the observation instance, course and coursemodule (in that order)
  */
function get_observation_course_cm_from_obid(int $obid, string $tablename='observation') {
    global $DB;
    if (!$cm = get_coursemodule_from_instance($tablename, $obid)) {
        throw new moodle_exception('invalidcoursemodule');
    }
    list($course, $cm) = get_course_and_cm_from_cmid($cm->id, 'observation');
    if (!$observation = $DB->get_record($tablename, array('id' => $obid))) {
        throw new moodle_exception('moduleinstancedoesnotexist');
    }
    return list($observation, $course, $cm) = array($observation, $course, $cm);
}