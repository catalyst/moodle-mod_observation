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
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\form;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');

/**
 * Creates a moodle_form to mark an observation point.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pointmarking extends \moodleform {

    /**
     * Defines the point marking form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('header', 'obpointheader', get_string('observationpoint', 'observation'));

        // Title.
        $mform->addElement('static', 'title', get_string('title', 'observation'));

        // Observation point information.
        $instext = $prefill['ins'];
        $insformat = $prefill['ins_f'];

        $mform->addElement('static', 'instructions', get_string('gradinginstructions', 'observation'),
            format_text($instext, $insformat));

        switch($prefill['res_type']) {
            // Text input type.
            case \mod_observation\observation_manager::INPUT_TEXT:
                $mform->addElement('textarea', 'response', get_string('textinputtype', 'observation'),
                    ['rows' => 3, 'cols' => 100]);
                $mform->setType('response', PARAM_TEXT);
                $mform->addRule('response', get_string('required', 'observation'), 'required', null, 'client');
                break;
            // Pass/Fail type.
            case \mod_observation\observation_manager::INPUT_PASSFAIL:
                $radioarray = array();
                $radioarray[] = $mform->createElement('radio', 'response', '', get_string('pass', 'observation'), 'Pass');
                $radioarray[] = $mform->createElement('radio', 'response', '', get_string('fail', 'observation'), 'Fail');
                $mform->addGroup($radioarray, 'radioar', get_string('passfailtype', 'observation'), array(' '), false);
                $mform->setType('response', PARAM_TEXT);
                $mform->addRule('radioar', get_string('required', 'observation'), 'required', null, 'client');
                break;
            case \mod_observation\observation_manager::INPUT_EVIDENCE:
                // Image upload here.
                $maxbytes = $prefill['file_size'];
                $mform->addElement('filemanager', 'response', get_string('evidenceupload', 'observation'), null,
                    array('subdirs' => 0, 'maxbytes' => $maxbytes, 'areamaxbytes' => $maxbytes, 'maxfiles' => 1,
                          'accepted_types' => 'audio,video,image,document'));
                $mform->setType('response', PARAM_INT);
                $mform->addRule('response', get_string('required', 'observation'), 'required', null, 'client');
                break;
        }

        $mform->addElement('static', 'max_grade_display', get_string('maxgrade', 'observation'), $prefill['max_grade']);

        $mform->addElement('text', 'grade_given', get_string('gradegiven', 'observation'));
        $mform->setType('grade_given', PARAM_INT);
        $mform->addRule('grade_given', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule('grade_given', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('grade_given', get_string('intgreaterthanorzero', 'observation'), 'regex', '/^[0-9]\d*$/', 'client');

        // Extra comment block.
        $mform->addElement('textarea', 'ex_comment', get_string('extracomment', 'observation'), ['rows' => 3, 'cols' => 100]);
        $mform->setType('ex_comment', PARAM_TEXT);

        // Action buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('save', 'observation'));
        $buttonarray[] = $mform->createElement('submit', 'saveandnext', get_string('saveandnext', 'observation'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

        // Submit Observation.

        $mform->registerNoSubmitButton('submitobservation');
        $mform->addElement('submit', 'submitobservation', get_string('submitobservation', 'observation'));

        // Cancel buttons.

        $mform->registerNoSubmitButton('abandonbutton');
        $mform->addElement('submit', 'abandonbutton', get_string('abandonobservation', 'observation'));

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

        // Set defaults.
        $this->set_data($prefill);
    }

    /**
     * Custom validations for the form.
     * NOTE: these are only run server side when get_data() is called.
     * @param mixed $data form data
     * @param mixed $files form files
     */
    public function validation($data, $files) {
        $errors = [];

        // Ensure grade given <= max grade.
        if ($data['grade_given'] > $data['max_grade']) {
            $errors['grade_given'] = get_string('gradegivengreatermaxgrade', 'observation');
        }

        return $errors;
    }
}
