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

namespace core_course\output\format;

use core_course\output\format as format_output;
use core\output\customtemplate;
use completion_info;
use format_base;
use renderable;
use templatable;
use stdClass;

/**
 * Base class to render a course section.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends format_output {

    /** @var format_base the course format class */
    protected $format;

    protected $thissection;

    /** @var section header output class */
    private $headerclass;

    /** @var cm list output class */
    private $cmlistclass;

    /** @var section summary output class */
    private $summaryclass;

    /** @var activities summary output class */
    private $activitiesclass;

    /**
     * Constructor.
     *
     * @param format_base $format
     * @param bool $editable
     * @param array $displayoptions
     */
    public function __construct(format_base $format, $thissection) {
        $this->format = $format;
        $this->thissection = $thissection;

        // Load output classes names from format.
        $this->headerclass = $format->get_output_classname('format\\section\\header');
        $this->cmlistclass = $format->get_output_classname('format\\section\\cmlist');
        $this->summaryclass = $format->get_output_classname('format\\section\\summary');
        $this->activitiesclass = $format->get_output_classname('format\\section\\activities');
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        $format = $this->format;
        $course = $format->get_course();
        $thissection = $this->thissection;
        // TODO: eliminate this variable when all elements are moved to output components.
        $singlesection = $format->get_single_section();

        $data = (object)[
            'num' => $thissection->section ?? '0',
            'id' => $thissection->id,
            'sectionreturnid' => $singlesection,
            'sectionstyle' => $this->export_section_style(),
            'leftcontent' => $output->section_left_content($thissection, $course, $singlesection),
            'rightcontent' => $output->section_right_content($thissection, $course, $singlesection),
            'availability' => $output->section_availability($thissection),
        ];

        $summary = new $this->summaryclass($format, $thissection);
        $data->summary = $output->render($summary);

        if ($output->show_editor($course)) {
            $data->cmcontrols = $output->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
        }

        if ($thissection->section == 0) {
            // Section zero is always visible only as a cmlist.
            $cmlist = new $this->cmlistclass($format, $thissection);
            $data->cmlist = $output->render($cmlist);
            return $data;
        }

        $header = new $this->headerclass($format, $thissection);
        $data->header = $output->render($header);

        // Add activities summary if necessary.
        if (!$output->show_editor($course) && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $activities = new $this->activitiesclass($format, $thissection);
            $data->activities = $output->render($activities);
            $data->onlysummary = true;
            if (!$format->is_section_current($thissection)) {
                // In multipage, only the current section (and the section zero) has elements.
                return $data;
            }
        }

        // Add the cm list.
        if ($thissection->uservisible) {
            $cmlist = new $this->cmlistclass($format, $thissection);
            $data->cmlist = $output->render($cmlist); // $cmlist->export_for_template($output);
        }
        return $data;
    }

    private function export_section_style() {
        $format = $this->format;
        $thissection = $this->thissection;

        $sectionstyle = '';
        if ($thissection->section != 0) {
            // Only in the non-general sections.
            if (!$thissection->visible) {
                $sectionstyle = 'hidden';
            }
            if ($format->is_section_current($thissection)) {
                $sectionstyle = 'current';
            }
        }
        return $sectionstyle;
    }
}
