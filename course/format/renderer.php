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
 * Base renderer for outputting course formats.
 *
 * @package core
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */

defined('MOODLE_INTERNAL') || die();


/**
 * This is a convenience renderer which can be used by section based formats
 * to reduce code duplication. It is not necessary for all course formats to
 * use this and its likely to change in future releases.
 *
 * @package core
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */
abstract class format_section_renderer_base extends plugin_renderer_base {

    /** @var core_course_renderer contains instance of core course renderer */
    public $courserenderer;

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->courserenderer = $this->page->get_renderer('core', 'course');
    }

    /**
     * Renders the provided widget and returns the HTML to display it.
     *
     * Note: this method can be removed when all visual elements
     * are moved to output components.
     *
     * @param renderable $widget instance with renderable interface
     * @return string
     */
    public function render(renderable $widget) {
        if ($widget instanceof templatable && $widget instanceof core\output\customtemplate) {
            $template = $widget->get_template();
            $context = $widget->export_for_template($this);
            return $this->render_from_template($template, $context);
        }
        return parent::render($widget);
    }

    /**
     * return true if the course editor must be displayed.
     *
     * @param stdClass $course course record
     * @return bool true if edit controls must be displayed
     */
    public function show_editor(stdClass $course): bool {
        $coursecontext = context_course::instance($course->id);
        return $this->page->user_is_editing() && has_capability('moodle/course:update', $coursecontext);
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        $title = get_section_name($course, $section);
        $url = course_get_url($course, $section->section, array('navigation' => true));
        if ($url) {
            $title = html_writer::link($url, $title);
        }
        return $title;
    }

    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return get_section_name($course, $section);
    }

    /**
     * Generate the edit control action menu
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * The section edit controls are now part of the main section_format output and does
     * not use renderer methods anymore.
     *
     * @param array $controls The edit control items from section_edit_control_items
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function section_edit_control_menu($controls, $course, $section) {
        throw new coding_exception('section_edit_control_menu() can not be used anymore. Please use ' .
            'core_course\output\section_format to render a section. In case you need to modify those controls '.
            'override core_course\output\section_format\controlmenu in your format plugin.');
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * Spatial references like "left" or "right" are limiting the way formats and themes can
     * extend courses. The elements from this method are now included in the section_format
     * output components.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    public function section_right_content($section, $course, $onsectionpage) {
        throw new coding_exception('section_right_content() can not be used anymore. Please use ' .
            'core_course\output\section_format to render a section.');
    }

    /**
     * Generate the content to displayed on the left part of a section
     * before course modules are included
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * Spatial references like "left" or "right" are limiting the way formats and themes can
     * extend courses. The elements from this method are now included in the section_format
     * output components.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    public function section_left_content($section, $course, $onsectionpage) {
        throw new coding_exception('section_left_content() can not be used anymore. Please use ' .
            'core_course\output\section_format to render a section.');
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    public function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        throw new coding_exception('section_header() can not be used anymore. Please use ' .
            'core_course\output\section_format to render a section.');
    }

    /**
     * Generate the display of the footer part of a section.
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @return string HTML to output.
     */
    protected function section_footer() {
        throw new coding_exception('section_footer() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections.');
    }

    /**
     * @deprecated since Moodle 3.0 MDL-48947 - Use format_section_renderer_base::section_edit_control_items() instead
     */
    protected function section_edit_controls() {
        throw new coding_exception('section_edit_controls() can not be used anymore. Please use ' .
            'section_edit_control_items() instead.');
    }

    /**
     * Generate the edit control items of a section
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        throw new coding_exception('section_edit_control_items() can not be used anymore, please use or extend'.
                        'core_course\output\section_format\controlmenu instead (like topics format do).');

    }

    /**
     * Generate a summary of a section for display on the 'course index page'
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    public function section_summary($section, $course, $mods) {
        throw new coding_exception('section_summary() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections. If you need to modify those summary, extend '.
            'core_course\output\section_format\summary in your format plugin.');
    }

    /**
     * Generate a summary of the activites in a section
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course the course record from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    public function section_activity_summary($section, $course, $mods) {
        throw new coding_exception('section_activity_summary() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections. If you need to modify those information, extend '.
            'core_course\output\section_format\cmsummary in your format plugin.');
    }

    /**
     * If section is not visible, display the message about that ('Not available
     * until...', that sort of thing). Otherwise, returns blank.
     *
     * For users with the ability to view hidden sections, it shows the
     * information even though you can view the section and also may include
     * slightly fuller information (so that teachers can tell when sections
     * are going to be unavailable etc). This logic is the same as for
     * activities.
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param section_info $section The course_section entry from DB
     * @param bool $canviewhidden True if user can view hidden sections
     * @return string HTML to output
     */
    protected function section_availability_message($section, $canviewhidden) {
        throw new coding_exception('section_availability_message() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections. If you need to modify this element, extend '.
            'core_course\output\section_format\availability in your format plugin.');
    }

    /**
     * Displays availability information for the section (hidden, not available unles, etc.)
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param section_info $section
     * @return string
     */
    public function section_availability($section) {
        throw new coding_exception('section_availability() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections. If you need to modify this element, extend '.
            'core_course\output\section_format\availability in your format plugin.');
    }

    /**
     * Show if something is on on the course clipboard (moving around)
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * While the non ajax course eidtion is still supported, the old clipboard will be
     * emulated by core_course\output\format_section\cmlist.
     *
     * @param stdClass $course The course entry from DB
     * @param int $sectionno The section number in the course which is being displayed
     * @return string HTML to output.
     */
    protected function course_activity_clipboard($course, $sectionno = null) {
        global $USER;
        debugging('Non ajax course edition using course_activity_clipboard is not supported anymore.', DEBUG_DEVELOPER);
        return '';
    }

    /**
     * Generate next/previous section links for naviation.
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the course which is being displayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        throw new coding_exception('get_nav_links() can not be used anymore. Please use ' .
            'core_course\output\course_format to render a course. If you need to modify this element, extend '.
            'core_course\output\course_format\sectionnavigation in your format plugin.');
    }

    /**
     * Generate the header html of a stealth section
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param int $sectionno The section number in the course which is being displayed
     * @return string HTML to output.
     */
    protected function stealth_section_header($sectionno) {
        throw new coding_exception('stealth_section_header() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections.');
    }

    /**
     * Generate footer html of a stealth section
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @return string HTML to output.
     */
    protected function stealth_section_footer() {
        throw new coding_exception('stealth_section_header() can not be used anymore. Please use ' .
            'core_course\output\section_format to render sections.');
    }

    /**
     * Generate the html for a hidden section
     *
     * @param int $sectionno The section number in the course which is being displayed
     * @param int|stdClass $courseorid The course to get the section name for (object or just course id)
     * @return string HTML to output.
     */
    protected function section_hidden($sectionno, $courseorid = null) {
        if ($courseorid) {
            $sectionname = get_section_name($courseorid, $sectionno);
            $strnotavailable = get_string('notavailablecourse', '', $sectionname);
        } else {
            $strnotavailable = get_string('notavailable');
        }

        $o = '';
        $o .= html_writer::start_tag('li', [
            'id' => 'section-'.$sectionno,
            'class' => 'section main clearfix hidden',
            'data-sectionid' => $sectionno
        ]);
        $o.= html_writer::tag('div', '', array('class' => 'left side'));
        $o.= html_writer::tag('div', '', array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));
        $o.= html_writer::tag('div', $strnotavailable);
        $o.= html_writer::end_tag('div');
        $o.= html_writer::end_tag('li');
        return $o;
    }

    /**
     * Generate the html for the 'Jump to' menu on a single section page.
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * This element is now a section_format output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param $displaysection the current displayed section number.
     *
     * @return string HTML to output.
     */
    protected function section_nav_selection($course, $sections, $displaysection) {
        throw new coding_exception('section_nav_selection() can not be used anymore. Please use ' .
            'core_course\output\course_format to render a course. If you need to modify this element, extend '.
            'core_course\output\course_format\sectionnavigation or '.
            'core_course\output\course_format\sectionselector in your format plugin.');
    }

    /**
     * Output the html for a single section page.
     *
     * @deprecated since 4.0
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {

        debugging('Method print_single_section_page is deprecated, please use'.
                'core_course\output\course_format instead', DEBUG_DEVELOPER);

        $format = course_get_format($course);

        // Set the section to display.
        $format->set_section_number($displaysection);

        // General course format output.
        $outputclass = $format->get_output_classname('course_format');
        $output = new $outputclass($format);
        echo $this->render($output);
    }

    /**
     * Output the html for a multiple section page
     *
     * @deprecated since 4.0
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {

        debugging('Method print_multiple_section_page is deprecated, please use'.
                'core_course\output\course_format instead', DEBUG_DEVELOPER);

        $format = course_get_format($course);

        // General course format output.
        $outputclass = $format->get_output_classname('course_format');
        $output = new $outputclass($format);
        echo $this->render($output);
    }

    /**
     * Returns controls in the bottom of the page to increase/decrease number of sections
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * @param stdClass $course
     * @param int|null $sectionreturn
     */
    public function change_number_sections($course, $sectionreturn = null) {
        debugging('Method change_number_sections is deprecated, please use'.
                'core_course\output\course_format\addsection instead', DEBUG_DEVELOPER);

        $format = course_get_format($course);
        if ($sectionreturn) {
            $format->set_section_number($sectionreturn);
        }
        $outputclass = $format->get_output_classname('course_format\\addsection');
        $output = new $outputclass($format);
        echo $this->render($output);
    }

    /**
     * Generate html for a section summary text
     *
     * @deprecated since 4.0 - use core_course output components instead.
     *
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    public function format_summary_text($section) {
        debugging('Method format_summary_text is deprecated, please use'.
                'core_course\output\section_format\summary::format_summary_text instead', DEBUG_DEVELOPER);

        $format = course_get_format($section->course);
        if (!($section instanceof section_info)) {
            $modinfo = $format->get_modinfo();
            $section = $modinfo->get_section_info($section->section);
        }
        $summaryclass = $format->get_output_classname('section_format\\summary');
        $summary = new $summaryclass($format, $section);
        return $summary->format_summary_text();
    }
}

/**
 * Site topics renderer.
 *
 * Site course is not a real course format, but it requires a format renderer to use the output
 * components.
 *
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_site_renderer extends format_section_renderer_base {

}
