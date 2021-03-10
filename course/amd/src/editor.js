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
 * Main course editor module
 *
 * @module     core_course/editor
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import defaultmutations from 'core_course/local/editor/mutations';
import StateManager from 'core_course/local/editor/statemanager';
import ajax from 'core/ajax';
import events from 'core_course/events';

let mutations;
let statemanager;
let components = new Set([]);
let watchers = new Map([]);

/**
* Set up the course editor when the page is ready.
*
* @method init
* @param {int} courseid course id
*/
async function init(courseid) {

    // Mutations can be overridden by the format plugin
    // but we need the default one at least.
    mutations = defaultmutations;

    // Register as event listener.
    document.addEventListener(events.statechanged, callWatchersHandler);

    try {
        // Async load the initial state.
        const jsonstate = await ajax.call([{
            methodname: 'core_course_get_state',
            args: {courseid}
        }])[0];
        const statedata = JSON.parse(jsonstate);
        statemanager = new StateManager(dispatchStateChangedEvent);
        statemanager.setInitialState(statedata);
    } catch (error) {
        log.error("EXCEPTION RAISED WHILE INIT COURSE EDITOR");
        log.error(error);
    }
}

/**
 * This function will be moved to core_course/events module
 * when the file is migrated to the new JS events structure proposed in MDL-70990.
 *
 * @method dispatchStateChangedEvent
 * @param {string} action the action done
 * @param {object} state the full state
 * @param {object} element the modified element
 */
function dispatchStateChangedEvent(action, state, element) {
    // Dispatch a custom event in case any component wants to listen.
    document.dispatchEvent(new CustomEvent(events.statechanged, {
        bubbles: true,
        detail: {action, state, element},
    }));
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
function callWatchersHandler(event) {
    const action = event.detail.action;
    // Execute any registered component watchers.
    if (watchers.has(action)) {
        watchers.get(action).forEach((watcher) => {
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
* Set up the mutation manager.
*
* @method setMutations
* @param {Object} manager the new mutation manager
*/
function setMutations(manager) {
    mutations = manager;
}

/**
* Notify a communication error during a mutation.
*
* @method notifyError
* @param {string} message the error message
*/
function notifyError(message) {
    // Not done yet.
    log.error(message);
}

/**
* Return the current state
*
* @method getState
* @return {object}
*/
function getState() {
    return statemanager.state;
}

/**
* Dispatch a change in the state.
*
* @method dispatch
* @param {string} actionname the action name (usually the mutation name)
* @param {Object} data the mutation data
*/
function dispatch() {
    let actionname, args;
    [actionname, ...args] = arguments;
    try {
        const mutationfunction = mutations[actionname] ?? defaultmutations[actionname];
        mutationfunction.apply(mutations, [statemanager, ...args]);
    } catch (error) {
        log.error(error);
    }
}

/**
* Register a new component.
*
* @method registerComponent
* @param {Object} component the new component
*/
function registerComponent(component) {
    // Register watchers.
    const watch = component.getEventHandlers();
    for (let key in watch) {
        if (watch.hasOwnProperty(key)) {
            let actionwathers = watchers.get(key) ?? [];
            actionwathers.push({
                name: component.name ?? 'Unkown component',
                handler: watch[key],
            });
            watchers.set(key, actionwathers);
        }
    }
    components.add(component);
    // There's the possibility a component is registered after the initial state
    // is loaded. For those cases the subcription to state_loaded
    // will not work so we execute this state manually.
    if (statemanager !== undefined && statemanager.state !== undefined) {
        if (watch.state_loaded !== undefined) {
            watch.state_loaded({state: statemanager.state});
        }
    }
}

export default {
    init,
    setMutations,
    notifyError,
    getState,
    dispatch,
    registerComponent,
};
