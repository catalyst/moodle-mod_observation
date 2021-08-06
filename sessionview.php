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

$id = required_param('id', PARAM_INT);

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

$pageurl = new moodle_url('/mod/observation/sessionview.php', ['id' => $id]);

$startsessionformprefill = array(
    'id' => $id,
    'observerid' => $USER->id
);
$startsessionform = new \mod_observation\startsession_form(null, $startsessionformprefill);

// If start session form was submitted, call function to start session.
if ($fromform = $startsessionform->get_data()) {
    $sessionid = \mod_observation\session_manager::start_session($fromform->id, $fromform->observerid, $fromform->observeeid);
    redirect(new moodle_url('session.php', ['sessionid' => $sessionid]));
}

$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('observationsessions', 'observation'), 2);

// Start new session form block.
$startsessionform->display();

// Current sessions block.
echo $OUTPUT->heading(get_string('previoussessions', 'observation'), 3);
echo \mod_observation\viewsessions\viewsessions::ob_sess_table($observation->id, $pageurl);

echo $OUTPUT->footer();
