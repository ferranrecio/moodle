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

namespace gradereport_grader\output;

use templatable;
use renderable;
use grade_report_grader;

/**
 * Renderable class for the action bar elements in the view pages in the database activity.
 *
 * @package    mod_data
 * @copyright  2021 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class footer implements templatable, renderable {

    /** @var int number of users. */
    private $numusers;

    /** @var grade_report_grader the current report */
    private $report;

    /**
     * The class constructor.
     *
     * @param grade_report_grader $report the current report
     * @param int $numusers total users
     */
    public function __construct(
        grade_report_grader $report,
        int $numusers
    ) {
        $this->report = $report;
        $this->numusers = $numusers;
    }

    /**
     * Export the data for the mustache template.
     *
     * @param \renderer_base $output The renderer to be used to render the action bar elements.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        global $USER;

        $data = [];
        $report = $this->report;
        $studentsperpage = $report->get_students_per_page();

        // print submit button
        if (!empty($USER->editing) && ($report->get_pref('showquickfeedback') || $report->get_pref('quickgrading'))) {
            $data['savebutton'] = true;
        }

        // prints paging bar at bottom for large pages
        if (!empty($studentsperpage)) {
            $data['pagination'] = $output->paging_bar(
                $this->numusers,
                $report->page,
                $studentsperpage,
                $report->pbarurl
            );
        }
        return $data;
    }
}
