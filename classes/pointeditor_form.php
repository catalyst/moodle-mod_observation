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

use moodleform;

class pointeditor_form extends moodleform {
    function definition(){
        $mform = $this->_form;

        $mform->addElement('header', 'gradingsettings', get_string('grading', 'observation'));

        // Point type selection.
        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'type', '', get_string('textinputtype', 'observation'), 0);
        $mform->addGroup($radioarray, 'radioar', get_string('obpointtype', 'observation'), array(' '), false);
        $mform->setDefault('type', 0);

        // Grading instructions.
        $mform->addElement('editor', 'pointins_editor', get_string('gradinginstructions', 'observation'));
        $mform->setType('pointins_editor', PARAM_RAW);
        $mform->addRule('pointins_editor', get_string('required', 'observation'), 'required', null, 'client');

        // Max / default grade selection.
        $mform->addElement('text', 'maxgradeinput', get_string('maxgrade', 'observation'));
        $mform->setType('maxgradeinput', PARAM_INT);
        $mform->addRule('maxgradeinput', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule('maxgradeinput', get_string('required', 'observation'), 'required', null, 'client');

        // Enforce validations.
        if ($mform->validate()) {
            $mform->freeze();
        }

        // Action buttons.
        $this->add_action_buttons();
    }
}