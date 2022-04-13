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
 * This file contains functions to create notifications for observation timeslots.
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
 * Creates a moodle_form to create notifications for observation timeslots.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>, Celine Lindeque
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notificationeditor extends \moodleform {
    /**
     * Defines the notification editor form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;

        $mform->addElement('header', 'notificationheader', get_string('createnotification', 'observation'));

        $options = [
            MINSECS => get_string('minutes').' '.get_string('before', 'observation'),
            HOURSECS => get_string('hours').' '.get_string('before', 'observation'),
            DAYSECS => get_string('days').' '.get_string('before', 'observation'),
        ];

        // Change the button text depending on if the filter is enabled.
        $intervalselector = [
            $mform->createElement('text', 'interval_amount'),
            $mform->createElement('select', 'interval_multiplier', '', $options),
            $mform->createElement('submit', 'submit_btn', get_string('create', 'observation'))
        ];

        $mform->addGroup($intervalselector, 'select_group', get_string('receivenotification', 'observation'), null, false);
        $mform->setType('interval_amount', PARAM_INT);

        // Hidden form elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'slotid', $prefill['slotid']);
        $mform->setType('slotid', PARAM_INT);

        // Set defaults.
        $this->set_data($prefill);
    }

    /**
     * Custom validations for the form.
     * NOTE: these are only run server side when get_data() is called.
     * @param mixed $data form data
     * @param mixed $files form files
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!is_int($data['interval_amount']) || $data['interval_amount'] < 1) {
            $errors['select_group'] = get_string('intgreaterthanone', 'observation');
        }

        return $errors;
    }
}
