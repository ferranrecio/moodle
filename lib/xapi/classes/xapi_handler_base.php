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
 * The core_xapi statement validation and tansformation.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class xapi_handler_base handles basic xapi statements.
 *
 * @package mod_hvp
 */
class xapi_handler_base {

    public function validate_statement(string $xapicontext, stdClass $statement) {
        return false;
    }

    /**
     * Convert a statmenet object into a Moodle xAPI Event
     * @param string $xapicontext
     * @param stdClass $statement
     * @return type
     */
    public function statement_to_event (string $xapicontext, stdClass $statement): ?\core\event\base {
        return null;
    }

}
