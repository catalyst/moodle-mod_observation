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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($dir . '../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
list($observation, $course, $cm) = \mod_observation\manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:editobservationpoints', $PAGE->context);

// Render page.
$PAGE->set_url(new moodle_url('/mod/observation/pointviewer.php', array('id' => $id)));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingobservationpoints', 'observation'), 2);

// Actions buttons.
echo $OUTPUT->box_start();
echo $OUTPUT->single_button(
    new moodle_url('/mod/observation/pointeditor.php', array('mode' => 'new', 'id' => $observation->id)), 
    get_string('createnew', 'observation'),
    'get'
);
echo $OUTPUT->box_end();

// View observation points already created.
echo $OUTPUT->heading(get_string('currentpoints', 'observation'), 3);
echo "point viewer table class here...";

echo $OUTPUT->footer();