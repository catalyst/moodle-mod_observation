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
 * User inteface to view timeslots.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
global $DB;

$id = required_param('id', PARAM_INT); // Observation instance ID.
$slotid = optional_param('slotid', null, PARAM_INT); // Time slot ID.
$action = optional_param('action', null, PARAM_TEXT); // Action.

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:editobservationpoints', $PAGE->context);

$pageurl = new moodle_url('/mod/observation/timeslots.php', array('id' => $id));

// Check if action and slotid are present.
if (!empty($action) && !empty($slotid)) {
    require_sesskey();

    switch ($action) {
        case 'edit':
            // Redirect to editor form page.
            redirect(new moodle_url('/mod/observation/timesloteditor.php', ['mode' => 'edit', 'slotid' => $slotid, 'id' => $id]));
            break;

        case 'delete':
            \mod_observation\timeslot_manager::delete_time_slot($observation->id, $slotid, $USER->id);
            break;

        case 'kick':
            \mod_observation\timeslot_manager::remove_observee($observation->id, $slotid, $USER->id);
            break;

        default:
            // Unknown action.
            throw new moodle_exception(
                'invalidqueryparam',
                'error',
                null,
                ['expected' => "'edit','delete','kick'", 'actual' => $action]);
    }

    // Redirect back to this page but without params after running action to avoid weird errors if user refreshes page.
    redirect($pageurl);
}

// Render page.
$pageurl = new moodle_url('/mod/observation/timeslots.php', array('id' => $id));
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingtimeslots', 'observation'), 2);

// Actions buttons.
echo $OUTPUT->container_start('mb-3 p-3 border border-secondary');

// Create new time slot.
echo $OUTPUT->single_button(
    new moodle_url('/mod/observation/timesloteditor.php', array('mode' => 'new', 'id' => $observation->id)),
    get_string('createnew', 'observation'),
    'get'
);

// Randomly assign students to timeslots.
echo $OUTPUT->single_button(
    new moodle_url('/mod/observation/assignstudents.php', array('mode' => 'randomassign', 'id' => $observation->id)),
    get_string('randomlyassign', 'observation'),
    'get'
);

echo $OUTPUT->container_end();

// Time Slot Viewer (Table).
echo $OUTPUT->heading(get_string('currenttimeslots', 'observation'), 3);

echo \mod_observation\table\timeslots\timeslots_display::timeslots_table($observation->id, $pageurl,
\mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_EDITING);

// Moodle footer.
echo $OUTPUT->footer();
