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

$id = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);
$slotid = optional_param('slotid', null, PARAM_INT);
$filter = optional_param('filter', 0, PARAM_INT);

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

$pageurl = new moodle_url('/mod/observation/sessionview.php', ['id' => $id]);

// If actions is in URL args, action button on timeslots table was pressed.
if ($action !== null && $slotid !== null) {
    // Get timeslot data.
    $timeslot = \mod_observation\timeslot_manager::get_existing_slot_data($id, $slotid);

    if ($timeslot->observee_id === null) {
        throw new \coding_exception("No observee assigned to this timeslot. Cannot start session.");
    }

    // Start session and redirect.
    $sessionid = \mod_observation\session_manager::start_session($timeslot->obs_id, $USER->id, $timeslot->observee_id);
    redirect(new moodle_url('session.php', ['sessionid' => $sessionid]));
}

$upcomingprefill = ['id' => $id, 'current_filter' => $filter];
$upcomingfilterform = new \mod_observation\upcomingfilter_form(null, $upcomingprefill);

// Redirect back to $pageurl if cancelled, removing the filter parameter.
if ($upcomingfilterform->is_cancelled()) {
    redirect($pageurl);
}

if ($fromform = $upcomingfilterform->get_data()) {
    // Redirect, putting the filter into the URL args.
    $filteramount = $fromform->interval_amount * (int) $fromform->interval_multiplier;
    redirect(new moodle_url('/mod/observation/sessionview.php', ['id' => $id, 'filter' => $filteramount]));
}

$startsessionformprefill = [
    'id' => $id,
    'observerid' => $USER->id
];
$startsessionform = new \mod_observation\startsession_form(null, $startsessionformprefill);

// If start session form was submitted, call function to start session.
if ($fromform = $startsessionform->get_data()) {
    $sessionid = \mod_observation\session_manager::start_session($fromform->id, $USER->id, $fromform->observeeid);
    redirect(new moodle_url('session.php', ['sessionid' => $sessionid]));
}

$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('observationsessions', 'observation'), 2);

// See upcoming assigned timeslots table and form.
echo $OUTPUT->heading(get_string('upcomingtimeslots', 'observation'), 3);
$upcomingfilterform->display();
echo \mod_observation\timeslots\timeslots::timeslots_table($observation->id, $pageurl,
\mod_observation\timeslots\timeslots::DISPLAY_MODE_UPCOMING, $filter);

// Start new session form block.
$startsessionform->display();

// Session history block.
echo $OUTPUT->heading(get_string('previoussessions', 'observation'), 3);
echo \mod_observation\viewsessions\viewsessions::ob_sess_table($observation->id, $pageurl);

echo $OUTPUT->footer();
