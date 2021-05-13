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
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
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
        $mform = $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'observation'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_RAW);

        // Activity Instructions Elements.
        $mform->addElement('header', 'instructions', get_string('instructions', 'observation'));
        $mform->addElement('editor', 'observerins_editor', get_string('instructionsobserver', 'observation'));
        $mform->setType('observerins_editor', PARAM_RAW);

        $mform->addElement('editor', 'observeeins_editor', get_string('instructionsobservee', 'observation'));
        $mform->setType('observeeins_editor', PARAM_RAW);

        // Timeslots.
        $mform->addElement('header', 'timeslots', get_string('timeslots', 'observation'));
        $mform->addElement('static', 'description', get_string('placeholder', 'observation'),
        get_string('placeholder', 'observation')); // TODO: replace.
        
        //Selecting Observer ***to be moved into Timeslots***
        $searcharea = \core_search\manager::get_search_areas_list(true);
        $areanames = array();
        foreach ($searcharea as $areaid => $searcharea) {
            $areanames[$areaid] = $searcharea->get_visible_name();
        }
        $options = array(
            'multiple' => false,
            'noselectionstring' => get_string('allareas', 'search'),
        );
        $mform->addElement('header', 'Selecting-Observer', get_string('Selecting Observer', 'observation');
        $mform->addElement('autocomplete', 'observers', get_string('Select Observer', 'observation'), get_string('searcharea', 'search'), $areanames, $options);
        

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

    /**
     * Overrides some default values for some form elements
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        // Get observation instruction editor values from the db.
        $obsid = $this->_instance;

        // Creating new - no defaults to add.
        if ($obsid == null) {
            return;
        }

        // Editing, find the defaults and update the form values.
        $obsdata = $DB->get_record('observation', array('id' => $obsid));

        $defaultvalues['observerins_editor']->text = $obsdata->observer_ins;
        $defaultvalues['observerins_editor']->format = $obsdata->observer_ins_f;
        $defaultvalues['observeeins_editor']->text = $obsdata->observee_ins;
        $defaultvalues['observeeins_editor']->format = $obsdata->observee_ins_f;
        return;
    }
}
