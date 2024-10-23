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
 * Contains the default activity control menu.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_courseformat\output\local\content\cm;

use cm_info;
use core\context\module as context_module;
use core\output\action_menu;
use core\output\action_menu\link as action_menu_link;
use core\output\action_menu\link_secondary as action_menu_link_secondary;
use core\output\action_menu\subpanel as action_menu_subpanel;
use core\output\pix_icon;
use core\output\renderer_base;
use core_courseformat\base as course_format;
use core_courseformat\output\local\content\basecontrolmenu;
use core_courseformat\sectiondelegate;
use core\url;
use section_info;
use stdClass;

/**
 * Base class to render a course module menu inside a course format.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends basecontrolmenu {

    /** @var array optional display options */
    protected $displayoptions;

    /** @var context_module|null modcontext the module context if any */
    protected ?context_module $modcontext = null;

    /** @var bool $canmanageactivities Optimization to know if the user can manage activities */
    protected bool $canmanageactivities;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     * @param cm_info $mod the course module info
     * @param array $displayoptions optional extra display options
     */
    public function __construct(course_format $format, section_info $section, cm_info $mod, array $displayoptions = []) {
        parent::__construct($format, $section, $mod, $mod->id);
        $this->displayoptions = $displayoptions;

        $this->modcontext = context_module::instance($mod->id);
        $this->canmanageactivities = has_capability('moodle/course:manageactivities', $this->modcontext);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass|null data context for a mustache template
     */
    public function export_for_template(renderer_base $output): ?stdClass {

        $mod = $this->mod;

        if (!$this->format->show_activity_editor_options($this->mod)) {
            return null;
        }

        $menu = $this->get_action_menu($output);

        if (empty($menu)) {
            return new stdClass();
        }

        $data = (object)[
            'menu' => $menu->export_for_template($output),
            'hasmenu' => true,
            'id' => $this->menuid,
        ];

        // After icons.
        if (!empty($mod->afterediticons)) {
            $data->afterediticons = $mod->afterediticons;
        }

        return $data;
    }

    /**
     * Generate the action menu element.
     *
     * This method is public in case some block needs to modify the menu before output it.
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the activity action menu
     */
    public function get_action_menu(renderer_base $output): ?action_menu {

        if (!empty($this->menu)) {
            return $this->menu;
        }

        // In case module is delegating a section, we should return delegated section action menu.
        if ($delegated = $this->mod->get_delegated_section_info()) {
            $controlmenuclass = $this->format->get_output_classname('content\\cm\\delegatedcontrolmenu');
            $controlmenu = new $controlmenuclass($this->format, $delegated, $this->mod);
            return $controlmenu->get_action_menu($output);
        }

        // TODO remove this if as part of MDL-83530.
        if (!$this->format->supports_components()) {
            $this->menu = $this->get_action_menu_legacy($output);
            return $this->menu;
        }

        $controls = $this->get_cm_control_items();
        return $this->format_controls($controls);
    }

    /**
     * Generate the edit control items of a course module.
     *
     * This method uses course_get_cm_edit_actions function to get the cm actions.
     * However, format plugins can override the method to add or remove elements
     * from the menu.
     *
     * @return array of edit control items
     */
    protected function get_cm_control_items(): ?array {
        $controls = [];

        $controls['update'] = $this->get_cm_edit_item();
        $controls['move'] = $this->get_cm_move_item();
        $controls['moveright'] = $this->get_cm_moveend_item();
        $controls['moveleft'] = $this->get_cm_movestart_item();
        $controls['availability'] = $this->get_cm_visibility_item();
        $controls['duplicate'] = $this->get_cm_duplicate_item();
        $controls['assign'] = $this->get_cm_assign_item();
        $controls['groupmode'] = $this->get_cm_groupmode_item();
        $controls['delete'] = $this->get_cm_delete_item();

        return $controls;
    }

    protected function get_cm_edit_item(): ?action_menu_link {
        if (!$this->canmanageactivities) {
            return null;
        }

        $url = new url(
            '/course/mod.php',
            [
                'sesskey' => sesskey(),
                'sr' => $this->mod->sectionnum,
                'update' => $this->mod->id,
            ],
        );

        return new action_menu_link_secondary(
            url: $url,
            icon: new pix_icon('i/settings', ''),
            text: get_string('editsettings'),
            attributes: [
                'class' => 'editing_update',
                'data-action' => 'update',
            ],
        );
    }

    /**
     * Generates the move item for a course module.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_cm_move_item(): ?action_menu_link {
        // Only show the move link if we are not already in the section view page.
        if (!$this->canmanageactivities) {
            return null;
        }
        return new action_menu_link_secondary(
            url: $this->baseurl,
            icon: new pix_icon('i/dragdrop', ''),
            text: get_string('move'),
            attributes: [
                // This tool requires ajax and will appear only when the frontend state is ready.
                'class' => 'editing_movecm waitstate',
                'data-action' => 'moveCm',
                'data-id' => $this->mod->id,
            ],
        );
    }

    protected function can_indent_cm(): bool {
        return $this->canmanageactivities
            && !sectiondelegate::has_delegate_class('mod_'.$this->mod->modname)
            && empty($this->displayoptions['disableindentation'])
            && $this->format->uses_indentation();
    }

    protected function get_cm_moveend_item(): ?action_menu_link {
        if (!$this->can_indent_cm() || $this->mod->indent > 0) {
            return null;
        }

        // NOPE
        $url = new url(
            '/course/mod.php',
            [
                'sesskey' => sesskey(),
                'sr' => $this->mod->sectionnum,
                'id' => $this->mod->id,
                'indent' => 1,
            ],
        );

        $icon = (right_to_left()) ? 't/left' : 't/right';

        return new action_menu_link_secondary(
            url: new url($this->baseurl, ['id' => $this->mod->id, 'indent' => '1']),
            icon: new pix_icon($icon, ''),
            text: get_string('moveright'),
            attributes: [
                'class' => 'editing_moveright',
                'data-action' => 'cmMoveRight',
                'data-keepopen' => true,
                'data-sectionreturn' => $this->format->get_sectionnum(),
                'data-id' => $this->mod->id,
            ],
        );
    }

    protected function get_cm_movestart_item(): ?action_menu_link {
        if (!$this->can_indent_cm() || $this->mod->indent <= 0) {
            return null;
        }

        // NOPE
        $url = new url(
            '/course/mod.php',
            [
                'sesskey' => sesskey(),
                'sr' => $this->mod->sectionnum,
                'id' => $this->mod->id,
                'indent' => -1,
            ],
        );

        $icon = (right_to_left()) ? 't/right' : 't/left';

        return new action_menu_link_secondary(
            url: new url($this->baseurl, ['id' => $this->mod->id, 'indent' => '-1']),
            icon: new pix_icon($icon, ''),
            text: get_string('moveleft'),
            attributes: [
                'class' => 'editing_moveleft',
                'data-action' => 'cmMoveLeft',
                'data-keepopen' => true,
                'data-sectionreturn' => $this->format->get_sectionnum(),
                'data-id' => $this->mod->id,
            ],
        );
    }

    protected function get_cm_visibility_item(): ?action_menu_link {
        if (!has_capability('moodle/course:activityvisibility', $this->modcontext)) {
            return null;
        }
        $outputclass = $this->format->get_output_classname('content\\cm\\visibility');
        /** @var \core_courseformat\output\local\content\cm\visibility $availability */
        $output = new $outputclass($this->format, $this->section, $this->mod);
        return $output->get_menu_item();
    }

    protected function get_cm_duplicate_item(): ?action_menu_link {
        if (
            !has_all_capabilities(
                ['moodle/backup:backuptargetimport', 'moodle/restore:restoretargetimport'],
                $this->coursecontext
            )
            || !plugin_supports('mod', $this->mod->modname, FEATURE_BACKUP_MOODLE2)
            || !course_allowed_module($this->mod->get_course(), $this->mod->modname)
        ) {
                return null;
        }

        return new action_menu_link_secondary(
            url: new url($this->baseurl, ['duplicate' => $this->mod->id]),
            icon: new pix_icon('t/copy', ''),
            text: get_string('duplicate'),
            attributes: [
                'class' => 'editing_duplicate',
                'data-action' => 'cmDuplicate',
                'data-sectionreturn' => $this->format->get_sectionnum(),
                'data-id' => $this->mod->id,
            ],
        );
    }

    protected function get_cm_assign_item(): ?action_menu_link {
        if (
            !has_capability('moodle/role:assign', $this->modcontext)
            || sectiondelegate::has_delegate_class('mod_'.$this->mod->modname)
        ) {
            return null;
        }

        return new action_menu_link_secondary(
            url: new url('/admin/roles/assign.php', ['contextid' => $this->modcontext->id]),
            icon: new pix_icon('t/assignroles', ''),
            text: get_string('assignroles', 'role'),
            attributes: [
                'class' => 'editing_assign',
                'data-action' => 'assignroles',
                'data-sectionreturn' => $this->format->get_sectionnum(),
            ],
        );
    }

    // get_cm_groupmode_item
    protected function get_cm_groupmode_item(): ?action_menu_subpanel {
        if (
            !$this->format->show_groupmode($this->mod)
            || $this->mod->coursegroupmodeforce
        ) {
            return null;
        }

        $groupmodeclass = $this->format->get_output_classname('content\\cm\\groupmode');
        /** @var \core_courseformat\output\local\content\cm\groupmode $groupmode */
        $groupmode = new $groupmodeclass($this->format, $this->section, $this->mod);
        return new action_menu_subpanel(
            text: get_string('groupmode', 'group'),
            subpanel: $groupmode->get_choice_list(),
            attributes: ['class' => 'editing_groupmode'],
            icon: new pix_icon('t/groupv', '', 'moodle', ['class' => 'iconsmall']),
        );
    }

    /**
     * Generates the delete item for a course module.
     *
     * @return action_menu_link|null The menu item if applicable, otherwise null.
     */
    protected function get_cm_delete_item(): ?action_menu_link {
        if (!$this->canmanageactivities) {
            return null;
        }

        $url = new url(
            '/course/mod.php',
            [
                'sesskey' => sesskey(),
                'delete' => $this->mod->id,
                'sr' => $this->mod->sectionnum,
            ],
        );

        return new action_menu_link_secondary(
            url: $url,
            icon: new pix_icon('t/delete', ''),
            text: get_string('delete'),
            attributes: [
                'class' => 'editing_delete text-danger',
                'data-action' => 'cmDelete',
                'data-sectionreturn' => $this->format->get_sectionnum(),
                'data-id' => $this->mod->id,
            ],
        );
    }

    /**
     * Generate the action menu element for old course formats.
     *
     * This method is public in case some block needs to modify the menu before output it.
     *
     * @todo Remove this method in Moodle 6.0 (MDL-83530).
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the activity action menu
     */
    private function get_action_menu_legacy(\renderer_base $output): ?action_menu {
        $mod = $this->mod;

        $controls = $this->cm_control_items();

        if (empty($controls)) {
            return null;
        }

        // Convert control array into an action_menu.
        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-cm-edit-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        $ownerselector = $this->displayoptions['ownerselector'] ?? '#module-' . $mod->id;
        $menu->set_owner_selector($ownerselector);

        foreach ($controls as $control) {
            if ($control instanceof action_menu_link) {
                $control->add_class('cm-edit-action');
            }
            $menu->add($control);
        }

        $this->menu = $menu;

        return $menu;
    }

    /**
     * Generate the edit control items of a course module.
     *
     * This method uses course_get_cm_edit_actions function to get the cm actions.
     * However, format plugins can override the method to add or remove elements
     * from the menu.
     *
     * @todo Remove this method in Moodle 6.0 (MDL-83530).
     * @return array of edit control items
     */
    protected function cm_control_items() {
        $format = $this->format;
        $mod = $this->mod;
        $sectionreturn = $format->get_sectionnum();
        if (!empty($this->displayoptions['disableindentation']) || !$format->uses_indentation()) {
            $indent = -1;
        } else {
            $indent = $mod->indent;
        }
        return course_get_cm_edit_actions($mod, $indent, $sectionreturn);
    }
}
