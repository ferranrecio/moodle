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
 * External function test for get_h5pactivity_report_attempts.
 *
 * @package    mod_h5pactivity
 * @category   external
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_h5pactivity\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

use mod_h5pactivity\local\manager;
use dml_missing_record_exception;
use moodle_exception;
use external_api;
use externallib_advanced_testcase;

/**
 * External function test for get_h5pactivity_report_attempts.
 *
 * @package    mod_h5pactivity
 * @copyright  2020 Carlos Escobedo <carlos@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_h5pactivity_report_attempts_testcase extends externallib_advanced_testcase {

    /**
     * Test the behaviour of get_h5pactivity_report_attempts.
     *
     * @dataProvider execute_data
     * @param int $grademethod the activity grading method
     * @param string $loginuser the user which calls the webservice
     * @param string|null $participant the user to get the data
     * @param bool $createattempts if the student user has attempts created
     * @param int|null $count the expected number of attempts returned (null for exception)
     * @return type
     */
    public function test_execute(int $grademethod, string $loginuser, ?string $participant,
            bool $createattempts, ?int $count): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity',
                ['course' => $course, 'enabletracking' => 1, 'grademethod' => $grademethod]);

        $manager = manager::create_from_instance($activity);
        $cm = $manager->get_coursemodule();

        // Prepare users: 1 teacher, 3 students, 1 unenroled user.
        $users = [
            'editingteacher' => $this->getDataGenerator()->create_and_enrol($course, 'editingteacher'),
            'student' => $this->getDataGenerator()->create_and_enrol($course, 'student'),
            'other' => $this->getDataGenerator()->create_and_enrol($course, 'student'),
            'noenrolled' => $this->getDataGenerator()->create_user(),
        ];

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_h5pactivity');

        if ($createattempts) {
            $user = $users['student'];
            $params = ['cmid' => $cm->id, 'userid' => $user->id];
            $generator->create_content($activity, $params);
            $generator->create_content($activity, $params);
        }

        // Create another user with 2 attempts to validate no cross attempts are returned.
        $user = $users['other'];
        $params = ['cmid' => $cm->id, 'userid' => $user->id];
        $generator->create_content($activity, $params);
        $generator->create_content($activity, $params);

        if ($count === null) {
            $this->expectException(moodle_exception::class);
        }

        // Execute external method.
        $this->setUser($users[$loginuser]);
        $userid = ($participant) ? $users[$participant]->id : null;

        $result = get_h5pactivity_report_attempts::execute($activity->id, $userid);
        $result = external_api::clean_returnvalue(
            get_h5pactivity_report_attempts::execute_returns(),
            $result
        );

        // Validate scored attempts.
        if ($grademethod == manager::GRADEMANUAL || $grademethod == manager::GRADEAVERAGEATTEMPT || $count == 0) {
            $this->assertArrayNotHasKey('scored', $result);
        } else {
            $this->assertArrayHasKey('scored', $result);
            list($dbgrademethod, $title) = $manager->get_selected_attempt();
            $this->assertEquals($grademethod, $dbgrademethod);
            $this->assertEquals($grademethod, $result['scored']['grademethod']);
            $this->assertEquals($title, $result['scored']['title']);
            $this->assertCount(1, $result['scored']['attempts']);
        }

        // Validate returned attempts.
        $this->assertCount($count, $result['attempts']);
        $checkuserid = ($participant) ? $users[$participant]->id : $users[$loginuser]->id;
        foreach ($result['attempts'] as $attempt) {
            $this->assertArrayHasKey('id', $attempt);
            $this->assertEquals($checkuserid, $attempt['userid']);
            $this->assertArrayHasKey('timecreated', $attempt);
            $this->assertArrayHasKey('timemodified', $attempt);
            $this->assertArrayHasKey('attempt', $attempt);
            $this->assertArrayHasKey('rawscore', $attempt);
            $this->assertArrayHasKey('maxscore', $attempt);
            $this->assertArrayHasKey('duration', $attempt);
            $this->assertArrayHasKey('completion', $attempt);
            $this->assertArrayHasKey('success', $attempt);
            $this->assertArrayHasKey('scaled', $attempt);
        }
    }

    /**
     * Data provider for the test_execute tests.
     *
     * @return  array
     */
    public function execute_data(): array {
        return [
            // Teacher checking a user with attempts.
            'Manual grade, Teacher asking participant with attempts' => [
                manager::GRADEMANUAL, 'editingteacher', 'student', true, 2
            ],
            'Highest grade, Teacher asking participant with attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'editingteacher', 'student', true, 2
            ],
            'Average grade, Teacher asking participant with attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'editingteacher', 'student', true, 2
            ],
            'Last grade, Teacher asking participant with attempts' => [
                manager::GRADELASTATTEMPT, 'editingteacher', 'student', true, 2
            ],
            'First grade, Teacher asking participant with attempts' => [
                manager::GRADEFIRSTATTEMPT, 'editingteacher', 'student', true, 2
            ],
            // Teacher checking a user without attempts.
            'Manual grade, Teacher asking participant without attempts' => [
                manager::GRADEMANUAL, 'editingteacher', 'student', false, 0
            ],
            'Highest grade, Teacher asking participant without attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'editingteacher', 'student', false, 0
            ],
            'Average grade, Teacher asking participant without attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'editingteacher', 'student', false, 0
            ],
            'Last grade, Teacher asking participant without attempts' => [
                manager::GRADELASTATTEMPT, 'editingteacher', 'student', false, 0
            ],
            'First grade, Teacher asking participant without attempts' => [
                manager::GRADEFIRSTATTEMPT, 'editingteacher', 'student', false, 0
            ],
            // Student checking own attempts specifying userid.
            'Manual grade, check same user attempts report with attempts' => [
                manager::GRADEMANUAL, 'student', 'student', true, 2
            ],
            'Highest grade, check same user attempts report with attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'student', 'student', true, 2
            ],
            'Average grade, check same user attempts report with attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'student', 'student', true, 2
            ],
            'Last grade, check same user attempts report with attempts' => [
                manager::GRADELASTATTEMPT, 'student', 'student', true, 2
            ],
            'First grade, check same user attempts report with attempts' => [
                manager::GRADEFIRSTATTEMPT, 'student', 'student', true, 2
            ],
            // Student checking own attempts.
            'Manual grade, check own attempts report with attempts' => [
                manager::GRADEMANUAL, 'student', null, true, 2
            ],
            'Highest grade, check own attempts report with attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'student', null, true, 2
            ],
            'Average grade, check own attempts report with attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'student', null, true, 2
            ],
            'Last grade, check own attempts report with attempts' => [
                manager::GRADELASTATTEMPT, 'student', null, true, 2
            ],
            'First grade, check own attempts report with attempts' => [
                manager::GRADEFIRSTATTEMPT, 'student', null, true, 2
            ],
            // Student checking own report without attempts.
            'Manual grade, check own attempts report without attempts' => [
                manager::GRADEMANUAL, 'student', 'student', false, 0
            ],
            'Highest grade, check own attempts report without attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'student', 'student', false, 0
            ],
            'Average grade, check own attempts report without attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'student', 'student', false, 0
            ],
            'Last grade, check own attempts report without attempts' => [
                manager::GRADELASTATTEMPT, 'student', 'student', false, 0
            ],
            'First grade, check own attempts report without attempts' => [
                manager::GRADEFIRSTATTEMPT, 'student', 'student', false, 0
            ],
            // Student trying to get another user attempts.
            'Manual grade, student trying to stalk another student' => [
                manager::GRADEMANUAL, 'student', 'other', false, null
            ],
            'Highest grade,  student trying to stalk another student' => [
                manager::GRADEHIGHESTATTEMPT, 'student', 'other', false, null
            ],
            'Average grade,  student trying to stalk another student' => [
                manager::GRADEAVERAGEATTEMPT, 'student', 'other', false, null
            ],
            'Last grade,  student trying to stalk another student' => [
                manager::GRADELASTATTEMPT, 'student', 'other', false, null
            ],
            'First grade,  student trying to stalk another student' => [
                manager::GRADEFIRSTATTEMPT, 'student', 'other', false, null
            ],
            // Teacher trying to get a non enroled user attempts.
            'Manual grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEMANUAL, 'teacher', 'noenrolled', false, null
            ],
            'Highest grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'teacher', 'noenrolled', false, null
            ],
            'Average grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'teacher', 'noenrolled', false, null
            ],
            'Last grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADELASTATTEMPT, 'teacher', 'noenrolled', false, null
            ],
            'First grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEFIRSTATTEMPT, 'teacher', 'noenrolled', false, null
            ],
            // Student trying to get a non enroled user attempts.
            'Manual grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEMANUAL, 'student', 'noenrolled', false, null
            ],
            'Highest grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEHIGHESTATTEMPT, 'student', 'noenrolled', false, null
            ],
            'Average grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEAVERAGEATTEMPT, 'student', 'noenrolled', false, null
            ],
            'Last grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADELASTATTEMPT, 'student', 'noenrolled', false, null
            ],
            'First grade, teacher trying to get an non enrolled user attempts' => [
                manager::GRADEFIRSTATTEMPT, 'student', 'noenrolled', false, null
            ],
        ];
    }

    /**
     * Test the behaviour of get_h5pactivity_report_attempts when no tracking is enabled.
     *
     */
    public function test_execute_no_tracking(): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity',
                ['course' => $course, 'enabletracking' => 0]);

        $manager = manager::create_from_instance($activity);
        $cm = $manager->get_coursemodule();

        // Prepare users: 1 teacher, 3 students, 1 unenroled user.
        $users = [
            'editingteacher' => $this->getDataGenerator()->create_and_enrol($course, 'editingteacher'),
            'student' => $this->getDataGenerator()->create_and_enrol($course, 'student'),
        ];


        // Execute external method.
        $this->setUser($users['editingteacher']);

        $this->expectException(moodle_exception::class);

        $result = get_h5pactivity_report_attempts::execute($activity->id, $users['student']->id);
        $result = external_api::clean_returnvalue(
            get_h5pactivity_report_attempts::execute_returns(),
            $result
        );
    }

    /**
     * Test the behaviour of get_h5pactivity_report_attempts when no own review allowed.
     *
     */
    public function test_execute_no_own_review(): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity',
                ['course' => $course, 'enabletracking' => 1, 'reviewmode' => manager::REVIEWNONE]);

        $manager = manager::create_from_instance($activity);
        $cm = $manager->get_coursemodule();

        // Prepare users: 1 teacher, 3 students, 1 unenroled user.
        $users = [
            'student' => $this->getDataGenerator()->create_and_enrol($course, 'student'),
        ];


        // Execute external method.
        $this->setUser($users['student']);

        $this->expectException(moodle_exception::class);

        $result = get_h5pactivity_report_attempts::execute($activity->id);
        $result = external_api::clean_returnvalue(
            get_h5pactivity_report_attempts::execute_returns(),
            $result
        );
    }
}
