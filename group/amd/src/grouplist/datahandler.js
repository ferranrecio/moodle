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
 * Default reactive data handler.
 *
 * @module     core_group/grouplist/store
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// import log from 'core/log';
import reactive from 'core_group/grouplist/reactive';


export default {

    /** Object of data listeners indexed by property. */
    listeners: {},

    get: function(target, prop) {
        if (target.data[prop] !== undefined) {
            // this.addListener(prop, receiver);
            return target.data[prop];
        }
        if (target.methods[prop] !== undefined) {
            return target.methods[prop]();
        }
        return target[prop];
    },

    set: function(target, prop, value) {

        // this.init(target);

        if (target.computed[prop] != undefined) {
            throw new TypeError('Calculated attributes cannot be set yet.');
        }

        if (target.data[prop] !== undefined) {
            target.data[prop] = value;
        }
        target[prop] = value;

        return true;
    },
    // Not for now and possibly never.
    recalculate(target, prop) {
        if (target.computed[prop] !== undefined && (target.dirty[prop] !== undefined || target.data[prop] === undefined)) {
            // This line seems complicated but it is not. Computed attributes require to access
            // the same proxied variables than the rest of the methods but this is not possible because
            // we are currently calculating most of the data. Instead, we use a new proxied object.
            const newvalue = target.computed[prop].bind(new Proxy(target, this))();
            const oldvalue = target.data[prop];
            if (newvalue !== oldvalue) {
                target.data[prop] = newvalue;
            }
            if (target.dirty[prop] !== undefined) {
                delete target.dirty[prop];
            }
            return target.data[prop];
        }
        return;
    },

    addListener(prop, receiver) {
        if (receiver === this) {
            return;
        }
        if (this.listeners[prop] == undefined) {
            this.listeners[prop] = new Set();
        }
        this.listeners[prop].add(receiver);
    },

    proxyComponent(component) {
        // If we have no parent means it will generate a new root node ID.
        const data = ReactiveData(component.data);
        component.data = data;
        const result = new Proxy(component, this);
        return result;
    },

    // Translate a string path into an internal path, if necessary
    getInternalPath(path, data) {
        // Check we are in the right root node.
        if (path.startsWith(absolutepathchar) && !path.startsWith(data.$id)) {
            throw new TypeError(`Path ${path} is not from storage ${data.$id}.`);
        }
        path = path.replace(`${data.$id}.`, '');
        return path;
    },

    // Get all root storages.
    getRootStores() {
        return reactiveStorages;
    },
};

let nextindex = 0;
let absolutepathchar = '@';

let reactiveStorages = {};

const generateStorageId = function() {
    nextindex++;
    return `${absolutepathchar}${nextindex}`;
};

const ReactiveData = function(node, alias) {
    // If we have no parent means it will generate a new root node ID.
    let registerid = null;
    if (alias === undefined) {
        registerid = generateStorageId();
        alias = new Set([registerid]);
    }
    // If the node is already a reactive node, we just add the new alias.
    if (node.$alias !== undefined) {
        node.$alias = new Set([...node.$alias, ...alias]);
        return node;
    }

    // Create new reactive node.
    let item;
    if (typeof node === 'object' && node !== null) {
        item = new Proxy(node, deepproxy(alias, registerid));
        Object.entries(node).forEach(([key, value]) => {
            item[key] = value;
        });
    } else {
        // Proxies cannot store primitives so we need to convert into an object.
        // For primitive values, we need to do thing differently :-(
        // TODO: try to figure out how to fake primitives properly.
        const fakenode = primitiveWrap(node);
        item = new Proxy(fakenode, deepproxy(alias, registerid));
    }
    // Only root nodes has register id.
    if (registerid !== null) {
        reactiveStorages[registerid] = item;
    }
    return item;
};

// Versió amb fullpaths
/**
 * Proxy handler to trap get and set on data and emit change events.
 */
const deepproxy = function (alias, registerid) {
    const newid = registerid ?? null;
    return {
        $alias: alias,
        $id: newid,
        // $parent: parent,
        // $sons: {},
        get: function (obj, prop) {
            if (prop == '$alias') {
                return this.$alias;
            }
            if (prop == '$id') {
                return this.$id;
            }
            return obj[prop];
        },
        set: function (obj, prop, value) {
            if (prop == '$alias') {
                this.$alias = value;
                // We don't need to emit any change here because this order came from
                // one of the parents who will do the proper emit.
                return true;
            }
            let newalias;
            if (obj[prop] !== value) {
                // Generate list of new object alias.
                newalias = [...this.$alias].reduce((news, current) => news.add(`${current}.${prop}`), new Set());
                // Dettach old node alias.
                if (obj[prop] !== undefined && obj[prop].$alias !== undefined) {
                    obj[prop].$alias = new Set([...obj[prop].$alias].filter(alias => !newalias.has(alias)));
                }
            } else {
                // This is already the same node and we don't need to modify any alias.
                newalias = new Set();
            }
            // Apply new alias to node and create reactive node if necessary.
            obj[prop] = ReactiveData(value, newalias);
            // Emit changes.
            [...newalias].map( path => reactive.emitChange(path, 'update'));

            return true;
        },
    };
};

const primitiveWrap = function(node) {
    const wrapper = {};
    let fakenode = Object.create(wrapper);
    fakenode.$value = node;
    fakenode.valueOf = function() {
      return this.$value;
    }.bind(fakenode);
    fakenode.toString = function() {
      return '' + this.$value;
    }.bind(fakenode);
    return fakenode;
};
