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
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$mode = required_param('mode', PARAM_TEXT); // Editor mode 'new' or 'edit'.
$pointid = optional_param('pointid', null, PARAM_INT); // Optional param - required if mode is 'edit'.

// Ensure $mode param is allowed option.
if ($mode !== 'new' && $mode !== 'edit') {
    throw new moodle_exception(
        'invalidqueryparam',
        'error',
        null,
        $a = array('expected' => 'mode to be \'new\' or \'edit\'', 'actual' => $mode));
}

// Ensure pointID is given if mode is 'edit'.
if ($mode === 'edit' && $pointid === null) {
    throw new moodle_exception('missingparam', 'error', null, $a = 'pointid');
}

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:editobservationpoints', $PAGE->context);

// Prefill hidden data in form regardless of mode.
$formprefill = array(
    'id' => $id,
    'mode' => $mode,
    'pointid' => $pointid,
);

// If editing, add prefill data from DB.
if ($mode === "edit") {
    $pointdata = \mod_observation\observation_manager::get_existing_point_data($id, $pointid);
    $formprefill['pointid'] = $pointdata->id;
    $formprefill['res_type'] = $pointdata->res_type;
    $formprefill['file_size'] = $pointdata->file_size;
    $formprefill['maxgrade'] = $pointdata->max_grade;
    $formprefill['title'] = $pointdata->title;
    $formprefill['ins']['text'] = $pointdata->ins;
    $formprefill['ins']['format'] = $pointdata->ins_f;
}

// Load form.
$pointeditorform = new \mod_observation\form\pointeditor(null, $formprefill);

// Form submitted, save/edit the data.
if ($fromform = $pointeditorform->get_data()) {
    $dbdata = array(
        "obs_id" => $fromform->id,
        "title" => $fromform->title,
        "ins" => $fromform->ins['text'],
        "ins_f" => $fromform->ins['format'],
        "max_grade" => $fromform->maxgrade,
        "res_type" => $fromform->res_type,
        "file_size" => (int)$fromform->res_type === 2 ? $fromform->file_size : null,
    );

    if ($fromform->mode === "new") {
        // Creating new.
        \mod_observation\observation_manager::modify_observation_point($dbdata);
    } else {
        // Editing existing.
        $dbdata['id'] = $fromform->pointid;
        \mod_observation\observation_manager::modify_observation_point($dbdata);
    }

    // Redirect back to point viewer.
    redirect(new moodle_url('viewpoints.php', array('id' => $id)));
    die;
}

// Form not submitted, render form.
$PAGE->set_url(new moodle_url('/mod/observation/pointeditor.php', array('mode' => $mode, 'id' => $id)));
$PAGE->set_title(get_string('creatingobservationpoint', 'observation'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if ($mode === 'new') {
    echo $OUTPUT->heading(get_string('creatingobservationpoint', 'observation'), 2);
} else if ($mode === 'edit') {
    echo $OUTPUT->heading(get_string('editingobservationpoint', 'observation'), 2);
}

$pointeditorform->display();

echo $OUTPUT->footer();
