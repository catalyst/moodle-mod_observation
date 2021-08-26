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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

/**
 * Creates a moodle_form to select a filter for the 'upcoming timeslots' table
 *
 * @package   mod_observation
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upcomingfilter_form extends \moodleform {
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

        $intervalselector = [
            $mform->createElement('text', 'interval_amount'),
            $mform->createElement('select', 'interval_multiplier', '', $options),
            $mform->createElement('submit', 'submit_btn', get_string('applyfilter', 'observation'))
        ];

        $mform->addGroup($intervalselector, 'interval_select_group', get_string('filterwithin', 'observation'), null, false);
        $mform->disabledIf('interval_select_group', 'enable_interval');
        $mform->setType('interval_amount', PARAM_INT);

        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'current_filter', $prefill['current_filter']);
        $mform->setType('current_filter', PARAM_INT);

        if ($prefill['current_filter'] !== 0) {
            $mform->addElement('cancel', 'reset_filter', get_string('resetfilter', 'observation'));
        }

        $mform->disabledIf('interval_select_group', 'current_filter', 'neq', 0);

        // Set defaults.
        $this->set_data($prefill);
    }
}
