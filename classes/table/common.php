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
 * This file contains common static functions used in tables within this plugi
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace mod_observation\table;

 /**
  * Common functions for the mod_observation table classes
  *
  * @package    mod_observation
  * @category   test
  * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
  * @author     Matthew Hilton <mj.hilton@outlook.com>
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
class common {
    /**
     * Creates an action button for the col_action function.
     * @param \moodle_url $url URL for the button to link to
     * @param string $text button text
     * @param string $style button classes
     */
    public static function action_button(\moodle_url $url, string $text, string $style = '') {
        return \html_writer::link(
            $url,
            $text,
            ['class' => 'btn mr-2 '.$style]
        );
    }
}
