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
 * @module     core_course/local/courseeditor/mutations
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ajax from 'core/ajax';

export default class Mutations {

    /**
     * Private method to call core_course_edit webservice.
     *
     * @method _callCoreCourseEditWebservice
     * @param {string} action
     * @param {int} courseid
     * @param {array} ids
     */
    async _callCoreCourseEditWebservice(action, courseid, ids) {
        let ajaxresult = await ajax.call([{
            methodname: 'core_course_edit',
            args: {
                action,
                courseid,
                ids,
            }
        }])[0];
        return JSON.parse(ajaxresult);
    }

    /**
     * A quick way to filter id lists.
     *
     * Non existent ids will be filtered authomatically before apply the filtering function.
     *
     * @param {Map} list the element list
     * @param {array} ids the ids array
     * @param {function} filter the filtering function
     * @return {array} filtered array
     */
    _filterIdList(list, ids, filter) {
        if (filter === undefined) {
            filter = () => true;
        }
        return list.filter((id) => {
            if (!list.has(id)) {
                return false;
            }
            return filter(list.get(id));
        });
    }

    /**
    * Show an activity.
    *
    * @method cmShow
    * @param {StateManager} statemanager the current state
    * @param {array} cmids the list of cm ids to show
    */
    async cmShow(statemanager, cmids) {
        let state = statemanager.state;
        // Filter cm ids that are already visible or inexistent.
        const ids = this._filterIdList(state.cm, cmids, (cm) => !cm.visible);

        let updates = await this._callCoreCourseEditWebservice('cm_show', state.course.id, ids);
        statemanager.processUpdates(updates);
    }

    /**
    * Hide an activity.
    *
    * @method cmHide
    * @param {StateManager} statemanager the current state
    * @param {array} cmids the list of cm ids to hide
    */
    async cmHide(statemanager, cmids) {
        let state = statemanager.state;
        // Filter cm ids that are already hidden or inexistent.
        const ids = this._filterIdList(state.cm, cmids, (cm) => cm.visible);

        let updates = await this._callCoreCourseEditWebservice('cm_hide', state.course.id, ids);
        statemanager.processUpdates(updates);
    }

}
