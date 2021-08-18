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
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
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
 * @author Jared Hungerford, Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeslot_form extends \moodleform {
    /**
     * Defines the time slot form
     */

    public function definition() {
        global $PAGE;
        $mform = $this->_form;

        $prefill = $this->_customdata;

        // Timeslots.

        // Start Time.
        $mform->addElement('header', 'timeslots', get_string('timeslotdetails', 'observation'));
        $mform->addElement('date_time_selector', 'start_time', get_string('starttime', 'observation'));
        $mform->addRule('start_time', get_string('required', 'observation'), 'required', null, 'client');

        // Duration.
        $mform->addElement('text', 'duration', get_string('duration', 'observation'));
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('duration', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addRule('duration', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('duration', get_string('intgreaterthanorzero', 'observation'), 'regex', '/^[0-9]\d*$/', 'client');

        // Multiple timeslots (only shown when creating new timeslots).
        if($prefill['mode'] === 'new') {
            $mform->addElement('header', 'multipletimeslots', get_string('multipletimeslots', 'observation'));
        
            $mform->addElement('checkbox', 'enable_interval', '', get_string('useinterval', 'observation'));

            // Interval selector group.
            $options = [
                MINSECS => get_string('minutes'),
                HOURSECS => get_string('hours'),
                DAYSECS => get_string('days'),
            ];
            
            $intervalselector = [
                $mform->createElement('text', 'interval_amount'),
                $mform->createElement('select', 'interval_multiplier', '', $options)
            ];
            $mform->addGroup($intervalselector, 'interval_select_group', get_string('repeatevery', 'observation'), null, false);
            $mform->disabledIf('interval_select_group', 'enable_interval');
            $mform->setType('interval_amount', PARAM_INT);

            $mform->addElement('date_time_selector', 'interval_end', get_string('until', 'observation'));
            $mform->disabledIf('interval_end', 'enable_interval');

            // Interval preview.
            $mform->addElement('static', 'preview_interval', get_string('previewinterval', 'observation'));
            $mform->addElement('submit', 'preview_submit', get_string('previewinterval', 'observation'));
            $mform->registerNoSubmitButton('preview_submit');
            $mform->disabledIf('preview_submit', 'enable_interval');
        }
            //TODO actually show preview
        
        
        // Selecting Observer.
        $context = $PAGE->context;
        $finalusers = [];
        $users = get_enrolled_users($context, 'mod/observation:performobservation');
        foreach ($users as $user) {
            $finalusers[$user->id] = fullname($user);
        }
        $options = array(
            'multiple' => false,
            'noselectionstring' => get_string('allareas', 'search'),
        );
        $mform->addElement('header', 'selecting_observer', get_string('selecting_observer', 'observation'));
        $mform->addElement('autocomplete', 'observer_id', get_string('teacher', 'observation'), $finalusers, $options);

        $mform->setType('observer_id', PARAM_INT);
        $mform->addRule('observer_id', get_string('required', 'observation'), 'required', null, 'client');
        $mform->addRule('observer_id', get_string('err_numeric', 'form'), 'numeric', null, 'client');

        // Hidden form elements.
        $mform->addElement('hidden', 'id', $prefill['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mode', $prefill['mode']);
        $mform->setType('mode', PARAM_TEXT);

        $mform->addElement('hidden', 'slotid', $prefill['slotid']);
        $mform->setType('slotid', PARAM_INT);

        // Set defaults.
        $this->set_data($prefill);

        // Action buttons.
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = [];

        if ($data['enable_interval'] === "1") {
            if(!is_int($data['interval_amount']) || $data['interval_amount'] < 1){
                $errors['interval_select_group'] = get_string('intgreaterthanone', 'observation');
            }
        }

        return $errors;
    }
}
