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
 * Contains the default section course format output class.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\output;

use core\output\customtemplate;
use core_course\course_format;
use completion_info;
use renderable;
use templatable;
use section_info;
use stdClass;

/**
 * Base class to render a course section.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_format implements renderable, templatable, customtemplate {

    /** @var course_format the course format */
    protected $format;

    /** @var section_info the section info */
    protected $thissection;

    /** @var section header output class */
    private $headerclass;

    /** @var cm list output class */
    private $cmlistclass;

    /** @var section summary output class */
    private $summaryclass;

    /** @var activities summary output class */
    private $cmsummaryclass;

    /** @var section control menu output class */
    private $controlclass;

    /** @var section availability output class */
    private $availabilityclass;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $thissection the section info
     */
    public function __construct(course_format $format, section_info $thissection) {
        $this->format = $format;
        $this->thissection = $thissection;

        // Load output classes names from format.
        $this->headerclass = $format->get_output_classname('section_format\\header');
        $this->cmlistclass = $format->get_output_classname('section_format\\cmlist');
        $this->summaryclass = $format->get_output_classname('section_format\\summary');
        $this->cmsummaryclass = $format->get_output_classname('section_format\\cmsummary');
        $this->controlmenuclass = $format->get_output_classname('section_format\\controlmenu');
        $this->availabilityclass = $format->get_output_classname('section_format\\availability');
    }

    /**
     * Return the output template path for the current component.
     *
     * By default this method will return a core_course template but each individual
     * course format component can override this method in case it uses a diferent template.
     *
     * @return string the template path
     */
    public function get_template(): string {
        return 'core_course/local/section_format';

    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        $format = $this->format;
        $course = $format->get_course();
        $thissection = $this->thissection;
        $singlesection = $format->get_single_section();

        $summary = new $this->summaryclass($format, $thissection);
        $availability = new $this->availabilityclass($format, $thissection);

        $data = (object)[
            'num' => $thissection->section ?? '0',
            'id' => $thissection->id,
            'sectionreturnid' => $singlesection,
            'summary' => $output->render($summary),
            'availability' => $output->render($availability),
        ];

        // Cheack if it is a stealth sections (orphaned).
        if ($thissection->section > $format->get_last_section_number()) {
            $data->isstealth = true;
            $data->ishidden = true;
        }

        if ($output->show_editor($course)) {
            $controlmenu = new $this->controlmenuclass($format, $thissection);
            $data->controlmenu = $output->render($controlmenu);
            if (empty($data->isstealth)) {
                $data->cmcontrols = $output->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            }
        }

        if ($thissection->section == 0) {
            // Section zero is always visible only as a cmlist.
            $cmlist = new $this->cmlistclass($format, $thissection);
            $data->cmlist = $output->render($cmlist);

            // section 0 could have a completion help icon.
            $completioninfo = new completion_info($course);
            $data->completioninfo = $completioninfo->display_help_icon();

            return $data;
        }

        $header = new $this->headerclass($format, $thissection);

        // When a section is displayed alone the title goes over the section, not inside it.
        if ($thissection->section == $singlesection) {
            $data->singleheader = $output->render($header);
        } else {
            $data->header = $output->render($header);

            // Add activities summary if necessary.
            if (!$output->show_editor($course) && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $activities = new $this->cmsummaryclass($format, $thissection);
                $data->activities = $output->render($activities);
                $data->onlysummary = true;
                if (!$format->is_section_current($thissection)) {
                    // In multipage, only the current section (and the section zero) has elements.
                    return $data;
                }
            }
        }

        // Add the cm list.
        if ($thissection->uservisible) {
            $cmlist = new $this->cmlistclass($format, $thissection);
            $data->cmlist = $output->render($cmlist); // $cmlist->export_for_template($output);
        }

        if (!$thissection->visible) {
            $data->ishidden = true;
        }
        if ($format->is_section_current($thissection)) {
            $data->iscurrent = true;
            $data->currentlink = get_accesshide(
                get_string('currentsection', 'format_'.$format->get_format())
            );
        }

        return $data;
    }
}
