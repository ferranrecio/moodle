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
 * Group toolbar component.
 *
 * @module     core_group/grouplist/store
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Set up general reactive class.
 *
 * @return {void}
 */

const toolbar = {
    name: 'core_group/grouplist/toolbar',
    props: {
        extra: 'This is the default prop value',
    },
    data() {
        return {
            title: 'my title',
        };
    },
    template: `<div>
            <h4>This is a subcomponent!</h4>
            <p>This content is loaded from core_group/grouplist/toolbar</p>
            <p>Each component has its own data like this text:
                <b data-bind-text="title">Error loading title!</b>
            </p>
            <p>And some props that the parent component can override like:
                <b data-bind-text="extra">Error loading extra!</b>
            </p>
        </div>`,
};


export default toolbar;
