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
 * Class to track state actions.
 *
 * The methods from this class should be executed via "stateactions" metdhos.
 *
 * Each format plugin could extend this class to provide new updates to the frontend
 * mutation module.
 * Extended classes should be locate in "format_XXX\course\stateupdates" namespace and
 * extends core_course\stateupdates.
 *
 * @package    core
 * @subpackage course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course;

use core_course\course_format;
use renderer_base;
use stdClass;
use course_modinfo;
use JsonSerializable;

defined('MOODLE_INTERNAL') || die();

class stateupdates implements JsonSerializable {

    /** @var course_format format the course format */
    protected $format;

    /** @var renderer_base renderer format renderer */
    protected $output;

    /** @var array the tracked updates */
    protected $updates;

    public function __construct(renderer_base $output, course_format $format) {
        $this->format = $format;
        $this->output = $output;
        $this->updates = [];
    }

    /**
     * Return the data to serialize the current track in JSON.
     *
     * @return stdClass the statement data structure
     */
    public function jsonSerialize(): array {
        return $this->updates;
    }

    /**
     * Add track about a general course state change.
     */
    public function add_course_update() {

        $courseclass = $this->format->get_output_classname('course_format\state');
        $current_state = new $courseclass($this->format);
        $this->updates[] = (object)[
            'name' => 'course',
            'action' => 'update',
            'fields' => $current_state->export_for_template($this->output),
        ];
    }

    /**
     * Add track about a section state update.
     *
     * @param int $sectionid the affected section id
     */
    public function add_section_update(int $sectionid) {

        $modinfo = course_modinfo::instance($this->format->get_course());

        $section = $modinfo->get_section_info($sectionid);
        $sectionclass = $this->format->get_output_classname('section_format\state');
        $current_state = new $sectionclass($this->format, $section);

        $this->updates[] = (object)[
            'name' => 'section',
            'action' => 'update',
            'fields' => $current_state->export_for_template($this->output),
        ];
    }

    /**
     * Add track about a new section created.
     *
     * @param int $sectionid the affected section id
     */
    public function add_section_create(int $sectionid) {
        $result = $this->add_section_update($sectionid);
        $result->action = 'create';
        $this->updates[] = $result;
    }

    /**
     * Add track about a section deleted.
     *
     * @param int $sectionid the affected section id
     */
    public function add_section_delete(int $sectionid) {
        $this->updates[] = (object)[
            'name' => 'section',
            'action' => 'delete',
            'fields' => (object)['id' => $sectionid],
        ];
    }

    /**
     * Add track about a course module state update.
     *
     * @param int $cmid the affected course module id
     * @param bool $exportcontent if the tracked state should contain also
     *             the pre-rendered cmitem content
     */
    public function add_cm_update(int $cmid, bool $exportcontent = false) {

        $modinfo = course_modinfo::instance($this->format->get_course());

        $cm = $modinfo->get_cm($cmid);
        $cmclass = $this->format->get_output_classname('cm_format\state');
        $section = $modinfo->get_section_info($cm->section);
        $current_state = new $cmclass($this->format, $section, $cm, $exportcontent);

        $this->updates[] = (object)[
            'name' => 'cm',
            'action' => 'update',
            'fields' => $current_state->export_for_template($this->output),
        ];
    }

    /**
     * Add track about a course module created.
     *
     * @param int $cmid the affected course module id
     */
    public function add_cm_create(int $cmid) {
        $result = $this->add_section_update($cmid, true);
        $result->action = 'create';
        $this->updates[] = $result;
    }

    /**
     * Add track about a course module deleted.
     *
     * @param int $cmid the affected course module id
     */
    public function add_cm_delete(int $cmid) {
        $this->updates[] = (object)[
            'name' => 'cm',
            'action' => 'delete',
            'fields' => (object)['id' => $cmid],
        ];
    }

}
