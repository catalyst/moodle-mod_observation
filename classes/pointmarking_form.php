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

        $sep = '_';

        $id = $prefill['id'];

        // TODO display username instead of id
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
                $responseid = $id.$sep.'response';
                $mform->addElement('textarea', $responseid, get_string('textinputtype', 'observation'), ['rows' => 3, 'cols' => 100]);
                $mform->setType($responseid, PARAM_RAW);
                $mform->addRule($responseid, get_string('required', 'observation'), 'required', null, 'client');
                $mform->setDefault($responseid, $prefill['response']);
        }

        $mform->addElement('static', 'max_grade_display', get_string('maxgrade', 'observation'), $prefill['max_grade']);

        $gradegivenid = $id.$sep.'grade_given';
        $mform->addElement('text', $gradegivenid, get_string('gradegiven', 'observation'));
        $mform->setType($gradegivenid, PARAM_INT);
        $mform->addRule($gradegivenid, get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule($gradegivenid, get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule($gradegivenid, get_string('intgreaterthanorzero', 'observation'), 'regex', '/^[0-9]\d*$/', 'client');

        // Extra comment block.
        $excommentid = $prefill['id'].$sep.'ex_comment';
        $mform->addElement('textarea', $excommentid, get_string('extracomment', 'observation'), ['rows' => 3, 'cols' => 100]);
        $mform->setType($excommentid, PARAM_RAW);
        $mform->setDefault($excommentid, $prefill['ex_comment']);

        // Hidden form elements.
        $mform->addElement('hidden', 'sessionid', $prefill['sessionid']);
        $mform->setType('sessionid', PARAM_INT);

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $maxgradeid = $id.$sep.'max_grade';
        $mform->addElement('hidden', $maxgradeid, $prefill['max_grade']);
        $mform->setType($maxgradeid, PARAM_INT);

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

        $sep = '_';
        $prefix = $data['id'].$sep;

        // Ensure grade given <= max grade.
        if($data[$prefix.'grade_given'] > $data[$prefix.'max_grade']){
            $errors[$prefix.'grade_given'] = get_string('gradegivengreatermaxgrade', 'observation');
        }

        return $errors;
    }
}
