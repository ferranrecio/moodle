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

namespace core_course\local\overview;

use core\output\renderer_base;

/**
 * Class resourceoverview
 *
 * @package    core_course
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resourceoverview extends \core_course\activityoverviewbase {
    #[\Override]
    public function get_extra_overview_items(renderer_base $output): array {
        // mod/assign/locallib.php:3312
        return [
            'type' => $this->get_extra_type_overview($output),
        ];
    }

    /**
     * Retrieves an overview item for the extra type of the resource.
     *
     * @param renderer_base $output
     * @return overviewitem|null
     */
    private function get_extra_type_overview(renderer_base $output): ?overviewitem {
        return new overviewitem(
            name: get_string('resource_type'),
            value: $this->cm->modfullname,
            content: $this->cm->modfullname,
        );
    }
}
