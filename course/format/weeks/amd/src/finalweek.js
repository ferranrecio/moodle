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
 * Module to define the course final week.
 *
 * @module     format_weeks/finalweek
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import {getString} from 'core/str';

const selectors = {
    actionButton: "[data-action='formatWeeksDefineFinalWeek']",
    weeksManager: '#format_weeks_week_manager',
};

let isInitialised = false;

/**
 * Show the final week dynamic form.
 *
 * @param {HTMLElement} returnFocus The element that triggered the modal.
 */
function showFinalWeekModal(returnFocus) {
    const courseEditor = getCurrentCourseEditor();

    const args = {
        courseid: courseEditor.courseId,
    };

    const modalForm = new ModalForm({
        modalConfig: {
            title: getString('finalweek_define', 'format_weeks'),
        },
        args,
        formClass: 'format_weeks\\form\\finalweek',
        returnFocus,
    });

    // Force the course editor to refresh the full course state.
    modalForm.addEventListener(
        modalForm.events.FORM_SUBMITTED,
        (event) => {
            // The forms will return a result true if something has changed in the course.
            if (event.detail?.result && event.detail?.url) {
                // The form could change many thing in the course to simply apply the changes.
                // We need to refresh the current page.
                document.location = event.detail?.url;
            }
        },
    );

    modalForm.show();
}

/**
 * Initialise the final week module.
 *
 * @returns {void}
 */
export const init = () => {
    if (isInitialised) {
        return;
    }
    isInitialised = true;

    document.body.addEventListener('click', (event) => {
        const target = event.target.closest(selectors.actionButton);
        if (!target) {
            return;
        }
        event.preventDefault();
        showFinalWeekModal(target);
    });
};
