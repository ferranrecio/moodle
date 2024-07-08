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

namespace core\fileredact;

/**
 * Tests for fileredact manager class.
 *
 * @package   core
 * @copyright Meirza <meirza.arson@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \core\fileredact\manager
 */
final class manager_test extends \basic_testcase {

    /** @var \stdClass File record */
    private $filerecord;

    /** @var array Additional options (path, contents) provided from the before_file_created hook */
    private $extra;

    public function setUp(): void {
        parent::setUp();
        // Set dummy file record.
        $this->filerecord = new \stdClass;
        $this->filerecord->filename = 'test.jpg';
        $this->filerecord->mimetype = 'image/jpeg';

        // Set dummy extra information.
        $this->extra = [];
        $this->extra['pathname'] = '/tmp/pathname';
    }

    /**
     * Tests the `get_services` method.
     *
     * This test initializes the `manager` and verifies that the `get_services` method.
     */
    public function test_get_services(): void {
        // Init the manager.
        $manager = new \core\fileredact\manager($this->filerecord, $this->extra);

        $rc = new \ReflectionClass(\core\fileredact\manager::class);
        $rcm = $rc->getMethod('get_services');
        $services = $rcm->invoke($manager);

        $this->assertGreaterThan(0, count($services));

    }

    /**
     * Tests the `execute` method and error handling.
     *
     * This test mocks the `manager` class to return a dummy service for `get_services`
     * and verifies that the `execute` method runs without errors.
     */
    public function test_execute(): void {
        $managermock = $this->getMockBuilder(\core\fileredact\manager::class)
            ->onlyMethods(['get_services'])
            ->setConstructorArgs([$this->filerecord, $this->extra])
            ->getMock();

        $managermock->expects($this->once())
            ->method('get_services')
            ->willReturn(['\\core\fileredact\\services\\dummy_service']);

        /** @var \core\fileredact\manager $managermock */
        $managermock->execute();
        $errors = $managermock->get_errors();

        // If execution is OK, then no errors.
        $this->assertEquals([], $errors);
    }
}
