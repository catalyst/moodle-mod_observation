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
 * This file contains the backup structure code for mod_observation
 *
 * @package     mod_observation
 * @copyright   Catalyst IT Australia
 * @author      Matthew Hilton
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/observation/backup/moodle2/backup_observation_stepslib.php');

/**
 * Task to backup observation activity
 */
class backup_observation_activity_task extends backup_activity_task {
    /**
     * Backup settings
     */
    protected function define_my_settings() {
    }

    /**
     * Define steps for backing up observation activity
     */
    protected function define_my_steps() {
        $this->add_step(new backup_observation_activity_structure_step('observation_structure', 'observation.xml'));
    }

    /**
     * Content link encoding
     * @param string $content content to encode
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
