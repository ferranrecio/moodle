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

namespace core\output;

/**
 * Tests for the pix_icon output class.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core\output\pix_icon
 */
final class pix_icon_test extends \advanced_testcase {
    /**
     * Test the export_for_external returns the right structure.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external(): void {
        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $pix = 'i/warning';
        $alt = get_string('warning');
        $component = 'moodle';
        $attributes = ['class' => 'me-0 pb-1'];

        $icon = new \core\output\pix_icon($pix, $alt, $component, $attributes);
        $data = $icon->export_for_external($renderer);

        $this->assertObjectHasProperty('key', $data);
        $this->assertObjectHasProperty('component', $data);
        $this->assertObjectHasProperty('attributes', $data);
        $this->assertCount(3, get_object_vars($data));

        $this->assertEquals($pix, $data->key);
        $this->assertEquals($component, $data->component);

        $expectedattributes = [
            'class' => 'me-0 pb-1',
            'alt' => $alt,
            'title' => $alt,
        ];
        $this->assertEquals($expectedattributes, $data->attributes);
    }
}
