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


defined('MOODLE_INTERNAL') || die();

/**
 * Class xapi_handler_base handles basic xapi statements.
 *
 * @package mod_hvp
 */
class xapi_handler_base {

    /** @var array Array of calculated Agents, Contexts that could not change between statements */
    protected static $entitiescache = array();

    public function validate_statement(string $xapicontext, \stdClass $statement): bool {
        return false;
    }

    /**
     * Convert a statmenet object into a Moodle xAPI Event
     *
     * Note: this method should be overriden by plugins in order to trigger specific events.
     *
     * @param string $xapicontext
     * @param \stdClass $statement
     * @return type
     */
    public function statement_to_event (string $xapicontext, \stdClass $statement): ?\core\event\base {
        return null;
    }

    /**
     * Try to convert an xAPI agent to a user record
     *
     * Note: for now, only 'mbox' and 'account' are supported
     *
     * @param \stdClass $agent
     * @return \stdClass|null user record if found, else null.
     */
    public function get_user_from_agent (\stdClass $agent): ?\stdClass {
        global $CFG;
        if (!empty($agent->account)) {
            if ($agent->account->homePage != $CFG->wwwroot) {
                return null;
            }
            $key = 'account_'.$agent->account->name;
            if (isset(self::$entitiescache[$key])) {
                return self::$entitiescache[$key];
            }
            self::$entitiescache[$key] = \core_user::get_user($agent->account->name) ?? null;
            return self::$entitiescache[$key];
        }
        if (!empty($agent->mbox)) {
            $mbox = str_replace('mailto:', '', $agent->mbox);
            $key = 'mbox_'.$mbox;
            if (isset(self::$entitiescache[$key])) {
                return self::$entitiescache[$key];
            }
            self::$entitiescache[$key] = \core_user::get_user_by_email($mbox) ?? null;
            return self::$entitiescache[$key];
        }
        return null;
    }

}
