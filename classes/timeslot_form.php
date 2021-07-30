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
 * This file contains functions to generate an time slot editor form
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
 * Creates a moodle_form to edit time slots.
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslot_form extends \moodleform {
    /**
     * Defines the time slot form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        //Timeslots

        //Start Time
        $mform->addElement('header', 'timeslots', get_string('timeslots', 'observation'));
        $mform->addElement('date_time_selector', 'start_time', get_string('starttime', 'observation'));
        $mform->addRule('start_time', get_string('required', 'observation'), 'required', null, 'client');
        
        //Duration
        $mform->addElement('text', 'duration', get_string('duration', 'observation'));
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('duration', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule('duration', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('duration', get_string('intgreaterthanorzero', 'observation'), 'regex', '/^[0-9]\d*$/', 'client');

        //Observer
        $mform->addElement('text', 'observer_id', get_string('observer_id', 'observation'));
        $mform->setType('observer_id', PARAM_INT);
        $mform->addRule('observer_id', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('observer_id', get_string('err_numeric', 'form'), 'numeric', null, 'client');

        //Testing with dummy values to check if the error was occuring but unfilled database fields 
        //Didn't fix anything when was attempted still a error reading database 
        //$mform->addElement('text', 'observee_id', get_string('observer_id', 'observation'));
        //$mform->addElement('text', 'id', get_string('observer_id', 'observation'));
        //$mform->addElement('text', 'obs_id', get_string('observer_id', 'observation'));

        // Hidden form elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mode', $prefill['mode']);
        $mform->setType('mode', PARAM_TEXT);

        $mform->addElement('hidden', 'slotid', $prefill['slotid']);
        $mform->setType('slotid', PARAM_INT);

        // Enforce validations.
        if ($mform->validate()) {
            $mform->freeze();
        }

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons();
    }
}
