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
 * Unit tests for activity creation.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign\tests;

use mod_peerassign\manager;
use mod_peerassign\local\models\peerassign as peerassign_model;

/**
 * Test case for activity creation.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(manager::class)]
final class activity_creation_test extends \advanced_testcase {
    /**
     * Test that an activity can be created.
     */
    public function test_create_activity(): void {
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create activity data.
        $data = new \stdClass();
        $data->course = $course->id;
        $data->name = 'Test Peer Review Assignment';
        $data->intro = 'Test introduction';
        $data->introformat = 1;
        $data->blindreview = 0;
        $data->groupsubmissions = 0;
        $data->groupingid = null;

        // Add the instance.
        $id = peerassign_add_instance($data);

        // Verify it was created.
        $this->assertNotEmpty($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * Test manager static creator methods.
     */
    public function test_manager_static_creators(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
            'name' => 'Creator methods test',
        ]);

        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $cm = get_coursemodule_from_instance(manager::MODULE, $module->id, $course->id, false, MUST_EXIST);

        $managerfrominstance = \mod_peerassign\manager::create_from_instance($instance);
        $this->assertInstanceOf(\mod_peerassign\manager::class, $managerfrominstance);

        $managerfromcm = \mod_peerassign\manager::create_from_coursemodule($cm);
        $this->assertInstanceOf(\mod_peerassign\manager::class, $managerfromcm);

        $record = (object)[
            'peerassignid' => $module->id,
        ];
        $managerfromrecord = \mod_peerassign\manager::create_from_data_record($record);
        $this->assertInstanceOf(\mod_peerassign\manager::class, $managerfromrecord);
    }
}
