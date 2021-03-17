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
 * Course index editor component.
 *
 * @module     core_course/courseindex
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import editor from 'core_course/editor';
import log from 'core/log';

// Those are the default selector for this component.
let cssselectors = {
    section: '.ci-sectionitem',
    cm: '.ci-cmitem',
    cicontent: '#courseindex-content',
};

// Prevent multiple initialize.
let initialized = false;

/**
 * Initialize the component.
 *
 * @param {object} newselectors optional selectors override
 * @returns {boolean}
 */
export const init = (newselectors) => {

    if (initialized) {
        return true;
    }
    initialized = true;

    // Overwrite the components selectors if necessary.
    cssselectors.section = newselectors.section ?? cssselectors.section;
    cssselectors.cm = newselectors.cm ?? cssselectors.cm;
    cssselectors.cicontent = newselectors.cicontent ?? cssselectors.cicontent;

    // Register the component.
    editor.registerComponent({
        name: 'courseindex',
        getWatchers,
    });

    // Bind any necessary actions.

    return true;
};

/**
 * Return a list of state watchers.
 *
 * @returns {array} an array of state watchers functions.
 */
export const getWatchers = () => {
    // This is an example on how to capture any change in both cm and sections.
    // To see how to capture specific element attributes such as visible or title
    // look at core_course/local/cm_format module.
    return [
        {watch: 'state:loaded', handler: readyState},
        {watch: 'cm:updated', handler: cmUpdate},
        {watch: 'section:updated', handler: sectionUpdate},
    ];
};

/**
 * This function is called when the course state is ready.
 *
 * Using this watcher the component can add elements to the interface
 * like edition buttons or bind events.
 *
 * @param {object} arg
 */
export const readyState = ({state}) => {
    // Create or bind the editor elements.
    if (state.course.editmode) {
        // Bind events. In this case we bind a click listener.
        const cicontent = document.querySelector(cssselectors.cicontent);
        cicontent.addEventListener("click", toogleVisibility);
    }
};

/**
 * Update an entry in the course index with the state information.
 *
 * @param {object} arg
 */
function cmUpdate({element}) {
    // Get DOM element.
    let domelement = document.querySelector(`${cssselectors.cm}[data-id='${element.id}']`);
    if (element.visible) {
        domelement.classList.remove("dimmed");
        domelement.classList.remove("bg-light");
    } else {
        domelement.classList.add("dimmed");
        domelement.classList.add("bg-light");
    }
    if (element.locked) {
        domelement.classList.add("locked");
    } else {
        domelement.classList.remove("locked");
    }
}

/**
 *
 * Update the section information with the current course state.
 *
 * @param {object} arg
 */
function sectionUpdate({element}) {
    // Get DOM element.
    let domelement = document.querySelector(`${cssselectors.section} [data-id='${element.id}']`);
    if (element.visible) {
        domelement.classList.remove("dimmed");
    } else {
        domelement.classList.add("dimmed");
    }
}

/**
 * Execute a mutation from a click event.
 *
 * This method is just an example on how to delegate evenets handling. In this case,
 * this function should be located in the main editor to capture all possible
 * actions.
 *
 * @param {*} event
 */
function toogleVisibility(event) {
    const actionbutton = event.target.closest('[data-action]');
    if (actionbutton) {
        editor.dispatch(actionbutton.dataset.action, [actionbutton.dataset.id]);
    }
}
