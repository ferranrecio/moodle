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
 * Helper class to get language configurations.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lang_config_helper {
    /**
     * Get translations and aliases from the config.
     *
     * This method reads the $CFG->langlist configuration variable.
     *
     * @return array An array with two keys:
     *               - 'translations': an array of language codes.
     *               - 'aliases': an associative array of language code => alias.
     */
    public function get_translations(): array {
        global $CFG;

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
        return [
            'translations' => $translations,
            'aliases' => $aliases,
        ];
    }

    /**
     * Get the root directory for all language packs but "en".
     *
     * @return string
     */
    public function get_langpacks_path(): string {
        global $CFG;
        return $CFG->langotherroot;
    }

    /**
     * Get the root directory for local custom language packs.
     *
     * @return string
     */
    public function get_local_langpacks_path(): string {
        global $CFG;
        return $CFG->dataroot . '/lang';
    }

    /**
     * Get the list of country codes from the config.
     *
     * @return array
     */
    public function get_country_codes(): array {
        global $CFG;
        return !empty($CFG->allcountrycodes) ? explode(',', $CFG->allcountrycodes) : [];
    }
}
