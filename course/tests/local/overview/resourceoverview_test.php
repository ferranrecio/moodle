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

/**
 * Tests for resource overview
 *
 * @package    core_course
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core_course\local\overview\resourceoverview
 */
final class resourceoverview_test extends \advanced_testcase {
    /**
     * Test get_extra_overview_items method.
     *
     * @covers ::get_extra_overview_items
     */
    public function test_get_extra_overview_items(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activity = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($activity->cmid);

        $overview = overviewfactory::create($cm);

        $result = $overview->get_extra_overview_items();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertInstanceOf(\core_course\local\overview\overviewitem::class, $result['type']);
    }

    /**
     * Test get_extra_type_overview method.
     *
     * @covers ::get_extra_overview_items
     * @covers ::get_extra_type_overview
     * @dataProvider get_extra_type_overview_provider
     * @param string $resourcetype
     */
    public function test_get_extra_type_overview(
        string $resourcetype,
    ): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activity = $this->getDataGenerator()->create_module($resourcetype, ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($activity->cmid);

        $overview = overviewfactory::create($cm);

        $items = $overview->get_extra_overview_items();
        $result = $items['type'];

        $this->assertEquals(get_string('resource_type'), $result->get_name());
        $this->assertEquals($cm->modfullname, $result->get_value());
        $this->assertEquals($cm->modfullname, $result->get_content());
    }

    /**
     * Data provider for test_get_extra_type_overview.
     *
     * @return array
     */
    public static function get_extra_type_overview_provider(): array {
        return [
            'book' => [
                'resourcetype' => 'book',
            ],
            'folder' => [
                'resourcetype' => 'folder',
            ],
            'page' => [
                'resourcetype' => 'page',
            ],
            'resource' => [
                'resourcetype' => 'resource',
            ],
            'url' => [
                'resourcetype' => 'url',
            ],
        ];
    }
}
