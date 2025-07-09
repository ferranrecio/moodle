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

namespace core_courseformat\output\local\content\cm;

use core_completion\external\completion_info_exporter;

/**
 * Tests for courseformat
 *
 * @package    core_courseformat
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core_courseformat\output\local\content\cm\completion_test
 */
final class completion_test extends \advanced_testcase {
    #[\Override]
    public static function setupBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
    }

    /**
     * Test the export_for_external returns the right structure.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => 1]);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_activity' => [$assign->cmid => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $user->id);

        $this->setUser($user);

        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        $cminfo = $modinfo->get_cm($assign->cmid);

        $completionoutput = new completion(
            $format,
            $modinfo->get_section_info($cminfo->sectionnum),
            $cminfo,
        );
        $data = $completionoutput->export_for_external($renderer);

        // The completion info has a specific exporter and the output should
        // use the same logic, no matter what.
        $exporter = new completion_info_exporter(
            $course,
            $cminfo,
            $user->id,
        );
        $expected = $exporter->export($renderer);

        $this->assertEquals($expected, $data);
    }
}
