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
 * This is the external API for generic xAPI handling.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir .'/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use core_component;

/**
 * This is the external API for generic xAPI handling.
 *
 * @copyright  2020 Ferran Recio
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
                'requestjson' => new external_value(PARAM_RAW, 'json object with all the statements to post', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Process a statement post request.
     *
     * @param string $component component name (frankenstyle)
     * @param string $requestjson json object with all the statements to post
     * @return array(string)
     */
    public static function post_statement(string $component, $requestjson) {
        $params = self::validate_parameters(self::post_statement_parameters(), array(
            'component' => $component,
            'requestjson' => $requestjson,
        ));

        // Check that $component is a real component name.
        $dir = core_component::get_component_directory($component);
        if (!$dir) {
            throw new invalid_xapi_request_exception("Component $component not available.");
        }

        // Process request statements, statements could be send in several ways.
        $validator = new \core_xapi\xapi_validator();
        $statements = $validator->get_statements_form_json($requestjson);
        if (empty($statements)) {
            $lastrerror = $validator->get_last_error_msg();
            $lastcheck = $validator->get_last_check_index();
            $msg = "Statement #$lastcheck error: $lastrerror.";
            throw new invalid_xapi_request_exception($msg);
        }

        // Get component xAPI statement handler class.
        $xapihandler = \core_xapi\xapi_helper::get_xapi_handler($component);
        if (!$xapihandler) {
            throw new invalid_xapi_request_exception('Component not compatible.');
        }

        $result = $xapihandler->process_statements($statements);

        // In case no statement is processed, an error must be returned.
        if (count(array_filter($result)) == 0) {
            if (count($result) == 1) {
                $msg = 'Statement error: '.$xapihandler->get_last_error_msg();
            } else {
                $msg = 'No statement can be processed.';
            }
            throw new invalid_xapi_request_exception($msg);
        }
        return $result;
    }

    /**
     * Return for post_statement.
     */
    public static function post_statement_returns() {
        return new external_multiple_structure(
            new external_value(PARAM_ALPHANUMEXT, 'Statements IDs')
        );
    }
}
