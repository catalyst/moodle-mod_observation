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
 * Timeslot notifications management page.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Jack Kepper <Jack@Kepper.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Observation instance ID.
$action = optional_param('action', null, PARAM_TEXT);
$notifyid = optional_param('notifyid', null, PARAM_INT);

list($observation, $course, $cm) = \mod_observation\observation_manager::get_observation_course_cm_from_obid($id);
$pageurl = new moodle_url('/mod/observation/timeslotnotifications.php', ['id' => $id]);

// Check permissions.
require_login($course, true, $cm);

// Check for actions in URL params.
if (!empty($action)) {

    if (empty($notifyid)) {
        throw new \moodle_exception('missingparam', 'error', null, $a = 'notifyid');
    }

    if ($action === 'delete') {
        require_sesskey();
        \mod_observation\timeslot_manager::delete_notification($observation->id, $USER->id, $notifyid);
        redirect($pageurl);
    }
}

// Get current timeslot user registered to.
$timeslot = \mod_observation\timeslot_manager::get_registered_timeslot($observation->id, $USER->id);

// Load forms.
$prefill = [
    'id' => $observation->id,
    'slotid' => $timeslot->id
];
$notificationeditor = new \mod_observation\form\notificationeditor(null, $prefill);

if ($fromform = $notificationeditor->get_data()) {
    $data = (object) [
        'interval_amount' => (int)$fromform->interval_amount,
        'interval_multiplier' => (int)$fromform->interval_multiplier
    ];

    \mod_observation\timeslot_manager::create_notification($fromform->id, $fromform->slotid, $USER->id, $data);

    redirect($pageurl);
}

// Render page.
$PAGE->set_url($pageurl);
$PAGE->set_title($course->shortname.': '.$observation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($observation->name, 2);
echo $OUTPUT->heading(get_string('timeslotnotifications', 'observation'), 3);

$notificationeditor->display();

echo \mod_observation\table\notifications\notifications_display::notifications_table($observation->id, $USER->id, $pageurl);

echo $OUTPUT->footer();
