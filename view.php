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
 * Page to view an observation module
 *
 * @package mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

global $PAGE;
global $DB;

$id = optional_param('id', 0, PARAM_INT);// Course module ID.
$observationid = optional_param('o', 0, PARAM_INT);// Observation instance ID.

// Can access directly from observation ID or from course module ID.
if ($observationid) {
    // Access directly via observation ID.
    if (!$cm = get_coursemodule_from_instance('observation', $observationid)) {
        throw new moodle_exception('invalidcoursemodule');
    }
    list($course, $cm) = get_course_and_cm_from_cmid($cm->id, 'observation');
} else if ($id) {
    // Access indirectly via course module ID.
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'observation');
    $observationid = $cm->instance;
} else {
    // Neither given, error.
    throw new moodle_exception('missingparameter');
}

require_login($course, true, $cm);

// If user can perform observations, redirect to the 'observer' view.
if (has_capability('mod/observation:performobservation', $PAGE->context)) {
    $observerurl = new moodle_url('/mod/observation/observer.php', array('id' => $observationid));
    redirect($observerurl);
    die;
}

// Else, redirect user to 'observee' view.
$observeeurl = new moodle_url('/mod/observation/observee.php', array('id' => $observationid));
redirect($observeeurl);
die;