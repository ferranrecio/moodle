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
 * Reactive UI component base class.
 *
 * @module     core_course/local/editor/component
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class Component {

    /**
     * The class constructor.
     * @param {reactive} reactive the reactive module to register the component and execute mutations
     */
    constructor(reactive) {
        // Optional component name.
        this.reactive = reactive;
        // Empty default component selectors.
        this.selectors = {};
    }

    /**
     * Initialize the component.
     *
     * @param {element|string} target the component DOM root element or its ID
     * @param {object} newselectors optional selectors overrides
     * @returns {Component}
     */
    register(target, newselectors) {

        if (target === undefined) {
            throw Error(`Reactive components needs a main DOM element to dispatch events`);
        }

        // Save DOM element.
        this.setElement(target);

        // Overwrite the components selectors if necessary.
        if (newselectors !== undefined) {
            this.addSelectors(newselectors);
        }

        // Call create function.
        this.create();

        // Register the component.
        this.reactive.registerComponent(this);

        return this;
    }

    /**
     * Update the current component target.
     *
     * @param {element|string} target the component DOM root element or its ID
     */
    setElement(target) {
        // The target can be a string selector if it is called from a mustache file
        // or a HTML element in case the component is created directly from JS.
        if (typeof target === 'string') {
            this.element = document.getElementById(target);
            if (!this.element) {
                throw Error(`Element ${target} not found in page`);
            }
        } else {
            this.element = target;
        }
    }

    /**
     * Get the main DOM element of ths component.
     *
     * @returns {element} the DOM element
     */
    getElement() {
        return this.element;
    }

    /**
     * Add or update the component selectors.
     *
     * @param {object} newselectors an object of new selectors.
     */
    addSelectors(newselectors) {
        for (const newselector in newselectors) {
            if (newselectors.hasOwnProperty(newselector) && typeof newselectors[newselector] !== 'string') {
                this.selectors[newselectors] = newselectors[newselector];
            }
        }
    }

    /**
     * Component create function.
     *
     * Default init method will call create when all internal attributes but
     * the component is not yet registered in the reactive module.
     */
    create() {
        // Components can override this method.
    }

    /**
     * Return the list of watchers that component has.
     *
     * Each watcher is represented by an object with two attributes:
     * - watch (string) the specific state event to watch. Example 'section.visible:updated'
     * - handler (function) the function to call when the watching state change happens
     *
     * @returns {array} array of watchers.
     */
    getWatchers() {
        return [];
    }

    /**
     * Reactive module will call this method when the state is ready.
     */
    stateReady() {
        // Components can override this method.
    }
}

export default Component;
