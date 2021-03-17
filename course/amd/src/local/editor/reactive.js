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
     * @param {object} description reactive manager description.
     */
    constructor(description) {

        if (description.name === undefined) {
            throw new Error(`Reactivity name required`);
        }

        if (description.eventname === undefined || description.eventdispatch === undefined) {
            throw new Error(`Reactivity event required`);
        }

        this.name = description.name;
        this.eventname = description.eventname;
        this.eventdispatch = description.eventdispatch;

        this.statemanager = new StateManager(this.eventdispatch);
        this.watchers = new Map([]);
        this.components = new Set([]);

        // Mutations can be overridden using setMutations method.
        this.mutations = description.mutations ?? {};

        document.addEventListener(this.eventname, this.callWatchersHandler.bind(this));
    }

    /**
     * State changed listener.
     *
     * This function take any change in the course state and send it to the proper
     * watchers. Each component is free to register as state change listener,
     * but we use a regular loop to avoid redundant code in all components
     * and prevent unnecessary browser memory usage.
     *
     * @param {CustomEvent} event
     */
    callWatchersHandler(event) {
        const action = event.detail.action;
        // Execute any registered component watchers.
        if (this.watchers.has(action)) {
            this.watchers.get(action).forEach((watcher) => {
                try {
                    log.debug(`Executing "${watcher.name}" ${action} watcher`);
                    watcher.handler(event.detail);
                } catch (error) {
                    log.error(`Component "${watcher.name}" error while watching ${action}`);
                    log.error(error);
                }
            });
        }
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
    * @method registerComponent
    * @param {Object} component the new component
    */
    registerComponent(component) {
        // Register watchers.
        const handlers = component.getEventHandlers();
        handlers.forEach(({watch, handler}) => {

            const componentname = component.name ?? 'Unkown component';

            if (watch === undefined) {
                throw new Error(`Empty watcher in ${componentname}`);
            }
            if (handler === undefined) {
                throw new Error(`Empty handler for watcher ${watch} in ${componentname}`);
            }

            let actionwathers = this.watchers.get(watch) ?? [];
            actionwathers.push({
                name: componentname,
                handler: handler,
            });
            this.watchers.set(watch, actionwathers);

            // There's the possibility a component is registered after the initial state
            // is loaded. For those cases the subcription to state_loaded
            // will not work so we execute this state manually.
            if (watch == 'state:loaded' && this.statemanager !== undefined && this.statemanager.state !== undefined) {
                handler({state: this.statemanager.state});
            }
        });
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
