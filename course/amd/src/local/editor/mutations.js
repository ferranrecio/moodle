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
 * Default mutation manager
 *
 * @module     core_course/editor/mutations
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import ajax from 'core/ajax';

const mutations = {};

/**
 * Call core_course_edit webservice.
 *
 * @method callEditWebservice
 * @param {string} action
 * @param {int} courseid
 * @param {array} ids
 */
mutations.callEditWebservice = async(action, courseid, ids) => {
    try {
        let ajaxresult = await ajax.call([{
            methodname: 'core_course_edit',
            args: {
                action,
                courseid,
                ids,
            }
        }])[0];
        return JSON.parse(ajaxresult);
    } catch (error) {
        log.error('ERROR IN WS');
        log.error(error);
        throw Error(`Error calling core_course_edit on ${action} action`);
    }
};

/**
 * Set the locked value to all elements in a list.
 *
 * @method setLocked
 * @param {StateManager} statemanager the state element
 * @param {Map} data the state element
 * @param {array} ids
 * @param {bool} newvalue
 */
mutations.setLocked = async(statemanager, data, ids, newvalue) => {
    // Before doing any manual change to the state we need to unlock it.
    // That is the reason why mutations uses the full statemanager instead that
    // just the state as the components.
    statemanager.setLocked(false);
    ids.forEach((id) => {
        if (data.has(id)) {
            data.get(id).locked = newvalue;
        }
    });
    // Lock again the state to prevent illegal writes.
    statemanager.setLocked(true);
};

/**
* Hide an activity.
*
* @method cm_hide
* @param {StateManager} statemanager the current state
* @param {array} cmids the list of cm ids to hide
*/
mutations.cmHide = async(statemanager, cmids) => {
    let state = statemanager.state;
    // Filter cm ids that are already hidden or inexistent.
    const ids = cmids.filter((id) => {
        if (!state.cm.has(id)) {
            log.error(`Course module with ID ${id} does not exists`);
        }
        return state.cm.get(id).visible;
    });

    mutations.setLocked(statemanager, state.cm, ids, true);

    try {
        let updates = await mutations.callEditWebservice('cm_hide', state.course.id, ids);
        statemanager.processUpdates(updates);
    } catch (error) {
        // TODO: notify error.
    }

    mutations.setLocked(statemanager, state.cm, ids, false);

};

/**
* Show an activity.
*
* @method cm_show
* @param {StateManager} statemanager the current state
* @param {array} cmids the list of cm ids to hide
*/
mutations.cmShow = async(statemanager, cmids) => {
    let state = statemanager.state;
    // Filter cm ids that are already visible or inexistent.
    const ids = cmids.filter((id) => {
        if (state.cm.get(id) === undefined) {
            log.error(`Course module with ID ${id} does not exists`);
        }
        return !state.cm.get(id).visible;
    });

    mutations.setLocked(statemanager, state.cm, ids, true);

    try {
        let updates = await mutations.callEditWebservice('cm_show', state.course.id, ids);
        statemanager.processUpdates(updates);
    } catch (error) {
        // TODO: notify error.
    }

    mutations.setLocked(statemanager, state.cm, ids, false);
};

export default mutations;
