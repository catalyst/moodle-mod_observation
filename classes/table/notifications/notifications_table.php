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
 * This file contains functions to interact with the observation notifications table.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\notifications;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to view time slot notifications using tablelib
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications_table extends \table_sql implements \renderable {

    /**
     * Constructs the table and defines how the data from the SQL query is displayed
     * @param string $uniqueid ID that uniquely identifies this element on the HTML page
     * @param \moodle_url $callbackurl URL used for callback for action buttons in the table
     * @param int $perpage number of entries per page for the table
     */
    public function __construct(string $uniqueid, \moodle_url $callbackurl, int $perpage = 50) {
        parent::__construct($uniqueid);

        $columns = [
            'id',
            'time_before',
            'actions'
        ];

        $headers = [
            get_string('id', 'observation'),
            get_string('notifyon', 'observation'),
            get_string('actions', 'observation')
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->pagesize = $perpage;
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($callbackurl);
    }

    /**
     * Data converter for the time_before column
     * @param mixed $row current row
     */
    public function col_time_before($row) {
        return userdate($row->start_time - $row->time_before);
    }

    /**
     * Data converter for the action column
     * @param mixed $row current row
     */
    public function col_actions($row) {
        return \mod_observation\table\common::action_button(
            new \moodle_url($this->baseurl, ['action' => 'delete', 'notifyid' => $row->id,
            'sesskey' => sesskey()]), get_string('delete'), 'btn-secondary');
    }
}
