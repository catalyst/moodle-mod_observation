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
 * Form to begin an observation
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
 * Creates a moodle_form to start an observation session.
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class startsession_form extends \moodleform {
    /**
     * Defines the session creation form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        // TODO display username instead of id
        $mform->addElement('text', 'observerid', get_string('observer', 'observation'));
        
        // TODO make this a dropdown with auto suggestions
        $mform->addElement('text', 'observeeid', get_string('observee', 'observation'));

        // Hidden form elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        // Enforce validations.
        if ($mform->validate()) {
            $mform->freeze();
        }

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons(false, get_string('start', 'observation'));
    }
}
