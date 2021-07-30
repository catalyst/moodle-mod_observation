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
 * Strings for component 'observation', language 'en'
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$mode = required_param('mode', PARAM_TEXT); // Editor mode 'new' or 'edit'.
$slotid = optional_param('slotid', null, PARAM_INT); // Optional param - required if mode is 'edit'.

// Ensure $mode param is allowed option.
if ($mode !== 'new' && $mode !== 'edit') {
    throw new moodle_exception(
        'invalidqueryparam',
        'error',
        null,
        $a = array('expected' => 'mode to be \'new\' or \'edit\'', 'actual' => $mode));
}

// Ensure slotID is given if mode is 'edit'.
if ($mode === 'edit' && $slotid === null) {
    throw new moodle_exception('missingparam', 'error', null, $a = 'slotid');
}

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:editobservationpoints', $PAGE->context);

// Prefill hidden data in form regardless of mode.
$formprefill = array(
    'id' => $id,
    'mode' => $mode,
    'slotid' => $slotid,
);

// If editing, add prefill data from DB.
if ($mode === "edit") {
    $slotdata = \mod_observation\observation_manager::get_existing_slot_data($id, $slotid);
    $formprefill['slotid'] = $slotdata->id;
}

// Load form.
$sloteditorform = new \mod_observation\timeslot_form(null, $formprefill);

// Form submitted, save/edit the data.
if ($fromform = $sloteditorform->get_data()) {

    $dbdata = array(
        "obs_id" => $fromform->id,
        "start_time" => $fromform->start_time,
        "duration" => $fromform->duration,
        "observer_id" => $fromform->observer_id
    );

    if ($fromform->mode === "new") {
        // Creating new.
        \mod_observation\observation_manager::modify_time_slot($dbdata, true);
    } else {
        // Editing existing.
        $dbdata['id'] = $fromform->slotid;
        \mod_observation\observation_manager::modify_time_slot($dbdata, false);
    }

    // Redirect back to slot viewer.
    redirect(new moodle_url('timeslots.php', array('id' => $id)));
    die;
}

// Form not submitted, render form.
$PAGE->set_url(new moodle_url('/mod/observation/timesloteditor.php', array('mode' => $mode, 'id' => $id)));
$PAGE->set_title(get_string('creatingtimeslot', 'observation'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if ($mode === 'new') {
    echo $OUTPUT->heading(get_string('creatingtimeslot', 'observation'), 2);
} else if ($mode === 'edit') {
    echo $OUTPUT->heading(get_string('editingtimeslot', 'observation'), 2);
}

$sloteditorform->display();

echo $OUTPUT->footer();
