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
 * Unit tests for the completion condition.
 *
 * @package availability_completion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_completion\condition;

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Unit tests for the completion condition.
 *
 * @package availability_completion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_completion_condition_testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info_module.php');
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info_section.php');
        availability_completion\condition::wipe_static_cache();
    }

    /**
     * Tests constructing and using condition as part of tree.
     */
    public function test_in_tree() {
        global $USER, $CFG;
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create course with completion turned on and a Page.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $page = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $selfpage = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));

        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);
        $info = new \core_availability\mock_info($course, $USER->id);

        $structure = (object)array('op' => '|', 'show' => true, 'c' => array(
                (object)array('type' => 'completion', 'cm' => (int)$cm->id,
                'e' => COMPLETION_COMPLETE)));
        $tree = new \core_availability\tree($structure);

        // Initial check (user has not completed activity).
        $result = $tree->check_available(false, $info, true, $USER->id);
        $this->assertFalse($result->is_available());

        // Mark activity complete.
        $completion = new completion_info($course);
        $completion->update_state($cm, COMPLETION_COMPLETE);

        // Now it's true!
        $result = $tree->check_available(false, $info, true, $USER->id);
        $this->assertTrue($result->is_available());
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // No parameters.
        $structure = new stdClass();
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->cm', $e->getMessage());
        }

        // Invalid $cm.
        $structure->cm = 'hello';
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->cm', $e->getMessage());
        }

        // Missing $e.
        $structure->cm = 42;
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->e', $e->getMessage());
        }

        // Invalid $e.
        $structure->e = 99;
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->e', $e->getMessage());
        }

        // Successful construct & display with all different expected values.
        $structure->e = COMPLETION_COMPLETE;
        $cond = new condition($structure);
        $this->assertEquals('{completion:cm42 COMPLETE}', (string)$cond);

        $structure->e = COMPLETION_COMPLETE_PASS;
        $cond = new condition($structure);
        $this->assertEquals('{completion:cm42 COMPLETE_PASS}', (string)$cond);

        $structure->e = COMPLETION_COMPLETE_FAIL;
        $cond = new condition($structure);
        $this->assertEquals('{completion:cm42 COMPLETE_FAIL}', (string)$cond);

        $structure->e = COMPLETION_INCOMPLETE;
        $cond = new condition($structure);
        $this->assertEquals('{completion:cm42 INCOMPLETE}', (string)$cond);

        // Successful contruct with previous activity
        $structure->cm = \availability_completion\condition::$PREVIOUS;
        // $structure->selfid = 43;
        $cond = new condition($structure);
        $this->assertEquals('{completion:cmPREVIOUS INCOMPLETE}', (string)$cond);

    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)array('cm' => 42, 'e' => COMPLETION_COMPLETE);
        $cond = new condition($structure);
        $structure->type = 'completion';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the is_available and get_description functions.
     */
    public function test_usage() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        // Create a Page with manual completion for basic checks.
        $page = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page!',
                'completion' => COMPLETION_TRACKING_MANUAL));

        // Create an assignment - we need to have something that can be graded
        // so as to test the PASS/FAIL states. Set it up to be completed based
        // on its grade item.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
                'course' => $course->id, 'name' => 'Assign!',
                'completion' => COMPLETION_TRACKING_AUTOMATIC));
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0,
                array('id' => $assignrow->cmid));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);

        // Get basic details.
        $modinfo = get_fast_modinfo($course);
        $pagecm = $modinfo->get_cm($page->cmid);
        $assigncm = $assign->get_course_module();
        $info = new \core_availability\mock_info($course, $user->id);

        // COMPLETE state (false), positive and NOT.
        $cond = new condition((object)array(
                'cm' => (int)$pagecm->id, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // INCOMPLETE state (true).
        $cond = new condition((object)array(
                'cm' => (int)$pagecm->id, 'e' => COMPLETION_INCOMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page!.*is marked complete~', $information);

        // Mark page complete.
        $completion = new completion_info($course);
        $completion->update_state($pagecm, COMPLETION_COMPLETE);

        // COMPLETE state (true).
        $cond = new condition((object)array(
                'cm' => (int)$pagecm->id, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page!.*is incomplete~', $information);

        // INCOMPLETE state (false).
        $cond = new condition((object)array(
                'cm' => (int)$pagecm->id, 'e' => COMPLETION_INCOMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page!.*is incomplete~', $information);
        $this->assertTrue($cond->is_available(true, $info,
                true, $user->id));

        // We are going to need the grade item so that we can get pass/fails.
        $gradeitem = $assign->get_grade_item();
        grade_object::set_properties($gradeitem, array('gradepass' => 50.0));
        $gradeitem->update();

        // With no grade, it should return true for INCOMPLETE and false for
        // the other three.
        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_INCOMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Check $information for COMPLETE_PASS and _FAIL as we haven't yet.
        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE_PASS));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and passed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE_FAIL));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and failed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Change the grade to be complete and failed.
        self::set_grade($assignrow, $user->id, 40);

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_INCOMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE_PASS));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and passed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE_FAIL));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is not complete and failed~', $information);

        // Now change it to pass.
        self::set_grade($assignrow, $user->id, 60);

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_INCOMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE_PASS));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is not complete and passed~', $information);

        $cond = new condition((object)array(
                'cm' => (int)$assigncm->id, 'e' => COMPLETION_COMPLETE_FAIL));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and failed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Simulate deletion of an activity by using an invalid cmid. These
        // conditions always fail, regardless of NOT flag or INCOMPLETE.
        $cond = new condition((object)array(
                'cm' => ($assigncm->id + 100), 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~(Missing activity).*is marked complete~', $information);
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $cond = new condition((object)array(
                'cm' => ($assigncm->id + 100), 'e' => COMPLETION_INCOMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
    }

    /**
     * Tests the is_available and get_description functions for previous activity option.
     */
    public function test_previous_activity() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        // Course structure for testing:
        //  1. page1 (manual completion)
        //  2. page2 (manual completion)
        //  3. page ignored (no completion)
        //  4. assignrow (auto completion + grading pass/fail)
        //  5. page3 (manual completion)

        $page1 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page1!',
                'completion' => COMPLETION_TRACKING_MANUAL));

        $page2 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page2!',
                'completion' => COMPLETION_TRACKING_MANUAL));

        $pagenocompletion = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page ignored!'));

        // Create an assignment - we need to have something that can be graded
        // so as to test the PASS/FAIL states. Set it up to be completed based
        // on its grade item.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
                'course' => $course->id, 'name' => 'Assign!',
                'completion' => COMPLETION_TRACKING_AUTOMATIC));
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0,
                array('id' => $assignrow->cmid));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);

        $page3 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page3!',
                'completion' => COMPLETION_TRACKING_MANUAL));

        // Get basic details.
        $modinfo = get_fast_modinfo($course);
        $page1cm = $modinfo->get_cm($page1->cmid);
        $page2cm = $modinfo->get_cm($page2->cmid);
        $assigncm = $assign->get_course_module();
        $page3cm = $modinfo->get_cm($page3->cmid);
        $prevvalue = \availability_completion\condition::$PREVIOUS;

        // PREVIOUS WITH non existent previous activity
        $info = new \core_availability\mock_info_module($user->id, $page1cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Missing activity.*is marked complete~', $information);

        // COMPLETE state on previous to Page2 (false), positive and NOT.
        $info = new \core_availability\mock_info_module($user->id, $page2cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // INCOMPLETE state (true).
        $info = new \core_availability\mock_info_module($user->id, $page2cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_INCOMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is marked complete~', $information);

        // Mark page1 complete.
        $completion = new completion_info($course);
        $completion->update_state($page1cm, COMPLETION_COMPLETE);

        // COMPLETE state (true).
        $info = new \core_availability\mock_info_module($user->id, $page2cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is incomplete~', $information);

        // Mark page2 complete.
        $completion = new completion_info($course);
        $completion->update_state($page2cm, COMPLETION_COMPLETE);

        // Ingoring "Page Ignore" (true).
        $info = new \core_availability\mock_info_module($user->id, $assigncm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page2!.*is marked complete~', $information);

        // We are going to need the grade item so that we can get pass/fails.
        $gradeitem = $assign->get_grade_item();
        grade_object::set_properties($gradeitem, array('gradepass' => 50.0));
        $gradeitem->update();

        // With no grade, it should return true for INCOMPLETE and false for
        // the other three.
        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_INCOMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Check $information for COMPLETE_PASS and _FAIL as we haven't yet.
        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE_PASS));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and passed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE_FAIL));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and failed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Change the grade to be complete and failed.
        self::set_grade($assignrow, $user->id, 40);

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_INCOMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE_PASS));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and passed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE_FAIL));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is not complete and failed~', $information);

        // Now change it to pass.
        self::set_grade($assignrow, $user->id, 60);

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_INCOMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE_PASS));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is not complete and passed~', $information);

        $info = new \core_availability\mock_info_module($user->id, $page3cm);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE_FAIL));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Assign!.*is complete and failed~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
    }

    /**
     * Tests the is_available and get_description functions for
     * previous activity option in course sections.
     */
    public function test_section_previous_activity() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
                array('numsections' => 4, 'enablecompletion' => 1),
                array('createsections' => true));
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        // Course structure for testing:
        // Section 1
        //  1. page1 (manual completion)
        //  2. page ignored 1 (no completion)
        // Section 2
        //  3. page ignored 2 (no completion)
        // Section 3
        //  4. page2 (manual completion)
        // Section 4
        //  -empty-

        $page1 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page1!', 'section' => 1,
                'completion' => COMPLETION_TRACKING_MANUAL));

        $pagenocompletion1 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course, 'name' => 'Page ignored!', 'section' => 1));

        $pagenocompletion2 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course, 'name' => 'Page ignored!', 'section' => 2));

        $page2 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'name' => 'Page2!', 'section' => 3,
                'completion' => COMPLETION_TRACKING_MANUAL));

        // Get basic details.
        get_fast_modinfo(0,0,true);
        $modinfo = get_fast_modinfo($course);
        $section1 = $modinfo->get_section_info(1);
        $section2 = $modinfo->get_section_info(2);
        $section3 = $modinfo->get_section_info(3);
        $section4 = $modinfo->get_section_info(4);
        $page1cm = $modinfo->get_cm($page1->cmid);
        $prevvalue = \availability_completion\condition::$PREVIOUS;

        // Section PREVIOUS WITH non existent previous activity
        $info = new \core_availability\mock_info_section($user->id, $section1);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Missing activity.*is marked complete~', $information);

        // Section COMPLETE state on activity previous to Section 2 (false), positive and NOT.
        $info = new \core_availability\mock_info_section($user->id, $section2);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Section COMPLETE state on activity previous to Section 3 (false), positive and NOT.
        $info = new \core_availability\mock_info_section($user->id, $section3);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Section COMPLETE state on activity previous to Section 4 (false), positive and NOT.
        $info = new \core_availability\mock_info_section($user->id, $section4);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page2!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Mark page1 complete.
        $completion = new completion_info($course);
        $completion->update_state($page1cm, COMPLETION_COMPLETE);

        // Section PREVIOUS WITH non existent previous activity
        $info = new \core_availability\mock_info_section($user->id, $section1);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Missing activity.*is marked complete~', $information);

        // Section COMPLETE state on activity previous to Section 2 (true), positive and NOT.
        $info = new \core_availability\mock_info_section($user->id, $section2);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is marked complete~', $information);
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        // Section COMPLETE state on activity previous to Section 3 (true), positive and NOT.
        $info = new \core_availability\mock_info_section($user->id, $section3);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page1!.*is marked complete~', $information);
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        // Section COMPLETE state on activity previous to Section 4 (false), positive and NOT.
        $info = new \core_availability\mock_info_section($user->id, $section4);
        $cond = new condition((object)array(
                'cm' => (int)$prevvalue, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertRegExp('~Page2!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
    }

    /**
     * Tests completion_value_used static function.
     */
    public function test_completion_value_used() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $prevvalue = \availability_completion\condition::$PREVIOUS;

        // Create course with completion turned on and some sections.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
                array('numsections' => 1, 'enablecompletion' => 1),
                array('createsections' => true));

        // Create six pages with manual completion.
        $page1 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $page2 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $page3 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $page4 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $page5 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $page6 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));

        // Set up page3 to depend on page1, and section1 to depend on page2.
        $DB->set_field('course_modules', 'availability',
                '{"op":"|","show":true,"c":[' .
                '{"type":"completion","e":1,"cm":' . $page1->cmid . '}]}',
                array('id' => $page3->cmid));
        $DB->set_field('course_sections', 'availability',
                '{"op":"|","show":true,"c":[' .
                '{"type":"completion","e":1,"cm":' . $page2->cmid . '}]}',
                array('course' => $course->id, 'section' => 1));
        // Set up page5 and page6 to depend on previous activity.
        $DB->set_field('course_modules', 'availability',
                '{"op":"|","show":true,"c":[' .
                '{"type":"completion","e":1,"cm":' . $prevvalue . '}]}',
                array('id' => $page5->cmid));
        $DB->set_field('course_modules', 'availability',
                '{"op":"|","show":true,"c":[' .
                '{"type":"completion","e":1,"cm":' . $prevvalue . '}]}',
                array('id' => $page6->cmid));

        // Check 1: nothing depends on page3 and page6 but something does on the others.
        $this->assertTrue(availability_completion\condition::completion_value_used(
                $course, $page1->cmid));
        $this->assertTrue(availability_completion\condition::completion_value_used(
                $course, $page2->cmid));
        $this->assertFalse(availability_completion\condition::completion_value_used(
                $course, $page3->cmid));
        $this->assertTrue(availability_completion\condition::completion_value_used(
                $course, $page4->cmid));
        $this->assertTrue(availability_completion\condition::completion_value_used(
                $course, $page5->cmid));
        $this->assertFalse(availability_completion\condition::completion_value_used(
                $course, $page6->cmid));
    }

    /**
     * Updates the grade of a user in the given assign module instance.
     *
     * @param stdClass $assignrow Assignment row from database
     * @param int $userid User id
     * @param float $grade Grade
     */
    protected static function set_grade($assignrow, $userid, $grade) {
        $grades = array();
        $grades[$userid] = (object)array(
                'rawgrade' => $grade, 'userid' => $userid);
        $assignrow->cmidnumber = null;
        assign_grade_item_update($assignrow, $grades);
    }

    /**
     * Tests the update_dependency_id() function.
     */
    public function test_update_dependency_id() {
        $cond = new condition((object)array(
                'cm' => 42, 'e' => COMPLETION_COMPLETE, 'selfid' => 43));
        $this->assertFalse($cond->update_dependency_id('frogs', 42, 540));
        $this->assertFalse($cond->update_dependency_id('course_modules', 12, 34));
        $this->assertTrue($cond->update_dependency_id('course_modules', 42, 456));
        $after = $cond->save();
        $this->assertEquals(456, $after->cm);
        // $this->assertEquals(43, $after->selfid);

        // test selfid updating
        $cond = new condition((object)array(
                'cm' => 42, 'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->update_dependency_id('frogs', 43, 540));
        $this->assertFalse($cond->update_dependency_id('course_modules', 12, 34));
        $after = $cond->save();
        $this->assertEquals(42, $after->cm);
        // $this->assertEquals(456, $after->selfid);

        // test on previous activity
        $cond = new condition((object)array(
                'cm' => \availability_completion\condition::$PREVIOUS,
                'e' => COMPLETION_COMPLETE));
        $this->assertFalse($cond->update_dependency_id('frogs', 43, 80));
        $this->assertFalse($cond->update_dependency_id('course_modules', 12, 34));
        $after = $cond->save();
        $this->assertEquals(\availability_completion\condition::$PREVIOUS, $after->cm);
        // $this->assertEquals(86, $after->selfid);
    }
}
