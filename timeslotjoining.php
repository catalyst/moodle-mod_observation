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
 * @author Jack Kepper <Jack@Kepper.net>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$slotid = optional_param('slotid', null, PARAM_INT); // Time slot ID.
$action = optional_param('action', null, PARAM_TEXT); // Action.

$calmonth = optional_param('calmonth', (int)date('m'), PARAM_INT); // Signup calendar month.
$calyear = optional_param('calyear', (int)date('Y'), PARAM_INT); // Signup calendar year.

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);

$pageurl = new moodle_url('/mod/observation/timeslotjoining.php', array('id' => $id));

if ($action !== null) {
    require_sesskey();
    switch ($action) {
        case 'join':
            // Assign user to timeslot.
            if ($slotid === null) {
                throw new \coding_exception("Missing SlotID parameter");
            }

            \mod_observation\timeslot_manager::timeslot_signup($observation->id, $slotid, $USER->id);
            break;
        case 'unenrol':
            // Unenrols user from course.
            if ($slotid === null) {
                throw new \coding_exception("Missing SlotID parameter");
            }
            \mod_observation\timeslot_manager::timeslot_unenrolment($observation->id, $slotid, $USER->id);
            break;

        default:
            // Unknown action.
            throw new moodle_exception(
                'invalidqueryparam',
                'error',
                null,
                ['expected' => "'join', 'unenrol'", 'actual' => $action]);
    }

    // Redirect back to this page but without params after running action to avoid weird errors if user refreshes page.
    redirect($pageurl);
}

// Render page.
$pageurl = new moodle_url('/mod/observation/timeslotjoining.php', array('id' => $id));
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('selectingslot', 'observation'), 2);

$signedupslot = \mod_observation\timeslot_manager::get_registered_timeslot($observation->id, $USER->id);

// Not signed up yet.
if ($signedupslot === false) {
    // Time Slot Viewer (Table).
    echo $OUTPUT->container_start('p-3 mb-2 bg-warning');
    echo get_string('noslotsignedup', 'observation');
    echo $OUTPUT->container_end();

    echo $OUTPUT->heading(get_string('currenttimeslots', 'observation'), 3);
    echo \mod_observation\table\timeslots\timeslots_display::timeslots_table($observation->id, $pageurl,
    \mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_SIGNUP);

    // Calendar signup view.
    echo \mod_observation\calendar_signup::calendar_signup_view($observation->id,
        get_string('calendarsignup', 'observation'), $calmonth, $calyear, $pageurl);
} else {
    // Already signed up - show details.
    echo $OUTPUT->heading(get_string('yourtimeslot', 'observation'), 3);
    echo \mod_observation\table\timeslots\timeslots_display::assigned_timeslots_table($observation->id, $pageurl,
    \mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_OBSERVEE_REGISTERED, $USER->id);

    echo $OUTPUT->heading(get_string('timeslotnotifications', 'observation'), 3);
    echo $OUTPUT->single_button(new moodle_url('/mod/observation/timeslotnotifications.php', ['id' => $id]),
        get_string('setuptimeslotnotifications', 'observation'), 'GET');
}

// Moodle footer.
echo $OUTPUT->footer();
