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
 * This file contains functions to edit time slots for an observation.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\timeslots;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to view time slots using tablelib
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslots_table extends \table_sql implements \renderable {

    /**
     * Constructs the table and defines how the data from the SQL query is displayed
     * @param string $uniqueid ID that uniquely identifies this element on the HTML page
     * @param \moodle_url $callbackurl URL used for callback for action buttons in the table
     * @param int $displaymode to determine the action that will be displayed
     * @param int $perpage number of entries per page for the table
     */
    public function __construct(string $uniqueid, \moodle_url $callbackurl, int $displaymode, int $perpage = 50) {
        parent::__construct($uniqueid);

        $columns = [
            'id',
            'start_time',
            'duration',
            'observer_fullname',
            'observer_email',
            'observee_fullname',
            'observee_email',
            'action',
        ];

        $headers = [
            get_string('id', 'observation'),
            get_string('starttime', 'observation'),
            get_string('duration', 'observation'),
            get_string('observer_fullname', 'observation'),
            get_string('observer_email', 'observation'),
            get_string('observee_fullname', 'observation'),
            get_string('observee_email', 'observation'),
            get_string('actions', 'observation'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->pagesize = $perpage;
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($callbackurl);
        $this->no_sorting('action');

        $this->displaymode = $displaymode;
    }

    /**
     * Data converter for the start_time column
     * @param mixed $row current row
     */
    public function col_start_time($row) {
        return(userdate($row->start_time));
    }

    /**
     * Data converter for the action column
     * @param mixed $row current row
     */
    public function col_action($row) {
        $htmlout = "";

        switch($this->displaymode) {
            case \mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_EDITING:
                $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl,
                    ['id' => $row->obs_id, 'slotid' => $row->id, 'action' => 'edit', 'sesskey' => sesskey()]),
                    get_string('edit', 'observation'), 'btn-secondary');
                $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl,
                    ['id' => $row->obs_id, 'slotid' => $row->id, 'action' => 'delete', 'sesskey' => sesskey()]),
                    get_string('delete', 'observation'), 'btn-secondary');

                if ($row->observee_id !== null) {
                    $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl,
                        ['id' => $row->obs_id, 'slotid' => $row->id, 'action' => 'kick', 'sesskey' => sesskey()]),
                        get_string('kickobservee', 'observation'), 'btn-warning');
                }
                break;

            case \mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_SIGNUP:
                $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl,
                    ['id' => $row->obs_id, 'slotid' => $row->id, 'action' => 'join', 'sesskey' => sesskey()]),
                    get_string('join', 'observation'), 'btn-secondary');
            break;

            case \mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_UPCOMING:
                if ($row->observee_id !== null) {
                    $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl, ['id' => $row->obs_id,
                        'slotid' => $row->id, 'action' => 'startsession', 'sesskey' => sesskey()]),
                        get_string('startobservationsession', 'observation'), 'btn-secondary');
                } else {
                    $htmlout .= get_string('noobservee', 'observation');
                }
            break;
            case \mod_observation\table\timeslots\timeslots_display::DISPLAY_MODE_OBSERVEE_REGISTERED:
                if (\mod_observation\timeslot_manager::can_unenrol($row->obs_id, $row->id, $row->observee_id) === true) {
                    $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl,
                    ['id' => $row->obs_id, 'slotid' => $row->id, 'sesskey' => sesskey(), 'action' => 'unenrol']),
                    get_string('unenrol', 'observation'), 'btn-danger');
                }
            break;
        }
        return $htmlout;
    }
}
