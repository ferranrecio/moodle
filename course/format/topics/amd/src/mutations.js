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
 * Format topics mutations.
 *
 * @module     format_topics/mutations
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import DefaultMutations from 'core_courseformat/local/courseeditor/mutations';
import CourseActions from 'core_courseformat/local/content/actions';

class TopicsMutations extends DefaultMutations {

    /**
     * Highlight sections.
     * @param {StateManager} stateManager the current state manager
     * @param {array} sectionIds the list of section ids
     */
    async sectionHighlight(stateManager, sectionIds) {
        const course = stateManager.get('course');
        this.sectionLock(stateManager, sectionIds, true);
        const updates = await this._callEditWebservice('section_highlight', course.id, sectionIds);
        stateManager.processUpdates(updates);
        this.sectionLock(stateManager, sectionIds, false);
    }

    /**
     * Unhighlight sections.
     * @param {StateManager} stateManager the current state manager
     * @param {array} sectionIds the list of section ids
     */
    async sectionUnhighlight(stateManager, sectionIds) {
        const course = stateManager.get('course');
        this.sectionLock(stateManager, sectionIds, true);
        const updates = await this._callEditWebservice('section_unhighlight', course.id, sectionIds);
        stateManager.processUpdates(updates);
        this.sectionLock(stateManager, sectionIds, false);
    }
}

export const init = () => {
    const courseEditor = getCurrentCourseEditor();
    // Override course editor mutations.
    courseEditor.setMutations(new TopicsMutations());
    // Add direct mutation content actions.
    CourseActions.addActions({
        setmarker: 'sectionHighlight',
        removemarker: 'sectionUnhighlight',
    });
};
