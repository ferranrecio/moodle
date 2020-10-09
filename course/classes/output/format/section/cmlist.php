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

namespace core_course\output\format\section;

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
 * Base class to render a section activity list.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmlist extends format_output {

    /** @var format_base the course format class */
    protected $format;

    /** @var format_base the course format class */
    private $section;

    protected $itemclass;

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

        // Get the necessary classes.
        $this->itemclass = $format->get_output_classname('format\\section\\cmlist\\item');
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
        $modinfo = $format->get_modinfo();

        $completioninfo = new completion_info($course);

        $data = new stdClass();
        $data->cms = [];

        if (empty($modinfo->sections[$section->section])) {
            return $data;
        }

        foreach ($modinfo->sections[$section->section] as $modnumber) {
            $mod = $modinfo->cms[$modnumber];
            if ($mod->is_visible_on_course_page()) {
                $data->cms[] = $this->export_list_item($output, $completioninfo, $mod);
            }
        }

        if (!empty($data->cms)) {
            $data->hascms = true;
        }

        return $data;
    }

    protected function export_list_item(\renderer_base $output, completion_info $completioninfo, cm_info $mod) {
        $item = new $this->itemclass($this->format, $this->section, $completioninfo, $mod);
        return (object)[
            'id' => $mod->id,
            'module' => $mod->modname,
            'extraclasses' => $mod->extraclasses,
            'modhtml' => $output->render($item),
        ];
    }
}
