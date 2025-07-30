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

namespace core;

use core\strings\lang_string;

/**
 * Tests for core\strings class.
 *
 * @package    core
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(strings::class)]
final class strings_test extends \advanced_testcase {
    /**
     * Test the get method.
     */
    public function test_get_string(): void {
        global $COURSE;

        // Make sure we are using English.
        $originallang = $COURSE->lang;
        $COURSE->lang = 'en';

        $strings = new strings(\core\di::get(\core\strings\string_manager::class));

        $yes = $strings->get('yes');
        $yesexpected = 'Yes';
        $this->assertIsString($yes);
        $this->assertSame($yesexpected, $yes);

        $yes = $strings->get('yes', 'moodle');
        $this->assertIsString($yes);
        $this->assertSame($yesexpected, $yes);

        $yes = $strings->get('yes', 'core');
        $this->assertIsString($yes);
        $this->assertSame($yesexpected, $yes);

        $yes = $strings->get('yes', '');
        $this->assertIsString($yes);
        $this->assertSame($yesexpected, $yes);

        $yes = $strings->get('yes', null);
        $this->assertIsString($yes);
        $this->assertSame($yesexpected, $yes);

        $yes = $strings->get('yes', null, 1);
        $this->assertIsString($yes);
        $this->assertSame($yesexpected, $yes);

        $days = 1;
        $numdays = $strings->get('numdays', 'core', '1');
        $numdaysexpected = $days . ' days';
        $this->assertIsString($numdays);
        $this->assertSame($numdaysexpected, $numdays);

        // Make sure that object properties that can't be converted don't cause
        // errors.
        // Level one: This is as deep as current language processing goes.
        $test = new \stdClass();
        $test->one = 'here';
        $string = $strings->get('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Reset the language.
        $COURSE->lang = $originallang;
    }

    /**
     * Test the get_lazy method.
     */
    public function test_get_lazy(): void {
        $strings = new strings(\core\di::get(\core\strings\string_manager::class));

        $yesexpected = 'Yes';

        $yes = $strings->get_lazy('yes', null, null);
        $this->assertInstanceOf('lang_string', $yes);
        $this->assertSame($yesexpected, (string) $yes);

        // Test lazy loading (returning lang_string) correctly interpolates 0 being used as args.
        $numdays = $strings->get_lazy('numdays', 'moodle', 0);
        $this->assertInstanceOf(lang_string::class, $numdays);
        $this->assertSame('0 days', (string) $numdays);

        // Test using a lang_string object as the $a argument for a normal
        // get_string call (returning string).
        $test = $strings->get_lazy('yes');
        $testexpected = $strings->get('numdays', 'core', $strings->get('yes'));
        $testresult = $strings->get('numdays', null, $test);
        $this->assertIsString($testresult);
        $this->assertSame($testexpected, $testresult);

        // Test using a lang_string object as the $a argument for an object
        // get_string call (returning lang_string).
        $test = new lang_string('yes', null, null, true);
        $testexpected = $strings->get('numdays', 'core', $strings->get('yes'));
        $testresult = $strings->get_lazy('numdays', null, $test);
        $this->assertInstanceOf('lang_string', $testresult);
        $this->assertSame($testexpected, "$testresult");
    }

    /**
     * Test the get_exists method.
     */
    public function test_exists(): void {
        $strings = new strings(\core\di::get(\core\strings\string_manager::class));

        $this->assertTrue($strings->exists('yes'));
        $this->assertTrue($strings->exists('yes', 'moodle'));
        $this->assertTrue($strings->exists('yes', 'core'));
        $this->assertTrue($strings->exists('yes', ''));
        $this->assertTrue($strings->exists('yes', null));

        $this->assertFalse($strings->exists('nonexistentstring'));
        $this->assertFalse($strings->exists('nonexistentstring', 'moodle'));
        $this->assertFalse($strings->exists('nonexistentstring', 'core'));
        $this->assertFalse($strings->exists('nonexistentstring', ''));
        $this->assertFalse($strings->exists('nonexistentstring', null));
    }

    /**
     * Test the is_string_deprecated method.
     */
    public function test_is_string_deprecated(): void {
        $strings = new strings(\core\di::get(\core\strings\string_manager::class));

        // Check non-deprecated string.
        $this->assertFalse($strings->is_string_deprecated('hidden', 'grades'));

        // Check deprecated string.
        $this->assertTrue($strings->is_string_deprecated('importantupdates_title', 'core_admin'));
        $this->assertDebuggingNotCalled();
    }
}
