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

namespace mod_label\output;

use core\output\templatable;
use core\output\renderable;
use core\output\renderer_base;
use core\url;
use stdClass;
use cm_info;

/**
 * Button to edit label.
 *
 * @package    mod_label
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editlabel  implements templatable, renderable {
    public function __construct(
        protected cm_info $cm,
    ) {
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    final public function export_for_template(renderer_base $output): stdClass {

        $format = \core_courseformat\base::instance($this->cm->course);

        return (object) [
            'cmid' => $this->cm->id,
            'label' => get_string('edit'),
            'title' => get_string('edit', 'mod_label', (object)[
                'name' => $this->cm->get_formatted_name(),
                'sectionname' => $format->get_section_name($this->cm->get_section_info()),
            ]),
            'class' => \core\output\local\properties\button::SECONDARY_OUTLINE->classes(),
        ];
    }
}
