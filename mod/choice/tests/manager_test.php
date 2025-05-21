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

namespace mod_choice;

/**
 * Generator tests class.
 *
 * @package    mod_choice
 * @copyright  2025 Laurent David <laurent.david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_choice\manager
 */
final class manager_test extends \advanced_testcase {

    /**
     * Data provider for test_get_all_answers_count.
     *
     * @return array
     */
    public static function get_all_answers_provider(): array {
        return [
            'teacher 1 (no group mode)' => [
                't1',
                NOGROUPS,
                ['s1' => ['A'], 's2' => ['A']],
            ],
            'teacher 1 (separate group mode)' => [
                't1',
                SEPARATEGROUPS,
                ['s1' => ['A']],
            ],
            'teacher 1 (visible group mode)' => [
                't1',
                VISIBLEGROUPS,
                ['s1' => ['A'], 's2' => ['A']],
            ],
            // Teacher 2 does not belong to any group.
            'teacher 2 (no group mode)' => [
                't2',
                NOGROUPS,
                ['s1' => ['A'], 's2' => ['A']],
            ],
            'teacher 2 (separate group mode)' => [
                't2',
                SEPARATEGROUPS,
                [],
            ],
            'teacher 2 (visible group mode)' => [
                't2',
                VISIBLEGROUPS,
                ['s1' => ['A'], 's2' => ['A']],
            ],
        ];
    }

    /**
     * Data provider for test_get_all_answers_count.
     *
     * @return array
     */
    public static function get_all_answers_count_provider(): array {
        return [
            'teacher 1 (no group mode)' => ['t1', NOGROUPS, 2],
            'teacher 1 (separate group mode)' => ['t1', SEPARATEGROUPS, 1],
            'teacher 1 (visible group mode)' => ['t1', VISIBLEGROUPS, 2],
            // Teacher 2 does not belong to any group.
            'teacher 2 (no group mode)' => ['t2', NOGROUPS, 2],
            'teacher 2 (separate group mode)' => ['t2', SEPARATEGROUPS, 0],
            'teacher 2 (visible group mode)' => ['t2', VISIBLEGROUPS, 2],
        ];
    }

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test creating a manager instance from an instance record.
     *
     * @covers \mod_choice\manager::create_from_instance
     */
    public function test_create_manager_instance_from_instance_record(): void {
        $this->resetAfterTest();
        ['instance' => $instance] = $this->setup_users_and_activity();
        $manager = \mod_choice\manager::create_from_instance($instance);
        $this->assertNotNull($manager);
    }

    /**
     * Setup users and activity for testing answers retrieval.
     *
     * @param int $groupmode the group mode to use for the course.
     * @return array indexed array with 'users', 'course' and  'instance'.
     */
    private function setup_users_and_activity(int $groupmode = NOGROUPS): array {
        $db = \core\di::get(\moodle_database::class);
        $users = [];
        $generator = $this->getDataGenerator();
        $courseparams = [];
        if ($groupmode !== NOGROUPS) {
            // Set the group mode for the course.
            $courseparams['groupmode'] = $groupmode;
            $courseparams['groupmodeforce'] = 1; // Force the group mode.
        }
        $course = $generator->create_course($courseparams);
        foreach (['s1' => 'student', 's2' => 'student', 't1' => 'teacher', 't2' => 'teacher'] as $username => $role) {
            $users[$username] = $generator->create_and_enrol($course, $role, ['username' => $username]);
        }

        $groups = [];
        if ($groupmode !== NOGROUPS) {
            // Create a group if the group mode is not NOGROUPS.
            $groups[] = $generator->create_group(['courseid' => $course->id]);
            $groups[] = $generator->create_group(['courseid' => $course->id]);
            groups_add_member($groups[0], $users['s1']->id);
            groups_add_member($groups[1], $users['s2']->id);
            groups_add_member($groups[0], $users['t1']->id);
        }
        $instance = $generator->create_module('choice', [
            'course' => $course,
            'option' => ['A', 'B', 'C'],
        ]);

        $choicesid = $db->get_fieldset('choice_options', 'id', ['choiceid' => $instance->id]);
        $currentchoiceid = array_shift($choicesid); // Get the first choice.
        $generator->get_plugin_generator('mod_choice')->create_response([
            'choiceid' => $instance->id,
            'responses' => $currentchoiceid,
            'userid' => $users['s1']->id,
        ]);
        $generator->get_plugin_generator('mod_choice')->create_response([
            'choiceid' => $instance->id,
            'responses' => $currentchoiceid,
            'userid' => $users['s2']->id,
        ]);
        return [
            'users' => $users,
            'course' => $course,
            'instance' => $instance,
        ];
    }

    /**
     * Test creating a manager instance from a course module.
     *
     * @covers \mod_choice\manager::create_from_coursemodule
     */
    public function test_create_manager_instance_from_coursemodule(): void {
        $this->resetAfterTest();
        ['instance' => $instance, 'course' => $course] = $this->setup_users_and_activity();
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        $manager = \mod_choice\manager::create_from_coursemodule($cm);
        $this->assertNotNull($manager);
    }

    /**
     * Test retrieving answers for a specific user.
     *
     * @param string $username the username of the user to retrieve answers for.
     * @param int $coursegroupmode the group mode of the course.
     * @param array $expected the expected answers for the user with keys as usernames and values as arrays of answers.
     * @covers       \mod_choice\manager::get_all_answers
     * @dataProvider get_all_answers_provider
     */
    public function test_get_all_answers(string $username, int $coursegroupmode, array $expected): void {
        ['users' => $users, 'instance' => $instance] = $this->setup_users_and_activity($coursegroupmode);
        $manager = \mod_choice\manager::create_from_instance($instance);
        $answers = $manager->get_all_answers($users[$username]->id);

        $this->assertCount(count($expected), $answers);
        $answers = array_values($answers); // Reset keys to ensure we can access by index.
        $db = \core\di::get(\moodle_database::class);

        $choices = $db->get_records_menu('choice_options', ['choiceid' => $instance->id], 'id', 'id, text');
        $choices = array_flip($choices); // Flip to get text by id.

        foreach ($expected as $username => $expectedanswers) {
            $expectedanswerids = array_map(
                fn($answer) => $choices[$answer] ?? null,
                $expectedanswers
            );
            $userid = $users[$username]->id;
            $answersfromuser = array_filter($answers, fn($answer) => $answer->userid === $userid);
            $answersidfromuser = array_map(fn($answer) => $answer->optionid, $answersfromuser);

            sort($answersidfromuser);
            sort($expectedanswerids);
            $this->assertEquals(
                $expectedanswerids,
                $answersidfromuser,
                "Answers for user '$username' do not match expected answers."
            );
        }
    }

    /**
     * Test retrieving answers count for all users.
     *
     * @param string $username the username of the user to retrieve answers count for.
     * @param int $coursegroupmode the group mode of the course.
     * @param int $expectedcount the expected count of answers for the user.
     *
     * @covers       \mod_choice\manager::get_all_answers_count
     * @dataProvider get_all_answers_count_provider
     */
    public function test_get_all_answers_count(string $username, int $coursegroupmode, int $expectedcount): void {
        ['users' => $users, 'instance' => $instance] = $this->setup_users_and_activity($coursegroupmode);
        $manager = \mod_choice\manager::create_from_instance($instance);
        $count = $manager->get_all_answers_count($users[$username]->id);
        $this->assertEquals($expectedcount, $count);
    }

    /**
     * Test retrieving answers count for a specific users.
     *
     * @covers \mod_choice\manager::get_user_answers
     */
    public function test_get_user_answers(): void {
        ['users' => $users, 'instance' => $instance] = $this->setup_users_and_activity();
        $manager = \mod_choice\manager::create_from_instance($instance);
        $answers = $manager->get_user_answers($users['s1']->id);
        $this->assertCount(1, $answers);
        $answers = array_values($answers); // Reset keys to ensure we can access by index.
        $this->assertEquals($users['s1']->id, $answers[0]->userid);
    }

    /**
     * Test retrieving answers count for a specific users.
     *
     * @covers \mod_choice\manager::get_user_answers_count
     */
    public function test_get_user_answers_count(): void {
        ['users' => $users, 'instance' => $instance] = $this->setup_users_and_activity();
        $manager = \mod_choice\manager::create_from_instance($instance);
        $count = $manager->get_user_answers_count($users['s1']->id);
        $this->assertEquals(1, $count);
    }
}
