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
 * This file contains the page view if the user has the capability 'perform_observations'
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

/**
 * mod_observation functions for processing instructions
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructions {
    /**
     * Generates a formatting block of HTML to output to a page that displays the instructions passed
     *
     * @param string|null $heading Instructions heading
     * @param string|null $bodytext Instructions body text
     * @param int|null $bodyformat Format for the body (moodle format identifier)
     * @param int $headinglevel HTML heading level
     * @param string|null $defaultmessage Message to display if there were no instructions given.
     * Defaults to lang string ['defaultmessagenoinstructions]
     * @return string formatted html to be displays encoded as a string
     **/
    public static function observation_instructions(?string $heading, ?string $bodytext, ?int $bodyformat, int $headinglevel = 3,
        string $defaultmessage = null): string {

        global $OUTPUT;
        // Can't set function values as default parameters, so do it here.
        if (is_null($defaultmessage)) {
            $defaultmessage = get_string('defaultmessagenoinstructions', 'observation');
        }

        $out = $OUTPUT->container_start();

        if (is_string($heading)) {
            $out .= $OUTPUT->heading($heading, $headinglevel);
        }

        if (!is_null($bodytext) && !is_null($bodyformat)) {
            $out .= format_text($bodytext, $bodyformat);
        } else {
            $out .= $defaultmessage;
        }

        $out .= $OUTPUT->container_end();
        return $out;
    }
}
