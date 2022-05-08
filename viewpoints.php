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
 * This file contains functions to edit observation points for an observation.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$pointid = optional_param('pointid', null, PARAM_INT); // Observation point ID.
$action = optional_param('action', null, PARAM_TEXT); // Action.

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:editobservationpoints', $PAGE->context);

$pageurl = new moodle_url('/mod/observation/viewpoints.php', array('id' => $id));

// Check if action and pointid are present.
if (!empty($action) && !empty($pointid)) {
    require_sesskey();

    switch ($action) {
        case 'edit':
            // Redirect to editor form page.
            redirect(new moodle_url('/mod/observation/pointeditor.php', ['mode' => 'edit', 'pointid' => $pointid, 'id' => $id]));
            break;

        case 'delete':
            \mod_observation\observation_manager::delete_observation_point($observation->id, $pointid);
            break;

        case 'moveup':
            \mod_observation\observation_manager::reorder_observation_point($observation->id, $pointid, -1);
            break;

        case 'movedown':
            \mod_observation\observation_manager::reorder_observation_point($observation->id, $pointid, 1);
            break;

        default:
            // Unknown action.
            throw new moodle_exception(
                'invalidqueryparam',
                'error',
                null,
                ['expected' => "'edit','delete','moveup' or 'movedown'", 'actual' => $action]);
    }

    // Redirect back to this page but without params after running action to avoid weird errors if user refreshes page.
    redirect($pageurl);
}

// Render page.
$pageurl = new moodle_url('/mod/observation/viewpoints.php', array('id' => $id));
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingobservationpoints', 'observation'), 2);

// Actions buttons.
echo $OUTPUT->box_start();

// Create new observation point.
echo $OUTPUT->single_button(
    new moodle_url('/mod/observation/pointeditor.php', array('mode' => 'new', 'id' => $observation->id)),
    get_string('createnew', 'observation'),
    'get'
);
echo $OUTPUT->box_end();

// Observation Point Viewer (table).
echo $OUTPUT->heading(get_string('currentpoints', 'observation'), 3);

echo \mod_observation\table\viewpoints\viewpoints_display::ob_point_table($observation->id, $pageurl);

// Moodle footer.
echo $OUTPUT->footer();
