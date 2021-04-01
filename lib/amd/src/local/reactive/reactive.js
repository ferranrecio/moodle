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
 * A generic single state reactive module.
 *
 * @module     core/reactive/local/reactive/reactive
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import StateManager from 'core/local/reactive/statemanager';

/**
 * Set up general reactive class to create a single state application with components.
 *
 * The reactive class is used for registering new UI components and manage the access to the state values
 * and mutations.
 *
 * When a new reactive instance is created, it will contain an empty state and and empty mutations
 * lists. When the state data is ready, the initial state can be loaded using the "setInitialState"
 * method. This will protect the state from writing and will trigger all the components "stateReady"
 * methods.
 *
 * State can only be altered by mutations. To replace all the mutations with a specific class,
 * use "setMutations" method. If you need to just add some new mutation methods, use "addMutations".
 *
 * To register new components into a reactive instance, use "registerComponent".
 *
 * Inside a component, use "dispatch" to invoke a mutation on the state (components can only access
 * the state in read only mode).
 */
export default class Reactive {

    /**
     * Create a basic reactive manager.
     *
     * The instance description can provide information on how the reactive instance will interact with its
     * components. The properties are:
     *  - eventname: the custom event name used for state changed events (mandatory)
     *  - eventdispatch: the state update event dispatch function (mandatory)
     *  - target (optional): the target of the event dispatch. If not passed a fake element will be created
     *  - mutations (optional): an object with state mutations functions
     *  - state (optional): if your state is not async loaded, you can pass directly on creation.
     *                      Note that passing state on creation will initialize the state, this means
     *                      setInitialState will throw an exception because the state is already defined.
     *
     * @param {object} description reactive manager description.
     */
    constructor(description) {

        if (description.eventname === undefined || description.eventdispatch === undefined) {
            throw new Error(`Reactivity event required`);
        }

        // Each reactive instance has its own element anchor to propagate state changes internally.
        // By default the module will create a fake DOM element to target custom events but
        // if all reactive components is constrait to a single element, this can be passed as
        // target in the description.
        this.target = description.target ?? document.createTextNode(null);

        this.eventname = description.eventname;
        this.eventdispatch = description.eventdispatch;

        // State manager is responsible for dispatch state change events when a mutation happens.
        this.statemanager = new StateManager(this.eventdispatch, this.target);

        // An internal registry of watchers and components.
        this.watchers = new Map([]);
        this.components = new Set([]);

        // Mutations can be overridden later using setMutations method.
        this.mutations = description.mutations ?? {};

        // Register the event to alert watchers when specific state change happens.
        this.target.addEventListener(this.eventname, this.callWatchersHandler.bind(this));

        // Set initial state if we already have it.
        if (description.state !== undefined) {
            this.setInitialState(description.state);
        }
    }

    /**
     * State changed listener.
     *
     * This function take any state change and send it to the proper watchers.
     *
     * To prevent internal state changes from colliding with other reactive instances, only the
     * general "state changed" is triggered at document level. All the internal changes are
     * triggered at private target level without bubbling. This way any reactive instance can alert
     * only its own watchers.
     *
     * @param {CustomEvent} event
     */
    callWatchersHandler(event) {
        // Execute any registered component watchers.
        this.target.dispatchEvent(new CustomEvent(event.detail.action, {
            bubbles: false,
            detail: event.detail,
        }));
    }

    /**
    * Set the initial state.
    *
    * @param {object} statedata the initial state data.
    */
    setInitialState(statedata) {
        this.statemanager.setInitialState(statedata);
    }

    /**
    * Add individual functions to the mutations.
    *
    * Note new mutations will be added to the existing ones. To replace the full mutation
    * object with a new one, use setMutations method.
    *
    * @method addMutations
    * @param {Object} newfunctions an object with new mutation functions.
    */
    addMutations(newfunctions) {
        for (const mutation in newfunctions) {
            if (newfunctions.hasOwnProperty(mutation) && typeof newfunctions[mutation] === 'function') {
                this.mutations[mutation] = newfunctions[mutation].bind(newfunctions);
            }
        }
    }

    /**
     * Replace the current mutations with a new object.
     *
     * This method is designed to override the full mutations class, for example by extending
     * the original one. To add some individual mutations, use addMutations instead.
     *
     * @param {object} manager the new mutations intance
     */
    setMutations(manager) {
        this.mutations = manager;
    }

    /**
    * Return the current state.
    *
    * @method getState
    * @return {object}
    */
    getState() {
        return this.statemanager.state;
    }

    /**
    * Return the initial state promise.
    *
    * Typically, components do not require to use this promise because registerComponent
    * will trigger their stateReady method automatically. But it could be useful for complex
    * components that require to combine state, template and string loadings.
    *
    * @method getState
    * @return {Promise}
    */
    getInitialStatePromise() {
        return this.statemanager.getInitialPromise();
    }

    /**
    * Register a new component.
    *
    * Component can provide some optional functions to the reactive module:
    * - getWatchers: returns an array of watchers
    * - stateReady: a method to call when the initial state is loaded
    *
    * It can also provide some optional attributes:
    * - name: the component name (default value: "Unkown component") to customize debug messages.
    *
    * The method will also use dispatchRegistrationSuccess and dispatchRegistrationFail. Those
    * are BaseComponent methods to inform parent components of the registration status.
    * Components should not override those methods.
    *
    * @method registerComponent
    * @param {object} component the new component
    * @return {object} the registered component
    */
    registerComponent(component) {

        // Component name is an optional attribute to customize debug messages.
        const componentname = component.name ?? 'Unkown component';

        // Components can provide special methods to communicate registration to parent components.
        let dispatchSuccess = () => {
            return;
        };
        let dispatchFail = dispatchSuccess;
        if (component.dispatchRegistrationSuccess !== undefined) {
            dispatchSuccess = component.dispatchRegistrationSuccess.bind(component);
        }
        if (component.dispatchRegistrationFail !== undefined) {
            dispatchFail = component.dispatchRegistrationFail.bind(component);
        }

        // Components can be registered only one time.
        if (this.components.has(component)) {
            dispatchSuccess();
            return component;
        }

        // Keep track of the event listeners.
        let listeners = [];

        // Register watchers.
        let handlers = [];
        if (component.getWatchers !== undefined) {
            handlers = component.getWatchers();
        }
        handlers.forEach(({watch, handler}) => {

            if (watch === undefined) {
                dispatchFail();
                throw new Error(`Missing watch attribute in ${componentname} watcher`);
            }
            if (handler === undefined) {
                dispatchFail();
                throw new Error(`Missing handler for watcher ${watch} in ${componentname}`);
            }

            const listener = (event) => {
                handler.apply(component, [event.detail]);
            };

            // Save the listener information in case the component must be unregistered later.
            listeners.push({target: this.target, watch, listener});

            // The state manager triggers a general "state changed" event at a document level. However,
            // for the internal watchers, each component can listen to specific state changed custom events
            // in the target element. This way we can use the native event loop without colliding with other
            // reactive instances.
            this.target.addEventListener(watch, listener);
        });

        // Register state ready function. There's the possibility a component is registered after the initial state
        // is loaded. For those cases we have a state promise to handle this specific state change.
        if (component.stateReady !== undefined) {
            this.getInitialStatePromise()
                .then(component.stateReady.bind(component))
                .catch(reason => {
                    log.error(`Initial state in ${componentname} rejected due to: ${reason}`);
                    log.error(reason);
                });
        }

        // Save unregister data.
        this.watchers.set(component, listeners);
        this.components.add(component);

        dispatchSuccess();
        return component;
    }

    /**
     * Unregister a component and its watchers.
     *
     * @param {object} component the object instance to unregister
     * @returns {object} the deleted component
     */
    unregisterComponent(component) {
        if (!this.components.has(component)) {
            return component;
        }

        this.components.delete(component);

        // Remove event listeners.
        const listeners = this.watchers.get(component);
        if (listeners === undefined) {
            return component;
        }

        listeners.forEach(({target, watch, listener}) => {
            target.removeEventListener(watch, listener);
        });

        this.watchers.delete(component);

        return component;
    }

    /**
    * Dispatch a change in the state.
    *
    * This method is the only way for components to alter the state. Watchers will receive a
    * read only state to prevent illegal changes. If some user action require a state change, the
    * component should dispatch a mutation to trigger all the necessary logic to alter the state.
    *
    * @method dispatch
    * @param {string} actionname the action name (usually the mutation name)
    * @param {*} param any number of params the mutation needs.
    */
    dispatch(...args) {
        let actionname, params;
        [actionname, ...params] = args;
        if (typeof actionname !== 'string') {
            throw new Error(`Dispatch action name must be a string`);
        }
        // JS does not have private methods yet. However, we prevent any component from calling
        // a method starting with "_" because the most accepted convention for private methods.
        if (actionname.charAt(0) === '_') {
            throw new Error(`Illegal Private ${actionname} mutation method dispatch`);
        }
        if (this.mutations[actionname] === undefined) {
            throw new Error(`Unkown ${actionname} mutation`);
        }
        const mutationfunction = this.mutations[actionname];
        mutationfunction.apply(this.mutations, [this.statemanager, ...params]);
    }
}
