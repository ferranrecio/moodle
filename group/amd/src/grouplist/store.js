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

const state = {
    // The complete group list.
    groups: {},
    title: 'title me!',
    onrequest: false,
};

// mutations are operations that actually mutate the state.
// each mutation handler gets the entire state tree as the
// first argument, followed by additional payload arguments.
const mutations = {
    processUpdates(state, updates) {
        state.onrequest = true;
        state.onrequest = false;
        return updates; // TODO: elmimate this!
    },
};

// actions are functions that cause side effects and can involve
// asynchronous operations. Each method will recive the store object.
const actions = {
    async loadGroup(store, group) {
        state.onrequest = true;
        // Load a group data into store.
        state.onrequest = false;
        return group; // TODO: elmimate this!
    },
};

// getters are functions to retrieve information from the state.
// They will get the state as only parameter. In case a getter has
// params, it will reqwuire a double return.
const getters = {
    group: (state) => (id) => {
        return state.groups[id];
    },
};

const store = {
  state,
  getters,
  actions,
  mutations,
};
export default store;
