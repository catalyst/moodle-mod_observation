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
 * @copyright  2021 Endurer Solutions Team
 * @author Jack Kepper <Jack@Kepper.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$slotid = optional_param('slotid', null, PARAM_INT); // Time slot ID.
$action = optional_param('action', null, PARAM_TEXT); // Action.

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);

// Check permissions.
require_login($course, true, $cm);

$pageurl = new moodle_url('/mod/observation/timeslotjoining.php', array('id' => $id));

if ($action !== null) {

    switch ($action) {
        case 'join':
            // Assign user to timeslot.
            if ($slotid === null) {
                throw new \coding_exception("Missing SlotID parameter");
            }

            \mod_observation\timeslot_manager::timeslot_signup($observation->id, $slotid, $USER->id);
            break;

        default:
            // Unknown action.
            throw new moodle_exception(
                'invalidqueryparam',
                'error',
                null,
                ['expected' => "'join'", 'actual' => $action]);
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

// Not signed up yet
if($signedupslot === false) {
    // Time Slot Viewer (Table).
    echo $OUTPUT->heading(get_string('currenttimeslots', 'observation'), 3);
    echo \mod_observation\timeslots\timeslots::timeslots_table($observation->id, $pageurl,
    \mod_observation\timeslots\timeslots::DISPLAY_MODE_SIGNUP);
} else {
    // Already signed up - show details
    echo $OUTPUT->heading(get_string('timeslotinfo', 'observation'), 3);
    // TODO show details in nice format
}

// Moodle footer.
echo $OUTPUT->footer();
