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
 * This file contains functions to edit observation points for an observation.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\table\viewpoints;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to view observation points using tablelib
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewpoints_table extends \table_sql implements \renderable {

    /**
     * Constructs the table and defines how the data from the SQL query is displayed
     * @param string $uniqueid ID that uniquely identifies this element on the HTML page
     * @param \moodle_url $callbackurl URL used for callback for action buttons in the table
     * @param int $displaymode display mode for table
     * @param int $perpage number of entries per page for the table
     */
    public function __construct(string $uniqueid, \moodle_url $callbackurl, int $displaymode = null, int $perpage = 50) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'id',
            'title',
            'max_grade',
            'list_order',
            'lang_string', // Response type lang string.
            'action',
        ]);

        $this->define_headers([
            get_string('id', 'observation'),
            get_string('title', 'observation'),
            get_string('maxgrade', 'observation'),
            get_string('order', 'observation'),
            get_string('obpointtype', 'observation'),
            get_string('actions', 'observation'),
        ]);

        $this->pagesize = $perpage;

        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->collapsible(false);
        $this->sortable(false, 'list_order', SORT_ASC);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($callbackurl);

        $this->displaymode = $displaymode;
    }

    /**
     * Data converter for the lang_string column
     * @param mixed $row current row
     */
    public function col_lang_string($row) {
        return(get_string($row->lang_string, 'observation'));
    }

    /**
     * Data converter for the action column
     * @param mixed $row current row
     */
    public function col_action($row) {
        // Add action buttons.
        $htmlout = \mod_observation\table\common::action_button(new \moodle_url($this->baseurl, ['id' => $row->obs_id,
            'pointid' => $row->id, 'action' => 'edit', 'sesskey' => sesskey()]),
            get_string('edit', 'observation'), 'btn-secondary');

        $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl, ['id' => $row->obs_id,
            'pointid' => $row->id, 'action' => 'delete', 'sesskey' => sesskey()]),
            get_string('delete', 'observation'), 'btn-secondary');

        $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl, ['id' => $row->obs_id,
            'pointid' => $row->id, 'action' => 'moveup', 'sesskey' => sesskey()]),
            get_string('moveup', 'observation'), 'btn-secondary');

        $htmlout .= \mod_observation\table\common::action_button(new \moodle_url($this->baseurl, ['id' => $row->obs_id,
            'pointid' => $row->id, 'action' => 'movedown', 'sesskey' => sesskey()]),
            get_string('movedown', 'observation'), 'btn-secondary');

        return $htmlout;
    }
}
