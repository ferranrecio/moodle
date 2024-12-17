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

namespace mod_glossary\course;

use core_course\local\overview\overviewitem;
use core\output\action_link;
use core\output\local\properties\text_align;
use core\output\local\properties\button;
use core\url;

/**
 * Class overview
 *
 * @package    mod_glossary
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_course\activityoverviewbase {
    #[\Override]
    public function get_extra_overview_items(\renderer_base $output): array {
        return [
            'entries' => $this->get_extra_entries_overview($output),
        ];
    }

    private function get_extra_entries_overview(\renderer_base $output): overviewitem {
        global $DB;

        $countentries = $DB->count_records_sql(
            "SELECT COUNT(*)
               FROM {glossary_entries}
              WHERE (glossaryid = ? OR sourceglossaryid = ?)",
            [$this->cm->instance, $this->cm->instance]
        );

        if ($countentries === 0) {
            return new overviewitem(
                name: get_string('entries', 'mod_glossary'),
                value: $countentries,
                content: '-',
                textalign: text_align::CENTER,
            );
        }

        $content = new action_link(
            url: new url('/mod/glossary/view.php', ['id' => $this->cm->id]),
            text: $countentries,
            attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
        );
        return new overviewitem(
            name: get_string('entries', 'mod_glossary'),
            value: $this->cm->name,
            content: $content,
            textalign: text_align::CENTER,
        );
    }
}
