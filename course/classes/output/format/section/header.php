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

namespace core_course\output\format\section;

use core_course\output\format as format_output;
use core\output\customtemplate;
use section_info;
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
class header extends format_output {

    /** @var format_base the course format class */
    protected $format;

    /** @var format_base the course format class */
    private $section;

    /**
     * Constructor.
     *
     * @param format_base $format
     * @param bool $editable
     * @param array $displayoptions
     */
    public function __construct(format_base $format, section_info $section) {
        $this->format = $format;
        $this->section = $section;
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        // TODO: check if anything of these atributes are necessary!
        $data = (object)[
            'num' => $section->section,
            'id' => $section->id,
            'title' => $output->section_title($section, $course),
        ];

        if (!$section->visible) {
            $data->headerstyle = 'dimmed_text';
        }

        if (!$output->show_editor($course) && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $data->url = course_get_url($course, $section->section);
            $data->name = get_section_name($course, $section);
        }

        return $data;
    }
}
