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
 * This file contains the three main elements of the state manager:
 * - State manager: the public class to alter the state and process update messages.
 * - Proxy handler: a private helper class to trigger events when a state object is modified.
 * - StateMap class: a private class extending Map class that triggers event when a state list is modifed.
 *
 * @module     core/local/reactive/statemanager
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * State manager class.
 *
 * This class handle the reactive state and ensure that only valid mutations can modify the state.
 * It also provide methods to apply batch state update messages (see processUpdates function doc
 * for more details on update messages).
 *
 * Implementing a deep state manager is complex and will require many frontend resources. To keep
 * the state fast and simple, the state can ONLY store two kind of data:
 *  - Object with attributes
 *  - List of objects with id attributes.
 *
 * This is an example of a valid state:
 *
 * {
 *  course: {
 *      name: 'course name',
 *      shortname: 'courseshort',
 *      sectionlist: [21, 34]
 *  },
 *  sections: [
 *      {id: 21, name: 'Topic 1', visible: true},
 *      {id: 34, name: 'Topic 2', visible: false,
 *  ],
 * }
 *
 * The following cases are NOT allowed at a state ROOT level (throws an exception if they are assigned):
 *  - Simple values (strings, boolean...).
 *  - Arrays of simple values.
 *  - Array of objects without ID attribute (all arrays will be converted to maps and requires an ID).
 *
 * Thanks to those limitations it can simplify the state update messages and the event names. If You
 * need to store simple data, just group them in an object.
 *
 * To grant any state change triggers the proper events, the class uses two private structures:
 * - proxy handler: any object stored in the state is proxied using this class.
 * - StateMap class: any object list in the state will be converted to StateMap using the
 *   objects id attribute.
 */
const StateManager = class {

    /**
     * Create a basic reactive state store.
     *
     * The state manager is meant to work independently with native JS events.
     * To ensure each reactive module can use it in its own way, the parent element must provide
     * a valid event dispatcher function and an optional DOM element to anchor the event.
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
        this.readonly = false;
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
    }

    /**
     * Loads the initial state.
     *
     * Note this method will trigger a state changed event with "state_loaded" actionname.
     *
     * The state will be locked automatically when the state is loaded.
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
        this.readonly = true;
        this.dispatchEvent({
            action: 'state:loaded',
            state: this.state,
        }, this.target);
    }

    /**
     * Generate a promise that will be resolved when the initial state is loaded.
     *
     * In most cases the final state will be loaded using an ajax call. This is the reason
     * why states manager are created with an unlocked empty state and won't be reactive until
     * the initial state is set.
     *
     * @return {Promise} the resulting promise
     */
    getInitialPromise() {
        return this.initialPromise;
    }

    /**
     * Locks or unlocks the state to prevent illegal updates.
     *
     * Mutations use this method to modify the state. Once the state is updated, they must
     * block again the state.
     *
     * All changes done while the state is writable will be registered using registerStateAction.
     * When the state is set to readonly again the method will trigger _publishEvents to communicate
     * changes to all watchers.
     *
     * @param {bool} readonly if the state is in read only mode enabled
     */
    setReadOnly(readonly) {
        this.readonly = readonly;

        // When the state is in readonly again is time to publish all events.
        if (this.readonly) {
            this._publishEvents();
        }
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
    }

    /**
     * Private function process a single state updates.
     *
     * Note this method unlocks the state while it is executing a state change
     * and relocks it when finishes.
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
            // Unlock the state to do some changes.
            this.readonly = false;

            // Create can be applied only to lists, not to objects.
            if (state[updatename] instanceof StateMap) {
                state[updatename].add(fields);
                this.readonly = true;
                return;
            }
            state[updatename] = fields;
            this.readonly = true;
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

        // Unlock the state to do some changes.
        this.readonly = false;

        // Process cm deletion.
        if (action == 'delete') {
            if (state[updatename] instanceof StateMap) {
                state[updatename].delete(fields.id);
                this.readonly = true;
                return;
            }
            delete state[updatename];
            this.readonly = true;
            return;
        }

        // Execute updates.
        if (action == 'update' || action === undefined) {
            for (const prop in fields) {
                if (fields.hasOwnProperty(prop)) {
                    current[prop] = fields[prop];
                }
            }
            this.readonly = true;
            return;
        }
    }

    /**
     * Register a state modification and generate the necessary events.
     *
     * This method is used mainly by proxy helpers to dispatch state change event.
     * However, mutations can use it to inform components about non reactive changes
     * in the state (only the two first levels of the state are reactive).
     *
     * @param {string} field the affected state field name
     * @param {string|null} prop the affecter field property (null if affect the full object)
     * @param {string} action the action done (created/updated/deleted)
     * @param {*} data the affected data
     */
    registerStateAction(field, prop, action, data) {

        let parentaction = 'updated';

        if (prop !== null) {
            this.eventstopublish.push({
                eventname: `${field}.${prop}:${action}`,
                eventdata: data,
                action,
            });
        } else {
            parentaction = action;
        }

        // Trigger extra events if the element has an ID attribute.
        if (data.id !== undefined) {
            if (prop !== null) {
                this.eventstopublish.push({
                    eventname: `${field}[${data.id}].${prop}:${action}`,
                    eventdata: data,
                    action,
                });
            }
            this.eventstopublish.push({
                eventname: `${field}[${data.id}]:${parentaction}`,
                eventdata: data,
                action: parentaction,
            });
        }

        // Register the general change.
        this.eventstopublish.push({
            eventname: `${field}:${parentaction}`,
            eventdata: data,
            action: parentaction,
        });

        // Register state updated event.
        this.eventstopublish.push({
            eventname: `state:updated`,
            eventdata: data,
            action: 'updated',
        });
    }

    /**
     * Internal method to publish events.
     *
     * This is a private method, it will be invoked when the state is set back to readonly.
     */
    _publishEvents() {
        const fieldChanges = this.eventstopublish;
        this.eventstopublish = [];

        // State changes can be registered in any order. However it will avoid many
        // components errors if they are sorted to have creations-updates-deletes in case
        // some component needs to create or destroy DOM elements before updating them.
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
                return a.eventname.length - b.eventname.length;
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
 * This proxy will trigger the folliwing events everytime an attribute is modified:
 *  - The specific attribute updated, created or deleter (example: "cm.visible:updated")
 *  - The general state object updated, created or deleted (example: "cm:updated")
 *  - If the element has an ID attribute, the specific event with id (example: "cm[42].visible:updated")
 *  - If the element has an ID attribute, the general event with id (example: "cm[42]:updated")
 *
 * The proxied variable will throw an error if it is altered when the state manager is locked.
 *
 * @param {string} name the variable name used for identify triggered actions
 * @param {StateManager} statemanager the state manager object
 * @param {boolean} proxyvalues if new values must be proxied (used only at state root level)
 * @returns {object} an object with all the handler functions.
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
         * Set trap to trigger events when the state changes.
         *
         * @param {object} obj the source object (not proxied)
         * @param {string} prop the attribute to set
         * @param {*} value the value to save
         * @param {*} receiver the proxied element to be attached to events
         * @returns {boolean} if the value is set
         */
        set: function(obj, prop, value, receiver) {

            // Only mutations should be able to set state values.
            if (this.statemanager.readonly) {
                throw new Error(`State locked. Use mutations to change ${prop} value in ${this.name}.`);
            }

            // Check any data change.
            if (JSON.stringify(obj[prop]) === JSON.stringify(value)) {
                return true;
            }

            let action = (obj[prop] !== undefined) ? 'updated' : 'created';

            // Proxy value if necessary (used at state root level).
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

            this.statemanager.registerStateAction(this.name, prop, action, receiver);

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
            if (this.statemanager.readonly) {
                throw new Error(`State locked. Use mutations to delete ${prop} in ${this.name}.`);
            }
            if (prop in obj) {

                delete obj[prop];

                this.statemanager.registerStateAction(this.name, prop, 'deleted', obj);
            }
            return true;
        },
    };
};

/**
 * Class to add event trigger into the JS Map class.
 *
 * When the state has a list of objects (with IDs) it will be converted into a StateMap.
 * StateMap is used in the same way as a regular JS map. Because all elements have an
 * id attribute, it has some specific methods:
 *  - add: a convenient method to add an element without specifying the key (ID will be used as a key).
 *  - loadValues: to add many elements at once wihout specifying keys (IDs will be used).
 *
 * Apart, the main difference between regular Map and MapState is that this one triggers events
 * every time an element is added or removed from the list:
 *  - A specific element updated, created or deleted (example: "cm[42]:created")
 *  - A generic list updated, created or deleted (example: "cm:created")
 */
class StateMap extends Map {

    /**
     * Create a reactive Map.
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
     * Each value needs it's own id attribute. Objects without id will be rejected.
     * The function will throw an error if the value id and the key are not the same.
     *
     * @param {*} key the key to store
     * @param {*} value the value to store
     * @returns {Map} the resulting Map object
     */
    set(key, value) {
        // Only mutations should be able to set state values.
        if (this.statemanager.readonly) {
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

        this.statemanager.registerStateAction(this.name, null, action, super.get(key));

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
            throw Error('State lists elements must contain at least an id attribute');
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
        if (this.statemanager.readonly) {
            throw new Error(`State locked. Use mutations to change ${key} value in ${this.name}.`);
        }

        const previous = super.get(key);

        const result = super.delete(key);
        if (!result) {
            return result;
        }

        this.statemanager.registerStateAction(this.name, null, 'deleted', previous);

        return result;
    }

    /**
     * Return a suitable structure for JSON conversion.
     *
     * This function is needed because new values are compared in JSON. StateMap has Private
     * attributes which cannot be stringified (like this.statemanager which will produce an
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
     * Insert a full list of values using the id attributes as keys.
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
            let key = data.id;
            let newvalue = new Proxy(data, handler(this.name, this.statemanager));
            this.set(key, newvalue);
        });
        return this;
    }
}
