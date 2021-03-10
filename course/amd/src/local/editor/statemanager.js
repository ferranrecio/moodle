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
 * Default state manager
 *
 * @module     core_course/editor/statemanager
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import {debounce} from 'core/utils';

/**
* Set up general reactive class.
*
* The reactive class is responsible for contining the main store,
* the complete components list that can be used and initialize the main
* component.
*
* @return {void}
*/
const StateManager = class {

    /**
     * Create a basic reactive state store.
     *
     * @param {function} dispatchevent the function to dispatch the custom event when the state changes.
     */
    constructor(dispatchevent) {
        this.dispatchEvent = dispatchevent;
        this.locked = false;
    }

    /**
     * Loads the initial state.
     *
     * Note this method will trigger a state changed event with "state_loaded" actionname.
     *
     * The state will be locked authomatically when the state is loaded.
     *
     * @param {object} initialstate
     */
    setInitialState(initialstate) {

        let state = {};
        for (const prop in initialstate) {
            if (initialstate.hasOwnProperty(prop)) {
                // Check is is an array.
                if (Array.isArray(initialstate[prop])) {
                    state[prop] = new Map();
                    initialstate[prop].forEach((data) => {
                        state[prop].set(data.id ?? 0, new Proxy(data, handler(prop, this)));
                    });
                } else {
                    state[prop] = new Proxy(initialstate[prop], handler(prop, this));
                }
            }
        }
        // Create the state object.
        this.state = new Proxy(state, handler('', this));
        // When the state is loaded we can lock it to prevent illegal changes.
        this.locked = true;
        this.dispatchEvent('state_loaded', this.state);
    }

    /**
     * Locks or unlocks the state to prevent illegal updates.
     *
     * @param {bool} lockvalue
     */
    setLocked(lockvalue) {
        this.locked = lockvalue;
    }

    /**
     * Process a state updates array and do all the necessary changes.
     *
     * Note this method unlocks the state while it is executing and relocks it
     * when finishes.
     *
     * @param {array} updates
     * @returns {bool}
     */
    processUpdates(updates) {
        this.locked = false;
        for (let update of updates) {
            this.processUpdate(update.name, update.action, update.fields);
        }
        this.locked = true;
        return true;
    }

    /**
     * Private function process a single state updates.
     *
     * Note this method unlocks the state while it is executing and relocks it
     * when finishes.
     *
     * @param {string} updatename
     * @param {string} action
     * @param {object} fields
     */
    processUpdate(updatename, action, fields) {
        let state = this.state;
        // Process cm creation.
        if (action == 'create') {
            // Create can be applied only to lists, not to objects.
            if (state[updatename] instanceof Map) {
                let proxied = new Proxy(fields, handler(updatename, this));
                state[updatename].add(fields.id ?? 0, proxied);
                this.dispatchEvent(`${updatename}_created`, state, proxied);
                return;
            }
            // TODO: add attribute creation suport (not needed for the proof of concept).
            log.error(`Cannot execute create on ${updatename}`);
            return;
        }

        // Get the current value.
        let current = state[updatename];
        if (current instanceof Map) {
            current = state[updatename].get(fields.id ?? 0);
            if (!current) {
                log.error(`Inexistent ${updatename} ${fields.id ?? 0}`);
                return;
            }
        }

        // Process cm deletion.
        if (action == 'delete') {
            if (state[updatename] instanceof Map) {
                state[updatename].delete(fields.id ?? 0);
                this.dispatchEvent(`${updatename}_deleted`, state, current);
                return;
            }
            delete state[updatename];
            return;
        }

        // Execute updates.
        for (const prop in fields) {
            if (fields.hasOwnProperty(prop)) {
                current[prop] = fields[prop];
            }
        }
    }
};

export default StateManager;

// Proxy helpers.

// This array contains the events that are not yet dispatched.
let eventstopublish = [];

/**
 * Dispatch all the pending events.
 *
 * This is a debounced function to prevent repeated updates.
 *
 * @param {*} state the affected current state.
 */
const publishEvents = debounce((statemanager) => {
    const fieldChanges = eventstopublish;
    eventstopublish = [];

    // List of the published events to prevent redundancies.
    let publishedevents = new Set();

    fieldChanges.forEach(function(event) {

        const eventkey = `${event.eventname}.${event.eventdata.id ?? 0}`;

        if (!publishedevents.has(eventkey)) {
            log.debug(`EVENT ${event.eventname}`);
            statemanager.dispatchEvent(event.eventname, statemanager.state, event.eventdata);
            // PubSub.publish(event.eventname, {state, element: event.eventdata});
            publishedevents.add(eventkey);
        }
    });
}, 10);


/**
 * The proxy handler class.
 *
 * This proxy will trigger two events everytime an attribute is modified:
 * one for the specific attribute and one for the variable.
 *
 * @param {*} name
 * @param {*} statemanager
 * @returns {object}
 */
const handler = function(name, statemanager) {
    return {
        $name: name,
        $statemanager: statemanager,
        set: function(obj, prop, value) {
            // Only mutations should be able to set state values.
            if (this.$statemanager.locked) {
                throw new Error(`State locked. Use mutations to change ${prop} value.`);
            }

            if (JSON.stringify(obj[prop]) === JSON.stringify(value)) {
                return true;
            }

            obj[prop] = value;

            eventstopublish.push({
                eventname: `${this.$name}_${prop}_updated`,
                eventdata: obj,
            });

            // Register the general change.
            eventstopublish.push({
                eventname: `${this.$name}_updated`,
                eventdata: obj,
            });

            publishEvents(this.$statemanager);
            return true;
        },
        deleteProperty: function(obj, prop) {
            // Only mutations should be able to set state values.
            if (this.$statemanager.locked) {
                throw new Error(`State locked. Use mutations to delete ${prop}.`);
            }
            if (prop in obj) {

                delete obj[prop];

                eventstopublish.push({
                    eventname: `${this.$name}_${prop}_deleted`,
                    eventdata: obj,
                });

                // Register the general change.
                eventstopublish.push({
                    eventname: `${this.$name}_updated`,
                    eventdata: obj,
                });
            }
            return true;
        },
    };
};
