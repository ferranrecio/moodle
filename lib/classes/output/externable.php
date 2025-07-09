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

namespace core\output;

use stdClass;

/**
 * Interface marking other classes exportable for external services.
 *
 * The main difference between this interface and the renderable interface is that
 * this interface is used to export data in a format that is suitable for external web services
 * and won't be altered even if the template data changes. For this reason, it is important
 * that every class that implements this interface provides unit tests to ensure that the exported data
 * does not change unexpectedly.
 *
 * @copyright 2025 Ferran Recio <ferran@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core
 * @category output
 * @since 5.1
 */
interface externable {
    /**
     * Function to export the output data in a format that is suitable for a
     * external Webservices in JSON format. This means: No complex types - only stdClass,
     * array, int, string, float, bool.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return \stdClass|array
     */
    public function export_for_external(renderer_base $output): stdClass;
}
