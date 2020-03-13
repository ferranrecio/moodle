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
 * This file contains unit test related to xAPI library
 *
 * @package    core_xapi
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi\local\statement;

use advanced_testcase;
use InvalidArgumentException;
use core_xapi\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Contains test cases for testing statement base classes
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_xapi_item_object_testcase extends advanced_testcase {

    /**
     * Test item creation with agent.
     */
    public function test_creation_agent (): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $item = item_agent::create_from_user($user);
        $data = $item->get_data();

        $item = item_object::create_from_data($data);

        $this->assertEquals(json_encode($item), json_encode($data));
        $this->assertEquals('core_xapi\local\statement\item_agent', get_class($item));
    }

    /**
     * Test item creation with group.
     */
    public function test_creation_group (): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $item = item_group::create_from_group($group);
        $data = $item->get_data();

        $item = item_object::create_from_data($data);

        $this->assertEquals(json_encode($item), json_encode($data));
        $this->assertEquals('core_xapi\local\statement\item_group', get_class($item));
    }

    /**
     * Test item creation with activity.
     */
    public function test_creation_activity (): void {

        $data = (object) [
            'id' => helper::generate_iri('cook', 'verb'),
        ];
        $item = item_object::create_from_data($data);

        $this->assertEquals(json_encode($item), json_encode($data));
        $this->assertEquals('core_xapi\local\statement\item_activity', get_class($item));
    }

    /**
     * Test for invalid structures.
     *
     * @dataProvider test_invalid_data_provider
     * @param string  $id
     */
    public function test_invalid_data(string $id): void {
        $this->expectException(InvalidArgumentException::class);
        $data = (object) [
            'id' => $id,
        ];
        $item = item_verb::create_from_data($data);
    }

        /**
     * Data provider for the test_invalid_data tests.
     *
     * @return  array
     */
    public function test_invalid_data_provider() : array {
        return [
            'Empty or null id' => [
                '',
            ],
            'Invalid IRI value' => [
                'invalid_iri_value',
            ],
        ];
    }

}
