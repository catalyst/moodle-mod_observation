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
 * Form for 'upcoming timeslots' table
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
 * Creates a moodle_form to select a filter for the 'upcoming timeslots' table
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upcomingfilter extends \moodleform {
    /**
     * Defines the form
     */
    public function definition() {
        $mform = $this->_form;

        $prefill = $this->_customdata;
        // Interval selector group.
        $options = [
            MINSECS => get_string('minutes'),
            HOURSECS => get_string('hours'),
            DAYSECS => get_string('days'),
        ];

        // Change the button text depending on if the filter is enabled.
        $buttontext = $prefill['filter_enabled'] === true ? get_string('resetfilter', 'observation')
            : get_string('applyfilter', 'observation');

        $intervalselector = [
            $mform->createElement('text', 'interval_amount'),
            $mform->createElement('select', 'interval_multiplier', '', $options),
            $mform->createElement('submit', 'submit_btn', $buttontext)
        ];

        // Interval selector block.
        $mform->addGroup($intervalselector, 'interval_select_group', get_string('filterwithin', 'observation'), null, false);
        $mform->disabledIf('interval_amount', 'enable_interval');
        $mform->setType('interval_amount', PARAM_INT);

        // Hidden Elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'filter_enabled', $prefill['filter_enabled']);
        $mform->setType('filter_enabled', PARAM_BOOL);

        // Disable all filter elements except the cancel button if the filter is applied.
        $mform->disabledIf('interval_amount', 'filter_enabled', 'eq', 1);
        $mform->disabledIf('interval_multiplier', 'filter_enabled', 'eq', 1);

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

        // Ensure interval amount is >= 0 if the interval is not currently enabled.
        if (!$data['filter_enabled'] && (!is_int($data['interval_amount']) || $data['interval_amount'] < 0)) {
            $errors['interval_select_group'] = get_string('intgreaterthanorzero', 'observation');
        }

        return $errors;
    }
}
