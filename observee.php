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
 * This file contains the default page view if the user does not have other higher permissions.
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require('./classes/instructions.php');

// Get related ids.
$id = required_param('id', PARAM_INT); // Observation instance ID.
if (!$cm = get_coursemodule_from_instance('observation', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
list($course, $cm) = get_course_and_cm_from_cmid($cm, 'observation');

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:view', $PAGE->context);

// Get the observation instance (or error).
global $DB;
if (!$observation = $DB->get_record('observation', array('id' => $id))) {
    throw new moodle_exception('moduleinstancedoesnotexist');
}

global $CFG, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/mod/observation/observee.php', array('id' => $id)));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($observation->name, 2);

echo observation_instructions(get_string('instructions', 'observation'), $observation->observee_ins, $observation->observee_ins_f);

echo $OUTPUT->container_start();
echo "Timeslot selection placeholder";
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
die;
