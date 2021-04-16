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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function observation_get_course_content_items(\core_course\local\entity\content_item $defaultmodulecontentitem) {
    global $CFG, $OUTPUT;

    $types = [];

    // Every thing that gets added to the activity picker is an instance of the 'content_item' class
    // An array is returned in case a mod might add multiple things to the activity picker
    // The 'External tool' entry (the main module content item), should always take the id of 1.

    $types = [new \core_course\local\entity\content_item(
        1, // This is the ID of the content item (in case of multiples)
        "observationActivityModule", // This is the internal name of the content item (not human readable)
        new core_course\local\entity\string_title("Observation"), // This is the human readable title that shows up on the activity picker (an instance of the string_title class)
        $defaultmodulecontentitem->get_link(), // TODO
        '<img src="/mod/observation/pix/icon.png" />', // This is a string, which is the HTML for the icon. Images in the 'pix' folder are public by default.
        $defaultmodulecontentitem->get_help(), // TODO
        $defaultmodulecontentitem->get_archetype(), // TODO
        $defaultmodulecontentitem->get_component_name() // TODO
    )];
    return $types;
}

/**
 * Adds an Observation instance.
 * @param object $data
 * @return int new observation instance id
 */
function observation_add_instance($data) {
    global $DB;
    $cmid = $data->coursemodule;

    // Prepare array for insert
    $insert_instance = array(
        "course" => $cmid,
        "name" => $data->name,
        "intro" => "",
        "timemodified" => time()
    );
    
    // Insert into DB
    $obs_id = $DB->insert_record('observation', $insert_instance);

    return $obs_id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $data the data that came from the form.
 * @return mixed true on success, false or a string error message on failure.
 */
function observation_update_instance($data) {
    global $DB;
    
    $data->id = $data->instance;
    $data->intro="";

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