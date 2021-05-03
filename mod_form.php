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
 * Defines the observation module settings form.
 *
 * @package    mod_form
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
// require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Settings form for the observation module.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_observation_mod_form extends moodleform_mod {
    public function __construct($current, $section, $cm, $course){
        // Can do things to add extra data to constructor here
        // Else just call the parents constructor 
        parent::__construct($current, $section, $cm, $course);
    }

    protected function definition() {
        // Get config and get the form object to construct
        $obsconfig = get_config('observation');
        $mform = $this->_form;

        // General
        $mform->addElement('header', 'general', get_string('general', 'observation'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));

        // Time Slots
        $mform->addElement('header', 'timeslot', get_string('timeslot', 'observation'));

        //Testing
        //$mform->addElement('text', 'starttime', get_string('starttime', 'observation'), array('size'=>'64'));
        $mform->setExpanded('timeslot', true);

        //Setting up boxes to set time slots
        $name = get_string('starttime', 'observation');
        $mform->addElement('date_time_selector', 'starttime', $name, array('optional'=>false));
        $mform->addHelpButton('starttime', 'starttime', 'observation');

        $name = get_string('endtime', 'observation');
        $mform->addElement('date_time_selector', 'endtime', $name, array('optional'=>false));
        $mform->addHelpButton('endtime', 'endtime', 'observation');

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------
        $this->add_action_buttons();
    }
}