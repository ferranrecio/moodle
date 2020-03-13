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
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for core_xapi implementing null_provider.
 *
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_group extends item {

    /** @var timestamp The statement timestamp. */
    protected $users;

    /** @var timestamp The statement timestamp. */
    protected $group;

    protected function __construct(stdClass $data, stdClass $group) {
        parent::__construct($data);
        $this->group = $group;
        $this->users = groups_get_members($group->id);
        if (!$this->users) {
            $this->users = [];
        }
    }

    public static function create_from_data($data): item {
        global $CFG;

        if ($data->objectType != 'Group') {
            throw new InvalidArgumentException("Group objectType must be 'Group'");
        }
        if (!isset($data->account)) {
            throw new InvalidArgumentException("Missing Group account");
        }
        if ($data->account->homePage != $CFG->wwwroot) {
            throw new InvalidArgumentException("Invalid group homePage '{$data->account->homePage}'");
        }
        if (!is_numeric($data->account->name)) {
            throw new InvalidArgumentException("Agent account aname must be integer '{$data->account->name}' found");
        }
        $group = groups_get_group($data->account->name);
        if (empty($group)) {
            throw new InvalidArgumentException("Inexsitent group '{$data->account->name}'");
        }
        return new self($data, $group);
    }

    /**
     * Create a item_group from a existing group.
     * @param stdClass $group A group record.
     * @return item_group
     */
    public static function create_from_group(stdClass $group): item_group {
        global $CFG;

        if (!isset($group->id)) {
            throw new InvalidArgumentException("Missing group id");
        }
        $data = (object) [
            'objectType' => 'Group',
            'account' => (object) [
                'homePage' => $CFG->wwwroot,
                'name' => $group->id,
            ],
        ];
        return new self($data, $group);
    }

    public function get_user(): stdClass {
        throw new InvalidArgumentException("Method not valid on gtoup");
    }

    public function get_all_users(): array {
        return $this->users;
    }

    public function get_group(): stdClass {
        return $this->group;
    }
}
