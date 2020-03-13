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
 * Statement base object for xAPI sctructure checking and validation.
 *
 * @package    core_xapi
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi\local\statement;

use InvalidArgumentException;
use core_user;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for core_xapi implementing null_provider.
 *
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_agent extends item {

    /** @var timestamp The statement timestamp. */
    protected $user;

    protected function __construct($data, $user) {
        parent::__construct($data);
        $this->user = $user;
    }

    public static function create_from_data($data): item {
        global $CFG;

        if ($data->objectType != 'Agent') {
            throw new InvalidArgumentException("Agent objectType must be 'Agent'");
        }
        if (isset($data->account) && isset($data->mbox)) {
            throw new InvalidArgumentException("Agent cannot have more than one identifier");
        }
        $user = null;
        if (!empty($data->account)) {
            if ($data->account->homePage != $CFG->wwwroot) {
                throw new InvalidArgumentException("Invalid agent homePage '{$data->account->homePage}'");
            }
            if (!is_numeric($data->account->name)) {
                throw new InvalidArgumentException("Agent account aname must be integer '{$data->account->name}' found");
            }
            $user = core_user::get_user($data->account->name);
            if (empty($user)) {
                throw new InvalidArgumentException("Inexsitent agent '{$data->account->name}'");
            }
        }
        if (!empty($data->mbox)) {
            $mbox = str_replace('mailto:', '', $data->mbox);
            $user = core_user::get_user_by_email($mbox);
            if (empty($user)) {
                throw new InvalidArgumentException("Inexsitent agent '{$data->mbox}'");
            }
        }
        if (empty($user)) {
            throw new InvalidArgumentException("Unsupported agent definition");
        }
        return new self($data, $user);
    }

    /**
     * Create a item_agent from a existing user.
     * @param stdClass $user A user record.
     * @return item_agent
     */
    public static function create_from_user(stdClass $user): item_agent {
        global $CFG;

        if (!isset($user->id)) {
            throw new InvalidArgumentException("Missing user id");
        }
        $data = (object) [
            'objectType' => 'Agent',
            'account' => (object) [
                'homePage' => $CFG->wwwroot,
                'name' => $user->id,
            ],
        ];
        return new self($data, $user);
    }

    public function get_user(): stdClass {
        return $this->user;
    }

    public function get_all_users(): array {
        return [$this->user->id => $this->user];
    }
}
