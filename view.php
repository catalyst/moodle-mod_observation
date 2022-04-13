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
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = optional_param('id', null, PARAM_INT);// Course module ID.
$observationid = optional_param('obid', null, PARAM_INT);// Observation instance ID.

// Needs at least one of the two optional parameters.
if (empty($id) && empty($observationid) ) {
    throw new moodle_exception('missingparameter');
}

// Access via observation instance id.
if (!empty($observationid) ) {
    list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($observationid);
}

// Access via course module id.
if (!empty($id)) {
    list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_cmid($id);
}

require_login($course, true, $cm);

// If user can perform observations, redirect to the 'observer' view.
if (has_capability('mod/observation:performobservation', $PAGE->context)) {
    $observerurl = new moodle_url('/mod/observation/observer.php', array('id' => $observation->id));
    redirect($observerurl);
    die;
}

// Else, redirect user to 'observee' view (default).
$observeeurl = new moodle_url('/mod/observation/observee.php', array('id' => $observation->id));
redirect($observeeurl);
