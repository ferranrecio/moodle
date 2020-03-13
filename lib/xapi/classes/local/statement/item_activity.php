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
use core_xapi\helper;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for core_xapi implementing null_provider.
 *
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_activity extends item {

    /** @var string Activity ID. */
    protected $activity;

    /** @var item_definition Definition object. */
    protected $definition;

    protected function __construct($data, item_definition $definition = null) {
        parent::__construct($data);
        $this->activity = helper::extract_iri_value($data->id, 'activity');
        $this->definition = $definition;
    }

    public static function create_from_data($data): item {
        if (!isset($data->objectType)) {
            $data->objectType = 'Activity';
        }
        if ($data->objectType != 'Activity') {
            throw new InvalidArgumentException('Activity objectType must be "Activity"');
        }
        if (empty($data->id)) {
            throw new InvalidArgumentException("Missing Activity id");
        }
        if (!helper::check_iri_value($data->id)) {
            throw new InvalidArgumentException("Activity id $data->id is not a valid IRI");
        }

        $definition = null;
        if (!empty($data->definition)) {
            $definition = item_definition::create_from_data($data->definition);
        }

        return new self($data, $definition);
    }

    public function get_activity(): string {
        return $this->activity;
    }

    public function get_definition(): ?item_definition {
        return $this->definition;
    }
}
