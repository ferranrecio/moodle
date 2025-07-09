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

use core\output\local\properties\text_align;
use core_reportbuilder\external\filters\set;

/**
 * Tests for overviewitem.
 *
 * @package    core_courseformat
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \core_courseformat\local\overview\overviewitem
 */
final class overviewitem_test extends \advanced_testcase {
    /**
     * Tests the constructor.
     *
     * @covers ::__construct
     * @covers ::get_name
     * @covers ::get_value
     * @covers ::get_content
     * @covers ::get_text_align
     * @covers ::get_alert_count
     * @covers ::get_alert_label
     */
    public function test_constructor(): void {
        $name = 'Activity name';
        $value = 1;
        $content = 'Activity content';
        $textalign = text_align::CENTER;
        $alertcount = 1;
        $alertlabel = 'Alert label';
        $extradata = (object)[
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $item = new overviewitem($name, $value, $content, $textalign, $alertcount, $alertlabel, $extradata);

        $this->assertEquals($name, $item->get_name());
        $this->assertEquals($value, $item->get_value());
        $this->assertEquals($content, $item->get_content());
        $this->assertEquals($textalign, $item->get_text_align());
        $this->assertEquals($alertcount, $item->get_alert_count());
        $this->assertEquals($alertlabel, $item->get_alert_label());
        $this->assertNull($item->get_key()); // Key is null by default.
        $this->assertEquals($extradata, $item->get_extra_data());
    }

    /**
     * Test chained setters.
     *
     * @covers ::set_name
     * @covers ::set_value
     * @covers ::set_content
     * @covers ::set_text_align
     * @covers ::set_alert_count
     * @covers ::set_alert_label
     * @covers ::get_name
     * @covers ::get_value
     * @covers ::get_content
     * @covers ::get_text_align
     * @covers ::get_alert_count
     * @covers ::get_alert_label
     */
    public function test_setters(): void {
        $item = new overviewitem('Sample', 1, 'Content', text_align::CENTER, 1, 'Alert label');

        $this->assertEquals('Sample', $item->get_name());
        $this->assertEquals(1, $item->get_value());
        $this->assertEquals('Content', $item->get_content());
        $this->assertEquals(text_align::CENTER, $item->get_text_align());
        $this->assertEquals(1, $item->get_alert_count());
        $this->assertEquals('Alert label', $item->get_alert_label());

        $name = 'New activity name';
        $value = 2;
        $content = 'New activity content';
        $textalign = text_align::END;
        $alertcount = 2;
        $alertlabel = 'New alert label';
        $key = 'newkey';
        $extradata = (object)[
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $item->set_name($name)
            ->set_value($value)
            ->set_content($content)
            ->set_text_align($textalign)
            ->set_alert($alertcount, $alertlabel)
            ->set_key($key)
            ->set_extra_data($extradata);

        $this->assertEquals($name, $item->get_name());
        $this->assertEquals($value, $item->get_value());
        $this->assertEquals($content, $item->get_content());
        $this->assertEquals($textalign, $item->get_text_align());
        $this->assertEquals($alertcount, $item->get_alert_count());
        $this->assertEquals($alertlabel, $item->get_alert_label());
        $this->assertEquals($key, $item->get_key());
        $this->assertEquals($extradata, $item->get_extra_data());
    }

    /**
     * Test the export_for_external returns the right structure.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external(): void {
        $name = 'Activity name';
        $value = 1;
        $content = 'Activity content';
        $textalign = text_align::CENTER;
        $alertcount = 1;
        $alertlabel = 'Alert label';
        $key = 'newkey';
        $extradata = (object) [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $item = new overviewitem($name, $value, $content, $textalign, $alertcount, $alertlabel, $extradata);
        $item->set_key($key);

        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $data = $item->export_for_external($renderer);

        $this->assertObjectHasProperty('name', $data);
        $this->assertObjectHasProperty('key', $data);
        $this->assertObjectHasProperty('contenttype', $data);
        $this->assertObjectHasProperty('alertlabel', $data);
        $this->assertObjectHasProperty('alertcount', $data);
        $this->assertObjectHasProperty('contentdata', $data);
        $this->assertObjectHasProperty('extradata', $data);
        $this->assertCount(7, get_object_vars($data));

        $this->assertObjectHasProperty('value', $data->contentdata);
        $this->assertObjectHasProperty('datatype', $data->contentdata);
        $this->assertCount(2, get_object_vars($data->contentdata));

        $this->assertEquals($name, $data->name);
        $this->assertEquals($key, $data->key);
        $this->assertEquals('basic', $data->contenttype);
        $this->assertEquals($alertcount, $data->alertcount);
        $this->assertEquals($alertlabel, $data->alertlabel);
        $this->assertEquals($extradata, $data->extradata);

        $this->assertEquals($value, $data->contentdata->value);
        $this->assertEquals(gettype($value), $data->contentdata->datatype);
    }
}
