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
 * @copyright  2021 Endurer Solutions Team
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\timeslots;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to view time slots using tablelib
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
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
    public function __construct(string $uniqueid, \moodle_url $callbackurl, int $displaymode, int $perpage = 10) {
        parent::__construct($uniqueid);

        $columns = [
            'id',
            'start_time',
            'duration',
            'observer_fullname',
            'observer_email',
            'action',
        ];

        $headers = [
            get_string('id', 'observation'),
            get_string('starttime', 'observation'),
            get_string('duration', 'observation'),
            get_string('observer_fullname', 'observation'),
            get_string('observer_email', 'observation'),
            get_string('actions', 'observation'),
        ];

        if ($displaymode === \mod_observation\timeslots\timeslots::DISPLAY_MODE_UPCOMING) {
            // Add observee details as the second last column.
            array_splice($columns, count($columns) - 1, 0, 'observee_fullname');
            array_splice($headers, count($headers) - 1, 0, get_string('observeename', 'observation'));
        }

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
     * Generates an action button for the table
     * @param string $url base URL for the button to link to
     * @param int $obsid observation ID to add to the URL
     * @param int $slotid observation slot ID to add to the url
     * @param string $action action to add to the URL
     * @param string $text button text
     */
    private function action_button(string $url, int $obsid, int $slotid, string $action, string $text) {
        return \html_writer::link(
            new \moodle_url($url, ['id' => $obsid, 'action' => $action, 'slotid' => $slotid]),
            $text,
            ['class' => 'btn btn-secondary']
        );
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

        // If statement to determine editting or viewing.

        $htmlout = "";

        switch($this->displaymode) {
            case \mod_observation\timeslots\timeslots::DISPLAY_MODE_EDITING:
                $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'edit', get_string('edit', 'observation'));
                $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'delete',
                    get_string('delete', 'observation'));
            break;

            case \mod_observation\timeslots\timeslots::DISPLAY_MODE_SIGNUP:
                $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'join', get_string('join', 'observation'));
            break;

            case \mod_observation\timeslots\timeslots::DISPLAY_MODE_UPCOMING:
                if ($row->observee_id !== null) {
                    $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'startsession',
                        get_string('startobservationsession', 'observation'));
                } else {
                    $htmlout .= get_string('noobservee', 'observation');
                }
            break;
        }

        if ($this->displaymode === \mod_observation\timeslots\timeslots::DISPLAY_MODE_VIEW_ASSIGNED) {
            $htmlout = $this->action_button('timesloteditor.php?mode=edit&', $row->obs_id, $row->id, 'edit', get_string('edit', 'observation'));
            $htmlout .= $this->action_button('timesloteditor.php?', $row->obs_id, $row->id, 'delete', get_string('delete', 'observation'));
        }

        return $htmlout;
    }
}
