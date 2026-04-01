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
 * Permissions class for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign;

/**
 * Permissions class for capability checks.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions {
    /**
     * Check if the current user can add a new activity instance.
     *
     * @param \context $context The course context
     * @return bool True if user can add an instance
     */
    public static function can_add_instance($context): bool {
        return has_capability('mod/peerassign:addinstance', $context);
    }

    /**
     * Check if the current user can view files in a given filearea.
     *
     * @param \stdClass $cm The course module
     * @param \context $context The module context
     * @param string $filearea The file area name
     * @return bool True if user can view files
     */
    public static function can_view_files($cm, $context, $filearea): bool {
        // Students can view submission files from their own submissions.
        // Teachers can view all files.
        if (has_capability('mod/peerassign:view', $context)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current user can manage the activity.
     *
     * @param \context $context The module context
     * @return bool True if user can manage the activity
     */
    public static function can_manage($context) {
        return has_capability('mod/peerassign:manage', $context);
    }

    /**
     * Check if the current user can submit work.
     *
     * @param \context $context The module context
     * @return bool True if user can submit
     */
    public static function can_submit($context) {
        return has_capability('mod/peerassign:submit', $context);
    }

    /**
     * Check if the current user can review submissions.
     *
     * @param \context $context The module context
     * @return bool True if user can review
     */
    public static function can_review($context): bool {
        return has_capability('mod/peerassign:review', $context);
    }

    /**
     * Check if the current user can grade submissions.
     *
     * @param \context $context The module context
     * @return bool True if user can grade
     */
    public static function can_grade($context): bool {
        return has_capability('mod/peerassign:grade', $context);
    }

    /**
     * Check if the current user can add phases.
     *
     * @param \context $context The module context
     * @return bool True if user can add phases
     */
    public static function can_add_phase($context): bool {
        return has_capability('mod/peerassign:addphase', $context);
    }

    /**
     * Require that the current user can view the activity, throwing an error if not.
     *
     * @param manager $manager The activity manager
     */
    public static function require_view_activity(manager $manager) {
        $cm = $manager->get_coursemodule();
        $context = $manager->get_context();
        require_login($cm->course, true, $cm);
        require_capability('mod/peerassign:view', $context);
    }
}
