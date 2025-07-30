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
 * Class to create a string manager instance.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class string_manager_factory {
    /**
     * Create a string manager instance.
     *
     * This method will return an instance of the string manager based on the
     * configuration settings.
     *
     * @return string_manager
     */
    public static function create(): string_manager {
        global $CFG;

        // The require_once below ensure compatibility with legacy class namespaces
        // (prior to the introduction of \core\strings\). The old names are not deprecated yet,
        // so they are not autoloaded. Once they are added to the renamed classes list in Moodle 6.0,
        // these require_once statements can be safely removed.
        // TODO Remove in 6.0 (MDL-86180).
        require_once("{$CFG->libdir}/classes/strings/string_manager.php");
        require_once("{$CFG->libdir}/classes/strings/installation_string_manager.php");
        require_once("{$CFG->libdir}/classes/strings/standard_string_manager.php");

        if (!empty($CFG->early_install_lang)) {
            return new installation_string_manager();
        }

        if (!empty($CFG->config_php_settings['customstringmanager'])) {
            $classname = (string) $CFG->config_php_settings['customstringmanager'];

            if (class_exists($classname)) {
                if (is_a($classname, string_manager::class, true)) {
                    // Previous to 5.1 the string manager construct params were not completely fixed.
                    // We try to create with the new signature, but if it fails we try the old one.
                    // This validation will be removed in 6.0, and will final deprecated in 7.0.
                    try {
                        return new $classname();
                    } catch (\TypeError $e) {
                        return self::create_legacy_manager($classname);
                    }
                }

                debugging(
                    "Unable to instantiate custom string manager: " .
                    "class {$classname} does not implement the core\\strings\\string_manager interface.",
                );
            } else {
                debugging("Unable to instantiate custom string manager: class {$classname} can not be found.");
            }
        }

        return new standard_string_manager();
    }

    /**
     * Create a legacy string manager instance using the old constructor signature.
     *
     * For now, using the old constructor signature will show a debugging message
     * indicating that the class should be updated to use the new signature.
     *
     * @todo Remove that method in 6.0 (MDL-86180).
     *
     * @param string $classname The class name of the string manager to create
     * @return string_manager
     */
    private static function create_legacy_manager(string $classname): string_manager {
        global $CFG;

        debugging(
            "Passing parameters to the string manager constructor is deprecated," .
            " please update the class {$classname} to use the new signature.",
        );

        // Code based on the protected standard_string_manager::get_translations_from_config.
        $aliases = [];
        $translations = [];
        if (!empty($CFG->langlist)) {
            $translations = explode(',', $CFG->langlist);
            $translations = array_map('trim', $translations);
            // Each language in the $CFG->langlist can has an "alias" that would substitute the default language name.
            foreach ($translations as $i => $value) {
                $parts = preg_split('/\s*\|\s*/', $value, 2);
                if (count($parts) == 2) {
                    $aliases[$parts[0]] = $parts[1];
                    $translations[$i] = $parts[0];
                }
            }
        }
        return new $classname(
            $CFG->langotherroot,
            $CFG->langlocalroot,
            $translations,
            $aliases,
        );
    }
}
