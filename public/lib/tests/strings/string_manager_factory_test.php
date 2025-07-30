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

use core\strings\installation_string_manager;
use core\strings\string_manager;
use core\tests\strings\mocking_string_manager;
use core\tests\strings\legacy_string_manager;

/**
 * Tests for core\strings\string_manager_factory class.
 *
 * @package    core
 * @category   test
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(string_manager_factory::class)]
final class string_manager_factory_test extends \advanced_testcase {
    /**
     * Set a custom string manager class in the configuration.
     *
     * @param string $classname The fully qualified class name of the custom string manager.
     */
    private function set_custom_string_manager(string $classname): void {
        global $CFG;
        $CFG->config_php_settings['customstringmanager'] = $classname;
    }

    public function test_create(): void {
        $manager = string_manager_factory::create();
        $this->assertInstanceOf(string_manager::class, $manager);
    }

    public function test_create_early_install(): void {
        $this->resetAfterTest();
        set_config('early_install_lang', 'en');
        $manager = string_manager_factory::create();
        $this->assertInstanceOf(installation_string_manager::class, $manager);
    }

    public function test_create_valid_classname_set(): void {
        $this->resetAfterTest();
        $this->set_custom_string_manager(mocking_string_manager::class);
        $manager = string_manager_factory::create();
        $this->assertInstanceOf(mocking_string_manager::class, $manager);
    }

    public function test_create_returns_legacy_manager_on_type_error(): void {
        $this->resetAfterTest();
        $this->set_custom_string_manager(legacy_string_manager::class);
        $manager = string_manager_factory::create();
        $this->assertInstanceOf(legacy_string_manager::class, $manager);
        $this->assertDebuggingCalled(
            "Passing parameters to the string manager constructor is deprecated," .
            " please update the class " . legacy_string_manager::class . " to use the new signature.",
        );
    }

    public function test_create_debugs_when_class_does_not_implement_interface(): void {
        $this->resetAfterTest();
        $this->set_custom_string_manager(\stdClass::class);
        string_manager_factory::create();
        $this->assertDebuggingCalled(
            "Unable to instantiate custom string manager: " .
            "class " . \stdClass::class . " does not implement the core\\strings\\string_manager interface.",
        );
    }

    public function test_create_debugs_when_class_not_found(): void {
        $this->resetAfterTest();
        $this->set_custom_string_manager('non_existent_class');
        string_manager_factory::create();
        $this->assertDebuggingCalled(
            "Unable to instantiate custom string manager: class non_existent_class can not be found."
        );
    }
}
