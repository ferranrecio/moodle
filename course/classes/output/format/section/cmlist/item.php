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
 * Contains the default activity list from a section.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\output\format\section\cmlist;

use core_course\output\format as format_output;
use core\output\customtemplate;
use section_info;
use completion_info;
use format_base;
use renderable;
use templatable;
use cm_info;
use stdClass;

/**
 * Base class to render a course module inside a course format.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item extends format_output {

    /** @var format_base the course format class */
    protected $format;

    /** @var format_base the course format class */
    private $section;

    protected $mod;

    protected $completioninfo;

    /**
     * Constructor.
     *
     * @param format_base $format
     * @param bool $editable
     * @param array $displayoptions
     */
    public function __construct(format_base $format, section_info $section, completion_info $completioninfo, cm_info $mod) {
        $this->format = $format;
        $this->section = $section;
        $this->completioninfo = $completioninfo;
        $this->mod = $mod;
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
        $mod = $this->mod;

        $data = (object)[
            'cmname' => $output->courserenderer->course_section_cm_name($mod),
            'afterlink' => $mod->afterlink,
            'altcontent' => $output->courserenderer->course_section_cm_text($mod),
            'availability' => $output->courserenderer->course_section_cm_availability($mod),
            'url' => $mod->url,
            'completion' => $output->courserenderer->course_section_cm_completion($course, $this->completioninfo, $mod),
        ];

        if (!empty($mod->indent)) {
            $data->indent = $mod->indent;
            if ($mod->indent > 15) {
                $data->hugeindent = true;
            }
        }

        if (!empty($data->cmname)) {
            $data->hasname = true;
        }
        if (!empty($data->url)) {
            $data->hasurl = true;
        }

        // TODO: move modicons to output components.
        $returnsection = $format->get_single_section();
        $data->extras = [];
        if ($output->show_editor($course)) {
            // Edit actions.
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $returnsection);
            $data->extras[] = $output->courserenderer->course_section_cm_edit_actions($editactions, $mod);
            if (!empty($mod->afterediticons)) {
                $data->extras[] = $mod->afterediticons;
            }
            // Move and select options.
            $data->moveicon = course_get_cm_move($mod, $returnsection);
        }

        if (!empty($data->completion) || !empty($data->extras)) {
            $data->hasextras = true;
        }

        return $data;
    }
}
