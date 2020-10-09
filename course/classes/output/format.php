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
use completion_info;
use format_base;
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
class format implements renderable, templatable, customtemplate {

    /** @var format_base the course format class */
    protected $format;

    protected $sectionclass;

    /**
     * Constructor.
     *
     * @param format_base $format
     * @param bool $editable
     * @param int|null $returnsection if
     */
    public function __construct(format_base $format) {
        $this->format = $format;

        // Load output classes names from format.
        $this->sectionclass = $format->get_output_classname('format\\section');
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
        global $CFG;
        // By default, all format oputput classnames contains the template path except the component
        // which can be format_XXX or core_course.
        // Remove the component from the classname and calculate the real path.
        $relativepath = str_replace('\\', '/', get_class($this));
        $offset = strpos($relativepath, '/output/') + 8;
        $relativepath = substr($relativepath, $offset);
        return $this->get_format_template($relativepath);

    }

    /**
     * Return the default template to render a specific component.
     *
     * Course formats can override this methos to use a diferent template path logic
     * and replace the default templates.
     *
     * @param string $relativepath the template relative path
     * @return string the real template path
     */
    protected function get_format_template(string $relativepath): string {
        return 'core_course/'.$relativepath;
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

        // Title with completion help icon.
        $completioninfo = new completion_info($course);

        $data = (object)[
            'title' => $output->page_title(),
            'completionhelp' => $this->get_legacy_html([$completioninfo, 'display_help_icon'], []), // $completioninfo->display_help_icon(),
            'sections' => $this->export_sections($output),
            // TODO: 'stealthsections' => $this->export_stealth_sections($output),
            'numsections' => $this->get_legacy_html([$output, 'change_number_sections'], [$course, 0]), // $output->change_number_sections($course, 0),
        ];
        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    private function export_sections(\renderer_base $output): array {

        $format = $this->format;
        $course = $format->get_course();

        // Generate seciton list.
        $sections = [];
        $numsections = $format->get_last_section_number();
        foreach ($this->get_sections_to_display() as $sectionnum => $thissection) {
            if ($sectionnum > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }

            $section = new $this->sectionclass($format, $thissection);

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
        return $sections;
    }

    private function get_sections_to_display() {
        $modinfo = $this->format->get_modinfo();
        $singlesection = $this->format->get_single_section();
        if ($singlesection) {
            return [$modinfo->get_section_info($singlesection)];
        }

        return $modinfo->get_section_info_all();
    }

    /**
     * This is just a temporal method and will be deleted when all output classes and templates are created.
     *
     * @param array $callback
     * @param array $params
     * @return string
     */
    protected function get_legacy_html(array $callback, array $params): string {
        ob_start();
        call_user_func_array ($callback , $params);
        $output = ob_get_clean();
        return $output;
    }

    // TODO: move to a separate output.
    protected function export_stealth_sections(\renderer_base $output): array {
        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $format->get_modinfo();

        if (!$output->show_editor($course)) {
            return [];
        }

        $sections = [];
        // Print stealth sections if present.
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section <= $numsections or empty($modinfo->sections[$section])) {
                // this is not stealth section or it is empty
                continue;
            }
            $sections[] = (object)[
                'title' => $output->stealth_section_header($section),
                'cmlist' => $output->courserenderer->course_section_cm_list($course, $thissection, 0),
            ];
        }

        return $sections;
    }
}
