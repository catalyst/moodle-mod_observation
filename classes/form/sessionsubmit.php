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
 * Form to submit an observation point sesssion
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\form;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

/**
 * Creates a moodle_form to submit an observation point sesssion
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessionsubmit extends \moodleform {
    /**
     * Defines the point marking form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('text', 'gradecalculated', get_string('overallgrade', 'observation'));
        $mform->setType('gradecalculated', PARAM_TEXT);
        $mform->freeze('gradecalculated');

        $mform->addElement('textarea', 'extracomment', get_string('gradebookcomment', 'observation'), ['rows' => 3, 'cols' => 100]);
        $mform->setType('extracomment', PARAM_TEXT);

        // Hidden form elements.
        $mform->addElement('hidden', 'sessionid', $prefill['sessionid']);
        $mform->setType('sessionid', PARAM_INT);

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons(false, get_string('submitobservation', 'observation'));
    }
}
