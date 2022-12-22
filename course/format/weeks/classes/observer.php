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
 * Event observers used by the weeks course format.
 *
 * @package format_weeks
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 use format_weeks\courseformat\format;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for format_weeks.
 *
 * @package format_weeks
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_weeks_observer {

    /**
     * Check if the weeks class is already loaded.
     * @return bool
     */
    protected static function is_format_loaded(): bool {
        // If class format_weeks was never loaded, this is definitely not a course in 'weeks' format.
            // Course may still be in another format but \format_weeks\courseformat\format::update_end_date() will check it.
        return class_exists('\format_weeks\courseformat\format', false);
    }

    /**
     * Triggered via \core\event\course_updated event.
     *
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event) {
        if (self::is_format_loaded()) {
            format::update_end_date($event->courseid);
        }
    }

    /**
     * Triggered via \core\event\course_section_created event.
     *
     * @param \core\event\course_section_created $event
     */
    public static function course_section_created(\core\event\course_section_created $event) {
        if (self::is_format_loaded()) {
            format::update_end_date($event->courseid);
        }
    }

    /**
     * Triggered via \core\event\course_section_deleted event.
     *
     * @param \core\event\course_section_deleted $event
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event) {
        if (self::is_format_loaded()) {
            format::update_end_date($event->courseid);
        }
    }
}
