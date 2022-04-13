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
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\form;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

/**
 * Creates a moodle_form to edit observation points.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pointeditor extends \moodleform {
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
        $radioarray[] = $mform->createElement('radio', 'res_type', '', get_string('passfailtype', 'observation'), 1);
        $radioarray[] = $mform->createElement('radio', 'res_type', '', get_string('evidencetype', 'observation'), 2);

        $mform->addGroup($radioarray, 'radioar', get_string('obpointtype', 'observation'), array(' '), false);
        $mform->setDefault('type', 0);

        // Evidence file size.
        $mform->addElement('text', 'file_size', get_string('maxfilesize', 'observation'));
        $mform->setType('file_size', PARAM_INT);
        $mform->setDefault('file_size', 500);
        $mform->addRule('file_size', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->hideIf('file_size', 'res_type', 'notchecked', '2');

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

    /**
     * Custom validations for the form.
     * NOTE: these are only run server side when get_data() is called.
     * @param mixed $data form data
     * @param mixed $files form files
     */
    public function validation($data, $files) {
        $errors = [];

        if ((int)$data['res_type'] === 2) {
            // Ensure file size is given for response type 'evidence'.
            if (empty($data['file_size'])) {
                $errors['file_size'] = get_string('filesizerequired', 'observation');
            }

            // Ensure 1 <= file size <= 1000 and is an integer.
            if ($data['file_size'] > 1000 || $data['file_size'] < 1 || !is_int($data['file_size'])) {
                $errors['file_size'] = get_string('filesizebounds', 'observation');
            }
        }

        return $errors;
    }
}
