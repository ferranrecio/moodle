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

import ajax from 'core/ajax';

/**
 * Default mutation manager
 *
 * @module     core_course/local/courseeditor/mutations
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class Mutations {

    // All course editor mutations for Moodle 4.0 will be located in this file.

    /**
     * Private method to call core_course_edit webservice.
     *
     * @method _callEditWebservice
     * @param {string} action
     * @param {int} courseid
     * @param {array} ids
     * @param {int} targetsectionid optional target section id (for moving actions)
     * @param {int} targetcmid optional target cm id (for moving actions)
     */
    async _callEditWebservice(action, courseid, ids, targetsectionid, targetcmid) {
        const args = {
            action,
            courseid,
            ids,
        };
        if (targetsectionid) {
            args.targetsectionid = targetsectionid;
        }
        if (targetcmid) {
            args.targetcmid = targetcmid;
        }
        let ajaxresult = await ajax.call([{
            methodname: 'core_course_edit',
            args,
        }])[0];
        return JSON.parse(ajaxresult);
    }

    /**
     * Move course modules to specific course location.
     *
     * Note that one of targetsectionid or targetcmid should be provided in order to identify the
     * new location:
     *  - targetcmid: the activities will be located avobe the target cm. The targetsectionid
     *                value will be ignored in this case.
     *  - targetsectionid: the activities will be appended to the section. In this case
     *                     targetsectionid should not be present.
     *
     * @param {StateManager} statemanager the current state manager
     * @param {array} cmids the list of cm ids to move
     * @param {int} targetsectionid the target section id
     * @param {int} targetcmid the target course module id
     */
    async cmMove(statemanager, cmids, targetsectionid, targetcmid) {
        if (!targetsectionid && !targetcmid) {
            throw new Error(`Mutation cmMove requires targetsectionid or targetcmid`);
        }
        const course = statemanager.get('course');
        const updates = await this._callEditWebservice('cm_move', course.id, cmids, targetsectionid, targetcmid);
        statemanager.processUpdates(updates);
    }

    /**
     * Move course modules to specific course location.
     *
     * @param {StateManager} statemanager the current state manager
     * @param {array} sectionids the list of section ids to move
     * @param {int} targetsectionid the target section id
     */
    async sectionMove(statemanager, sectionids, targetsectionid) {
        if (!targetsectionid) {
            throw new Error(`Mutation sectionMove requires targetsectionid`);
        }
        const course = statemanager.get('course');
        const updates = await this._callEditWebservice('section_move', course.id, sectionids, targetsectionid);
        statemanager.processUpdates(updates);
    }

    /**
    * Get updated state data related to some cm ids.
    *
    * @param {StateManager} statemanager the current state
    * @param {array} cmids the list of cm ids to update
    */
    async cmState(statemanager, cmids) {
        const course = statemanager.get('course');
        const updates = await this._callEditWebservice('cm_state', course.id, cmids);
        statemanager.processUpdates(updates, {update: this._forcedUpdateAction});
    }

    /**
    * Get updated state data related to some section numbers.
    *
    * @param {StateManager} statemanager the current state
    * @param {array} sectionids the list of section ids to update
    */
    async sectionState(statemanager, sectionids) {
        const course = statemanager.get('course');
        const updates = await this._callEditWebservice('section_state', course.id, sectionids);
        statemanager.processUpdates(updates, {update: this._forcedUpdateAction});
    }

    /**
    * Get the full updated state data of the course.
    *
    * @param {StateManager} statemanager the current state
    */
    async courseState(statemanager) {
        const course = statemanager.get('course');
        const updates = await this._callEditWebservice('course_state', course.id);
        statemanager.processUpdates(updates, {update: this._forcedUpdateAction});
    }

    /**
     * Alternative update action for processUpdates.
     *
     * This method is used in sectionState, cmState and courseState mutations to tranform
     * update actions in creates if necessary.
     *
     * @param {Object} statemanager the state manager
     * @param {String} updatename the state element to update
     * @param {Object} fields the new data
     */
    _forcedUpdateAction(statemanager, updatename, fields) {
        if (statemanager.get(updatename, fields.id)) {
            statemanager.defaultUpdate(statemanager, updatename, fields);
        } else {
            statemanager.defaultCreate(statemanager, updatename, fields);
        }
    }
}
