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
 * Functional page, used by the activity coordinators to assign students
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_observation\timeslot_manager;

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$mode = required_param('mode', PARAM_TEXT); // Editor mode - currently only 'randomassign' is supported.
$confirm = optional_param('confirm', null, PARAM_BOOL);
$pageurl = new moodle_url('/mod/observation/assignstudents.php', ['id' => $id, 'mode' => $mode]);

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Ensure mode is correct.
if ($mode !== 'randomassign') {
    throw new moodle_exception(
        'invalidqueryparam',
        'error',
        null,
        $a = array('expected' => 'mode to be \'randomassign\'', 'actual' => $mode));
}

require_login($course, true, $cm);
require_capability('mod/observation:assignstudents', $PAGE->context);

$pageoutput = '';

if ($mode === 'randomassign') {
    // If no confirmation yet, display confirmation dialog.
    if ($confirm === null) {
        $pageoutput = $OUTPUT->confirm(get_string('confirmrandomlyassign', 'observation'),
            new moodle_url($pageurl, ['confirm' => true]), new moodle_url($pageurl, ['confirm' => false]));
    }

    // Accepted confirmation.
    if ($confirm === 1) {
        require_sesskey();

        $remainingusers = timeslot_manager::randomly_assign_students($id);

        $message = get_string('randomassignsuccess', 'observation');
        $messagetype = \core\output\notification::NOTIFY_SUCCESS;

        // Success, but some users weren't assigned - show a warning.
        if (!empty($remainingusers)) {
            $message = get_string('randomassignwarning', 'observation', count($remainingusers));
            $messagetype = \core\output\notification::NOTIFY_WARNING;
        }

        // Redirect back with confirmation message.
        redirect(new moodle_url('/mod/observation/timeslots.php', ['id' => $id]), $message, null, $messagetype);
    }

    // Declined confirmation.
    if ($confirm === 0) {
        // Return to timeslot list page.
        redirect(new moodle_url('/mod/observation/timeslots.php', ['id' => $id]));
    }
}

// Output page if no redirects.
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $pageoutput;
echo $OUTPUT->footer();
