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
 * Peerassign activity persistent model.
 *
 * @package   mod_peerassign
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign\local\models;

use core\persistent;
use mod_peerassign\manager;

/**
 * Persistent model for peerassign activity instances.
 *
 * @package   mod_peerassign
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerassign extends persistent {
    /** @var string The table name. */
    const TABLE = manager::MODULE;

    /**
     * Define the model properties.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'course' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'intro' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'introformat' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'blindreview' => [
                'type' => PARAM_BOOL,
                'default' => 0,
            ],
            'groupsubmissions' => [
                'type' => PARAM_BOOL,
                'default' => 0,
            ],
            'groupingid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }
}
