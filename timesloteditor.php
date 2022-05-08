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
 * Timeslot editor page.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$mode = required_param('mode', PARAM_TEXT); // Editor mode 'new' or 'edit'.
$slotid = optional_param('slotid', null, PARAM_INT); // Optional param - required if mode is 'edit'.

$validmodes = ['new', 'edit'];

// Ensure $mode param is allowed option.
if (!in_array($mode, $validmodes)) {
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
    $slotdata = \mod_observation\timeslot_manager::get_existing_slot_data($id, $slotid);
    $formprefill['slotid'] = $slotdata->id;
    $formprefill['duration'] = $slotdata->duration;
    $formprefill['start_time'] = $slotdata->start_time;
    $formprefill['observer_id'] = $slotdata->observer_id;
}

// Load form.
$sloteditorform = new \mod_observation\form\timesloteditor(null, $formprefill);

// Form submitted.
if ($fromform = $sloteditorform->get_data()) {
    $dbdata = \mod_observation\timeslot_manager::transform_form_data($fromform);

    // Preview interval button pressed.
    if (!empty($fromform->preview_submit)) {
        $previewslots = \mod_observation\timeslot_manager::generate_interval_timeslots($dbdata,
            $fromform->interval_amount, $fromform->interval_multiplier, $fromform->interval_end);
        $formprefill['preview_interval'] = \mod_observation\timeslot_manager::generate_preview($previewslots);
        $sloteditorform->set_data($formprefill);
    }

     // Submit button pressed.
    if (!empty($fromform->submit_form)) {
        if ($fromform->mode === "new") {

            // Interval or single ?
            if ($fromform->enable_interval === "1") {
                \mod_observation\timeslot_manager::create_timeslots_by_interval($fromform);
            } else {
                // Creating new single.
                \mod_observation\timeslot_manager::modify_time_slot($dbdata);
            }
        } else {
            // Editing existing.
            $dbdata['id'] = $fromform->slotid;
            \mod_observation\timeslot_manager::modify_time_slot($dbdata);
        }

        // Redirect back to slot viewer.
        redirect(new moodle_url('timeslots.php', array('id' => $id)));
        die;
    }
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
