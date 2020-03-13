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
class item_verb extends item{

    /** @var string The statement. */
    protected $verb;

    protected function __construct(stdClass $data) {
        parent::__construct($data);
        $this->verb = helper::extract_iri_value($data->id, 'verb');
    }

    public static function create_from_data($verb): item {
        if (empty($verb->id)) {
            throw new InvalidArgumentException("missing verb id");
        }
        if (!helper::check_iri_value($verb->id)) {
            throw new InvalidArgumentException("verb id $verb->id is not a valid IRI");
        }
        return new self($verb);
    }

    public function get_verb(): string {
        return $this->verb;
    }
}
