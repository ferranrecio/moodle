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
 * Submission persistent model.
 *
 * @package   mod_peerassign
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign\local\models;

defined('MOODLE_INTERNAL') || die();

use core\persistent;
use mod_peerassign\manager;

/**
 * Persistent model for phase submissions.
 *
 * @package   mod_peerassign
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission extends persistent {

    /** @var string The table name. */
    const TABLE = manager::MODULE . '_submissions';

    /**
     * Define the model properties.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'phaseid' => [
                'type' => PARAM_INT,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'groupid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'attemptnum' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'plugindata' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'status' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => 'submitted',
            ],
        ];
    }
}
