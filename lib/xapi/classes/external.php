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
 * This is the external API for blogs.
 *
 * @package    core_xapi
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir .'/externallib.php');
require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->dirroot .'/blog/locallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_system;
use context_course;
use moodle_exception;
use invalid_parameter_exception;

/**
 * This is the external API for blogs.
 *
 * @copyright  2018 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Parameters for post_statement
     *
     * @return external_function_parameters
     */
    public static function post_statement_parameters() {
        return new external_function_parameters(
            [
                'component' => new external_value(PARAM_COMPONENT, 'Component name', VALUE_REQUIRED),
                'xapicontext' => new external_value(PARAM_ALPHANUMEXT,  'xAPI Context', VALUE_REQUIRED),
                'requestjson' => new external_value(PARAM_RAW, 'Component name', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Process a statement post request
     * @param string $component component name (frankenstyle)
     * @param string $xapicontext internal ID assigned by the component
     * @param string $requestjson json object with all the statements to post
     * @return array(string)
     */
    public static function post_statement(string $component, string $xapicontext, $requestjson) {
        $params = self::validate_parameters(self::post_statement_parameters(), array(
            'component' => $component,
            'xapicontext' => $xapicontext,
            'requestjson' => $requestjson,
        ));

        // Process request statements, statements could be send in several ways.
        $request = json_decode($requestjson);

        if ($request === null) {
            $lasterror = json_last_error_msg();
            throw new invalid_parameter_exception('Invalid json in request: ' . $lasterror);
        }
        $statements = self::get_statements_form_json($request);
        if (empty($statements)) {
            throw new invalid_parameter_exception('Invalid statements parameters.');
        }

        // Get component xAPI statement handler class.
        $handlerclassname = "\\$component\\xapi_handler";
        if (!class_exists($handlerclassname)) {
            throw new invalid_parameter_exception('Component not compatible.');
        }
        $xapihandler = new $handlerclassname();

        $result = [];

        // Send every statement to the component.
        foreach ($statements as $statement) {
            // Component must validate statement
            if ($xapihandler->validate_statement($xapicontext, $statement)) {
                // Give the oportinity to convert statement to standard Moodle event.
                $event = $xapihandler->statement_to_event($xapicontext, $statement);
                // Execute event and save the ID/Hash
                // If the statement have result atribute, give to the component directly (for now).
            } else {
                $result[] = null;
            }
        }

        xapi_restful_success($statements);
        return $result;
    }

    /**
     * Return for post_statement.
     */
    public static function post_statement_returns() {
        global $CFG;
        return new external_multiple_structure(
            new external_value(PARAM_ALPHANUMEXT, 'Statements IDs')
        );
    }

    /**
     * Convert mulitple types of statement rquest into an array of statements.
     * @param mixed $request json decoded statements structure
     * @return array(statements) | null
     */
    private static function get_statements_form_json ($request): ?array {
        $result = array();
        if (is_array($request)) {
            foreach ($request as $key => $value) {
                $statement = self::get_statements_form_json ($value);
                if (empty($statement)) {
                    return null;
                }
                $result += $statement;
            }
        } else {
            // Check if it's real statement or we need to go deeper in the structure.
            if (isset($request->actor)) {
                if (!self::validate_statement ($request)) {
                    return null;
                }
                $result[] = $request;
            } else {
                $statements = $request->statements ?? array();
                if (isset($request->statement)) {
                    $statements[] = $request->statement;
                }
                foreach ($statements as $key => $value) {
                    $statement = self::get_statements_form_json ($value);
                    if (empty($statement)) {
                        return null;
                    }
                    $result += $statement;
                }
            }
        }
        if (empty($result)) return null;
        return $result;
    }

    /**
     * Basic xAPI statement structure validation. This will ensure that mandatory
     * fields are created so rest of the logic could avoid tons of calls to isset and empty.
     *
     * NOTE: For now this validator only check for supported statements. In the future this kind
     * of validation should be done with a more complex json schema validator
     * if more scenarios are supported.
     *
     * @param \stdClass $statement json decoded statement structure
     * @return bool
     */
    private static function validate_statement (\stdClass $statement): bool {
        $requiredfields = ['actor', 'verb', 'object'];
        foreach ($requiredfields as $required) {
            if (empty($statement->$required)) {
                return false;
            }
            $validatefunction = 'validate_'.$required;
            if (!self::$validatefunction($statement->$required)) {
                return false;
            }
        }
        return true;
    }

    /**
     * check Agent minimal atributes (note: only Agent and Group supported).
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private static function validate_actor (\stdClass $field): bool {
        if (empty($field->objectType)) {
            $field->objectType = 'Agent';
        }
        switch ($field->objectType) {
            case 'Agent':
                return self::validate_agent($field);
            case 'Group':
                return self::validate_group($field);
        }
        return false;
    }

    /**
     * check Verb minimal atributes.
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private static function validate_verb (\stdClass $field): bool {
        if (empty($field->id)) {
            return false;
        }
        return true;
    }

    /**
     * check Object minimal atributes (Note: for now only Activity supported).
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private static function validate_object (\stdClass $field): bool {
        if (empty($field->objectType)) {
            $field->objectType = 'Activity';
        }
        if (empty($field->id)) {
            return false;
        }
        if (!empty($field->definition)) {
            return self::validate_definition($field->definition);
        }
        return true;
    }

    /**
     * check Agent minimal atributes (note: mbox_sha1sum and openid not suported).
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private static function validate_agent (\stdClass $field): bool {
        $requiredfields = ['mbox' => [],'account' => ['homePage', 'name']];
        $found = 0;
        foreach ($requiredfields as $required => $atributes) {
            if (!empty($field->$required)) {
                $found++;
            }
            foreach ($atributes as $atribute) {
                if (!empty($field->$required->$atribute)) {
                    return false;
                }
            }
        }
        if ($found != 1) {
            return false;
        }
        return true;
    }

    /**
     * check Group minimal atributes, validating also group members as Agents.
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private static function validate_group (\stdClass $field): bool {
        if (empty($field->member) || !is_array($field->member)) {
            return false;
        }
        foreach ($field->member as $member) {
            if (!self::validate_agent($member)) {
                return false;
            }
        }
        return true;
    }

        /**
     * check Object Defintion minimal atributes.
     *
     * Note: validate specific interactionType delegated to plugins.
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private static function validate_definition (\stdClass $field): bool {
        if (empty($field->interactionType)) {
            return false;
        }
        return true;
    }

}
