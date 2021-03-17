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
* Set up general state manager class.
*
* The state manager contains the state data, trigger update events and
* can lock and unlock the state data.
*
* This module will be mover to core\statemanager once the new editor dev starts.
*
* @return {void}
*/
const StateManager = class {

    /**
     * Create a basic reactive state store.
     *
     * @param {function} dispatchevent the function to dispatch the custom event when the state changes.
     * @param {element} target the state changed custom event target (document if none provided)
     */
    constructor(dispatchevent, target) {
        // The dispatch event function
        this.dispatchEvent = dispatchevent;
        // The DOM container to trigger events.
        this.target = target ?? document;
        // State is not locked until initial state is set.
        this.locked = false;
        // List of events to publish as an event.
        this.eventstopublish = [];
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
                    state[prop] = new StateMap(prop, this);
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
        this.dispatchEvent({
            action: 'state:loaded',
            state: this.state,
        }, this.target);
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
            let proxied = new Proxy(fields, handler(updatename, this));
            if (state[updatename] instanceof StateMap) {
                state[updatename].add(fields.id ?? 0, proxied);
                return;
            }
            state[updatename] = proxied;
            return;
        }

        // Get the current value.
        let current = state[updatename];
        if (current instanceof StateMap) {
            current = state[updatename].get(fields.id ?? 0);
            if (!current) {
                log.error(`Inexistent ${updatename} ${fields.id ?? 0}`);
                return;
            }
        }

        // Process cm deletion.
        if (action == 'delete') {
            if (state[updatename] instanceof StateMap) {
                state[updatename].delete(fields.id ?? 0);
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

/**
 * Dispatch all the pending events.
 *
 * This is a debounced function to prevent repeated updates.
 *
 * @param {*} state the affected current state.
 */
const publishEvents = debounce((statemanager) => {
    const fieldChanges = statemanager.eventstopublish;
    statemanager.eventstopublish = [];

    // List of the published events to prevent redundancies.
    let publishedevents = new Set();

    fieldChanges.forEach(function(event) {

        const eventkey = `${event.eventname}.${event.eventdata.id ?? 0}`;

        if (!publishedevents.has(eventkey)) {
            log.debug(`EVENT ${event.eventname}`);
            statemanager.dispatchEvent({
                    action: event.eventname,
                    state: statemanager.state,
                    element: event.eventdata
                }, statemanager.target);
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
        name: name,
        statemanager: statemanager,
        set: function(obj, prop, value) {
            // Only mutations should be able to set state values.
            if (this.statemanager.locked) {
                throw new Error(`State locked. Use mutations to change ${prop} value.`);
            }

            if (JSON.stringify(obj[prop]) === JSON.stringify(value)) {
                return true;
            }

            obj[prop] = value;

            this.statemanager.eventstopublish.push({
                eventname: `${this.name}.${prop}:updated`,
                eventdata: obj,
            });

            // Register the general change.
            this.statemanager.eventstopublish.push({
                eventname: `${this.name}:updated`,
                eventdata: obj,
            });

            publishEvents(this.statemanager);
            return true;
        },
        deleteProperty: function(obj, prop) {
            // Only mutations should be able to set state values.
            if (this.statemanager.locked) {
                throw new Error(`State locked. Use mutations to delete ${prop}.`);
            }
            if (prop in obj) {

                delete obj[prop];

                this.statemanager.eventstopublish.push({
                    eventname: `${this.name}.${prop}:deleted`,
                    eventdata: obj,
                });

                // Register the general change.
                this.statemanager.eventstopublish.push({
                    eventname: `${this.name}:updated`,
                    eventdata: obj,
                });

                publishEvents(this.statemanager);
            }
            return true;
        },
    };
};

/**
 * Class to add event trigger into the JS Map class.
 */
class StateMap extends Map {
    /**
     * Creat the reactive Map.
     *
     * @param {string} name the property name
     * @param {StateManager} statemanager the state manager
     * @param {*} iterable an iterable object to create the Map
     */
    constructor(name, statemanager, iterable) {
        // We don't have any "this" until be call super.
        super(iterable);
        this.name = name;
        this.statemanager = statemanager;
    }
    /**
     * Set an element into the map
     *
     * @param {*} key the key to store
     * @param {*} value the value to store
     * @returns {Map} the resulting Map object
     */
    set(key, value) {
        const result = super.set(key, value);
        // If the state is not ready yet means the initial state is not yet loaded.
        if (this.statemanager.state === undefined) {
            return result;
        }
        // Trigger update opr create event.
        let action = (super.has(key)) ? 'updated' : 'created';
        this.statemanager.eventstopublish.push({
            eventname: `${this.name}:${action}`,
            eventdata: super.get(key),
        });
        publishEvents(this.statemanager);
        return result;
    }
    /**
     * Delete an element from the map
     *
     * @param {*} key
     * @returns {boolean}
     */
    delete(key) {
        const result = super.delete(key);
        if (!result) {
            return result;
        }
        // Trigger deleted event
        const previous = super.get(key);
        this.statemanager.eventstopublish.push({
            eventname: `${this.name}:deleted`,
            eventdata: previous,
        });
        publishEvents(this.statemanager);
        return result;
    }
}
