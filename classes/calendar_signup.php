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
 * Calendar view to signup to timeslots.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

/**
 * Calendar view to signup to timeslots.
 *
 * @package   mod_observation
 * @copyright  Matthew Hilton, Celine Lindeque, Jack Kepper, Jared Hungerford
 * @author Matthew Hilton <mj.hilton@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_signup {

    /**
     * Generates a calendar signup HTML block.
     * @param int $observationid ID of the observation instance
     * @param string $title calendar title
     * @param int $month month to show (1-12)
     * @param int $year year to show
     * @param string $baseurl URL used in button callbacks
     */
    public static function calendar_signup_view(int $observationid, string $title, int $month, int $year, string $baseurl) {
        global $OUTPUT;

        // Get the relevant timeslots.
        $calendarslots = \mod_observation\timeslot_manager::get_timeslots_for_calendar($observationid, $month, $year);

        // Determine how many days in the month, and the starting day of week for padding.
        $daysinmonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Padding is the day of week number (e.g. monday = 1) for the first day of the month & year.
        $startpad = (int) date("w", date(strtotime($year.'-'.$month.'-01'))) - 1;

        // Create array for every day of month.
        $totaldays = $daysinmonth + $startpad;
        $monthdays = array_fill(0, $totaldays, []);

        // Map an object to every day of the month.
        $monthdays = array_map(function($month, $i) use($startpad) {
            // Don't show numbers for the padded section.
            $daynum = $i < $startpad ? "" : $i - $startpad + 1;

            return (object) [
                "daynum" => $daynum,
                "events" => []
            ];
        }, $monthdays, array_keys($monthdays));

        // Place each timeslot into the right place in the month day array.
        foreach ($calendarslots as $slot) {
            $timeslotday = (int) date('j', $slot->start_time) - 1;

            // Add extra data to use in the template.
            $slot->start_time_formatted = userdate($slot->start_time, "%a %e %b %I:%M %p");
            $slot->join_button = $OUTPUT->single_button(new \moodle_url($baseurl, ['id' => $slot->obs_id,
                'slotid' => $slot->id, 'action' => 'join', 'sesskey' => sesskey()]), get_string('join', 'observation'));

            $timeslotdaypadded = $timeslotday + $startpad;
            array_push($monthdays[$timeslotdaypadded]->events, $slot);
        }

        $weeks = array_fill(0, 6, []);

        // Split into weeks.
        for ($week = 0; $week < 6; $week++) {
            $start = $week * 7;

            $weekslots = array_slice($monthdays, $start, 7);

            $weeks[$week] = $weekslots;
        }

        // Determine where the buttons should link to.
        $nextmonth = $month === 12 ? 1 : $month + 1;
        $nextyear = $month === 12 ? $year + 1 : $year;

        $prevmonth = $month === 1 ? 12 : $month - 1;
        $prevyear = $month === 1 ? $year - 1 : $year;

        $nextmonthbtn = $OUTPUT->single_button(new \moodle_url($baseurl,
            ['id' => $observationid, 'calmonth' => $nextmonth, 'calyear' => $nextyear, 'sesskey' => sesskey()]),
            get_string('nextmonth', 'observation'));

        $prevmonthbtn = $OUTPUT->single_button(new \moodle_url($baseurl,
            ['id' => $observationid, 'calmonth' => $prevmonth, 'calyear' => $prevyear, 'sesskey' => sesskey()]),
            get_string('prevmonth', 'observation'));

        // Package data up to pass to template.
        $templatedata = (object) [
            'title' => $title,
            'month' => $month,
            'year' => $year,
            'monthname' => date('F', mktime(0, 0, 0, $month, 10)),
            'nextbtn' => $nextmonthbtn,
            'prevbtn' => $prevmonthbtn,
            'entries' => $weeks
        ];

        return $OUTPUT->render_from_template('mod_observation/calendar_signup', $templatedata);
    }
}
