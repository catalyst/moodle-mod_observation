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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Return the preconfigured tools which are configured for inclusion in the activity picker.
 *
 * @param \core_course\local\entity\content_item $defaultmodulecontentitem reference to the content item for the Observation module.
 * @return array the array of content items.
 */
function observation_get_course_content_items(\core_course\local\entity\content_item $defaultmodulecontentitem) {
    global $CFG, $OUTPUT;

    $types = [];

    $types = [new \core_course\local\entity\content_item(
        1,
        "observationActivityModule",
        new core_course\local\entity\string_title("Observation"),
        $defaultmodulecontentitem->get_link(),
        '<img src="/mod/observation/pix/icon.png" />',
        $defaultmodulecontentitem->get_help(),
        $defaultmodulecontentitem->get_archetype(),
        $defaultmodulecontentitem->get_component_name()
    )];
    return $types;
}

/**
 * Adds an Observation instance.
 * @param object $data
 * @return int new observation instance id
 */
function observation_add_instance($data): int {
    global $DB;
    $cmid = $data->coursemodule;
    // Insert into DB.
    return $DB->insert_record('observation', array(
            "course" => $cmid,
            "name" => $data->name,
            "intro" => "",
            "timemodified" => time()
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
    global $DB;
    $data->id = $data->instance;
    $data->intro = "";
    return $DB->update_record('observation', $data);
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