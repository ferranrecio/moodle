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
 * This file is used to call xAPI LRS functions function in Moodle.
 *
 * It will process more than one request and return more than one response if required.
 *
 * @since Moodle 3.9
 * @package core_xapi
 * @copyright 2020 Ferran Recio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('AJAX_SCRIPT', true);

// if (!empty($_GET['nosessionupdate'])) {
//     define('NO_SESSION_UPDATE', true);
// }

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/externallib.php');

if(!isloggedin()){
    define('NO_OUTPUT_BUFFERING', true);
}

define('PREFERRED_RENDERER_TARGET', RENDERER_TARGET_GENERAL);
$requestjson = '';
$cacherequest = false;
if (defined('ALLOW_GET_PARAMETERS')) {
    $requestjson = optional_param('args', '', PARAM_RAW);
}

// If no standard args passed a fake form could be used (like H5P packages).
if (empty($requestjson)) {
    $requestjson = optional_param('xAPIResult', '', PARAM_RAW);
}

// Either we are not allowing GET parameters or we didn't use GET because
// we did not pass a cache key or the URL was too long.
if (empty($requestjson)) {
    $requestjson = file_get_contents('php://input');
}

if (empty($requestjson)) {
    throw new coding_exception('Missing request');
}

$rest_verb = $_SERVER['REQUEST_METHOD'] ?? 'GET';//GET, POST, PUT, DELETE
$rest_verb = strtolower($rest_verb);
$url_elements = explode('/',get_file_argument());
$url_elements = array_values(array_filter($url_elements));

// Get apikey from request (not for now).
$rest_apikey = $_SERVER['HTTP_APIKEY'] ?? null;

if (!xapi_validate_apikey($rest_apikey)) {
    xapi_restful_error (403, 'Wrong apikey');
}

// First argument must be a valid frankensyle component.
$component = array_shift($url_elements) ?? null;
$component = clean_param($component, PARAM_COMPONENT);
if (empty($component)) {
    xapi_restful_error (403, "Wrong component name $component");
}

$pluginman = core_plugin_manager::instance();
$plugin = $pluginman->get_plugin_info($component);
if (!$plugin || !$plugin->is_enabled()) {
    xapi_restful_error (403, "Disabled component $component");
}

// Second param is the resource
$xapiresource = array_shift($url_elements) ?? null;
$xapiresource = clean_param($xapiresource, PARAM_ALPHANUMEXT);

// Third argument is the generated xAPI context.
$xapicontext = array_shift($url_elements) ?? null;
$xapicontext = clean_param($xapicontext, PARAM_ALPHANUMEXT);

// Defines the external settings required for Ajax processing.
$settings = external_settings::get_instance();

// Invoke plugin xAPI service (wrapper)
$response = array();
$methodname = 'core_xapi_'.$xapiresource.'_'.$rest_verb;
$args = array(
    'component' => $component,
    'xapicontext' => $xapicontext,
    'requestjson' => $requestjson
);
try {
    $response = external_api::call_external_function($methodname, $args, true);
} catch (Exception $e) {
    xapi_restful_error (500, 'Internal Error');
}

xapi_restful_success($response);//!!!!!

if ($response['error']) {
    xapi_restful_error (400, 'Bad request');
}

xapi_restful_success($response);
