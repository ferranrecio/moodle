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
 * Webservice definitions for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_peerassign\manager;

$services = [
    'mod_peerassign_service' => [
        'enabled' => 1,
        'requiredcapability' => 'mod/peerassign:addphase',
        'restrictedusers' => 0,
        'shortname' => manager::MODULE,
        'functions' => [
            'mod_peerassign_create_phase',
        ],
    ],
];

$functions = [
    'mod_peerassign_create_phase' => [
        'classname' => 'mod_peerassign\external\create_phase',
        'methodname' => 'execute',
        'classpath' => 'mod/peerassign/classes/external/create_phase.php',
        'description' => 'Create a new phase for a Peer Review Assignment activity',
        'type' => 'write',
        'ajax' => true,
        'services' => ['mod_peerassign_service'],
    ],
];
