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

namespace format_weeks\courseformat;

use section_info;

/**
 * Format weeks section actions.
 *
 * @package    format_weeks
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sectionactions extends \core_courseformat\local\sectionactions{
    /**
     * Adjust the numbers course sections to include a final date.
     *
     * @param int $finaldate A date included in the final week.
     * @param bool $movecontents Whether to move the contents of the weeks after the final week date (will be deleted otherwise).
     * @return bool Whether the course sections were changed.
     */
    public function set_final_week_date(int $finaldate, bool $movecontents = true): bool {
        if ($finaldate < $this->course->startdate) {
            throw new \moodle_exception('finalweek_error_before_start', 'format_weeks');
        }

        $oldlastsectionum = $this->get_format()->get_last_section_number();

        $newlastsection = $this->create_weeks_to_date($finaldate);

        if ($oldlastsectionum <= $newlastsection->sectionnum) {
            // We only create new sections, no need to remove anything.
            return $oldlastsectionum != $newlastsection->sectionnum;
        }

        return $this->remove_later_weeks($newlastsection, $movecontents);
    }

    /**
     * Create as many weeks as needed to reach to include a final date.
     *
     * @param int $finaldate A date included in the final week.
     * @return section_info The last section created.
     */
    private function create_weeks_to_date(int $finaldate): section_info {
        $format = $this->get_format();
        $modinfo = get_fast_modinfo($this->course);
        $sections = $modinfo->get_section_info_all();

        $lastsectionchecked = reset($sections);
        $lastcheckeddate = 0;
        foreach ($sections as $section) {
            $dates = $format->get_section_dates($section);
            if ($dates->start < $finaldate) {
                $lastsectionchecked = $section;
                $lastcheckeddate = $dates->end;
                continue;
            } else {
                // This is the first section after the final week date
                // we return the last section checked.
                return $lastsectionchecked;
            }
        }

        while ($lastcheckeddate <= $finaldate) {
            $sectiondata = $this->create();
            // We are creating new sections se we need to get new modinfo every time.
            $modinfo = get_fast_modinfo($this->course);
            $lastsectionchecked = $modinfo->get_section_info_by_id($sectiondata->id);
            $lastcheckeddate = $format->get_section_dates($lastsectionchecked)->end;
        }
        return $lastsectionchecked;
    }

    /**
     * Remove all sections after the given section.
     *
     * @param section_info $section The section to remove.
     * @param bool $movecontents Whether to move the contents of the weeks after the final week date (will be deleted otherwise).
     * @return bool Whether the course sections were changed.
     */
    private function remove_later_weeks(section_info $lastsection, bool $movecontents): bool {
        global $CFG;

        // This action still needs some global course lib functions to move activities.
        require_once($CFG->dirroot . '/course/lib.php');

        $modinfo = get_fast_modinfo($this->course);
        $sectiontodelete = $modinfo->get_section_info($lastsection->sectionnum + 1);

        $coursechanged = false;
        while($sectiontodelete !== null) {
            if ($movecontents) {
                $cms = $sectiontodelete->get_sequence_cm_infos();
                foreach ($cms as $cm) {
                    moveto_module(
                        $cm,
                        $modinfo->get_section_info_by_id($lastsection->id)
                    );
                }
                // We need to get the section object with the updated cm_info sequence.
                $sectiontodelete = get_fast_modinfo($this->course)->get_section_info_by_id($sectiontodelete->id);
            }
            $this->delete($sectiontodelete);
            $coursechanged = true;

            // We need a fresh modinfo every time we delete a section.
            $modinfo = get_fast_modinfo($this->course);
            $sectiontodelete = $modinfo->get_section_info($lastsection->sectionnum + 1);
        }
        return $coursechanged;
    }
}
