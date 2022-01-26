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
 * The core_courseformat subsystem renderer.
 *
 * The methods on this class are used to configure the subsystem renderer.
 * Format plugins should extend core_courseformat\otuput\section_renderer.
 *
 * @deprecated since Moodle 4.0 MDL-72656
 *
 * @package core_courseformat
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */

/**
 * The core courseformat renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','courseformat');
 */
class core_courseformat_renderer extends plugin_renderer_base {

    /**
     * Add extra paths for templates locations.
     *
     * Format plugins can override individual core_courseformat mustaches templates.
     * To do so, they must locate the replaced templates in the subsystem folders following the same
     * structured used in themes overrides. For example, a mustache file located in
     * course/format/MYPLUGIN/templates/courseformat/local/content/section/cmitem.mustache.
     *
     * @param string $themename The name of the current theme
     * @param array $parentthemes The names of the parent themes.
     * @return array of extra mustache location directories.
     */
    public function extra_mustache_locations(string $themename, array $parentthemes): array {
        global $CFG;
        $dirs = [];

        $currentcomponent = 'format_' . $this->page->course->format;

        // Theme courseformat subsystem overrides.
        $dirs[] = $CFG->dirroot . '/theme/' . $themename . '/templates/' . $currentcomponent . '/courseformat/';
        if (isset($CFG->themedir)) {
            $dirs[] = $CFG->themedir . '/' . $themename . '/templates/' . $currentcomponent . '/courseformat/';
        }

        // The parent themes subsystem overrides.
        foreach ($parentthemes as $parent) {
            $dirs[] = $CFG->dirroot . '/theme/' . $parent . '/templates/' . $currentcomponent . '/courseformat/';
            if (isset($CFG->themedir)) {
                $dirs[] = $CFG->themedir . '/' . $parent . '/templates/' . $currentcomponent . '/courseformat/';
            }
        }

        // Check the course format plugin.
        $compdirectory = core_component::get_component_directory($currentcomponent);
        if ($compdirectory) {
            $dirs[] = $compdirectory . '/templates/courseformat/';
        }
        return $dirs;
    }
}
