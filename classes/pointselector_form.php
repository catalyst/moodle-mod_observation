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
 * Form to mark an observation point
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

/**
 * Creates a moodle_form to select an observation point
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pointselector_form extends \moodleform {
    /**
     * Defines the point selection form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('header', 'selector_header', get_string('selectpoint', 'observation'));

        // TODO make this display the titles maybe along with the ID.
        $mform->addElement('select', 'pointid', get_string('observationpoint', 'observation'), $prefill['pointid_options']);

        $mform->addElement('hidden', 'sessionid', $prefill['session_id']);
        $mform->setType('sessionid', PARAM_INT);

        // Enforce validations.
        if ($mform->validate()) {
            $mform->freeze();
        }

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons(false, get_string('go', 'observation'));
    }
}
