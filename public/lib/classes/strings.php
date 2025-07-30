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

use core\exception\coding_exception;
use core\strings\string_manager;
use core\strings\lang_string;

/**
 * String Helper for fetching localised strings.
 *
 * @package    core
 * @copyright  Andrew Lyons <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class strings {
    /**
     * Create an instance of the strings helper class.
     *
     * This construct is designed to be used with dependency injection,
     * so you should not instantiate this class directly.
     *
     * Instead, do one of the following:
     *
     * - Inject `core\strings $strings` into your dependency injection container.
     * - Use `\core\di::get(\core\strings::class)` to get the singleton instance.
     *
     * @param string_manager $stringmanager injected string manager instance
     */
    public function __construct(
        /** @var string_manager */
        protected string_manager $stringmanager,
    ) {
    }

    /**
     * Returns a localized string.
     *
     * Returns the translated string specified by $identifier as
     * for $module.  Uses the same format files as STphp.
     * $a is an object, string or number that can be used
     * within translation strings
     *
     * - Example with object $a: 'hello {$a->firstname} {$a->lastname}'
     * - Example with string $a: 'hello {$a}'
     *
     * Example usage of this function involves finding the string you would
     * like a local equivalent of and using its identifier and module information
     * to retrieve it.
     *
     * If you open lang/en/moodle.php and look near line 348
     * you will find a string to prompt a user for their word for 'course'
     * <code>
     *     $string['course'] = 'Course';
     * </code>
     *
     * So if you want to display the string 'Course'
     * in any language that supports it on your site
     * you just need to use the identifier 'course'
     * <code>
     *     $mystring = '<strong>'. $strings->get('course') .'</strong>';
     * </code>
     *
     * If the string you want is in another file you'd take a slightly
     * different approach. Looking in lang/en/calendar.php you find
     * around line 258:
     * <code>
     *     $string['typecourse'] = 'Course event';
     * </code>
     *
     * If you want to display the string "Course event" in any language
     * supported you would use the identifier 'typecourse' and the module 'calendar'
     * (because it is in the file calendar.php):
     * <code>
     *     $mystring = '<h1>'. $strings->get('typecourse', 'calendar') .'</h1>';
     * </code>
     *
     * As a last resort, should the identifier fail to map to a string
     * the returned string will be [[ $identifier ]]
     *
     * @param string $identifier The key identifier for the localized string
     * @param string|null $component The module where the key identifier is stored,
     *      usually expressed as the filename in the language pack without the
     *      .php on the end but can also be written as mod/forum or grade/export/xls.
     *      If none is specified then moodle.php is used.
     * @param mixed $a An object, string, lang_string or number to be used within translation strings
     * @return string The localized string.
     * @throws coding_exception
     */
    public function get(
        string $identifier,
        ?string $component = null,
        mixed $a = null,
    ): lang_string|string {
        global $CFG;

        if ($CFG->debugdeveloper && clean_param($identifier, PARAM_STRINGID) === '') {
            throw new coding_exception(
                'Invalid string identifier. The identifier cannot be empty.',
                DEBUG_DEVELOPER,
            );
        }

        if ($component !== null && strpos((string) $component, '/') !== false) {
            throw new coding_exception(
                "The component passed is in a deprecated format which is no longer supported.",
            );
        }

        $result = $this->stringmanager->get_string($identifier, $component, $a);

        // Debugging feature lets you display string identifier and component.
        if (isset($CFG->debugstringids) && $CFG->debugstringids && optional_param('strings', 0, PARAM_INT)) {
            $result .= " {$identifier}/{$component}";
        }

        return $result;
    }

    /**
     * Returns a lazy loaded lang_string object.
     *
     * A lazy loaded lang_string object is a string that is not immediately
     * fetched from the language files. Instead, it is fetched when the object
     * is first used. This can be useful for performance reasons, especially
     * if the string is not used immediately or if it is used in a context where
     * the string may not be needed at all.
     *
     * The object can be used in several ways:
     *
     * Option A: calling it's out method:
     * <code>
     *     $stringobject->out();
     * </code>
     *
     * Option B: by casting the object to a string, either directly. For example:
     * <code>
     *     (string)$stringobject
     * </code>
     *
     * Option C: indirectly by using the string within another string or echoing it out
     * <code>
     *     echo $stringobject
     *     return "<p>{$stringobject}</p>";
     * </code>
     *
     * It is worth noting that using $lazyload and attempting to use the string as an
     * array key will cause a fatal error as objects cannot be used as array keys.
     * But you should never do that anyway!
     *
     * @param string $identifier The key identifier for the localized string
     * @param string|null $component The module where the key identifier is stored,
     *      usually expressed as the filename in the language pack without the
     *      .php on the end but can also be written as mod/forum or grade/export/xls.
     *      If none is specified then moodle.php is used.
     * @param mixed $a An object, string, lang_string or number to be used within translation strings
     * @return lang_string A lazy loaded lang_string object.
     */
    public function get_lazy(
        string $identifier,
        ?string $component = null,
        mixed $a = null,
    ): lang_string {
        return new lang_string($identifier, $component, $a);
    }

    /**
     * Checks if the string exists.
     *
     * @param string $identifier The identifier of the string to search for
     * @param string|null $component The module the string is associated with, null means moodle.php
     * @return bool true if the string exists
     */
    public function exists(
        string $identifier,
        ?string $component = null,
    ): bool {
        return $this->stringmanager->string_exists($identifier, $component ?? '');
    }

    /**
     * Checks if the string has been deprecated.
     *
     * @param string $identifier The identifier of the string to search for
     * @param string|null $component The module the string is associated with, null means moodle.php
     * @return bool true if the string has been deprecated
     */
    public function is_string_deprecated(
        string $identifier,
        ?string $component = null,
    ): bool {
        return $this->stringmanager->string_deprecated($identifier, $component);
    }

    /**
     * Returns the string manager instance.
     *
     * @return string_manager
     */
    public function get_manager(): string_manager {
        return $this->stringmanager;
    }
}
