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
 * @package    mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Settings form for the observation module.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_observation_mod_form extends moodleform_mod {
    /**
     * Called to define the form for this observation instance.
     * @return void
     */
    protected function definition() {
        // Get config and get the form object to construct.
        $obsconfig = get_config('observation');
        $mform = $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'observation'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));

        // Instructions.
        $mform->addElement('header', 'instructions', get_string('instructions', 'observation'));
        $mform->addElement('static', 'description', get_string('instructions', 'observation'), 
                            get_string('instructions', 'observation')); // TODO: replace.

        // Timeslots.
        $mform->addElement('header', 'timeslots', get_string('timeslots', 'observation'));
        $mform->addElement('static', 'description', get_string('placeholder', 'observation'), 
                            get_string('placeholder', 'observation')); // TODO: replace.

        // Observation Points.
        $mform->addElement('header', 'observationpoints', get_string('observationpoints', 'observation'));
        // Actions.
        // Observation points.
        // TODO: replace.
        $mform->addElement('advcheckbox', 'placeholders', get_string('placeholder', 'observation'), 
                            'Example: Using correct PPE while handling chemicals');
        $mform->addElement('advcheckbox', 'placeholders', get_string('placeholder', 'observation'), 
                            'Example: Reading chemical safety information');
        $mform->addElement('advcheckbox', 'placeholders', get_string('placeholder', 'observation'), 
                            'Example: Choosing correct equipment');

        // Settings.

        // Footer.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}