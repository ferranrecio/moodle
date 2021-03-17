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
 * Generic reactive module used in the course editor.
 *
 * TODO: This module will be mover to core\reactive once the new editor dev starts.
 *
 * @module     core_course/editor
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import StateManager from 'core_course/local/editor/statemanager';

/**
* Set up general reactive class.
*
* The reactive class is responsible for contining the main store,
* the complete components list that can be used and initialize the main
* component.
*
* @return {void}
*/
const Reactive = class {

    /**
     * Create a basic reactive manager.
     *
     * The instance description can provide information on how the reactive instance will interact with its
     * components. The properties are:
     *  - eventname: the custom event name used for state changed
     *  - eventdispatch: the event dispatch function
     *  - target (optional): the target of the event dispatch. If not passed a fake element will be created
     *  - mutations (optional): an object with state mutations functions
     *
     * @param {object} description reactive manager description.
     */
    constructor(description) {

        if (description.eventname === undefined || description.eventdispatch === undefined) {
            throw new Error(`Reactivity event required`);
        }

        // To prevent every component from replicating the same eventlistener, each reactive
        // instance has its own element anchor to propagate state changes internally.
        // By default the module will create a fake DOM element to target custom evenets but
        // if all reactive components is constrait to a single element, this can be passed as
        // target in the description.
        this.target = description.target ?? document.createTextNode(null);

        this.eventname = description.eventname;
        this.eventdispatch = description.eventdispatch;

        this.statemanager = new StateManager(this.eventdispatch, this.target);
        this.watchers = new Map([]);
        this.components = new Set([]);

        // Mutations can be overridden using setMutations method.
        this.mutations = description.mutations ?? {};

        // Register the event to alert watchers when specific state change happens.
        this.target.addEventListener(this.eventname, this.callWatchersHandler.bind(this));
    }

    /**
     * State changed listener.
     *
     * This function take any change in the course state and send it to the proper watchers.
     * Any AMD module is free to register as state change listener at a document level,
     * but components can register as watchers to listen to specific state changes directly.
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
    * Create the state manager and set the initial state.
    *
    * @param {object} statedata the initial state data.
    */
    setInitialState(statedata) {
        this.statemanager.setInitialState(statedata);
    }

    /**
    * Set up the mutation manager.
    *
    * Note new mutations will be added to the existing ones.
    *
    * @method addMutations
    * @param {Object} manager the new mutation manager
    */
    addMutations(manager) {
        for (const mutation in manager) {
            if (manager.hasOwnProperty(mutation)) {
                this.mutations[mutation] = manager[mutation];
            }
        }
    }

    /**
    * Return the current state
    *
    * @method getState
    * @return {object}
    */
    getState() {
        return this.statemanager.state;
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
    * @method registerComponent
    * @param {Object} component the new component
    */
    registerComponent(component) {

        const componentname = component.name ?? 'Unkown component';

        // Components can be registered only ones.
        if (this.components.has(component)) {
            return;
        }

        // Register watchers.
        let handlers = [];
        if (component.getWatchers !== undefined) {
            handlers = component.getWatchers();
        }
        handlers.forEach(({watch, handler}) => {

            if (watch === undefined) {
                throw new Error(`Missing watch attribute in ${componentname} watcher`);
            }
            if (handler === undefined) {
                throw new Error(`Missing handler for watcher ${watch} in ${componentname}`);
            }

            // The state manager triggers a general "state changed" event at a document level. However,
            // for the internal watchers, each component can listen to specific state changed custom events
            // in the target element. This way we can use the native event loop wihtout colliding with other
            // reactive instances.
            this.target.addEventListener(watch, (event) => {
                handler.apply(component, [event.detail]);
            });
        });

        // Register state ready function. There's the possibility a component is registered after the initial state
        // is loaded. For those cases we have a state promise to handle this specific state change.
        if (component.stateReady !== undefined) {
            this.statemanager.getInitialPromise()
                .then(component.stateReady.bind(component))
                .catch(reason => {
                    log.error(`Initial state in ${componentname} rejected due to: ${reason}`);
                });
            return;
        }

        this.components.add(component);
    }

    /**
    * Dispatch a change in the state.
    *
    * @method dispatch
    * @param {string} actionname the action name (usually the mutation name)
    * @param {Object} data the mutation data
    */
    dispatch(...args) {
        let actionname, params;
        [actionname, ...params] = args;
        if (this.mutations[actionname] === undefined) {
            throw new Error(`Unkown ${actionname} mutation`);
        }
        try {
            const mutationfunction = this.mutations[actionname];
            mutationfunction.apply(this.mutations, [this.statemanager, ...params]);
        } catch (error) {
            log.error(error);
            throw new Error(`Exception dispatching ${actionname}`);
        }
    }
};

export default Reactive;
