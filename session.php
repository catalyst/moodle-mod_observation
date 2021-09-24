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
if (is_null($pointid)) {
    $firstpoint = empty($observationpoints) ? null : reset($observationpoints);
    if (is_null($firstpoint)) {
        // No observation points - redirect back with error message.
        redirect(new moodle_url('sessionview.php', ['id' => $obid]), get_string('noobservationpoints', 'observation'),
            null, \core\output\notification::NOTIFY_ERROR);
    } else {
        redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $firstpoint->point_id]));
    }
    return;
}

// Load point selector form.
$selectoroptions = [];

foreach ($observationpoints as $point) {
    $selectoroptions[$point->point_id] = $point->list_order.'. '.$point->title;
}

$selectprefill = [
    'pointid' => $pointid,
    'pointid_options' => $selectoroptions,
    'sessionid' => $sessionid,
];
$selectorform = new \mod_observation\form\pointselector(null, $selectprefill);

if ($fromform = $selectorform->get_data()) {
    redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $fromform->pointid]));
    return;
}

// Load point marking form.
if (!array_key_exists($pointid, $observationpoints)) {
    throw new moodle_exception('listnoitem', 'error', null, $a = 'pointid');
}

$selectedpointdata = $observationpoints[$pointid];

$formprefill = (array)$selectedpointdata;
$formprefill['sessionid'] = $sessionid;
$markingform = new \mod_observation\form\pointmarking(null, $formprefill);

if ($markingform->no_submit_button_pressed()) {
    $fromform = $markingform->get_submitted_data();

    // Cancel / abandon observation button pressed.
    if (!is_null($fromform->abandonbutton)) {
        \mod_observation\session_manager::cancel_session($sessionid);
        redirect(new moodle_url('sessionview.php', ['id' => $obid]), get_string('successfulcancel', 'observation'),
            null, \core\output\notification::NOTIFY_SUCCESS);
        return;
    }

    // Submit observation button pressed.
    if (!is_null($fromform->submitobservation)) {
        // Redirect to final session page (summary, add final comments, etc.).
        redirect(new moodle_url('sessionsummary.php', ['sessionid' => $sessionid]));
        return;
    }

    return;
}

// If point marking form was submitted.
if ($fromform = $markingform->get_data()) {

    // Save submitted image
    $draftitemid = file_get_submitted_draft_itemid('response');
    file_save_draft_area_files($draftitemid, $PAGE->context->id, 'mod_observation', 'response', $pointid); // context may be fine, $obs_id is fine
    // component "user" (observation) and filearea "draft" (response)
    // ($contextid = 5, 'user', 'draft', $item->response)

    // Save or Save and Next point button pressed.
    \mod_observation\observation_manager::submit_point_response($sessionid, $pointid, $fromform);

    // If save and continue button pressed, find next observation point to redirect to.
    if (!is_null($fromform->saveandnext)) {
        $allpointids = array_column($observationpoints, 'point_id');
        $index = array_search($pointid, $allpointids);
        $nextpointid = $allpointids[$index + 1];

        // Only continue if there is a point to continue to.
        if (!is_null($nextpointid)) {
            $pointid = $nextpointid;
        } else {
            // No more points to process - redirect to the session summary/submission screen.
            redirect(new moodle_url('sessionsummary.php', ['sessionid' => $sessionid]), get_string('responsesaved', 'observation'),
                null, \core\output\notification::NOTIFY_SUCCESS);
            return;
        }
    }

    redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $pointid]),
        get_string('responsesaved', 'observation'), null, \core\output\notification::NOTIFY_SUCCESS);
    return;
}

// Render page.
$PAGE->set_url(new moodle_url('/mod/observation/session.php', array('sessionid' => $sessionid)));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('markingobservation', 'observation'), 2);

// Render forms.
echo $OUTPUT->container_start('p-3 mb-2 bg-secondary');
$selectorform->display();
echo $OUTPUT->container_end();

$markingform->display();

echo $OUTPUT->footer();
