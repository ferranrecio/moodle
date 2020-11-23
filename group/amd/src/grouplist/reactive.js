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
 * Course groups store.
 *
 * @module     core_group/grouplist/store
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {createComponent} from 'core_group/grouplist/component';
// import datahandler from 'core_group/grouplist/datahandler';
import {debounce} from 'core/utils';
import log from 'core/log';


const reactives = [];

/**
 * Set up general reactive class.
 *
 * The reactive class is responsible for contining the main store,
 * the complete components list that can be used and initialize the main
 * component.
 *
 * @return {void}
 */
const reactive = class {

    constructor(definition) {
        // Component definitions. This is used to create new components.
        this.maindefinition = definition;
        this.definitions = {};
        this.store = definition.store ?? {};
        // This array contains the unique name of every component. The name list
        // is used on every component rendering and we want it as fast as posible.
        this.componentnames = new Set([]);
        this.components = [];
    }

    component(definition) {
        this.definitions[definition.name] = definition;
        this.componentnames.add(definition.name);
    }

    render() {
        // Generate the main component.
        this.maincomponent = createComponent(
            Object.assign(this.maindefinition, {name: 'appmain', $reactive: this}),
            {}
        );
        reactives.push(this.maincomponent);
    }

    renderComponent(parentcomponent, elem) {
        // Check for base definition.
        const name = elem.dataset.name;
        if (this.definitions[name] == undefined) {
            throw new TypeError(`Unkown component ${name}`);
        }
        const definition = Object.create(this.definitions[name]);
        definition.$reactive = this;
        definition.el = elem;
        const extradata = {};
        // Load props.
        for (const key in definition.props) {
            const bindname = elem.dataset[key];
            if (bindname !== undefined) {
                extradata[key] = parentcomponent.getDataFromPath(bindname) ?? definition.props[key];
            } else {
                extradata[key] = definition.props[key];
            }
        }

        // Render component.
        const result = createComponent(definition, extradata);
        // Add binds.
        for (const key in definition.props) {
            const bindname = elem.dataset[key];
            if (bindname !== undefined) {
                parentcomponent.addBind(bindname, {
                    update: (value) => {
                        result.data[key] = value;
                    },
                });
            }
        }
        this.components.push(result);
        reactives.push(result);
        return result;
    }

    getComponents() {
        return this.components;
    }

    getStore() {
        return this.store;
    }

    getMainComponent() {
        return this.maincomponent;
    }
};

export default reactive;

// This structure contains all changes pending to propagate.
let pendingchanges = {};

export const emitChange = function(path, action) {
    // Convert path to structure.
    let patharray = path.split('.');
    action = action ?? 'update';
    queueChange(patharray, action, pendingchanges);
    // Send the order to propagate changes.
    propagateChanges();
};


const queueChange = function(patharray, action, node) {
    if (patharray.length == 0) {
        node.$action = action;
        return;
    }
    const first = patharray.shift();
    if (node[first] === undefined) {
        // We don't need to add any action because the default action is update.
        node[first] = {};
    }
    return queueChange(patharray, action, node[first]);
};

// Propagate changes to all components.
const instantPropagateChanges = function() {
    log.debug("Sending changes to the observers");
    // Safe the current changes and clean the list.
    const changestosend = pendingchanges;
    pendingchanges = {};
    // Check the storages which need to propagate to all reactives.
    Object.keys(changestosend).forEach((rootnode) => {
        // Check which reactives uses this storage.
        reactives.forEach((component) => {
            if (component.data.$id == rootnode) {
                component.propagateChanges(changestosend[rootnode]);
            }
        });
    });
};

const propagateChanges = debounce(instantPropagateChanges, 10);
