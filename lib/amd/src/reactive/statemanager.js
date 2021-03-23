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
 * Reactive simple state manager.
 *
 * The state manager contains the state data, trigger update events and
 * can lock and unlock the state data.
 *
 * @module     core/reactive/statemanager
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {debounce} from 'core/utils';

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

        // The state_loaded event is special because it only happens one but all components
        // may react to that state, even if they are registered after the init. For these reason
        // we use a promise for that event.
        this.initialPromise = new Promise((resolve) => {
            const initialStateDone = (event) => {
                resolve(event.detail.state);
            };
            this.target.addEventListener('state:loaded', initialStateDone);
        });

        // Add a public debounced publishEvents function.
        this.publishEvents = debounce(this._publishEvents, 10);
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

        if (this.state !== undefined) {
            throw Error('Initial state can only be initialized ones');
        }

        // Create the state object.
        let state = new Proxy({}, handler('state', this, true));
        for (const prop in initialstate) {
            if (initialstate.hasOwnProperty(prop)) {
                state[prop] = initialstate[prop];
            }
        }
        this.state = state;

        // When the state is loaded we can lock it to prevent illegal changes.
        this.locked = true;
        this.dispatchEvent({
            action: 'state:loaded',
            state: this.state,
        }, this.target);
    }

    /**
     * Generate a promise that will be revolved when the initial state is loaded.
     *
     * @return {Promise} the resulting promise
     */
    getInitialPromise() {
        return this.initialPromise;
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
     */
    processUpdates(updates) {
        this.locked = false;
        if (!Array.isArray(updates)) {
            throw Error('State updates must be an array');
        }
        updates.forEach((update) => {
            if (update.name === undefined) {
                throw Error('Missing state update name');
            }
            this.processUpdate(
                update.name, update.action, update.fields
            );
        });
        this.locked = true;
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

        if (!fields) {
            throw Error('Missing state update fields');
        }

        // Process cm creation.
        if (action == 'create') {
            // Create can be applied only to lists, not to objects.
            if (state[updatename] instanceof StateMap) {
                state[updatename].add(fields);
                return;
            }
            state[updatename] = fields;
            return;
        }

        // Get the current value.
        let current = state[updatename];
        if (current instanceof StateMap) {
            if (fields.id === undefined) {
                throw Error(`Missing id for ${updatename} state update`);
            }
            current = state[updatename].get(fields.id);
            if (!current) {
                throw Error(`Inexistent ${updatename} ${fields.id}`);
            }
        }

        // Process cm deletion.
        if (action == 'delete') {
            if (state[updatename] instanceof StateMap) {
                state[updatename].delete(fields.id);
                return;
            }
            delete state[updatename];
            return;
        }

        // Execute updates.
        if (action == 'update' || action === undefined) {
            for (const prop in fields) {
                if (fields.hasOwnProperty(prop)) {
                    current[prop] = fields[prop];
                }
            }
            return;
        }
    }

    /**
     * Internal method to publish events.
     *
     * This is a private method, use de beounced "publishEvents" instead.
     */
    _publishEvents() {
        const fieldChanges = this.eventstopublish;
        this.eventstopublish = [];

        // State changes can be registered in any orded. However it will avoid many
        // components errors if they are sorted to have creations-updates-deletes
        // in case some component needs to create or destroy DOM elements.
        fieldChanges.sort((a, b) => {
            const weights = {
                created: 0,
                updated: 1,
                deleted: 2,
            };
            const aweight = weights[a.action] ?? 0;
            const bweight = weights[b.action] ?? 0;
            // In case both have the same weight, the eventname length decide.
            if (aweight === bweight) {
                return b.eventname.length - a.eventname.length;
            }
            return aweight - bweight;
        });

        // List of the published events to prevent redundancies.
        let publishedevents = new Set();

        fieldChanges.forEach((event) => {

            const eventkey = `${event.eventname}.${event.eventdata.id ?? 0}`;

            if (!publishedevents.has(eventkey)) {
                this.dispatchEvent({
                    action: event.eventname,
                    state: this.state,
                    element: event.eventdata
                }, this.target);
                // PubSub.publish(event.eventname, {state, element: event.eventdata});
                publishedevents.add(eventkey);
            }
        });
    }
};

export default StateManager;

// Proxy helpers.

/**
 * The proxy handler class.
 *
 * This proxy will trigger two events everytime an attribute is modified:
 * one for the specific attribute and one for the variable.
 *
 * @param {string} name the variable name used for identify triggered actions
 * @param {StateManager} statemanager
 * @param {boolean} proxyvalues if new values must be proxied (default false)
 * @returns {object}
 */
const handler = function(name, statemanager, proxyvalues) {

    proxyvalues = proxyvalues ?? false;

    return {
        /** Var {string} name the state element name. */
        name,
        /** Var {StateManager} statemanager the state manager object. */
        statemanager,
        /** Var {boolean} if new values must be proxied. */
        proxyvalues,
        /**
         * Set trap to trigger events when the state change.
         *
         * @param {object} obj the source object (not proxied)
         * @param {string} prop the attribute to set
         * @param {*} value the value to save
         * @param {*} receiver the proxied element to be attached to events
         * @returns {boolean} if the value is set
         */
        set: function(obj, prop, value, receiver) {

            // Only mutations should be able to set state values.
            if (this.statemanager.locked) {
                throw new Error(`State locked. Use mutations to change ${prop} value in ${this.name}.`);
            }

            // Check any data change.
            if (JSON.stringify(obj[prop]) === JSON.stringify(value)) {
                return true;
            }

            let action = (obj[prop] !== undefined) ? 'updated' : 'created';

            // Proxy value if necessary.
            if (this.proxyvalues) {
                if (Array.isArray(value)) {
                    obj[prop] = new StateMap(prop, this.statemanager).loadValues(value);
                } else {
                    obj[prop] = new Proxy(value, handler(prop, this.statemanager));
                }
            } else {
                obj[prop] = value;
            }

            // If the state is not ready yet means the initial state is not yet loaded.
            if (this.statemanager.state === undefined) {
                return true;
            }

            // Publish attribute update or create event.
            this.statemanager.eventstopublish.push({
                eventname: `${this.name}.${prop}:${action}`,
                eventdata: receiver,
                action,
            });

            // Trigger extra events if the element has an ID attrribute.
            if (obj.id !== undefined) {
                this.statemanager.eventstopublish.push({
                    eventname: `${this.name}[${obj.id}].${prop}:${action}`,
                    eventdata: receiver,
                    action,
                });
                this.statemanager.eventstopublish.push({
                    eventname: `${this.name}[${obj.id}]:${action}`,
                    eventdata: receiver,
                    action,
                });
            }

            // Register the general change.
            this.statemanager.eventstopublish.push({
                eventname: `${this.name}:updated`,
                eventdata: receiver,
                action: 'updated',
            });

            this.statemanager.publishEvents(this.statemanager);
            return true;
        },
        /**
         * Delete property trap to trigger state change events.
         *
         * @param {*} obj the affected object (not proxied)
         * @param {*} prop the prop to delete
         * @returns {boolean} if prop is deleted
         */
        deleteProperty: function(obj, prop) {
            // Only mutations should be able to set state values.
            if (this.statemanager.locked) {
                throw new Error(`State locked. Use mutations to delete ${prop} in ${this.name}.`);
            }
            if (prop in obj) {

                delete obj[prop];

                this.statemanager.eventstopublish.push({
                    eventname: `${this.name}.${prop}:deleted`,
                    eventdata: obj,
                    action: 'deleted',
                });

                // Trigger extra events if the element has an ID attrribute.
                if (obj.id !== undefined) {
                    this.statemanager.eventstopublish.push({
                        eventname: `${this.name}[${obj.id}].${prop}:deleted`,
                        eventdata: obj,
                        action: 'deleted',
                    });
                    this.statemanager.eventstopublish.push({
                        eventname: `${this.name}[${obj.id}]:updated`,
                        eventdata: obj,
                        action: 'updated',
                    });
                }

                // Register the general change.
                this.statemanager.eventstopublish.push({
                    eventname: `${this.name}:updated`,
                    eventdata: obj,
                    action: 'updated',
                });

                this.statemanager.publishEvents(this.statemanager);
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
     * @param {iterable} iterable an iterable object to create the Map
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
     * Each value needs it's own id attribute. Objects withouts id will be rejected.
     * The function will throw an error if the value id and the key are not the same.
     *
     * @param {*} key the key to store
     * @param {*} value the value to store
     * @returns {Map} the resulting Map object
     */
    set(key, value) {
        // Only mutations should be able to set state values.
        if (this.statemanager.locked) {
            throw new Error(`State locked. Use mutations to change ${key} value in ${this.name}.`);
        }

        this.checkValue(value);

        if (key === undefined || key === null) {
            throw Error('State lists keys cannot be null or undefined');
        }

        // ID is mandatory and should be the same as the key.
        if (value.id !== key) {
            throw new Error(`State error: ${this.name} list element ID (${value.id}) and key (${key}) mismatch`);
        }

        let action = (super.has(key)) ? 'updated' : 'created';

        // Save proxied data into the list.
        const result = super.set(key, new Proxy(value, handler(this.name, this.statemanager)));

        // If the state is not ready yet means the initial state is not yet loaded.
        if (this.statemanager.state === undefined) {
            return result;
        }

        // Trigger update opr create events.
        this.statemanager.eventstopublish.push({
            eventname: `${this.name}[${value.id}]:${action}`,
            eventdata: super.get(key),
            action,
        });
        this.statemanager.eventstopublish.push({
            eventname: `${this.name}:${action}`,
            eventdata: super.get(key),
            action,
        });

        this.statemanager.publishEvents(this.statemanager);
        return result;
    }

    /**
     * Check a value is valid to be stored in a a State List.
     *
     * Only objects with id attribute can be stored in State lists.
     *
     * This method throws an error if the value is not valid.
     *
     * @param {object} value (with ID)
     */
    checkValue(value) {
        if (!typeof value === 'object' && value !== null) {
            throw Error('State lists can contain objects only');
        }

        if (value.id === undefined) {
            throw Error('State lists elements must contains at least an id attribute');
        }
    }

    /**
     * Insert a new element int a list.
     *
     * Each value needs it's own id attribute. Objects withouts id will be rejected.
     *
     * @param {object} value the value to add (needs an id attribute)
     * @returns {Map} the resulting Map object
     */
    add(value) {
        this.checkValue(value);
        return this.set(value.id, value);
    }

    /**
     * Delete an element from the map
     *
     * @param {*} key
     * @returns {boolean}
     */
    delete(key) {

        // Only mutations should be able to set state values.
        if (this.statemanager.locked) {
            throw new Error(`State locked. Use mutations to change ${key} value in ${this.name}.`);
        }

        const previous = super.get(key);

        const result = super.delete(key);
        if (!result) {
            return result;
        }

        this.statemanager.eventstopublish.push({
            eventname: `${this.name}[${key}]:deleted`,
            eventdata: previous,
            action: 'deleted',
        });
        this.statemanager.eventstopublish.push({
            eventname: `${this.name}:deleted`,
            eventdata: previous,
            action: 'deleted',
        });
        this.statemanager.publishEvents(this.statemanager);
        return result;
    }

    /**
     * Return a suitable structure for JSON conversion.
     *
     * This function is needed because new values are compared in JSON StateMap has Private
     * attributes which cannot be stringified (like this.statremanager which will produce an
     * infinite recursivity).
     *
     * @returns {array}
     */
    toJSON() {
        let result = [];
        this.forEach((value) => {
            result.push(value);
        });
        return result;
    }

    /**
     * Insert a full list of values without triggering events.
     *
     * This method is used mainly to initialize the list. Note each element is indexed by its "id" attribute.
     * This is a basic restriction of StateMap. All elements need an id attribute, otherwise it won't be saved.
     *
     * @param {iterable} values the values to load
     * @returns {StateMap} return the this value
     */
    loadValues(values) {
        values.forEach((data) => {
            this.checkValue(data);
            const key = data.id;
            let newvalue = new Proxy(data, handler(this.name, this.statemanager));
            this.set(key, newvalue);
        });
        return this;
    }
}
