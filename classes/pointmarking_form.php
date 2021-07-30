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
 * Creates a moodle_form to mark an observation point.
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pointmarking_form extends \moodleform {
    /**
     * Defines the point marking form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('header', 'title');
        
        // Observation point information
        $instext = $prefill['ins'];
        $insformat = $prefill['ins_f'];
        
        $mform->addElement('static', 'instructions', get_string('gradinginstructions', 'observation'), format_text($instext, $insformat));        
        
        // TODO break into own function ??
        // TODO add a break or line here to seperate components
        switch($prefill['res_type']){
            // Text input type
            case 0:
                // TODO link the res_type with the lang string
                $mform->addElement('textarea', 'response', get_string('textinputtype', 'observation'), ['rows' => 3, 'cols' => 100]);
                $mform->setType('response', PARAM_RAW);
                $mform->addRule('response', get_string('required', 'observation'), 'required', null, 'client');
        }

        $mform->addElement('static', 'max_grade_display', get_string('maxgrade', 'observation'), $prefill['max_grade']);

        $mform->addElement('text', 'grade_given', get_string('gradegiven', 'observation'));
        $mform->setType('grade_given', PARAM_INT);
        $mform->addRule('grade_given', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule('grade_given', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('grade_given', get_string('intgreaterthanorzero', 'observation'), 'regex', '/^[0-9]\d*$/', 'client');

        // Extra comment block.
        $mform->addElement('textarea', 'ex_comment', get_string('extracomment', 'observation'), ['rows' => 3, 'cols' => 100]);
        $mform->setType('ex_comment', PARAM_RAW);

        // Hidden form elements.

        // Note this has to remove the underscore or else the required_param check fails.
        $mform->addElement('hidden', 'sessionid', $prefill['session_id']); 
        $mform->setType('sessionid', PARAM_INT);

        $mform->addElement('hidden', 'pointid', $prefill['point_id']);
        $mform->setType('pointid', PARAM_INT);

        $mform->addElement('hidden', 'id', $prefill['point_id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'max_grade');
        $mform->setType('max_grade', PARAM_INT);


        // Enforce validations.
        if ($mform->validate()) {
            $mform->freeze();
        }

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons(false, get_string('save', 'observation'));
    }

    /**
     * Custom validations for the form.
     * NOTE: these are only run server side when get_data() is called.
     */
    function validation($data, $files){
        $errors = [];

        // Ensure grade given <= max grade.
        if($data['grade_given'] > $data['max_grade']){
            $errors['grade_given'] = get_string('gradegivengreatermaxgrade', 'observation');
        }

        return $errors;
    }
}
