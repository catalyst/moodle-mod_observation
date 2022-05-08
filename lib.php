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
 * This file contains the moodle hooks for the assign module.
 *
 * It delegates most functions to the assignment class.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Return the preconfigured tools which are configured for inclusion in the activity picker.
 *
 * @param \core_course\local\entity\content_item $defaultmodulecontentitem reference to the content item for the Observation module.
 * @return array the array of content items.
 */
function observation_get_course_content_items(\core_course\local\entity\content_item $defaultmodulecontentitem) {
    global $OUTPUT;

    $contentitem = new \core_course\local\entity\content_item(
        1,
        "observationActivityModule",
        new core_course\local\entity\string_title("Observation"),
        $defaultmodulecontentitem->get_link(),
        $OUTPUT->pix_icon('icon', 'add observation', 'observation'),
        $defaultmodulecontentitem->get_help(),
        $defaultmodulecontentitem->get_archetype(),
        $defaultmodulecontentitem->get_component_name(),
        MOD_PURPOSE_ASSESSMENT
    );

    return [$contentitem];
}

/**
 * Adds an Observation instance.
 * @param object $data form data
 * @return int new observation instance id
 */
function observation_add_instance($data): int {
    return \mod_observation\observation_manager::modify_instance(array(
        "course" => (int)$data->course,
        "name" => $data->name,
        "intro" => "",
        "timemodified" => time(),
        "observer_ins" => $data->observerins_editor['text'],
        "observer_ins_f" => $data->observerins_editor['format'],
        "observee_ins" => $data->observeeins_editor['text'],
        "observee_ins_f" => $data->observeeins_editor['format'],
        "students_self_unregister" => (int) $data->students_self_unregister
    ));
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $data the data that came from the form.
 * @return bool true on success, false or a string error message on failure.
 */
function observation_update_instance($data): bool {
    $success = \mod_observation\observation_manager::modify_instance(array(
        "id" => $data->instance,
        "name" => $data->name,
        "timemodified" => time(),
        "observer_ins" => $data->observerins_editor['text'],
        "observer_ins_f" => $data->observerins_editor['format'],
        "observee_ins" => $data->observeeins_editor['text'],
        "observee_ins_f" => $data->observeeins_editor['format'],
        "students_self_unregister" => (int) $data->students_self_unregister
    ));

    if ($success === true) {
        // Update all the calendar events to get the new data.
        $timeslots = \mod_observation\timeslot_manager::get_time_slots($data->instance);
        $timeslotids = array_column($timeslots, 'id');

        foreach ($timeslotids as $slotid) {
            \mod_observation\timeslot_manager::update_timeslot_calendar_events($data->instance, $slotid);
        }
    }

    return $success;
}

/**
 * Delete observation instance.
 * @param int $id
 * @return bool true
 */
function observation_delete_instance($id) {
    global $DB;

    $DB->delete_records('observation', array('id' => $id));
    return true;
}

/**
 * Defines what features this activity supports.
 * @param mixed $feature given feature enum
 * @return mixed True is supports feature, else null.
 */
function observation_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Determines if a calendar event is visible.
 * @param calendar_event $event event to determine visibility for.
 */
function mod_observation_core_calendar_is_event_visible(calendar_event $event) {
    global $USER;
    global $DB;

    // For some archaic reason, $event->userid is NOT the userID of the event in the database,
    // but is instead the caller of this function (i.e. the same as $USER->id).
    // We have no choice but to query the $DB again.
    $sql = 'SELECT *
              FROM {observation_timeslots}
             WHERE (observer_event_id = :observerevent AND observer_id = :observer)
                OR (observee_event_id = :observeeevent AND observee_id = :observee)';

    $params = [
        "observerevent" => $event->id,
        "observeeevent" => $event->id,
        "observer" => $USER->id,
        "observee" => $USER->id
    ];

    $matchingevent = $DB->get_records_sql($sql, $params);

    return !empty($matchingevent);
}
/**
 * Context checks and serves file from certain areas
 * @param mixed $course course
 * @param mixed $cm course module
 * @param context $context context
 * @param string $filearea file area
 * @param mixed $args args
 * @param bool $forcedownload bool if download should be forced
 * @param array $options an array of options
 */
function observation_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Ensure logged in to course.
    require_course_login($course->id);

    // Ensure has correct capability.
    require_capability('mod/observation:viewfiles', $context);

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    if (strpos($filearea, 'response') !== 0 ) {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $itemid = array_pop($args);
    $filepath = '/';

    $file = $fs->get_file($context->id, 'mod_observation', $filearea, $itemid, $filepath, $filename);

    send_stored_file($file, null, 0, $forcedownload, $options);
}
