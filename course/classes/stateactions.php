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
 * Contains the core course state actions.
 *
 * The methods from this class should be executed via "core_course_edit" web service.
 *
 * Each format plugin could extend this class to provide new actions to the editor.
 * Extended classes should be locate in "format_XXX\course\stateactions" namespace and
 * extends core_course\stateactions.
 *
 * @package    core
 * @subpackage course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course;

use core\event\course_module_updated;
use context_module;
use stdClass;
use course_modinfo;

defined('MOODLE_INTERNAL') || die();

class stateactions {

    /**
     * Hide a course module.
     *
     * @param stateupdates $updates the affeted course elements track
     * @param stdClass $course the course object
     * @param int[] $ids the list of affected course module ids
     * @param int $targetsectionid optional target section id (not used in hide action)
     * @param int $targetcmid optional target cm id (not used in hide action)
     * @return array of state updates.
     */
    public function cm_hide(stateupdates $updates, stdClass $course, array $ids,
            ?int $targetsectionid = null, ?int $targetcmid = null) {

        $modinfo = course_modinfo::instance($course);

        foreach ($ids as $cmid) {
            $modcontext = context_module::instance($cmid);
            require_capability('moodle/course:activityvisibility', $modcontext);
            set_coursemodule_visible($cmid, 0);
            course_module_updated::create_from_cm($modinfo->get_cm($cmid), $modcontext)->trigger();
            $updates->add_cm_update($cmid);
        }
    }

    /**
     * Show a course module.
     *
     * @param stateupdates $updates the affeted course elements track
     * @param stdClass $course the course object
     * @param int[] $ids the list of affected course module ids
     * @param int $targetsectionid optional target section id (not used in show action)
     * @param int $targetcmid optional target cm id (not used in show action)
     * @return array of state updates.
     */
    public function cm_show(stateupdates $updates, stdClass $course, array $ids,
            ?int $targetsectionid = null, ?int $targetcmid = null) {

        $modinfo = course_modinfo::instance($course);

        foreach ($ids as $cmid) {
            $modcontext = context_module::instance($cmid);
            require_capability('moodle/course:activityvisibility', $modcontext);
            set_coursemodule_visible($cmid, 1);
            course_module_updated::create_from_cm($modinfo->get_cm($cmid), $modcontext)->trigger();
            $updates->add_cm_update($cmid);
        }
    }
}
