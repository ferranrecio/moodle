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

namespace core\output\local\properties;

/**
 * Button style property enum.
 *
 * @package    core
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum button: string implements cssproperty {
    case PRIMARY = 'btn btn-primary';
    case SECONDARY = 'btn btn-secondary';
    case SUCCESS = 'btn btn-success';
    case DANGER = 'btn btn-danger';
    case PRIMARY_OUTLINE = 'btn btn-outline-primary';
    case SECONDARY_OUTLINE = 'btn btn-outline-secondary';

    /**
     * Returns the CSS classes for the property based on its type.
     *
     * @return string The CSS classes.
     */
    public function classes(): string {
        return ' ' . $this->value;
    }
}
