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
     * Basic xAPI statement structure validation.
     * @param mixed $statement json decoded statement structure
     * @return bool
     */
    private static function validate_statement ($statement): bool {
        $mandatory_fields = ['actor', 'verb', 'object'];
        foreach ($mandatory_fields as $field) {
            if (!isset($statement->$field)) {
                return false;
            }
        }
        return true;
    }
}
