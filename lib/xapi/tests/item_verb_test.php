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
class core_xapi_item_verb_testcase extends advanced_testcase {

    /**
     * Test item creation.
     */
    public function test_creation (): void {

        $data = (object) [
            'id' => helper::generate_iri('cook', 'verb'),
        ];
        $item = item_verb::create_from_data($data);

        $this->assertEquals(json_encode($item), json_encode($data));
        $this->assertEquals($item->get_verb(), 'cook');
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
