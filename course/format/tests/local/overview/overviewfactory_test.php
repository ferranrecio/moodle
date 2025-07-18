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

namespace core_courseformat\local\overview;

/**
 * Tests for course
 *
 * @package    core_courseformat
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core_courseformat\local\overview\overviewfactory
 */
final class overviewfactory_test extends \advanced_testcase {
    #[\Override()]
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/format/tests/fixtures/wrongcm_activityoverview.php');
        parent::setUpBeforeClass();
    }

    /**
     * Test create method on resource activities.
     *
     * @covers ::create
     * @dataProvider create_resource_provider
     * @param string $resourcetype
     */
    public function test_create_resource(
        string $resourcetype,
        ?string $expected,
    ): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activity = $this->getDataGenerator()->create_module($resourcetype, ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($activity->cmid);

        $overview = overviewfactory::create($cm);

        $this->assertInstanceOf($expected, $overview);
    }

    /**
     * Data provider for test_create_resource.
     *
     * @return array
     */
    public static function create_resource_provider(): array {
        return [
            // Resource activities.
            'book' => [
                'resourcetype' => 'book',
                'expected' => resourceoverview::class,
            ],
            'folder' => [
                'resourcetype' => 'folder',
                'expected' => resourceoverview::class,
            ],
            'page' => [
                'resourcetype' => 'page',
                'expected' => resourceoverview::class,
            ],
            'resource' => [
                'resourcetype' => 'resource',
                'expected' => resourceoverview::class,
            ],
            'url' => [
                'resourcetype' => 'url',
                'expected' => resourceoverview::class,
            ],
            // Fallbacks and integrations.
            'assign' => [
                'resourcetype' => 'assign',
                'expected' => \mod_assign\courseformat\overview::class,
            ],
            'bigbluebuttonbn' => [
                'resourcetype' => 'bigbluebuttonbn',
                'expected' => resourceoverview::class,
            ],
            'choice' => [
                'resourcetype' => 'choice',
                'expected' => \mod_choice\courseformat\overview::class,
            ],
            'data' => [
                'resourcetype' => 'data',
                'expected' => \mod_data\courseformat\overview::class,
            ],
            'feedback' => [
                'resourcetype' => 'feedback',
                'expected' => \mod_feedback\courseformat\overview::class,
            ],
            'forum' => [
                'resourcetype' => 'forum',
                'expected' => resourceoverview::class,
            ],
            'glossary' => [
                'resourcetype' => 'glossary',
                'expected' => \mod_glossary\courseformat\overview::class,
            ],
            'h5pactivity' => [
                'resourcetype' => 'h5pactivity',
                'expected' => \mod_h5pactivity\courseformat\overview::class,
            ],
            'lesson' => [
                'resourcetype' => 'lesson',
                'expected' => \mod_lesson\courseformat\overview::class,
            ],
            'lti' => [
                'resourcetype' => 'lti',
                'expected' => resourceoverview::class,
            ],
            'qbank' => [
                'resourcetype' => 'qbank',
                'expected' => resourceoverview::class,
            ],
            'quiz' => [
                'resourcetype' => 'quiz',
                'expected' => resourceoverview::class,
            ],
            'scorm' => [
                'resourcetype' => 'scorm',
                'expected' => resourceoverview::class,
            ],
            'wiki' => [
                'resourcetype' => 'wiki',
                'expected' => resourceoverview::class,
            ],
            'workshop' => [
                'resourcetype' => 'workshop',
                'expected' => \mod_workshop\courseformat\overview::class,
            ],
        ];
    }

    /**
     * Test create method on a fake activity with a wrong class.
     *
     * @covers ::create
     */
    public function test_create_exception(
    ): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($activity->cmid);

        // We know the factory will only use the modname to create the overview,
        // this is a small trick to make the factory to use a wrong class and
        // won't happen in a real code. However, this is the easiest way to test
        // the exception.
        $reflection = new \ReflectionClass($cm);
        $property = $reflection->getProperty('modname');
        $property->setAccessible(true);
        $property->setValue($cm, 'wrongcm');

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessageMatches("/.* must extend core_courseformat\\\\activityoverviewbase.*/");
        overviewfactory::create($cm);
    }

    /**
     * Test activity_has_overview_integration for existing modules.
     *
     * @dataProvider activity_has_overview_integration_provider
     * @param string $modname
     * @param bool $expectempty
     */
    public function test_activity_has_overview_integration(
        string $modname,
        bool $expectempty,
    ): void {
        $result = overviewfactory::activity_has_overview_integration($modname);
        $this->assertEquals($expectempty, $result);
    }

    /**
     * Data provider for test_overview_integrations.
     *
     * @return array
     */
    public static function activity_has_overview_integration_provider(): array {
        return [
            'assign' => ['modname' => 'assign', 'expectempty' => true],
            'bigbluebuttonbn' => ['modname' => 'bigbluebuttonbn', 'expectempty' => false],
            'book' => ['modname' => 'book', 'expectempty' => false],
            'choice' => ['modname' => 'choice', 'expectempty' => true],
            'data' => ['modname' => 'data', 'expectempty' => true],
            'feedback' => ['modname' => 'feedback', 'expectempty' => true],
            'folder' => ['modname' => 'folder', 'expectempty' => false],
            'forum' => ['modname' => 'forum', 'expectempty' => false],
            'glossary' => ['modname' => 'glossary', 'expectempty' => true],
            'h5pactivity' => ['modname' => 'h5pactivity', 'expectempty' => true],
            'imscp' => ['modname' => 'imscp', 'expectempty' => false],
            'label' => ['modname' => 'label', 'expectempty' => false],
            'lesson' => ['modname' => 'lesson', 'expectempty' => true],
            'lti' => ['modname' => 'lti', 'expectempty' => false],
            'page' => ['modname' => 'page', 'expectempty' => false],
            'qbank' => ['modname' => 'qbank', 'expectempty' => false],
            'quiz' => ['modname' => 'quiz', 'expectempty' => false],
            'resource' => ['modname' => 'resource', 'expectempty' => true],
            'scorm' => ['modname' => 'scorm', 'expectempty' => false],
            'url' => ['modname' => 'url', 'expectempty' => false],
            'wiki' => ['modname' => 'wiki', 'expectempty' => false],
            'workshop' => ['modname' => 'workshop', 'expectempty' => true],
        ];
    }

    public function test_activity_has_overview_integration_non_existing(): void {
        $result = overviewfactory::activity_has_overview_integration('fakemodulenonexisting');
        $this->assertFalse($result);
    }
}
