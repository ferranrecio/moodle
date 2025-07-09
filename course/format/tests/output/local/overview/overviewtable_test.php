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

namespace core_courseformat\output\local\overview;

/**
 * Tests for courseformat
 *
 * @package    core_courseformat
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core_courseformat\output\local\overview\overviewtable
 */
final class overviewtable_test extends \advanced_testcase {
    /**
     * Test the export_for_external returns the right structure.
     *
     * @covers ::export_for_external
     */
    public function test_export_for_external(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $mods = [
            'assign1' => $this->getDataGenerator()->create_module('assign', ['course' => $course->id]),
            'assign2' => $this->getDataGenerator()->create_module('assign', ['course' => $course->id]),
        ];

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);

        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();

        $overviewtable = new overviewtable($course, 'assign');

        $data = $overviewtable->export_for_external($renderer);

        $this->assertObjectHasProperty('caption', $data);
        $this->assertObjectHasProperty('headers', $data);
        $this->assertObjectHasProperty('courseid', $data);
        $this->assertObjectHasProperty('hasintegration', $data);
        $this->assertObjectHasProperty('overviews', $data);
        $this->assertCount(5, get_object_vars($data));

        foreach ($data->headers as $header) {
            $this->assertObjectHasProperty('name', $header);
            $this->assertObjectHasProperty('key', $header);
            $this->assertObjectHasProperty('textalign', $header);
            $this->assertCount(3, get_object_vars($header));
        }

        foreach ($data->overviews as $overview) {
            $this->assertObjectHasProperty('cmid', $overview);
            $this->assertObjectHasProperty('contextid', $overview);
            $this->assertObjectHasProperty('modname', $overview);
            $this->assertObjectHasProperty('name', $overview);
            $this->assertObjectHasProperty('url', $overview);
            $this->assertObjectHasProperty('haserror', $overview);
            $this->assertObjectHasProperty('items', $overview);
            $this->assertCount(7, get_object_vars($overview));

            foreach ($overview->items as $item) {
                $this->assertObjectHasProperty('name', $item);
                $this->assertObjectHasProperty('key', $item);
                $this->assertObjectHasProperty('contenttype', $item);
                $this->assertObjectHasProperty('alertlabel', $item);
                $this->assertObjectHasProperty('alertcount', $item);
                $this->assertObjectHasProperty('contentdata', $item);
                $this->assertObjectHasProperty('extradata', $item);
                $this->assertCount(7, get_object_vars($item));
            }
        }

        // The internal header and items depends on the type of activity, we only need to
        // check that we have the expected number of headers and overviews.
        $this->assertCount(3, $data->headers);
        $this->assertCount(2, $data->overviews);

        $modinfo = get_fast_modinfo($course);

        $cm = $modinfo->get_cm($mods['assign1']->cmid);
        $this->assertEquals($cm->id, $data->overviews[0]->cmid);
        $this->assertEquals($cm->context->id, $data->overviews[0]->contextid);
        $this->assertEquals($cm->modname, $data->overviews[0]->modname);
        $this->assertEquals($cm->name, $data->overviews[0]->name);
        $this->assertEquals($cm->url->out(false), $data->overviews[0]->url);
        $this->assertFalse($data->overviews[0]->haserror);
        $this->assertCount(count($data->headers), $data->overviews[0]->items);

        $cm = $modinfo->get_cm($mods['assign2']->cmid);
        $this->assertEquals($cm->id, $data->overviews[1]->cmid);
        $this->assertEquals($cm->context->id, $data->overviews[1]->contextid);
        $this->assertEquals($cm->modname, $data->overviews[1]->modname);
        $this->assertEquals($cm->name, $data->overviews[1]->name);
        $this->assertEquals($cm->url->out(false), $data->overviews[1]->url);
        $this->assertFalse($data->overviews[1]->haserror);
        $this->assertCount(count($data->headers), $data->overviews[1]->items);
    }
}
