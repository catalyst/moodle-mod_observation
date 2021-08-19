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

$id = required_param('id', PARAM_INT); // Observation instance ID.
list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

// Render page.
$PAGE->set_url(new moodle_url('/mod/observation/observer.php', array('id' => $id)));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($observation->name, 2);

// If user has permissions show observation point editor page link.
if (has_capability('mod/observation:editobservationpoints', $PAGE->context)) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('actions', 'observation'), 3);
    echo $OUTPUT->single_button(
        new moodle_url('/mod/observation/viewpoints.php', array('id' => $observation->id)),
        get_string('editobservationpoints', 'observation'),
        'get'
    );
}

// If user has permissions show time slot editor page link.
if (has_capability('mod/observation:edittimeslots', $PAGE->context)) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->single_button(
        new moodle_url('/mod/observation/timeslots.php', array('id' => $observation->id)),
        get_string('edittimeslotss', 'observation'),
        'get'
    );
    echo $OUTPUT->box_end();
}

echo \mod_observation\instructions::observation_instructions(
    get_string('instructions', 'observation'),
    $observation->observer_ins,
    $observation->observer_ins_f);

// Start observation session block.
if (has_capability('mod/observation:performobservation', $PAGE->context)) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('performobservation', 'observation'), 3);

    echo $OUTPUT->single_button(
        new moodle_url('/mod/observation/sessionview.php', ['id' => $observation->id]),
        get_string('observationsessions', 'observation'),
        'get'
    );
    echo $OUTPUT->box_end();
}

echo $OUTPUT->box_start();
echo "Timeslots assigned placeholder";
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
