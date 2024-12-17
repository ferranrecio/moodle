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

namespace core_calendar\output;

use core\output\templatable;
use core\output\renderable;

/**
 * Class humandate
 *
 * @package    core_calendar
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class humandate implements renderable, templatable {
    /** @var int $now the current timestamp. */
    protected int $now;

    /**
     * Class constructor.
     *
     * @param int $timestamp The timestamp.
     * @param int $near The number of seconds within which a date is considered near. Defaults to DAYSECS.
     */
    public function __construct(
        /** @var int $timestamp the timestamp. */
        protected int $timestamp,
        /** @var int $near the number of seconds within which a date is considered near. */
        protected int|null $near = null,
    ) {
        $this->now = time();
    }

    #[\Override]
    public function export_for_template(\renderer_base $output): array {
        $userdate = userdate($this->timestamp);
        $due = $this->timestamp - $this->now;
        $relative = $this->format_relative_date();

        $data = [
            'timestamp' => $this->timestamp,
            'userdate' => $userdate,
            'date' => $relative ?? $userdate,
            'ispast' => $this->timestamp < $this->now,
            'needtitle' => $relative !== null,
        ];
        if ($this->near !== null) {
            $data['isnear'] = $due < $this->near && $due > 0;
        }
        return $data;
    }

    /**
     * Formats the timestamp as a relative date string (e.g., "Today", "Yesterday", "Tomorrow").
     *
     * This method compares the given timestamp with the current date and returns a formatted
     * string representing the relative date. If the timestamp corresponds to today, yesterday,
     * or tomorrow, it returns the appropriate string. Otherwise, it returns null.
     *
     * @return string|null
     */
    private function format_relative_date(): string|null {
        $this->now = time();
        $format = '';
        $userdate = userdate($this->timestamp);

        if (date('Y-m-d', $this->timestamp) == date('Y-m-d', $this->now)) {
            $format = get_string('strftimerelativetoday', 'langconfig');
        } elseif (date('Y-m-d', $this->timestamp) == date('Y-m-d', strtotime('yesterday', $this->now))) {
            $format = get_string('strftimerelativeyesterday', 'langconfig');
        } elseif (date('Y-m-d', $this->timestamp) == date('Y-m-d', strtotime('tomorrow', $this->now))) {
            $format = get_string('strftimerelativetomorrow', 'langconfig');
        } else {
            return null;
        }

        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        return $calendartype->timestamp_to_date_string(
            time: $this->timestamp,
            format: $format,
            timezone: 99,
            fixday: true,
            fixhour: true,
        );
    }
}
