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
 * mod_h5pactivity generator tests
 *
 * @package    mod_h5pactivity
 * @category   test
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Attempt tests class for mod_h5pactivity.
 *
 * @package    mod_h5pactivity
 * @category   test
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_h5pactivity_attempt_testcase extends advanced_testcase {

    public function test_create_attempt() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $course));
        $cm = get_coursemodule_from_id('h5pactivity', $activity->cmid, 0, false, MUST_EXIST);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create first attempt.
        $attempt = \mod_h5pactivity\attempt::new_attempt($student, $cm);
        $this->assertEquals($student->id, $attempt->get_userid());
        $this->assertEquals($cm->instance, $attempt->get_h5pacivityid());
        $this->assertEquals(1, $attempt->get_attempt());

        // Create a second attempt.
        $attempt = \mod_h5pactivity\attempt::new_attempt($student, $cm);
        $this->assertEquals($student->id, $attempt->get_userid());
        $this->assertEquals($cm->instance, $attempt->get_h5pacivityid());
        $this->assertEquals(2, $attempt->get_attempt());
    }

    public function test_last_attempt() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $course));
        $cm = get_coursemodule_from_id('h5pactivity', $activity->cmid, 0, false, MUST_EXIST);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create first attempt.
        $attempt = \mod_h5pactivity\attempt::last_attempt($student, $cm);
        $this->assertEquals($student->id, $attempt->get_userid());
        $this->assertEquals($cm->instance, $attempt->get_h5pacivityid());
        $this->assertEquals(1, $attempt->get_attempt());
        $lastid = $attempt->get_id();

        // Get last attempt.
        $attempt = \mod_h5pactivity\attempt::last_attempt($student, $cm);
        $this->assertEquals($student->id, $attempt->get_userid());
        $this->assertEquals($cm->instance, $attempt->get_h5pacivityid());
        $this->assertEquals(1, $attempt->get_attempt());
        $this->assertEquals($lastid, $attempt->get_id());

        // Now force a new attempt.
        $attempt = \mod_h5pactivity\attempt::new_attempt($student, $cm);
        $this->assertEquals($student->id, $attempt->get_userid());
        $this->assertEquals($cm->instance, $attempt->get_h5pacivityid());
        $this->assertEquals(2, $attempt->get_attempt());
        $lastid = $attempt->get_id();

        // Get last attempt.
        $attempt = \mod_h5pactivity\attempt::last_attempt($student, $cm);
        $this->assertEquals($student->id, $attempt->get_userid());
        $this->assertEquals($cm->instance, $attempt->get_h5pacivityid());
        $this->assertEquals(2, $attempt->get_attempt());
        $this->assertEquals($lastid, $attempt->get_id());
    }

    public function test_save_statement() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $course));
        $cm = get_coursemodule_from_id('h5pactivity', $activity->cmid, 0, false, MUST_EXIST);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');


    }

    private function generate_statement () {
        $res = new stdClass();
        $res->object = new stdClass();
        $res->object->defintion = (object) [
                    'interactionType' => 'other',
                    'correctResponsesPattern' => '1',

                ];
    }
}
