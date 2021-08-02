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

$pointid = optional_param('pointid', null, PARAM_INT);

$obid = $sessiondata['obid'];
list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($obid);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

// Get observation points and current responses.
$observationpoints = (array)\mod_observation\observation_manager::get_points_and_responses($obid, $sessionid);

// Redirect to the first observation point if none was provided.
if(is_null($pointid)){
    $firstpoint = empty($observationpoints) ? null : reset($observationpoints);
    if(is_null($firstpoint)){
        // No observation points - redirect back with error message
        redirect(new moodle_url('sessionview.php', ['id' => $obid]), get_string('noobservationpoints', 'observation'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $firstpoint->point_id]));
    }
    return;
}

// Load point selector form.
$selectoroptions = [];

foreach($observationpoints as $point){
    $selectoroptions[$point->point_id] = $point->title.' [id: '.$point->point_id.']';
}

$selectprefill = [
    'pointid' => $pointid,
    'pointid_options' => $selectoroptions,
    'sessionid' => $sessionid,
];
$selectorform = new \mod_observation\pointselector_form(null, $selectprefill);

if($fromform = $selectorform->get_data()) {
    redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $fromform->pointid]));
    return;
}

// Load point marking form.
if(!array_key_exists($pointid, $observationpoints)){
    throw new moodle_exception('listnoitem', 'error', null, $a = 'pointid');
}

$selectedpointdata = $observationpoints[$pointid];

$formprefill = (array)$selectedpointdata;
$formprefill['sessionid'] = $sessionid;
$markingform = new \mod_observation\pointmarking_form(null, $formprefill);

// If point marking form was submitted.
if ($fromform = $markingform->get_data()) {
    if($fromform->submitbutton === get_string('submitobservation', 'observation')){
        // Submit entire observation.
        $sessionstatus = \mod_observation\session_manager::finish_session($sessionid);

        if($sessionstatus === true){
            redirect(new moodle_url('sessionview.php', ['id' => $obid]), get_string('sessioncomplete', 'observation'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $pointid]), $sessionstatus, null, \core\output\notification::NOTIFY_ERROR);
        }

        return;
    } else {
        // Submit only point.
        \mod_observation\observation_manager::submit_point_response($sessionid, $pointid, $fromform);
    }

    // If save and continue button pressed, find next observation point to redirect to.
    if($fromform->submitbutton === get_string('saveandnext', 'observation')){
        $allpointids = array_column($observationpoints, 'point_id');
        $index = array_search($pointid, $allpointids);
        $nextpointid = $allpointids[$index + 1];

        // Only continue if there is a point to continue to.
        if(!is_null($nextpointid)){
            $pointid = $nextpointid;
        } else {
            redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $pointid]), get_string('responsesavedbutcannotcontinue', 'observation'), null, \core\output\notification::NOTIFY_WARNING); 
            return;
        }
    }

    redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $pointid]), get_string('responsesaved', 'observation'), null, \core\output\notification::NOTIFY_SUCCESS);
    return;
}   

// Render page.
$PAGE->set_url(new moodle_url('/mod/observation/session.php', array('sessionid' => $sessionid)));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('markingobservation', 'observation'), 2);

// Observation point table/list block
echo $OUTPUT->container_start();

$selectorform->display();
$markingform->display();

echo print_object($formprefill);

echo print_object($observationpoints);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
