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

import defaultmutations from 'core_course/local/editor/mutations';
import Reactive from 'core_course/local/editor/reactive';
import events from 'core_course/events';
import log from 'core/log';
import ajax from 'core/ajax';

class Editor extends Reactive {

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

            // Edit mode is part of the state but it could change over time,
            // components should use isEditing method instead.
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
     * The edit mode is parts of the course state, but it should only be checked on the initial state.
     *
     * @return {boolean} if edit is enabled
     */
    isEditing() {
        return this.editing ?? false;
    }
}

export default new Editor({
    name: 'CourseEditor',
    eventname: events.statechanged,
    eventdispatch: dispatchStateChangedEvent,
    // Mutations can be overridden by the format plugin but we need the default one at least.
    mutations: defaultmutations,
});

/**
 * This function will be moved to core_course/events module
 * when the file is migrated to the new JS events structure proposed in MDL-70990.
 *
 * @method dispatchStateChangedEvent
 * @param {object} detail the full state
 * @param {object} target the custom event target (document if none provided)
 */
function dispatchStateChangedEvent(detail, target) {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(new CustomEvent(events.statechanged, {
        bubbles: true,
        detail: detail,
    }));
}
