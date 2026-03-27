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
 * Phase persistent model.
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
 * Persistent model for peerassign phases.
 *
 * @package   mod_peerassign
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class phase extends persistent {

    /** @var string The table name. */
    const TABLE = manager::MODULE . '_phases';

    /**
     * Define the model properties.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'peerassignid' => [
                'type' => PARAM_INT,
            ],
            'phasetype' => [
                'type' => PARAM_INT,
            ],
            'sequencenumber' => [
                'type' => PARAM_INT,
            ],
            'title' => [
                'type' => PARAM_TEXT,
            ],
            'description' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'required' => [
                'type' => PARAM_BOOL,
                'default' => 1,
            ],
            'unlockmethod' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => 'manual',
            ],
            'unlockdate' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'allowfiles' => [
                'type' => PARAM_BOOL,
                'default' => 1,
            ],
            'filetypes' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'maxfilesize' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'extras' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'startdate' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'enddate' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'cutoffdate' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'visible' => [
                'type' => PARAM_BOOL,
                'default' => 1,
            ],
        ];
    }
}
