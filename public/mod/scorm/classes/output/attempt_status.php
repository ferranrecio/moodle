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

namespace mod_scorm\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\output\single_button;
use mod_scorm\manager;
use core\url;

/**
 * SCORM attempt status renderable class.
 *
 * @package    mod_scorm
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_status implements renderable, templatable {
    protected \stdClass $instance;
    protected array $attempts;
    public function __construct(
        private manager $manager,
    ) {
        global $USER;

        $this->instance = $manager->get_instance();
        /** @var array $attempts */
        $attempts = scorm_get_attempt_count($USER->id, $this->instance, true);
        $this->attempts = $attempts ?: [];
    }
    /**
     * Provide data for the template
     *
     * @param renderer_base $output renderer_base object.
     * @return array data for the template.
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'status' => $this->get_attempt_status_data($output),
            'alert' => $this->max_attempts_notice(),
            // 'alert' => [
            //     'type' => 'info',
            //     'message' => 'This is an informational message.'
            // ],
        ];
    }

    private function get_attempt_status_data(renderer_base $output): array {
        global $USER;
        $statusdata = [];

        $statusdata[] = [
            'label' => get_string('noattemptsallowed', 'scorm'),
            'value' => ($this->instance->maxattempt > 0) ? $this->instance->maxattempt : get_string('unlimited'),
        ];

        $statusdata[] = $this->count_attempts_status_data($output);
        $statusdata = array_merge($statusdata, $this->attempts_status_data());
        $statusdata[] = $this->grading_status_data();
        $statusdata[] = $this->grade_status_data();

        return $statusdata;
    }

    private function count_attempts_status_data(renderer_base $output): array {
        $attemptcount = count($this->attempts);
        $result = [
            'label' => get_string('noattemptsmade', 'scorm'),
            'value' => $attemptcount,
        ];

        if ($attemptcount > 0 && has_capability('mod/scorm:deleteownresponses', $this->manager->get_context())) {
            $page = \core\di::get(\core\output\renderer_helper::class)->get_page();
            $extrabutton = new single_button(
                url: new url($page->url, ['action' => 'delete', 'sesskey' => sesskey()]),
                label: get_string('deleteallattempts', 'scorm'),
                type: single_button::BUTTON_DANGER,
            );
            $result['extrabutton'] = $extrabutton->export_for_template($output);
            $result['hasbutton'] = true;
        }
        return $result;
    }

    private function attempts_status_data(): array {
        global $USER;
        if (empty($this->attempts)) {
            return [];
        }
        $result = [];
        $i = 1;
        foreach ($this->attempts as $attempt) {
            $gradereported = scorm_grade_user_attempt($this->instance, $USER->id, $attempt->attemptnumber);

            if (
                $this->instance->grademethod !== GRADESCOES
                && !empty($this->instance->maxgrade)
            ) {
                $gradereported = $gradereported / $this->instance->maxgrade;
                $gradereported = number_format($gradereported * 100, 0) . '%';
            }

            $result[] = [
                'label' => get_string('gradeforattempt', 'scorm') . ' ' . $i,
                'value' => $gradereported,
            ];
            $i++;
        }
        return $result;
    }

    private function grading_status_data(): array {
        global $CFG;
        // Ensure we have the constant definitions.
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        if ($this->instance->maxattempt == 1) {
            $grademethod = match ($this->instance->grademethod) {
                GRADEHIGHEST => get_string('gradehighest', 'scorm'),
                GRADEAVERAGE => get_string('gradeaverage', 'scorm'),
                GRADESUM => get_string('gradesum', 'scorm'),
                GRADESCOES => get_string('gradescoes', 'scorm'),
                default => null,
            };
        } else {
            $grademethod = match ($this->instance->whatgrade) {
                HIGHESTATTEMPT => get_string('highestattempt', 'scorm'),
                AVERAGEATTEMPT => get_string('averageattempt', 'scorm'),
                FIRSTATTEMPT => get_string('firstattempt', 'scorm'),
                LASTATTEMPT => get_string('lastattempt', 'scorm'),
                default => null,
            };
        }
        return [
            'label' => get_string('grademethod', 'scorm'),
            'value' => $grademethod,
        ];
    }

    private function grade_status_data(): array {
        global $USER;

        $calculatedgrade = scorm_grade_user($this->instance, $USER->id);
        if ($this->instance->grademethod !== GRADESCOES && !empty($this->instance->maxgrade)) {
            $calculatedgrade = $calculatedgrade / $this->instance->maxgrade;
            $calculatedgrade = number_format($calculatedgrade * 100, 0) . '%';
        }

        return [
            'label' => get_string('gradereported', 'scorm'),
            'value' => !empty($calculatedgrade) ? $calculatedgrade : get_string('none'),
        ];
    }

    private function max_attempts_notice(): ?array {
        if (
            $this->instance->maxattempt > 0
            && count($this->attempts) >= $this->instance->maxattempt
        ) {
            return [
                'type' => 'info',
                'message' => get_string('exceededmaxattempts', 'scorm'),
            ];
        }
        return null;
    }
}
