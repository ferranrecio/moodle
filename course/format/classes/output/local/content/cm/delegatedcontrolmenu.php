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

namespace core_courseformat\output\local\content\cm;

use action_menu;
use action_menu_link_secondary;
use context_course;
use core\output\named_templatable;
use core_courseformat\base as course_format;
use core_courseformat\output\local\courseformat_named_templatable;
use moodle_url;
use pix_icon;
use renderable;
use section_info;
use cm_info;
use stdClass;

/**
 * Base class to render delegated section controls.
 *
 * @package   core_courseformat
 * @copyright 2024 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delegatedcontrolmenu implements named_templatable, renderable {

    use courseformat_named_templatable;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     * @param cm_info $cm the cm info
     */
    public function __construct(
            /** @var course_format the course format class */
            protected course_format $format,
            /** @var section_info the delegated section */
            protected section_info $section,
            /** @var cm_info the delegator module */
            protected cm_info $cm
    ) {
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $menu = $this->get_action_menu($output);
        if (empty($menu)) {
            return new stdClass();
        }

        $data = (object)[
            'menu' => $output->render($menu),
            'hasmenu' => true,
            'id' => $this->section->id,
        ];

        return $data;
    }

    /**
     * Generate the action menu element depending on the delegated section.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the activity action menu or null if no action menu is available
     */
    public function get_action_menu(\renderer_base $output): ?action_menu {
        return $this->get_default_action_menu($output);
    }

    /**
     * Generate the default delegated section action menu.
     *
     * This method is public in case some block needs to modify the menu before output it.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the activity action menu
     */
    public function get_default_action_menu(\renderer_base $output): ?action_menu {
        $controls = $this->delegated_control_items();
        if (empty($controls)) {
            return null;
        }

        // Convert control array into an action_menu.
        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-actions';
        $menu->attributes['data-sectionid'] = $this->section->id;
        foreach ($controls as $value) {
            $url = empty($value['url']) ? '' : $value['url'];
            $icon = empty($value['icon']) ? '' : $value['icon'];
            $name = empty($value['name']) ? '' : $value['name'];
            $attr = empty($value['attr']) ? [] : $value['attr'];
            $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
            $al = new action_menu_link_secondary(
                new moodle_url($url),
                new pix_icon($icon, '', null, ['class' => "smallicon " . $class]),
                $name,
                $attr
            );
            $menu->add($al);
        }
        return $menu;
    }

    /**
     * Generate the edit control items of a section.
     *
     * It is not clear this kind of controls are still available in 4.0 so, for now, this
     * method is almost a clone of the previous section_control_items from the course/renderer.php.
     *
     * This method must remain public until the final deprecation of section_edit_control_items.
     *
     * @return array of edit control items
     */
    public function delegated_control_items() {
        global $USER, $PAGE;

        $format = $this->format;
        $section = $this->section;
        $cm = $this->cm;
        $course = $format->get_course();
        $sectionreturn = !is_null($format->get_sectionid()) ? $format->get_sectionnum() : null;
        $user = $USER;

        $usecomponents = $format->supports_components();
        $coursecontext = context_course::instance($course->id);

        $baseurl = course_get_url($course, $sectionreturn);
        $baseurl->param('sesskey', sesskey());

        $cmbaseurl = new moodle_url('/course/mod.php');
        $cmbaseurl->param('sesskey', sesskey());

        $hasmanageactivities = has_capability('moodle/course:manageactivities', $coursecontext);
        $isheadersection = $format->get_sectionid() == $section->id;

        $controls = [];

        // Only show the view link if we are not already in the section view page.
        if (!$isheadersection) {
            $controls['view'] = [
                'url'   => new moodle_url('/course/section.php', ['id' => $section->id]),
                'icon' => 'i/viewsection',
                'name' => get_string('view'),
                'pixattr' => ['class' => ''],
                'attr' => ['class' => 'view'],
            ];
        }

        if (has_capability('moodle/course:update', $coursecontext, $user)) {
            $params = ['id' => $section->id];
            $params['sr'] = $section->section;
            if (get_string_manager()->string_exists('editsection', 'format_'.$format->get_format())) {
                $streditsection = get_string('editsection', 'format_'.$format->get_format());
            } else {
                $streditsection = get_string('editsection');
            }

            // Edit settings goes to section settings form.
            $controls['edit'] = [
                'url'   => new moodle_url('/course/editsection.php', $params),
                'icon' => 'i/settings',
                'name' => $streditsection,
                'pixattr' => ['class' => ''],
                'attr' => ['class' => 'edit'],
            ];
        }

        // Delete deletes the module.
        // Only show the view link if we are not already in the section view page.
        if (!$isheadersection && $hasmanageactivities) {
            $url = clone($cmbaseurl);
            $url->param('delete', $cm->id);
            $url->param('sr', $cm->sectionnum);

            $controls['delete'] = [
                'url' => $url,
                'icon' => 't/delete',
                'name' => get_string('delete'),
                'pixattr' => ['class' => ''],
                'attr' => [
                    'class' => 'editing_delete text-danger',
                    'data-action' => ($usecomponents) ? 'cmDelete' : 'delete',
                    'data-sectionreturn' => $sectionreturn,
                    'data-id' => $cm->id,
                ],
            ];
        }

        // Add section page permalink.
        if (
            has_any_capability([
                'moodle/course:movesections',
                'moodle/course:update',
                'moodle/course:sectionvisibility',
            ], $coursecontext)
        ) {
            $sectionlink = new moodle_url(
                '/course/section.php',
                ['id' => $section->id]
            );
            $controls['permalink'] = [
                'url' => $sectionlink,
                'icon' => 'i/link',
                'name' => get_string('sectionlink', 'course'),
                'pixattr' => ['class' => ''],
                'attr' => [
                    'data-action' => 'permalink',
                ],
            ];
        }

        return $controls;
    }
}
