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
 * @module     core/reactive/component
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class Component {

    /**
     * The class constructor.
     *
     * The only param this method gets is a constructor with all the mandatory
     * and optional component data. Component will receive the same descriptor
     * as create method param.
     *
     * The main descriptor attributes are:
     * - reactive {reactive}: this is mandatory reactive module to register
     * - element {DOMelement}: all components needs an element to anchor events
     * - (optional) selectors {object}: an optional object to override query selectors
     *
     * @param {object} descriptor data to create the object.
     */
    constructor(descriptor) {

        if (descriptor.element === undefined || !(descriptor.element instanceof HTMLElement)) {
            throw Error(`Reactive components needs a main DOM element to dispatch events`);
        }

        if (descriptor.reactive === undefined) {
            throw Error(`Reactive components needs a reactive module to work with`);
        }

        this.reactive = descriptor.reactive;

        this.element = descriptor.element;

        // Empty default component selectors.
        this.selectors = {};

        // Empty default evenet list.
        this.events = {};

        // Call create function to get the component defaults.
        this.create(descriptor);

        // Overwrite the components selectors if necessary.
        if (descriptor.selectors !== undefined) {
            this.addSelectors(descriptor.selectors);
        }

        // Register the component.
        this.reactive.registerComponent(this);
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
        for (const selectorname in newselectors) {
            if (newselectors.hasOwnProperty(selectorname) && typeof newselectors[selectorname] === 'string') {
                this.selectors[selectorname] = newselectors[selectorname];
            }
        }
    }

    /**
     * Return a component selector.
     *
     * @param {string} selectorname the selector name
     * @return {string|undefined} the query selector
     */
    getSelector(selectorname) {
        return this.selectors[selectorname];
    }

    /**
     * Return a component specific event names.
     *
     * @return {object} and object with all the component event names.
     */
    getEvents() {
        return this.events;
    }

    /**
     * Component create function.
     *
     * Default init method will call create when all internal attributes but
     * the component is not yet registered in the reactive module.
     *
     * This method is mainly for any component to define its own defaults such as:
     * - this.selectors {object} the default query selectors of this component.
     * - this.events {object} a list of event names this component dispatch
     * - extract any data form the main dom element (this.element)
     * - any other data this component uses
     *
     * @param {object} descriptor the component descriptor
     */
    // eslint-disable-next-line no-unused-vars
    create(descriptor) {
        // Components may override this method to initialize selects, events or other data.
    }

    /**
     * Dispatch a custom event form this.element
     *
     * This is just a quick way to dispatch custom events from within a component.
     * Components are free to use an alternative function to dispatch custom
     * events. The only restriction is that it should be dispatched on this.element
     * and specify "bubbles:true" to alert component listeners.
     *
     * @param {string} eventname the event name
     * @param {*} detail event detail data
     */
    dispatchEvent(eventname, detail) {
        this.element.dispatchEvent(new CustomEvent(eventname, {
            bubbles: true,
            detail: detail,
        }));
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
