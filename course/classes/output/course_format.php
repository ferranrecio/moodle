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
 * Contains the main course format out class.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\output;

use core\output\customtemplate;
use core_course\course_format as course_format_base;
use completion_info;
use course_modinfo;
use renderable;
use templatable;
use stdClass;

/**
 * Base class to render a course format.
 *
 * @package   core_course
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_format implements renderable, templatable, customtemplate {

    /** @var core_course\course_format the course format class */
    protected $format;

    protected $sectionclass;
    protected $addsectionclass;
    protected $sectionnavigationclass;
    protected $sectionselectorclass;

    /**
     * Constructor.
     *
     * @param course_format_base $format the coruse format
     */
    public function __construct(course_format_base $format) {
        $this->format = $format;

        // Load output classes names from format.
        $this->sectionclass = $format->get_output_classname('section_format');
        $this->addsectionclass = $format->get_output_classname('course_format\\addsection');
        $this->sectionnavigationclass = $format->get_output_classname('course_format\\sectionnavigation');
        $this->sectionselectorclass = $format->get_output_classname('course_format\\sectionselector');
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
        return 'core_course/local/course_format';

    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        $format = $this->format;
        $course = $format->get_course();

        $addsection = new $this->addsectionclass($format);

        // Most formats uses section 0 as a separate section so we remove from the list.
        $sections = $this->export_sections($output);
        if (count($sections) > 1){
            $initialsection = array_shift($sections);
        }

        $data = (object)[
            'title' => $format->page_title(), // This method should be in the course_format class.
            'initialsection' => $initialsection,
            'sections' => $sections,
            'numsections' => $output->render($addsection),
            'format' => $format->get_format(),
        ];

        // The single section format has extra navigation.
        $singlesection = $this->format->get_single_section();
        if ($singlesection) {
            $sectionnavigation = new $this->sectionnavigationclass($format, $singlesection);
            $data->sectionnavigation = $output->render($sectionnavigation);

            $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
            $data->sectionselector = $output->render($sectionselector);

            $data->hasnavigation = true;
            $data->singlesection = array_shift($data->sections);
        }

        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    protected function export_sections(\renderer_base $output): array {

        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();

        // Generate section list.
        $sections = [];
        $stealthsections = [];
        $numsections = $format->get_last_section_number();
        foreach ($this->get_sections_to_display($modinfo) as $sectionnum => $thissection) {
            // The course/view.php check the section existence but the output can be called
            //  from other parts so we need to check it.
            if (!$thissection) {
                print_error('unknowncoursesection', 'error', course_get_url($course), format_string($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);

            if ($sectionnum > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                if (!empty($modinfo->sections[$sectionnum])) {
                    $stealthsections[] = $output->render($section);
                }
                continue;
            }

            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                    (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            $sections[] = $output->render($section);
        }
        if (!empty($stealthsections)) {
            $sections = array_merge($sections, $stealthsections);
        }
        return $sections;
    }

    /**
     * Return an array of sections to display.
     *
     * This method is used to differentiate between display a specific section
     * or a list of them.
     *
     * @param course_modinfo $modinfo the current course modinfo object
     * @return section_info[] an array of section_info to display
     */
    private function get_sections_to_display(course_modinfo $modinfo): array {
        $singlesection = $this->format->get_single_section();
        if ($singlesection) {
            return [
                $modinfo->get_section_info(0),
                $modinfo->get_section_info($singlesection),
            ];
        }

        return $modinfo->get_section_info_all();
    }
}
