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
 * The custom template interface.
 *
 * @package    core
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;
defined('MOODLE_INTERNAL') || die();

/**
 * Interface marking other classes having the hability to define their own template.
 *
 * This interface can be used with renderable and templatable to ensure
 * the output->render method knows which template use to output the component.
 *
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @package core
 * @category output
 * @since 4.0
 */
interface customtemplate {

    /**
     * Return the template path which will replace the default template of a templatable class.
     *
     * @return string the template path
     */
    public function get_template(): string;
}
