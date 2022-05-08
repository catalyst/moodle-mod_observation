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
 * This file contains the summary and submission page for an observation session
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

// Get data from the session ID.
$sessionid = required_param('sessionid', PARAM_INT);
$session = \mod_observation\session_manager::get_session_data($sessionid);

$sessiondata = $session['data'];
$sessioninfo = $session['info'];

$pointid = optional_param('pointid', null, PARAM_INT);

$mode = optional_param('mode', null, PARAM_TEXT);
$isviewonly = $mode === 'viewing';

$obid = $sessioninfo['obid'];
list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($obid);

// Check permissions.
require_login($course, true, $cm);
require_capability('mod/observation:performobservation', $PAGE->context);

$grade = \mod_observation\session_manager::calculate_grade($sessionid);
$gradeformatted = $grade['total'].'/'.$grade['max'];
$formprefill = [
    'gradecalculated' => $gradeformatted,
    'sessionid' => $sessionid,
    'extracomment' => $sessioninfo['ex_comment']
];

$submitform = new \mod_observation\form\sessionsubmit(null, $formprefill, 'post', '', null, !$isviewonly);

// Submission form was submitted.
if ($fromform = $submitform->get_data()) {
    // Save extra comment.
    \mod_observation\session_manager::save_extra_comment($sessionid, $fromform->extracomment);

    // Submit observation.
    $status = \mod_observation\session_manager::finish_session($sessionid);

    if ($status === true) {
        redirect(new moodle_url('sessionview.php', ['id' => $obid]), get_string('sessioncomplete', 'observation'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Error.
        redirect(new moodle_url('sessionsummary.php', ['sessionid' => $sessionid]), $status,
            null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Get observation points and current responses.
$observationpoints = (array)\mod_observation\observation_manager::get_points_and_responses($obid, $sessionid);
$incompletepoints = \mod_observation\session_manager::get_incomplete_points($sessionid);

$PAGE->set_url(new moodle_url('/mod/observation/sessionsummary.php', ['sessionid' => $sessionid]));
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if ($isviewonly) {
    echo $OUTPUT->notification(get_string('sessionviewonly', 'observation'), \core\output\notification::NOTIFY_INFO);
}

if (!empty($incompletepoints)) {
    echo $OUTPUT->notification(get_string('existsincompletepoints', 'observation'), \core\output\notification::NOTIFY_WARNING);
}

// Heading.
echo $OUTPUT->container_start('mb-3 p-3 border border-secondary');
echo $OUTPUT->heading(get_string('actions', 'observation'), 4);
if (!$isviewonly) {
    echo $OUTPUT->single_button(new moodle_url('session.php', ['sessionid' => $sessionid]),
        get_string('returntosession', 'observation'));
} else {
    echo $OUTPUT->single_button(new moodle_url('sessionview.php', ['id' => $obid]),
        get_string('returntosessionlist', 'observation'));
}
echo $OUTPUT->container_end();

echo $OUTPUT->heading(get_string('sessionsummary', 'observation'), 2);

// Summary of points and responses.
echo \mod_observation\observation_manager::format_points_and_responses($obid, $sessionid);

// Session submission form.
$submitform->display();

echo $OUTPUT->footer();
