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

namespace mod_workshop\course;

/**
 * Workshop overview integration.
 *
 * @package    mod_workshop
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_course\activityoverviewbase {
    #[\Override]
    protected function get_grade_item_names(array $items): array {
        if (count($items) != 2) {
            return parent::get_grade_item_names($items);
        }
        $names = [];
        foreach ($items as $item) {
            $stridentifier = ($item->itemnumber == 0) ? 'overview_assessment_grade' : 'overview_submission_grade';
            $names[$item->id] = get_string($stridentifier, 'mod_workshop');
        }
        return $names;
    }
}
