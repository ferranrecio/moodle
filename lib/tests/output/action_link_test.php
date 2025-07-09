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
 * Tests for
 *
 * @package    core
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core\output\action_link
 */
final class action_link_test extends \advanced_testcase {
    /**
     * Test the export_for_external returns the right structure when the content is a string.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external_string(): void {
        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $url = new \core\url('/some/url');
        $text = 'Click here';
        $icon = new \core\output\pix_icon('i/warning', 'sample');
        $attributes = ['class' => 'me-0 pb-1'];

        $actionlink = new \core\output\action_link(
            url: $url,
            text: $text,
            icon: $icon,
            attributes: $attributes,
        );
        $data = $actionlink->export_for_external($renderer);

        $this->assertObjectHasProperty('url', $data);
        $this->assertObjectHasProperty('text', $data);
        $this->assertObjectHasProperty('icon', $data);
        $this->assertObjectHasProperty('classes', $data);
        $this->assertObjectHasProperty('texttype', $data);
        $this->assertObjectHasProperty('textdata', $data);
        $this->assertCount(6, get_object_vars($data));

        $this->assertEquals($url->out(false), $data->url);
        $this->assertEquals($text, $data->text);
        $this->assertEquals($icon->export_for_external($renderer), $data->icon);
        $this->assertEquals($attributes['class'], $data->classes);
        // For string text, we don't have extra data, so texttype is 'string' and textdata is null.
        $this->assertEquals('string', $data->texttype);
        $this->assertEquals(null, $data->textdata);
    }

    /**
     * Test the export_for_external returns the right structure when the content is a renderable.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external_renderable(): void {
        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $url = new \core\url('/some/url');
        $icon = new \core\output\pix_icon('i/warning', 'sample');
        $attributes = ['class' => 'me-0 pb-1'];

        // We use help_icon as text to simulate a renderable content that is not externable.
        $text = new help_icon('search', 'core');

        $actionlink = new \core\output\action_link(
            url: $url,
            text: $text,
            icon: $icon,
            attributes: $attributes,
        );
        $data = $actionlink->export_for_external($renderer);

        $this->assertObjectHasProperty('url', $data);
        $this->assertObjectHasProperty('text', $data);
        $this->assertObjectHasProperty('icon', $data);
        $this->assertObjectHasProperty('classes', $data);
        $this->assertObjectHasProperty('texttype', $data);
        $this->assertObjectHasProperty('textdata', $data);
        $this->assertCount(6, get_object_vars($data));

        $this->assertEquals($url->out(false), $data->url);
        $this->assertEquals($renderer->render($text), $data->text);
        $this->assertEquals($icon->export_for_external($renderer), $data->icon);
        $this->assertEquals($attributes['class'], $data->classes);
        // Since help_icon is not externable, we expect the texttype to be 'string' and textdata to be null.
        $this->assertEquals('string', $data->texttype);
        $this->assertEquals(null, $data->textdata);
    }

    /**
     * Test the export_for_external returns the right structure when the content is a externable.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external_externable(): void {
        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $url = new \core\url('/some/url');
        $icon = new \core\output\pix_icon('i/warning', 'sample');
        $attributes = ['class' => 'me-0 pb-1'];

        // We use pix_icon as text to simulate a renderable content that is externable.
        $text = new \core\output\pix_icon('i/info', 'Information');

        $actionlink = new \core\output\action_link(
            url: $url,
            text: $text,
            icon: $icon,
            attributes: $attributes,
        );
        $data = $actionlink->export_for_external($renderer);

        $this->assertObjectHasProperty('url', $data);
        $this->assertObjectHasProperty('text', $data);
        $this->assertObjectHasProperty('icon', $data);
        $this->assertObjectHasProperty('classes', $data);
        $this->assertObjectHasProperty('texttype', $data);
        $this->assertObjectHasProperty('textdata', $data);
        $this->assertCount(6, get_object_vars($data));

        $this->assertEquals($url->out(false), $data->url);
        $this->assertEquals($renderer->render($text), $data->text);
        $this->assertEquals($icon->export_for_external($renderer), $data->icon);
        $this->assertEquals($attributes['class'], $data->classes);
        $this->assertEquals('core\output\pix_icon', $data->texttype);
        $this->assertEquals($text->export_for_external($renderer), $data->textdata);
    }
}
