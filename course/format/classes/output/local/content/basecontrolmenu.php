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

namespace core_courseformat\output\local\content;

use core\output\action_menu;
use core\output\action_menu\link as action_menu_link;
use core\output\action_menu\link_secondary as action_menu_link_secondary;
use core\output\named_templatable;
use core\output\pix_icon;
use core_courseformat\base as course_format;
use core_courseformat\output\local\courseformat_named_templatable;
use context_course;
use moodle_url;
use renderable;
use section_info;
use cm_info;
use stdClass;

/**
 * Base class to render course element controls.
 *
 * @package   core_courseformat
 * @copyright 2024 Amaia Anabitarte <amaia@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class basecontrolmenu implements named_templatable, renderable {

    use courseformat_named_templatable;

    /** @var course_format the course format class */
    protected $format;

    /** @var section_info the course section class */
    protected $section;

    /** @var cm_info the course module class */
    protected $mod;

    /** @var stdClass the course instance */
    protected stdClass $course;

    /** @var context_course the course context */
    protected $coursecontext;

    /** @var string the menu ID */
    protected $menuid;

    /** @var action_menu the action menu */
    protected $menu;

    /** @var moodle_url The base URL for the course or the section */
    protected moodle_url $baseurl;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     * @param cm_info|null $mod the module info
     * @param string $menuid the ID value for the menu
     */
    public function __construct(course_format $format, section_info $section, ?cm_info $mod = null, string $menuid = '') {
        $this->format = $format;
        $this->section = $section;
        $this->mod = $mod;
        $this->menuid = $menuid;
        $this->course = $format->get_course();
        $this->coursecontext = $format->get_context();
        $this->baseurl = $format->get_view_url($format->get_sectionnum(), ['navigation' => true]);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return null|array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): ?stdClass {
        $menu = $this->get_action_menu($output);
        if (empty($menu)) {
            return new stdClass();
        }

        $data = (object)[
            'menu' => $output->render($menu),
            'hasmenu' => true,
            'id' => $this->menuid,
        ];

        return $data;
    }

    /**
     * Generate the action menu element.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the action menu or null if no action menu is available
     */
    public function get_action_menu(\renderer_base $output): ?action_menu {

        if (!empty($this->menu)) {
            return $this->menu;
        }

        $this->menu = $this->get_default_action_menu($output);
        return $this->menu;
    }

    /**
     * Generate the default action menu.
     *
     * This method is public in case some block needs to modify the menu before output it.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the action menu
     */
    public function get_default_action_menu(\renderer_base $output): ?action_menu {
        return null;
    }

    /**
     * Format control array into an action_menu.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the action menu
     */
    protected function format_controls(array $controls): ?action_menu {
        if (empty($controls)) {
            return null;
        }

        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-actions';
        $menu->attributes['data-sectionid'] = $this->section->id;
        foreach ($controls as $item) {
            // Actions not available for the user can be null.
            if ($item === null) {
                continue;
            }
            // Some third party formats can use array to define the action menu items.
            if (is_array($item)) {
                $item = $this->normalize_action_menu_link($item);
            }
            $menu->add($item);
        }
        return $menu;
    }

    /**
     * Nromalize the action menu item, or return null if it is not possible.
     *
     * Traditionally, this class uses array to define the action menu items,
     * for backward compatibility, this method will normalize the array into
     * te correct action_menu_link object.
     *
     * @param array|null $itemdata the item data
     * @return void
     */
    protected function normalize_action_menu_link(
        array|null $itemdata
    ): ?action_menu_link_secondary {
        if (empty($itemdata)) {
            return null;
        }
        $url = empty($itemdata['url']) ? '' : $itemdata['url'];
        $icon = empty($itemdata['icon']) ? '' : $itemdata['icon'];
        $name = empty($itemdata['name']) ? '' : $itemdata['name'];
        $attr = empty($itemdata['attr']) ? [] : $itemdata['attr'];
        $class = empty($itemdata['pixattr']['class']) ? '' : $itemdata['pixattr']['class'];
        return new action_menu_link_secondary(
                new moodle_url($url),
                new pix_icon($icon, '', null, ['class' => "smallicon " . $class]),
                $name,
                $attr
        );
    }

    /**
     * Generate the edit control items of a section.
     *
     * This method must remain public until the final deprecation of section_edit_control_items.
     *
     * @return array of edit control items
     */
    public function section_control_items() {
        return [];
    }

    // Section items.

    /**
     * Retrieves the view item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_view_item(): ?action_menu_link {
        // Only show the view link if we are not already in the section view page.
        if ($this->format->get_sectionid() == $this->section->id) {
            return null;
        }
        return $this->normalize_action_menu_link([
            'url'   => new moodle_url('/course/section.php', ['id' => $this->section->id]),
            'icon' => 'i/viewsection',
            'name' => get_string('view'),
            'pixattr' => ['class' => ''],
            'attr' => ['class' => 'icon view'],
        ]);
    }

    /**
     * Retrieves the edit item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_edit_item(): ?action_menu_link {
        if (!has_capability('moodle/course:update', $this->coursecontext)) {
            return null;
        }
        $params = ['id' => $this->section->id];
        $params['sr'] = $this->section->section;
        return $this->normalize_action_menu_link([
            'url'   => new moodle_url('/course/editsection.php', $params),
            'icon' => 'i/settings',
            'name' => get_string('editsection'),
            'pixattr' => ['class' => ''],
            'attr' => ['class' => 'icon edit'],
        ]);
    }

    /**
     * Retrieves the duplicate item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_duplicate_item(): ?action_menu_link {
        if (
            $this->section->sectionnum == 0
            || !has_capability('moodle/course:update', $this->coursecontext)
        ) {
            return null;
        }
        $url = clone($this->baseurl);
        $url->param('sectionid', $this->section->id);
        $url->param('duplicatesection', 1);
        $url->param('sesskey', sesskey());
        return $this->normalize_action_menu_link([
            'url' => $url,
            'icon' => 't/copy',
            'name' => get_string('duplicate'),
            'pixattr' => ['class' => ''],
            'attr' => ['class' => 'icon duplicate'],
        ]);
    }

    /**
     * Retrieves the get_section_visibility_menu_item item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_visibility_item(): ?action_menu_link {
        if (
            $this->section->sectionnum == 0
            || !has_capability('moodle/course:sectionvisibility', $this->coursecontext)
        ) {
            return null;
        }
        $sectionreturn = $this->format->get_sectionnum();

        $url = clone($this->baseurl);

        $strhide = get_string('hide');
        $strshow = get_string('show');

        if ($this->section->visible) {
            $url->param('hide', $this->section->sectionnum);
            $item = [
                'url' => $url,
                'icon' => 'i/show',
                'name' => $strhide,
                'pixattr' => ['class' => ''],
                'attr' => [
                    'class' => 'icon editing_showhide',
                    'data-sectionreturn' => $sectionreturn,
                    'data-action' => 'sectionHide',
                    'data-id' => $this->section->id,
                    'data-icon' => 'i/show',
                    'data-swapname' => $strshow,
                    'data-swapicon' => 'i/hide',
                ],
            ];
        } else {
            $url->param('show', $this->section->sectionnum);
            $item = [
                'url' => $url,
                'icon' => 'i/hide',
                'name' => $strshow,
                'pixattr' => ['class' => ''],
                'attr' => [
                    'class' => 'icon editing_showhide',
                    'data-sectionreturn' => $sectionreturn,
                    'data-action' => 'sectionShow',
                    'data-id' => $this->section->id,
                    'data-icon' => 'i/hide',
                    'data-swapname' => $strhide,
                    'data-swapicon' => 'i/show',
                ],
            ];
        }
        return $this->normalize_action_menu_link($item);
    }

    /**
     * Retrieves the move item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_movesection_item(): ?action_menu_link {
        if (
            $this->section->sectionnum == 0
            || $this->format->get_sectionid()
            || !has_capability('moodle/course:movesections', $this->coursecontext)
        ) {
            return null;
        }

        $url = clone ($this->baseurl);
        $url->param('movesection', $this->section->sectionnum);
        $url->param('section', $this->section->sectionnum);
        return $this->normalize_action_menu_link([
            'url' => $url,
            'icon' => 'i/dragdrop',
            'name' => get_string('move'),
            'pixattr' => ['class' => ''],
            'attr' => [
                // This tool requires ajax and will appear only when the frontend state is ready.
                'class' => 'icon move waitstate',
                'data-action' => 'moveSection',
                'data-id' => $this->section->id,
            ],
        ]);
    }

    /**
     * Retrieves the permalink item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_permalink_item(): ?action_menu_link {
        if (!has_any_capability(
                [
                    'moodle/course:movesections',
                    'moodle/course:update',
                    'moodle/course:sectionvisibility',
                ],
                $this->coursecontext
            )
        ) {
            return null;
        }

        $url = new moodle_url(
            '/course/section.php',
            ['id' => $this->section->id]
        );
        return $this->normalize_action_menu_link([
            'url' => $url,
            'icon' => 'i/link',
            'name' => get_string('sectionlink', 'course'),
            'pixattr' => ['class' => ''],
            'attr' => [
                'class' => 'icon',
                'data-action' => 'permalink',
            ],
        ]);
    }

    /**
     * Retrieves the delete item for the section control menu.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_delete_item(): ?action_menu_link {
        if (!course_can_delete_section($this->format->get_course(), $this->section)) {
            return null;
        }

        $params = [
            'id' => $this->section->id,
            'delete' => 1,
            'sesskey' => sesskey(),
        ];
        $params['sr'] ??= $this->format->get_sectionnum();

        $url = new moodle_url(
            '/course/editsection.php',
            $params,
        );
        return $this->normalize_action_menu_link([
            'url' => $url,
            'icon' => 'i/delete',
            'name' => get_string('delete'),
            'pixattr' => ['class' => ''],
            'attr' => [
                'class' => 'icon editing_delete text-danger',
                'data-action' => 'deleteSection',
                'data-id' => $this->section->id,
            ],
        ]);
    }
}
