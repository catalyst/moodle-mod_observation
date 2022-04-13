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
 * Form to begin an observation
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
 * Creates a moodle_form to start an observation session.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class startsession extends \moodleform {
    /**
     * Defines the session creation form
     */
    public function definition() {
        global $PAGE;
        global $USER;

        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('header', 'startnewheading', 'Start New');

        // Observer and Observee Selection.

        // Get list of users full names .
        $context = $PAGE->context;
        $finalusers = [];
        $users = get_enrolled_users($context);
        foreach ($users as $u) {
            $finalusers[$u->id] = fullname($u);
        }

        $options = array(
            'multiple' => false,
        );

        $mform->addElement('text', 'observername', get_string('observer', 'observation'));
        $mform->setDefault('observername', fullname($USER));
        $mform->setType('observername', PARAM_TEXT);
        $mform->freeze('observername');

        $mform->addElement('autocomplete', 'observeeid', get_string('observee', 'observation'), $finalusers, $options);
        $mform->addRule('observeeid', get_string('required', 'observation'), 'required', null, 'client');

        // Hidden form elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'observerid', $prefill['observerid']);
        $mform->setType('observerid', PARAM_INT);

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $mform->addElement('submit', 'submitbtn', get_string('start', 'observation'));
    }
}
