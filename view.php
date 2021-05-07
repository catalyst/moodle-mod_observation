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
 * Page to view an observation module
 *
 * @package mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id    = optional_param('id', 0, PARAM_INT);        // Course module ID.
$observationid = optional_param('o', 0, PARAM_INT);          // Observation instance ID.

// Can access directly from observation ID or from course module ID.
if ($observationid) {
    // Access directly via observation ID.
    if (!$cm = get_coursemodule_from_instance('observation', $observationid)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    // Get the course from the course module (or error).
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        throw new moodle_exception('coursemisconf');
    }
} else {
    // Access indirectly via course module ID.

    // Get the course module object from the ID (or error).
    if (!$cm = get_coursemodule_from_id('observation', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }
    // Get the course from the course module (or error).
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        throw new moodle_exception('coursemisconf');
    }

    $observationid = $cm->instance;
}

// Get the observation instance (or error).
if (!$observation = $DB->get_record('observation', array('id' => $observationid))) {
    throw new moodle_exception('cannotfindcontext');
}

require_login($course, true, $cm);

// TODO check permissions.

$PAGE->set_url('/mod/url/view.php', array('id' => $observationid));

// Render output (nothing right now, just some random debug info) TODO move into function.
global $CFG, $PAGE, $OUTPUT;

// Moodle header.
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Our activity page header.
echo $OUTPUT->heading($observation->name);
echo "Test.\n it works!";

// Moodle footer.
echo $OUTPUT->footer();
die;