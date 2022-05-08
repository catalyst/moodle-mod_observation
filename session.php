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
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Get data from the session ID.
$sessionid = required_param('sessionid', PARAM_INT);
$sessiondata = \mod_observation\session_manager::get_session_info($sessionid);
$pointid = optional_param('pointid', null, PARAM_INT);
$confirmcancel = optional_param('confirmcancel', null, PARAM_BOOL);
$confirmsubmit = optional_param('confirmsubmit', null, PARAM_BOOL);
$mode = optional_param('mode', null, PARAM_TEXT);

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
        redirect(
            new moodle_url('sessionview.php', ['id' => $obid]),
            get_string('noobservationpoints', 'observation'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    } else {
        redirect(new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $firstpoint->point_id]));
    }
    return;
}

// Load point selector form.
$selectoroptions = [];

foreach ($observationpoints as $point) {
    $selectoroptions[$point->point_id] = $point->list_order . '. ' . $point->title;
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
$draftitemid = file_get_submitted_draft_itemid('response');
file_prepare_draft_area($draftitemid, $PAGE->context->id, 'mod_observation', 'response', $draftitemid);
$formprefill['sessionid'] = $sessionid;

if (is_null($formprefill['file_size'])) {
    $formprefill['file_size'] = 500; // 500MB.
}
$formprefill['file_size'] = $formprefill['file_size'] * 1048576; // MB in binary.

$markingform = new \mod_observation\form\pointmarking(null, $formprefill);

// If point marking form was submitted.
if ($fromform = $markingform->get_data()) {
    // Save or Save and Next point button pressed.
    $responseid = \mod_observation\observation_manager::submit_point_response($sessionid, $pointid, $fromform);

    // If response type is file, save draft files to storage.
    if ($observationpoints[$pointid]->res_type == 2) {
        // Save response files using response ID.
        file_save_draft_area_files($draftitemid, $PAGE->context->id, 'mod_observation', 'response', $responseid);

        // Update the 'response' to be the the new file ID.
        $DB->update_record('observation_point_responses', ['id' => $responseid, 'response' => $responseid]);
    }

    // If save and continue button pressed, find next observation point to redirect to.
    if (!is_null($fromform->saveandnext)) {
        $allpointids = array_column($observationpoints, 'point_id');
        $index = array_search($pointid, $allpointids);

        // Only continue if there is a point to continue to.
        if (count($allpointids) > $index + 1) {
            $pointid = $allpointids[$index + 1];
        } else {
            // No more points to process - redirect to the session summary/submission screen.
            redirect(
                new moodle_url('sessionsummary.php', ['sessionid' => $sessionid]),
                get_string('responsesaved', 'observation'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            return;
        }
    }

    redirect(
        new moodle_url('session.php', ['sessionid' => $sessionid, 'pointid' => $pointid]),
        get_string('responsesaved', 'observation'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
    return;
}

// Check confirm dialogs.

// If confirmation approved proceed to cancel session.
if ($confirmcancel === 1) {
    // Cancel Session.
    \mod_observation\session_manager::cancel_session($sessionid);

    redirect(
        new moodle_url('sessionview.php', ['id' => $obid]),
        get_string('successfulcancel', 'observation'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// If confirmation rejected proceed back to session.php.
if ($confirmcancel === 0) {
    // Return to session page.
    redirect(new moodle_url('/mod/observation/session.php', array('sessionid' => $sessionid)));
}

// If confirmation approved proceed to submit session.
if ($confirmsubmit === 1) {
    // Redirect to final session page (summary, add final comments, etc.).
    redirect(new moodle_url('/mod/observation/sessionsummary.php', array('sessionid' => $sessionid)));
}

// If confirmation rejected proceed back to session.php.
if ($confirmsubmit === 0) {
    // Return to session page.
    redirect(new moodle_url('/mod/observation/session.php', array('sessionid' => $sessionid)));
}

// Render page.
$pageurl = new moodle_url('/mod/observation/session.php', array(
    'sessionid' => $sessionid, 'pointid' => $pointid
));
$pageurl->out(false);
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname . ': ' . $observation->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('markingobservation', 'observation'), 2);

if ($markingform->no_submit_button_pressed()) {
    $fromform = $markingform->get_submitted_data();

    // Cancel / abandon observation button pressed.
    if (!is_null($fromform->abandonbutton)) {
        // If no confirmation yet, display confirmation dialog.
        if ($confirmcancel === null) {
            echo $OUTPUT->confirm(
                get_string('confirmcancel', 'observation'),
                new moodle_url($pageurl, ['confirmcancel' => true]),
                new moodle_url($pageurl, ['confirmcancel' => false])
            );
        }
    }

    // Submit observation button pressed.
    if (!is_null($fromform->submitobservation)) {

        // If no confirmation yet, display confirmation dialog.
        if ($confirmsubmit === null) {
            echo $OUTPUT->confirm(
                get_string('confirmsubmit', 'observation'),
                new moodle_url($pageurl, ['confirmsubmit' => true]),
                new moodle_url($pageurl, ['confirmsubmit' => false])
            );
        }
    }
    return;
}

// Render forms.
echo $OUTPUT->container_start('p-3 mb-2 bg-secondary');
$selectorform->display();
echo $OUTPUT->container_end();

$markingform->display();

echo $OUTPUT->footer();
