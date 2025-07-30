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
 * Load all DI configuration for the Strings system.
 *
 * This callback is called with priority 999 to ensure it runs after any other to
 * ensure third-party plugins can override the string manager if needed.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Provide DI Configuration for the language string system.
     *
     * @param \core\hook\di_configuration $hook
     * @codeCoverageIgnore
     */
    public static function provide_di_configuration(
        \core\hook\di_configuration $hook,
    ): void {
        global $CFG;

        // The require_once below ensure compatibility with legacy class namespaces
        // (prior to the introduction of \core\strings\). The old names are not deprecated yet,
        // so they are not autoloaded. Once they are added to the renamed classes list in Moodle 6.0,
        // these require_once statements can be safely removed.
        // TODO Remove in 6.0 (MDL-86180).
        require_once("{$CFG->libdir}/classes/strings/string_manager.php");
        require_once("{$CFG->libdir}/classes/strings/installation_string_manager.php");
        require_once("{$CFG->libdir}/classes/strings/standard_string_manager.php");

        // The original definition of the string manager (now redirected).
        $hook->add_definition(
            \core_string_manager::class,
            \DI\get(\core\strings\string_manager::class),
        );

        $hook->add_definition(
            \core\strings\string_manager::class,
            \DI\get(\core\strings\standard_string_manager::class),
        );

        // TODO Remove this if in 6.0 (MDL-86180).
        if (!empty($CFG->config_php_settings['customstringmanager'])) {
            $hook->add_definition(
                \core\strings\string_manager::class,
                fn() => hook_callbacks::get_legacy_string_manager_instance(),
            );
        }

        if (!empty($CFG->early_install_lang)) {
            $hook->add_definition(
                \core\strings\string_manager::class,
                \DI\get(\core\strings\installation_string_manager::class),
            );
        }
    }

    /**
     * Get an instance of the legacy string manager based on the current configuration.
     *
     * @todo remove in 6.0 (MDL-86180).
     * @return \core\strings\string_manager
     */
    public static function get_legacy_string_manager_instance(): \core\strings\string_manager {
        global $CFG;

        $langconfighelper = new \core\strings\lang_config_helper();
        $translations = $langconfighelper->get_translations();
        $classname = $CFG->config_php_settings['customstringmanager'];

        if (!class_exists($classname)) {
            throw new \coding_exception('The class configured in $CFG->customstringmanager does not exist: ' . $classname);
        }

        return new $classname(
            $langconfighelper->get_langpacks_path(),
            $langconfighelper->get_local_langpacks_path(),
            $translations['translations'],
            $translations['aliases'],
        );
    }
}
