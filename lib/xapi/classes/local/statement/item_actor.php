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
abstract class item_actor extends item {

    public static function create_from_data($data): item {
        if (!isset($data->objectType)) {
            $data->objectType = 'Agent';
        }
        switch ($data->objectType) {
            case 'Agent':
                return core_xapi\local\statement_agent::create_from_data($data);
                break;
            case 'Group':
                return core_xapi\local\statement_group::create_from_data($data);
                break;
            default:
                throw new InvalidArgumentException("Unknown Actor type '{$data->objectType}'");
        }
    }

    abstract public function is_valid(): bool;

    abstract public function get_user(): stdClass;

    abstract public function get_all_users(): array;

    public function validate(): void {
        if (!$this->is_valid()) {

        }
    }
}
