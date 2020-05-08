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
 * H5P activity external functions and service definitions.
 *
 * @package    mod_h5pactivity
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_h5pactivity_get_h5pactivity_report_attempts' => [
        'classname'     => 'mod_h5pactivity\external\get_h5pactivity_report_attempts',
        'methodname'    => 'execute',
        'classpath'     => '',
        'description'   => 'Return the information needed to list a user attempts.',
        'type'          => 'read',
        'capabilities'  => 'mod/h5pactivity:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ]
];