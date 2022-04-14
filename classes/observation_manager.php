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
 * This file contains functions to get various observation data objects
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

/**
 * mod_observation observation management class
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_manager {

    /**
     * @var int Observation point text input type.
     */
    const INPUT_TEXT = 0;
    /**
     * @var int Observation point pass/fail input type.
     */
    const INPUT_PASSFAIL = 1;
    /**
     * @var int Observation point evidence input type.
     */
    const INPUT_EVIDENCE = 2;

    /**
     * Gets observation, course and coursemodule from course module ID
     * @param int $cmid Course module ID
     * @return list List containing the observation instance, course and coursemodule (in that order)
     */
    public static function get_observation_course_cm_from_cmid(int $cmid) {
        global $DB;
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'observation');
        $observationid = $cm->instance;
        if (!$observation = $DB->get_record('observation', ['id' => $observationid])) {
            throw new \moodle_exception('moduleinstancedoesnotexist');
        }
        return [$observation, $course, $cm];
    }

    /**
     * Gets observation, course and coursemodule from observation instance ID
     * @param int $obid Observation instance ID
     * @return list List containing the observation instance, course and coursemodule (in that order)
     */
    public static function get_observation_course_cm_from_obid(int $obid) {
        global $DB;
        if (!$cm = get_coursemodule_from_instance('observation', $obid)) {
            throw new \moodle_exception('invalidcoursemodule');
        }
        list($course, $cm) = get_course_and_cm_from_cmid($cm->id, 'observation');
        if (!$observation = $DB->get_record('observation', ['id' => $obid])) {
            throw new \moodle_exception('moduleinstancedoesnotexist');
        }
        return [$observation, $course, $cm];
    }

    /**
     * Modifies an instance of an observation by either creating a new one or updating existing one.
     * Note that when updating an instance, an ID must be passed in the $data param array.
     * @param mixed $data Data to be passed to the update or create DB function
     * @return int Returns ID of new instance if creating, else returns 1 if updated successfully, else 0.
     */
    public static function modify_instance($data) {
        global $DB;

        // Editor data need to be checked to ensure empty strings are not added.
        if ($data['observer_ins'] === "") {
            $data['observer_ins'] = null;
            $data['observer_ins_f'] = null;
        }

        if ($data['observee_ins'] === "") {
            $data['observee_ins'] = null;
            $data['observee_ins_f'] = null;
        }

        $newinstance = empty($data['id']);

        if ($newinstance) {
            return $DB->insert_record('observation', $data);
        } else {
            return (int)$DB->update_record('observation', $data);
        }
    }

    /**
     * Modifies or creates a new observation point in the database
     * @param mixed $data Data to pass to database function
     * @param bool $returnid True if should return id (only when creating new instance)
     * @return mixed True if successful. If $returnid is True and creating new point, returns ID
     */
    public static function modify_observation_point($data, bool $returnid = false) {
        global $DB;

        $data = (object)$data;

        if (!empty($data->max_grade)) {
            // Ensure maxgrade (if set) is an int.
            if (!is_int($data->max_grade)) {
                throw new \coding_exception("Property max_grade must be an int.");
            }

            // Ensure maxgrade (if set) is not negative.
            if ($data->max_grade < 0) {
                throw new \coding_exception("Property max_grade cannot be negative");
            }
        }

        if (!empty($data->res_type)) {
            // Get record, MUST_EXIST is passed so will except if res_type is invalid.
            $DB->get_record('observation_res_type_map', ['res_type' => $data->res_type], '*', MUST_EXIST);

            // Evidence response type checks.
            if ((int)$data->res_type === "2") {
                if (empty($data->file_size)) {
                    throw new \coding_exception("Property file_size must exist for response type 'evidence'.");
                }

                if (!is_int($data->file_size)) {
                    throw new \coding_exception("Property file_size must be an int.");
                }
            }
        }

        $newinstance = empty($data->id);

        if ($newinstance) {
            // Get the max observation point list_order to place this new one after it.
            // Get all the points to get the bounds.
            $allpoints = self::get_observation_points($data->obs_id);
            $alllistorders = array_column($allpoints, 'list_order');

            // Default to zero, update to max if a point already exists.
            $maxordering = 0;
            if (count($alllistorders) != 0) {
                $maxordering = max($alllistorders);
            }

            // Update $data ordering property to be the max_ordering + 1 (place at end of list).
            $data->list_order = $maxordering + 1;

            // Insert.
            return $DB->insert_record('observation_points', $data, $returnid);
        } else {
            return $DB->update_record('observation_points', $data);
        }
    }

    /**
     * Gets observation point data
     * @param int $observationid ID of observation instance
     * @param int $pointid ID of the observation point
     * @return stdClass existing point data
     */
    public static function get_existing_point_data(int $observationid, int $pointid) {
        global $DB;
        return $DB->get_record('observation_points', ['id' => $pointid, 'obs_id' => $observationid], '*', MUST_EXIST);
    }

    /**
     * Get all observation points in an observation instance
     * @param int $observationid ID of the observation instance
     * @param string $sortby column to sort by
     * @return array array of database objects obtained from database
     */
    public static function get_observation_points(int $observationid, string $sortby='list_order') {
        global $DB;
        return $DB->get_records('observation_points', ['obs_id' => $observationid], $sortby);
    }

    /**
     * Deletes observation point
     * @param int $observationid ID of the observation instance
     * @param int $obpointid ID of the observation point to delete
     */
    public static function delete_observation_point(int $observationid, int $obpointid) {
        global $DB;
        // To ensure ordering stays intact, should move all those points with a higher order than this one down by 1.
        $currentpoint = self::get_existing_point_data($observationid, $obpointid, 'observation_points');

        // Get those with a higher list ordering than this one.
        $pointsabove = $DB->get_records_select(
            'observation_points',
            "obs_id = :obsid AND list_order > :listorder",
            [
                'obsid' => $observationid,
                'listorder' => $currentpoint->list_order
            ]
        );

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('observation_points', ['id' => $obpointid, 'obs_id' => $observationid]);

        // Shuffle those above down.
        foreach ($pointsabove as $pointabove) {
            $DB->update_record(
                'observation_points',
                [
                    'id' => $pointabove->id,
                    'list_order' => $pointabove->list_order - 1
                ]
            );
        }

        $transaction->allow_commit();
    }

    /**
     * Reorders an observation point in relation to the other observation points in this observation instance
     * @param int $observationid ID of the observation instance
     * @param int $obpointid ID of the observation point to reorder
     * @param int $direction direction and magnitude to reorder the point in
     */
    public static function reorder_observation_point(int $observationid, int $obpointid, int $direction) {
        global $DB;

        if (!is_int($direction) || $direction == 0) {
            throw new \coding_exception("direction must be an integer that is not zero");
        }

        // First get the ordering of the current point.
        $targetpoint = self::get_existing_point_data($observationid, $obpointid, 'observation_points');

        // Get all the points to get the bounds.
        $allpoints = self::get_observation_points($observationid);
        $alllistorders = array_column($allpoints, 'list_order');

        $newordering = $targetpoint->list_order + $direction;

        // Is currently the minimum - do nothing.
        if ($newordering < min($alllistorders)) {
            return;
        }

        // Is currently the maximum - do nothing.
        if ($newordering > max($alllistorders)) {
            return;
        }

        // Ordering is valid, so get the points that are affected by this reordering.
        $affectedpoints = array_filter($allpoints, function($elem) use($newordering, $direction, $targetpoint) {
            // Don't include the item being reordered.
            if ($elem->id === $targetpoint->id) {
                return false;
            }

            // If shifting down, filter those 'above'.
            if ($direction < 0) {
                return $elem->list_order >= $newordering && $elem->list_order < $targetpoint->list_order;
            }
            // If shifting up, filter those 'below'.
            if ($direction > 0) {
                return $elem->list_order <= $newordering && $elem->list_order > $targetpoint->list_order;
            }

            return false;
        });

        $transaction = $DB->start_delegated_transaction();

        // Give the target point the new ordering.
        $DB->update_record('observation_points',
        [
            'id' => $targetpoint->id,
            'list_order' => $newordering
        ]);

        // Reduce the direction to a unit vector (e.g. 5 -> 1 and -5 -> -1).
        $reductionamount = intdiv($direction, abs($direction));

        // Apply reduction to all affected points.
        array_map(function($e) use($DB, $reductionamount) {
            $DB->update_record('observation_points',
            [
                'id' => $e->id,
                'list_order' => $e->list_order - $reductionamount
            ]);
        }, $affectedpoints);

        $transaction->allow_commit();
    }

    /**
     * Returns a list of observation points with responses for a particular session
     * @param int $observationid ID of the observation instance
     * @param int $sessionid ID of the observation sesssion
     * @return array array of observation points with responses for the given session
     */
    public static function get_points_and_responses(int $observationid, int $sessionid) {
        global $DB;

        // Selects all points for this observation,
        // and attaches responses for the current session (if one exists).
        // Note: SELECT * was not used here as it caused issues with DB cross compatibility.
        $sql = 'SELECT pts.id as point_id, obs_id, title, list_order, ins, ins_f, max_grade, res_type,
                        file_size, sess_resp.id as response_id, obs_ses_id as session_id,
                        grade_given, response, ex_comment
                  FROM {observation_points} pts
             LEFT JOIN {observation_point_responses} sess_resp
                    ON pts.id = sess_resp.obs_pt_id AND sess_resp.obs_ses_id = :sessionid
                 WHERE pts.obs_id = :observationid
              ORDER BY list_order';

        return $DB->get_records_sql($sql, ['observationid' => $observationid, 'sessionid' => $sessionid]);
    }

    /**
     * Submits a response to a particular observation point for a given sesssion.
     * @param int $sessionid ID of the observation session
     * @param int $pointid ID of the observation point this response is for
     * @param mixed $data data object returned from the pointmarking_form
     * @return int ID of point response.
     */
    public static function submit_point_response(int $sessionid, int $pointid, $data) {
        global $DB;

        $data = (object)$data;

        if ($data->grade_given < 0 || !is_int($data->grade_given)) {
            throw new \coding_exception("Grade given must be an integer that is zero or more");
        }

        if (!isset($data->response)) {
            throw new \coding_exception("No response was found in the data.");
        }

        $sessioninfo = \mod_observation\session_manager::get_session_info($sessionid);
        $pointdata = self::get_existing_point_data($sessioninfo['obid'], $pointid);
        if ($data->grade_given > $pointdata->max_grade) {
            throw new \coding_exception("Grade given must be less than the max grade.");
        }

        // See if a response already exists for this session and pointid.
        $existingresponse = $DB->get_record('observation_point_responses', ['obs_pt_id' => $pointid, 'obs_ses_id' => $sessionid]);

        // Clean data.
        $dbdata = [
            'obs_pt_id' => $pointid,
            'obs_ses_id' => $sessionid,
            'grade_given' => $data->grade_given,
            'response' => $data->response,
            'ex_comment' => $data->ex_comment,
            'timemodified' => time()
        ];

        if ($existingresponse === false) {
            // Insert new.
            $dbdata['timecreated'] = time();
            return $DB->insert_record('observation_point_responses', $dbdata);
        } else {
            // Update existing.
            $dbdata['id'] = $existingresponse->id;
            $DB->update_record('observation_point_responses', $dbdata);
            return $dbdata['id'];
        }
    }

    /**
     * Generates a HTML table that summarises the observation points and their responses
     * @param int $observationid ID of the observation instance
     * @param int $sessionid ID of the observation session
     * @return string HTML string
     */
    public static function format_points_and_responses($observationid, $sessionid) {
        $pointsandresponses = self::get_points_and_responses($observationid, $sessionid);

        $table = new \html_table();
        $table->head = ['Title', 'Response', 'Grade Given', 'Comment'];

        $table->data = array_map(function($item) use ($sessionid) {

            if ($item->res_type == self::INPUT_EVIDENCE) {
                // Get file area.
                global $DB;
                $record = $DB->get_record('observation', ['id' => $item->obs_id]);
                $data = (array) $record;

                list($observation, $course, $cm) =
                \mod_observation\observation_manager::get_observation_course_cm_from_obid($item->obs_id);
                $context = \context_module::instance($cm->id);

                $storage = get_file_storage();
                $files = $storage->get_area_files($context->id, 'mod_observation', 'response', $item->response);
                $selectedfile = null;

                // Iterate through to find the non-directory file.
                foreach ($files as $file) {
                    if (!$file->is_directory()) {
                        $selectedfile = $file;
                    }
                }

                // Make pluginfile url.
                if (!empty($selectedfile)) {
                    $itemid = $selectedfile->get_itemid();

                    $data['link'] = \moodle_url::make_pluginfile_url(
                        $selectedfile->get_contextid(),
                        $selectedfile->get_component(),
                        $selectedfile->get_filearea(),
                        $itemid,
                        $selectedfile->get_filepath(),
                        $selectedfile->get_filename()
                    );
                } else {
                    $data['link'] = 'submitted file is empty';
                }

                if (!empty($selectedfile)) {
                    // Set the response to html that allows the file to be viewed and downloaded.
                    $item->response = '<img src="'.$data['link'].'?preview=thumb"></img><br>'.$selectedfile->get_filename().'<br>'.
                    \html_writer::link($data['link'], get_string('opennewtab', 'observation'), ['target' => '_blank']).'<br>'.
                    \html_writer::link($data['link'], get_string('download', 'observation'),
                        ['target' => '_blank', 'download' => $selectedfile->get_filename()]);
                }
            }

            return [
                $item->title,
                $item->response,
                $item->grade_given,
                $item->ex_comment
            ];
        }, $pointsandresponses);

        return \html_writer::table($table);
    }
}
