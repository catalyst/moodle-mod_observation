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
 * This file contains the page view if the user has the capability 'perform_observations'
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

// Get data from the session ID.
$sessionid = required_param('sessionid', PARAM_INT);
$sessiondata = \mod_observation\session_manager::get_session_info($sessionid);

$obid = $sessiondata['obid'];
list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($obid);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

// Load form.
$observationpoints = (array)\mod_observation\session_manager::get_session_data($sessionid);

// TODO turn into array of forms

$obspointforms = [];

foreach($observationpoints as $point) {
    $formprefill = (array)$point;
    $formprefill['sessionid'] = $sessionid;
    $markingform = new \mod_observation\pointmarking_form(null, $formprefill);
    
    array_push($obspointforms, $markingform);

    if ($fromform = $markingform->get_data()) {
        echo print_object($fromform);
        return;
    }    
}

// Render page.
$PAGE->set_url(new moodle_url('/mod/observation/session.php', array('sessionid' => $sessionid)));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('markingobservation', 'observation'), 2);

// Observation point table/list block
echo $OUTPUT->container_start();

foreach($obspointforms as $form) {
    $form->display();
}

echo print_object($observationpoints);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
