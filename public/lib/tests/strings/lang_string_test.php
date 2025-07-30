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

namespace core\strings;

/**
 * Tests for core\strings\lang_string class.
 *
 * @package    core
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(lang_string::class)]
final class lang_string_test extends \advanced_testcase {
    /**
     * Test the all stringable methods.
     */
    public function test_stringable(): void {
        $strings = \core\di::get(\core\strings::class);

        $yesexpected = $strings->get('yes');

        $yes = new lang_string('yes', null, null);
        $this->assertSame($yesexpected, (string) $yes);
        $this->assertSame($yesexpected, $yes->out());

        // Test lazy loading (returning lang_string) correctly interpolates 0 being used as args.
        $numdays = new lang_string('numdays', 'moodle', 0);
        $this->assertSame('0 days', (string) $numdays);

        // Test using a lang_string object as the $a argument for a normal
        // get_string call (returning string).
        $test = new lang_string('yes');
        $testexpected = $strings->get('numdays', 'core', $yesexpected);
        $testresult = $strings->get('numdays', null, $test);
        $this->assertIsString($testresult);
        $this->assertSame($testexpected, $testresult);

        // Test using a lang_string object as the $a argument for an object
        // get_string call (returning lang_string).
        $test = new lang_string('yes', null, null, true);
        $testexpected = $strings->get('numdays', 'core', $yesexpected);
        $testresult = new lang_string('numdays', null, $test);
        $this->assertSame($testexpected, "$testresult");

        // Make sure that object properties that can't be converted don't cause errors.
        // Level two: Language processing doesn't currently reach this deep.
        // only immediate scalar properties are worked with.
        $test = new \stdClass();
        $test->one = new \stdClass();
        $test->one->two = 'here';
        $string = new lang_string('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Make sure that object properties that can't be converted don't cause
        // errors.
        // Level three: It should never ever go this deep, but we're making sure
        // it doesn't cause any probs anyway.
        $test = new \stdClass();
        $test->one = new \stdClass();
        $test->one->two = new \stdClass();
        $test->one->two->three = 'here';
        $string = new lang_string('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Make sure that object properties that can't be converted don't cause
        // errors and check lang_string properties.
        // Level one: This is as deep as current language processing goes.
        $test = new \stdClass();
        $test->one = new lang_string('yes');
        $string = new lang_string('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Make sure that object properties that can't be converted don't cause
        // errors and check lang_string properties.
        // Level two: Language processing doesn't currently reach this deep.
        // only immediate scalar properties are worked with.
        $test = new \stdClass();
        $test->one = new \stdClass();
        $test->one->two = new lang_string('yes');
        $string = new lang_string('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Make sure that object properties that can't be converted don't cause
        // errors and check lang_string properties.
        // Level three: It should never ever go this deep, but we're making sure
        // it doesn't cause any probs anyway.
        $test = new \stdClass();
        $test->one = new \stdClass();
        $test->one->two = new \stdClass();
        $test->one->two->three = new lang_string('yes');
        $string = new lang_string('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Make sure that array properties that can't be converted don't cause
        // errors.
        $test = [];
        $test['one'] = new \stdClass();
        $test['one']->two = 'here';
        $string = new lang_string('yes', null, $test);
        $this->assertEquals($yesexpected, $string);

        // Same thing but as above except using an object... this is allowed :P.
        $string = new lang_string('yes', null, null);
        $object = new \stdClass();
        $object->$string = 'Yes';
        $this->assertEquals($yesexpected, $string);
        $this->assertEquals($yesexpected, $object->$string);
    }

    /**
     * Test the var_export() method.
     */
    public function test_lang_string_var_export(): void {

        // Call var_export() on a newly generated lang_string.
        $str = new lang_string('no');

        // In PHP 8.2 exported class names are now fully qualified;
        // previously, the leading backslash was omitted.
        $leadingbackslash = (version_compare(PHP_VERSION, '8.2.0', '>=')) ? '\\' : '';

        $expected1 = <<<EOF
{$leadingbackslash}core\strings\lang_string::__set_state(array(
   'component' => 'moodle',
   'a' => NULL,
   'string' => NULL,
   'forcedstring' => false,
   'identifier' => 'no',
   'lang' => NULL,
))
EOF;

        $v = var_export($str, true);
        $this->assertEquals($expected1, $v);

        // Now execute the code that was returned - it should produce a correct string.
        $str = lang_string::__set_state(
            [
                'identifier' => 'no',
                'component' => 'moodle',
                'a' => null,
                'lang' => null,
                'string' => null,
                'forcedstring' => false,
            ],
        );

        $this->assertInstanceOf(lang_string::class, $str);
        $this->assertEquals('No', $str);
    }
}
