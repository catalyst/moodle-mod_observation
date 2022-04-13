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
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

// Render page.
$pageurl = new moodle_url('/mod/observation/observer.php', array('id' => $id));
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($observation->name, 2);

// Action buttons.
echo $OUTPUT->container_start('mb-3 p-3 border border-secondary');
echo $OUTPUT->heading(get_string('actions', 'observation'), 3);

// Edit observation point link button.
if (has_capability('mod/observation:editobservationpoints', $PAGE->context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/mod/observation/viewpoints.php', array('id' => $observation->id)),
        get_string('editobservationpoints', 'observation'),
        'get'
    );
}

// Edit timeslots link button.
if (has_capability('mod/observation:edittimeslots', $PAGE->context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/mod/observation/timeslots.php', array('id' => $observation->id)),
        get_string('edittimeslotss', 'observation'),
        'get'
    );
}

echo $OUTPUT->container_end();

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

// Table of timeslots the user has been assigned.
echo $OUTPUT->heading(get_string('assignedtimeslots', 'observation'), 3);
echo \mod_observation\table\timeslots\timeslots_display::assigned_timeslots_table($observation->id, $pageurl,
\mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_OBSERVER_ASSIGNED, $USER->id);

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
