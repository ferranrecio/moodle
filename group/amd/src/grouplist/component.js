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
 * Defgault reactive component.
 *
 * @module     core_group/grouplist/store
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import {debounce} from 'core/utils';
import datahandler from 'core_group/grouplist/datahandler';


/**
 * Generate a valir component from definition.
 *
 * @return {void}
 */
export const createComponent = function (definition, extradata) {
    // We save all the required strings into the debounced string loader.
    // TODO: the rest of the component logic should wait until all strings are loaded.

    if (extradata === undefined) {
        extradata = {};
    }

    if (definition.data == undefined) {
        definition.data = () => {};
    }

    if (typeof definition.data !== 'function') {
        throw new TypeError('Data should be a function');
    }

    if (definition.name == undefined) {
        throw new TypeError('All components require a name property');
    }

    if (definition.$reactive == undefined) {
        throw new TypeError('core_group/grouplist/reactive to register new components');
    }

    // Some methods require a small debounce to prevent infinite chains.
    const refresh = debounce(definition.refresh ?? defaultRefresh, 50);

    const component = datahandler.proxyComponent({
        name: definition.name,
        $el: {},
        $reactive : definition.$reactive,
        $store : definition.store ?? {},
        // $definition: definition,
        // Component render and refresh.
        template : definition.template ?? '',
        domstructure : definition.domstructure,
        render : definition.render ?? defaultRenderer,
        refresh: refresh,
        // Internal lists.
        subcomponents: [],
        binds: { child: {}, binds: new Set([])},
        // dirty: {}, // TODO move this to a proxy.
        onOutput: [], // Array of methods to execute on output.
        // Stored attributes.
        props : definition.props ?? {},
        data : Object.assign(extradata, definition.data() ?? {}),
        computed : definition.computed ?? {},
        // Other helpers.
        watchers : definition.watchers ?? {},
        methods : definition.methods ?? {},
        getDataFromPath : getDataFromPath,
        updateDataFromPath : updateDataFromPath,
        // Binding methods.
        propagateChanges : definition.propagateChanges ?? defaultPropagateChanges,
        addBind : definition.addBind ?? defaultAddBind,
        processAllBinds : definition.processAllBinds ?? processAllBinds,
        processBinds : definition.processBinds ?? processBinds,
        // Component lifecycle.
        mounted : definition.mounted ?? defaultMounted,
        // markDirty: markDirty,
        finishiOutput: finishiOutput,
    });

    // Add computed props.
    // datahandler.addPComputedProxis(component);

    // Generate the element (bindings will be applied later).
    component.$el = component.render();
    // Index all bindings and subcomponents.
    parseOutput(component);

    // Each DOM object is resposible of it's own data and it is stored there.
    // Maybe this is unnecessary.
    component.$el.component = component;

    // Now we have the element, we can invoke the async component methods before display it.
    Promise.resolve(component.mounted()).then(() => {
        component.finishiOutput();
        // Replace the original DOM element by the new one.
        definition.el.parentNode.replaceChild(component.$el, definition.el);
        log.debug(`Component ${component.name} rendered`);
    });

    return component;
};

// Translate a string path to a specific data path.
const getDataFromPath = function(path) {
    // Paths can include the root node or not. Because this is a component method
    // we don't need to care about that but we check if it is correct to avoid problems.
    path = datahandler.getInternalPath(path, this.data);
    let patharray = path.split('.');
    return findNodeFromPath(patharray, this.data);
};

// This is a generic method to get data from complex objects using a path array.
const findNodeFromPath = function(patharray, data) {
    if (patharray.length == 0) {
        return data;
    }
    const first = patharray.shift();
    if (data[first] === undefined) {
        return undefined;
    }
    return findNodeFromPath(patharray, data[first]);
};

/**
 * Returns an element to be used ar initial component.
 */
const defaultRenderer = function() {

    if (this.domstructure !== undefined) {
        return this.domstructure;
    }
    let temp = document.createElement('template');
    // If the component hass a static template.
    if (this.template != '') {
        // Trim is necessary to avoid empty nodes in the structure.
        temp.innerHTML = this.template.trim();
    } else {
        temp.innerHTML = '<div>Invalid component!</div>';
    }
    // If the component has a mustache template (soon).
    return temp.content.firstChild;
};

const defaultRefresh = function(prop, value) {
    if (prop == undefined) {
        // processAllBinds(this);
        shutup(value);
        return;
    }
    // processBinds(this, prop, value);
};
//TODO delete this method
const shutup = function(val) {
    return val;
};

const defaultMounted = function() {
    // The default Mount does not do anything.
    return true;
};


// Binds is a structure of reactive data observer. Its a tree where each node represents a set of
// observer methods. When some change is performed in the data tree the emit change will look for the
// equivalent node of that tree and will execute all methods of the set.
const defaultPropagateChanges = function(changes) {
    // Let bindings do their job.
    this.processBinds(changes);
    // Propagate changes to subcomponents.
    this.subcomponents.forEach((subcomponent) => {
        subcomponent.propagateChanges(changes);
    });
    return true;
};

/**
 * Binds a watcher into a specific data path.
 *
 * Binds, like watchers, can have several methods:
 *  - update(value) will recieve the node value when a change is emitted.
 *  - delete() executed when the binded data is eliminated
 *  - init(value) this will be executed only once when the object is ready to be rendered.
 *
 * @param String path: the internal path (root node will be added if not present)
 * @bind Object the bind object.
 */
const defaultAddBind = function(path, bind) {
    // Paths can include the root node or not. Because this is a component method
    // we don't need to care about that but we check if it is correct to avoid problems.
    path = datahandler.getInternalPath(path, this.data);
    let patharray = path.split('.');
    bindObjectToPath(patharray, this.binds, bind);

    if (bind.init !== undefined) {
        this.onOutput.push(bind.init);
    }
};

const bindObjectToPath = function(patharray, node, bind) {
    if (patharray.length == 0) {
        node.binds.add(bind);
        return;
    }
    const first = patharray.shift();
    if (node.child[first] === undefined) {
        // log.debug(data);
        // throw new TypeError(`Cannot find ${first} to bind data.`);
        node.child[first] = { child: {}, binds: new Set([])};
    }
    return bindObjectToPath(patharray, node.child[first], bind);
};

/**
 * Process a specific property bindings from a component.
 *
 * @param String the property name
 * @param component the reactive component
 */
const processAllBinds = function () {
    // The default action is "update". If we send the full data will execute all
    // possible "updates"
    executeBinds(this.data, this.binds, this.data);
};

const processBinds = function (changes) {
    executeBinds(changes, this.binds, this.data);
};

const executeBinds = function (changenode, bindnode, datanode) {
    // If change node is undefined we don't need to continue any further.
    if (changenode === undefined) {
        return;
    }
    // Detect change type.
    const action = changenode.$action ?? 'update';
    // Process all binding of that node (if any).
    [...bindnode.binds].forEach((binder) => {
        if (binder[action] !== undefined) {
            binder[action](datanode);
        }
    });
    // Propagate changes to sons.
    Object.keys(bindnode.child).forEach((childname) => {
        const bindchild = bindnode.child[childname];
        if (datanode !== undefined) {
            executeBinds(changenode[childname], bindchild, datanode[childname]);
        } else {
            // there are some situations where a binding has no data:
            // - When data is deleted.
            // - When the data is binded to a value it will be there eventually.
            executeBinds(changenode[childname], bindchild);
        }

    });
};

// Other.

const finishiOutput = function() {
    this.onOutput.forEach((initmethod) => {
        initmethod();
    });
    this.processAllBinds();
};

// Reactive DOM!
// TODO: move this to another module and generate a single method wrapper that gets:
// - domelement.
// - reactive data storage.
// - Methods for events.
// - blocks / slots

/**
 * Generate bindings and subcomponents.
 *
 * One key point of reactivity is not to update all page every time but only the affected
 * elements. Once the component has loaded the template, this method generate all
 * bindings, models, subcomponents and if affected by refreshes.
 */
const parseOutput = function(component) {
    // Ifs and Else.
    parseIf(component);
    parseElse(component);
    // For.
    // Remainign subcomponents.
    parseSubcomponents(component);
    // Direct bindings on internal elements.
    parseBinds(component);
    // Models.
    parseModel(component);
    // Events.
    parseEvents(component);
};

const parseSubcomponents = function(component) {
    const subcomponents = component.$el.querySelectorAll('component');
    subcomponents.forEach((subcomponent) => {
        const subc = component.$reactive.renderComponent(component, subcomponent);
        component.subcomponents.push(subc);
    });
};

const parseBinds = function(component) {
    parseBindsText(component);
    parseBindsHTML(component);
    // Full binds.
    // parseFullBinds(component);
};

const parseEvents = function(component) {
    parseOnClick(component);
    // Full binds.
    // parseFullBinds(component);
};

const parseIf = function(component) {
    const binds = component.$el.querySelectorAll('[data-if]');
    binds.forEach((bind) => {
        const name = bind.dataset.if;
        const original = bind.style.display;
        component.addBind(name, {
            update: (value) => {
                if (isEmpty(value)) {
                    bind.style.display = 'none';
                } else {
                    bind.style.display = original;
                }
            },
        });
    });
};

const parseElse = function(component) {
    const binds = component.$el.querySelectorAll('[data-else]');
    binds.forEach((bind) => {
        const name = bind.dataset.else;
        const original = bind.style.display;
        component.addBind(name, {
            update: (value) => {
                if (isEmpty(value)) {
                    bind.style.display = original;
                } else {
                    bind.style.display = 'none';
                }
            },
        });
    });
};

// TODO: move to util.js.
/**
 * This is a equivalent of PHP empty in JS but only for objects.
 *
 * @param mixed value the variable to check.
 */
const isEmpty = (value) => {
    if (value === undefined) {
        return true;
    }
    if (typeof (value) == 'function') {
        return false;
    }
    if (value == '' || value == false || value == 0 || value === null || value.length === 0){
        return true;
    }
    if (value.valueOf !== undefined) {
        const primitive = value.valueOf();
        if (primitive == '' || primitive == false || primitive == 0 || primitive === null || primitive.length === 0){
            return true;
        }
        return false;
    }

    if (typeof (value) == "object") {
        let r = true;
        for (let f in value) {
            r = (f !== null);
        }
        return r;
    }
    return false;
};

const parseBindsText = function(component) {
    const binds = component.$el.querySelectorAll('[data-bind-text]');
    binds.forEach((bind) => {
        const name = bind.dataset.bindText;
        // const data = component.getDataFromPath(name);
        component.addBind(name, {
            update: (value) => {
                if (bind.innerText != value) {
                    bind.innerText = value;
                }
            },
        });
    });
};

const parseBindsHTML = function(component) {
    const binds = component.$el.querySelectorAll('[data-bind-html]');
    binds.forEach((bind) => {
        const name = bind.dataset.bindHtml;
        // const data = component.getDataFromPath(name);
        component.addBind(name, {
            update: (value) => {
                if (bind.innerHTML != value) {
                    bind.innerHTML = value;
                }
            },
        });
    });
};

// TODO: for now this is limited to input values.
const parseModel = function(component) {
    const binds = component.$el.querySelectorAll('input[data-model]');
    binds.forEach((bind) => {
        const name = bind.dataset.model;
        component.addBind(name, {
            update: (value) => {
                if (bind.value != value && document.activeElement !== bind) {
                    bind.value = value;
                }
            },
        });
        // Add events to the input element.
        bind.addEventListener(
          'keyup',
          () => {
            const data = component.getDataFromPath(name);
            if (bind.value != data.valueOf()) {
                component.updateDataFromPath(name, bind.value);
            }
          }
        );
    });
};

const updateDataFromPath = function(path, value) {
    path = datahandler.getInternalPath(path, this.data);
    let patharray = path.split('.');
    const propname = patharray.pop();
    let parent = findNodeFromPath(patharray, this.data);
    parent[propname] = value;
};


/**
 * Generate fake components for all full bindings.
 *
 * Full binds is seems like a regular bind-text or bind-html but it is not.
 * a full bind is in reality to go deeper in the component data and it is, in fact,
 * a fake subcomponent that will react to their own version of the data.
 *
 * @param component a component structure.
 */
const parseFullBinds = function(component) {
    const binds = component.$el.querySelectorAll('[data-bind]');
    binds.forEach((bind) => {
        const name = bind.dataset.bind;
        // const data = component.getDataFromPath(name);
        // Generate a fake component to replace the current node.
        const fakecompoment = generateFakeComponent(bind, component, component.data[name]);
        component.subcomponents.push(fakecompoment);
        // Add this event as a bind to progate changes.
        component.addBind(name, {
            update: (value) => {
                if (fakecompoment.data[name] !== value) {
                    fakecompoment.data[name] = value;
                }
            },
        });
    });
};

const generateFakeComponent = function(elem, component, extradata) {
    // We don't want the fakecomponent to interfere with the main component so we
    // remove from the presentation until it is reactive by itself.
    const dummie = document.createElement('template');
    elem.parentNode.replaceChild(dummie, elem);
    // const data = Object.assign(extradata, component.data);
    // const data = Object.create(component.data);
    const data = {};
    Object.assign(data, component.data);
    Object.assign(data, extradata);

    const result = createComponent({
        el: dummie,
        name: Date.now(),
        props : component.props,
        domstructure : elem,
        data : function() {
            return extradata;
        },
        // Copy some elements form component definition.
        $reactive : component.$reactive,
        $store : component.$store,
        render: component.render,
        computed: component.computed,
        methods: component.methods,
        // Fake components does not have lifecycle.
        mounted : defaultMounted,
    });
    return result;
};

const parseOnClick = function(component) {
    const binds = component.$el.querySelectorAll('[data-onclick]');
    binds.forEach((bind) => {
        const name = bind.dataset.onclick;
        const method = component.methods[name].bind(component);
        bind.addEventListener("click", (e) => {method(e);});
    });
};
