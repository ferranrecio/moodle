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

class CourseIndex {

    /**
     * The class constructor.
     */
    constructor() {
        // Optional component name.
        this.name = 'courseindex';
        // Default component css selectors.
        this.selectors = {
            section: '.ci-sectionitem',
            cm: '.ci-cmitem',
            cicontent: '#courseindex-content',
        };
    }

    /**
     * Initialize the component.
     *
     * @param {object} newselectors optional selectors override
     * @returns {boolean}
     */
    init(newselectors) {

        // Overwrite the components selectors if necessary.
        this.selectors.section = newselectors.section ?? this.selectors.section;
        this.selectors.cm = newselectors.cm ?? this.selectors.cm;
        this.selectors.cicontent = newselectors.cicontent ?? this.selectors.cicontent;

        // Register the component.
        editor.registerComponent(this);

        // Bind actions if necessary.

        return true;
    }

    /**
     * Return a list of state watchers.
     *
     * @returns {array} an array of state watchers functions.
     */
    getWatchers() {
        // This is an example on how to capture any change in both cm and sections.
        // To see how to capture specific element attributes such as visible or title
        // look at core_course/local/cm_format module.
        return [
            {watch: 'cm:updated', handler: this.cmUpdate},
            {watch: 'section:updated', handler: this.sectionUpdate},
        ];
    }

    /**
     * Render the real course index using the course state.
     *
     * @param {object} state the initial state
     */
    stateReady(state) {
        // Create or bind the editor elements.
        if (state.course.editmode) {
            // Bind events. In this case we bind a click listener.
            const cicontent = document.querySelector(this.selectors.cicontent);
            cicontent.addEventListener("click", this.toogleVisibility.bind(this));
        }
    }

    /**
     * Update an entry in the course index with the state information.
     *
     * @param {object} arg
     */
    cmUpdate({element}) {
        // Get DOM element.
        let domelement = document.querySelector(`${this.selectors.cm}[data-id='${element.id}']`);
        if (!domelement) {
            return;
        }
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
    sectionUpdate({element}) {
        // Get DOM element.
        let domelement = document.querySelector(`${this.selectors.section} [data-id='${element.id}']`);
        if (!domelement) {
            return;
        }
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
    toogleVisibility(event) {
        const actionbutton = event.target.closest('[data-action]');
        if (actionbutton) {
            editor.dispatch(actionbutton.dataset.action, [actionbutton.dataset.id]);
        }
    }
}

export default new CourseIndex();
