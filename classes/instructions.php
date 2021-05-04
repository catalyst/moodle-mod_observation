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
 * @copyright  2021 Endurer Solutions Team
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generates a formatting block of HTML to output to a page that displays the instructions passed
 *
 * @param string $heading Instructions heading
 * @param string $bodytext Instructions body text
 * @param int $bodyformat Format for the body (moodle format identifier)
 * @param int $headinglevel HTML heading level
 * @return string formatted html to be displays encoded as a string
 **/
function observation_instructions(string $heading, string $bodytext, int $bodyformat, int $headinglevel=3): string {
    global $OUTPUT;

    $out = $OUTPUT->container_start();
    $out .= $OUTPUT->heading($heading, $headinglevel);
    $out .= format_text($bodytext, $bodyformat);
    $out .= $OUTPUT->container_end();

    return $out;
}