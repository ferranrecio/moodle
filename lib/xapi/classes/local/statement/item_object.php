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

namespace local\statement;

use core_xapi\InvalidArgumentException;
use core_xapi\helper;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for core_xapi implementing null_provider.
 *
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class statement_object extends item {

    /** @var string The statement. */
    protected $object;

    /** @var string The statement. */
    protected $definition;

    protected function __construct($data, $definition) {
        parent::__construct($data);
        $this->object = helper::extract_iri_value($data->id, 'object');
        $this->definition = $data->definition ?? null;
    }

    public static function create_from_data($data): item {
        if ($data->objectType != 'Activity') {
            throw new InvalidArgumentException("Activity objectType must be 'Activity'");
        }
        switch ($data->objectType) {
            case 'Agent':
                return core_xapi\local\statement_agent::create_from_data($data);
                break;
            case 'Group':
                return core_xapi\local\statement_group::create_from_data($data);
                break;
            case 'Activity':
                if (empty($data->id)) {
                    throw new InvalidArgumentException("missing Activity id");
                }
                if (!helper::check_iri_value($data->id)) {
                    throw new InvalidArgumentException("Activity id $data->id is not a valid IRI");
                }
                if (empty($data->id)) {
                    throw new InvalidArgumentException("missing Activity id");
                }
                return new self($data);
                break;
            default:
                throw new InvalidArgumentException("Unknown Object type '{$data->objectType}'");
        }
    }

    public function get_object(): string {
        return $this->object;
    }

    public function get_definition(): ?stdClass {
        return $this->definition;
    }
}
