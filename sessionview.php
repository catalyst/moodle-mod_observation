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

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);
$slotid = optional_param('slotid', null, PARAM_INT);
$interval = optional_param('interval', null, PARAM_INT);
$intervalmultiplier = optional_param('period', null, PARAM_INT);

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

$pageurl = new moodle_url('/mod/observation/sessionview.php', ['id' => $id]);

// If actions is in URL args, action button on timeslots table was pressed.
if ($action !== null && $slotid !== null) {
    require_sesskey();

    // Get timeslot data.
    $timeslot = \mod_observation\timeslot_manager::get_existing_slot_data($id, $slotid);

    if ($timeslot->observee_id === null) {
        throw new \coding_exception("No observee assigned to this timeslot. Cannot start session.");
    }

    // Start session and redirect.
    $sessionid = \mod_observation\session_manager::start_session($timeslot->obs_id, $USER->id, $timeslot->observee_id);
    redirect(new moodle_url('session.php', ['sessionid' => $sessionid]));
}

$filterenabled = $interval !== null && $intervalmultiplier !== null;
$upcomingprefill = [
    'id' => $id,
    'interval_amount' => $interval,
    'interval_multiplier' => $intervalmultiplier,
    'filter_enabled' => $filterenabled
];

$upcomingfilterform = new \mod_observation\form\upcomingfilter(null, $upcomingprefill);

if ($fromform = $upcomingfilterform->get_data()) {
    if ($fromform->filter_enabled) {
        // Disable filter.
        redirect($pageurl);
    } else {
        // Enable filter.
        // Redirect, putting the filter into the URL args so the table and form can pick it up.
        redirect(new moodle_url('/mod/observation/sessionview.php', ['id' => $id, 'interval' => $fromform->interval_amount,
            'period' => $fromform->interval_multiplier]));
    }
}

$startsessionformprefill = [
    'id' => $id,
    'observerid' => $USER->id
];
$startsessionform = new \mod_observation\form\startsession(null, $startsessionformprefill);

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

// If there are URL args from the filter form, calculate the filter amount to pass to the table.
$filter = $filterenabled ? $interval * $intervalmultiplier : 0;
echo \mod_observation\table\timeslots\timeslots_display::assigned_timeslots_table($observation->id, $pageurl,
\mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_UPCOMING, $USER->id, $filter);

// Start new session form block.
$startsessionform->display();

// Session history block.
echo $OUTPUT->heading(get_string('previoussessions', 'observation'), 3);
echo \mod_observation\table\viewsessions\viewsessions_display::ob_sess_table($observation->id, $pageurl);

echo $OUTPUT->footer();
