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

namespace core_ai\local;

use aiplacement_editor\tests\aiactions\custom_action;
use core_ai\aiactions\generate_text;

/**
 * Tests AI action name upgrade helpers.
 *
 * @package    core_ai
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(upgrader::class)]
final class upgrader_test extends \advanced_testcase {
    /**
     * Test migration of registered actions, placement settings, and course module settings.
     */
    public function test_migrate_action_names(): void {
        global $DB;

        $this->resetAfterTest();
        $clock = $this->mock_clock_with_frozen();
        $user = self::getDataGenerator()->create_user();

        $DB->insert_record('ai_action_register', (object) [
            'actionname' => 'generate_text',
            'actionid' => 741,
            'success' => 0,
            'userid' => $user->id,
            'contextid' => \context_system::instance()->id,
            'provider' => 'aiprovider_openai',
            'timecreated' => $clock->time(),
        ]);
        $DB->insert_record('ai_action_register', (object) [
            'actionname' => 'custom_action',
            'actionid' => 742,
            'success' => 0,
            'userid' => $user->id,
            'contextid' => \context_system::instance()->id,
            'provider' => 'aiprovider_openai',
            'timecreated' => $clock->time(),
        ]);

        set_config('generate_text', 1, 'aiplacement_editor');
        set_config('custom_action', 1, 'aiplacement_editor');
        set_config('enabledactions', json_encode([generate_text::class => 0]), 'aiplacement_editor');

        $course = self::getDataGenerator()->create_course();
        $module = self::getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $DB->set_field(
            'course_modules',
            'enabledaiactions',
            json_encode(['generate_text' => 1]),
            ['id' => $module->cmid],
        );

        upgrader::migrate_action_names([
            'aiprovider_openai' => [custom_action::class],
            'aiplacement_editor' => [custom_action::class, generate_text::class],
        ]);

        $this->assertSame(
            generate_text::class,
            $DB->get_field('ai_action_register', 'actionname', ['actionid' => 741]),
        );
        $this->assertSame(
            custom_action::class,
            $DB->get_field('ai_action_register', 'actionname', ['actionid' => 742]),
        );

        $this->assertSame(
            [
                generate_text::class => 0,
                custom_action::class => 1,
            ],
            json_decode(get_config('aiplacement_editor', 'enabledactions'), true),
        );
        $this->assertFalse(get_config('aiplacement_editor', 'generate_text'));
        $this->assertFalse(get_config('aiplacement_editor', 'custom_action'));

        $this->assertSame(
            [generate_text::class => 1],
            json_decode($DB->get_field('course_modules', 'enabledaiactions', ['id' => $module->cmid]), true),
        );
    }
}
