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
 * Editor component for the cm_format template.
 *
 * @module     core_course/cm_format
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import editor from 'core_course/editor';

// Those are the default selector for this component.
let cssselectors = {
    cm: '.cm_format',
};

// Prevent multiple initialize.
let initialized = false;

/**
 * Initialize the component.
 *
 * @method init
 * @param {object} newselectors optional selectors override
 * ean}
 * @return {boolean}
 */
export const init = (newselectors) => {

    if (initialized) {
        return true;
    }
    initialized = true;

    // Overwrite the components selectors if necessary.
    cssselectors.cm = newselectors.cm ?? cssselectors.cm;

    // Register the component.
    editor.registerComponent({
        name: 'cm_format',
        getWatchers,
    });

    // Bind actions if necessary.

    return true;
};

/**
 * Return a list of state watchers.
 *
 * @returns {array} an array of state watchers functions.
 */
export const getWatchers = () => {
    // This is just an example of how a component could watch only
    // some attributes of an element. For an example on how to capture
    // any change in an element see core_coure/local/editor/courseindex module.

    return [
        {watch: 'cm.visible:updated', handler: cmVisibility},
        {watch: 'cm.locked:updated', handler: cmLocked},
    ];
};

/**
 *
 * @param {*} arg
 */
function cmVisibility({element}) {
    // Get DOM element.
    let domelement = document.querySelector(`${cssselectors.cm}[data-id='${element.id}']`);
    if (element.visible) {
        domelement.classList.remove("dimmed_text");
    } else {
        domelement.classList.add("dimmed_text");
    }
}

/**
 *
 * @param {*} arg
 */
function cmLocked({element}) {
    // Get DOM element.
    let domelement = document.querySelector(`${cssselectors.cm}[data-id='${element.id}']`);
    if (element.locked) {
        domelement.classList.add("locked");
    } else {
        domelement.classList.remove("locked");
    }
}
