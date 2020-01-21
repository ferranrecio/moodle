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
 * xAPI LRS Restful responses support.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Shows a standard Restfull response.
 * @param  stdClass $data Data object to send a json result.
 */
function xapi_restful_success($data) {
    header('HTTP/ 200 OK');
    $res = new stdClass();
    $res->status = 'success';
    $res->data = $data;
    //print_r($data);
    echo json_encode($res);
    die();
}

/**
 * Shows a standard Restfull error response.
 * @param  int $errorcode HTTP Status Code.
 * @param  string $errormessage Error message to send.
 */
function xapi_restful_error ($errorcode, $errormessage) {
    header('HTTP/ '.$errorcode.' '.$errormessage);
    $res = new stdClass();
    $res->status = 'error';
    $res->code = $errorcode;
    $res->message = $errormessage;
    echo json_encode($res);
    die();
}

/**
 * Checks if the HTTP apikey provided is correct.
 * TODO: validate as a OAuth token.
 *
 * @return String $apikey the request apikey.
 */
function xapi_validate_apikey (?string $apikey) : bool {
    return true;
}