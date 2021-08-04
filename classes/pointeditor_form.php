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
 * This file contains functions to generate an observation point editor form
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
 * Creates a moodle_form to edit observation points.
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pointeditor_form extends \moodleform {
    /**
     * Defines the observation point form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('header', 'gradingsettings', get_string('grading', 'observation'));

        // Point type selection.
        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'res_type', '', get_string('textinputtype', 'observation'), 0);
        $radioarray[] = $mform->createElement('radio', 'res_type', '', get_string('passfailtype', 'observation'), 0);
        $mform->addGroup($radioarray, 'radioar', get_string('obpointtype', 'observation'), array(' '), false);
        $mform->setDefault('type', 0);

        // Title.
        $mform->addElement('text', 'title', get_string('title', 'observation'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required', 'observation'), 'required', null, 'client');

        // Grading instructions.
        $mform->addElement('editor', 'ins', get_string('gradinginstructions', 'observation'));
        $mform->setType('ins', PARAM_TEXT);
        $mform->addRule('ins', get_string('required', 'observation'), 'required', null, 'client');

        // Max / default grade selection.
        $mform->addElement('text', 'maxgrade', get_string('maxgrade', 'observation'));
        $mform->setType('maxgrade', PARAM_INT);
        $mform->addRule('maxgrade', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule('maxgrade', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('maxgrade', get_string('intgreaterthanorzero', 'observation'), 'regex', '/^[0-9]\d*$/', 'client');

        // Hidden form elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mode', $prefill['mode']);
        $mform->setType('mode', PARAM_TEXT);

        $mform->addElement('hidden', 'pointid', $prefill['pointid']);
        $mform->setType('pointid', PARAM_INT);

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons();
    }
}
