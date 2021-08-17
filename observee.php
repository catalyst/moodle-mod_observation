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
 * @author Matthew Hilton <mj.hilton@outlook.com>, Jack Kepper <Jack@Kepper.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:view', $PAGE->context);

// Render page.
$pageurl = new moodle_url('/mod/observation/observee.php', array('id' => $id));
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($observation->name, 2);

echo \mod_observation\instructions::observation_instructions(get_string('instructions', 'observation'),
    $observation->observee_ins, $observation->observee_ins_f);

echo $OUTPUT->container_start();
echo $OUTPUT->heading(get_string('currenttimeslots', 'observation'), 3);
echo \mod_observation\timeslots\timeslots::timeslots_table($observation->id, $pageurl, 0);
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
