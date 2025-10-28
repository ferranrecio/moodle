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

namespace core\router\parameters;

use core\exception\not_found_exception;
use core\param;
use core\router\schema\example;
use core\router\schema\parameters\mapped_property_parameter;
use core\router\schema\referenced_object;
use Psr\Http\Message\ServerRequestInterface;
use core_course\modinfo;

/**
 * A Moodle course module referenced in the path.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class path_course_module extends \core\router\schema\parameters\path_parameter implements
    mapped_property_parameter,
    referenced_object
{
    /**
     * Create a new path_activity parameter.
     *
     * @param string $name The name of the parameter to use for the course identifier
     * @param mixed ...$extra Additional arguments
     */
    public function __construct(
        string $name = 'cm',
        ...$extra,
    ) {
        $extra['name'] = $name;
        $extra['type'] = param::INT;
        $extra['description'] = 'The activity course module id.';
        $extra['examples'] = [
            new example(
                name: 'A course module id',
                value: 54,
            ),
        ];

        parent::__construct(...$extra);
    }

    /**
     * Get the course module info for the given value.
     *
     * @param string $value A course id, idnumber, or shortname
     * @return object
     * @throws not_found_exception If the course cannot be found
     */
    protected function get_cminfo_for_value(string $value): mixed {
        global $DB;

        $record = $DB->get_record('course_modules', ['id' => $value], '*');
        if (!$record) {
            throw new not_found_exception('course_module', $value);
        }

        $modinfo = modinfo::instance($record->course);
        $cm = $modinfo->get_cm($record->id);
        if (!$cm) {
            throw new not_found_exception('course_module', $value);
        }

        return $cm;
    }

    #[\Override]
    public function add_attributes_for_parameter_value(
        ServerRequestInterface $request,
        string $value,
    ): ServerRequestInterface {
        $cminfo = $this->get_cminfo_for_value($value);

        return $request
            ->withAttribute($this->name, $cminfo)
            ->withAttribute("{$this->name}context", \core\context\module::instance($cminfo->id));
    }
}
