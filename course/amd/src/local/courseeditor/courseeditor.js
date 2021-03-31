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

import {Reactive} from 'core/reactive';
import notification from 'core/notification';
import log from 'core/log';
import ajax from 'core/ajax';

/**
 * Main course editor module.
 *
 * All formats can register new components on this object to create new reactive
 * UI components that watch the current course state.
 *
 * @module     core_course/courseeditor
 * @package    core_course
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class CourseEditor extends Reactive {

    /**
    * Set up the course editor when the page is ready.
    *
    * @method init
    * @param {int} courseid course id
    */
    async init(courseid) {

        try {
            // Async load the initial state.
            const jsonstate = await ajax.call([{
                methodname: 'core_course_get_state',
                args: {courseid}
            }])[0];
            const statedata = JSON.parse(jsonstate);

            // Check we have the minimum state elements.
            statedata.course = statedata.course ?? {};
            statedata.section = statedata.section ?? [];
            statedata.cm = statedata.cm ?? [];

            // Edit mode is part of the state but it could change over time.
            // Components should use isEditing method to check the editing mode instead.
            this.editing = false;
            if (statedata.course !== undefined) {
                this.editing = statedata.course.editmode ?? false;
            }

            this.setInitialState(statedata);
        } catch (error) {
            log.error("EXCEPTION RAISED WHILE INIT COURSE EDITOR");
            log.error(error);
        }
    }

    /**
     * Return the current edit mode.
     *
     * Components should use this method to check if edit mode is active.
     *
     * @return {boolean} if edit is enabled
     */
    isEditing() {
        return this.editing ?? false;
    }

    /**
    * Dispatch a change in the state.
    *
    * Usually reactive modules throw an error directly to the components when something
    * goes wrong. However, course editor can directly display a notification.
    *
    * @method dispatch
    * @param {string} actionname the action name (usually the mutation name)
    * @param {*} param any number of params the mutation needs.
    */
    dispatch(...args) {
        try {
            super.dispatch(...args);
        } catch (error) {
            notification.exception(error);
        }
    }
}
