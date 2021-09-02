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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\viewpoints;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to view observation points using tablelib
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
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
     * Generates an action button for the table
     * @param string $url base URL for the button to link to
     * @param int $obsid observation ID to add to the URL
     * @param int $pointid observation point ID to add to the url
     * @param string $action action to add to the URL
     * @param string $text button text
     */
    private function action_button(string $url, int $obsid, int $pointid, string $action, string $text) {
        return \html_writer::link(
            new \moodle_url($url, ['id' => $obsid, 'action' => $action, 'pointid' => $pointid]),
            $text,
            ['class' => 'btn btn-secondary']
        );
    }

    /**
     * Data converter for the action column
     * @param mixed $row current row
     */
    public function col_action($row) {
        if ($this->displaymode == \mod_observation\timeslots\timeslots::DISPLAY_MODE_ASSIGNED) {
            $htmlout = "";
            return $htmlout;
        };
        // Add action buttons.
        $htmlout = $this->action_button($this->baseurl, $row->obs_id, $row->id, 'edit', get_string('edit', 'observation'));
        $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'delete', get_string('delete', 'observation'));
        $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'moveup', get_string('moveup', 'observation'));
        $htmlout .= $this->action_button($this->baseurl, $row->obs_id, $row->id, 'movedown', get_string('movedown', 'observation'));
        return $htmlout;
    }
}
