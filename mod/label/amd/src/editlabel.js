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
 * Module to edit a text an media element using a modal.
 *
 * @module     mod_label/editlabel
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import {getString} from 'core/str';

let isInitialised = false;

const selectors = {
    editButton: "[data-mdl-label-action='edit']",
};

/**
 * Show the edit modal.
 *
 * @param {HTMLElement} returnFocus The element that triggered the modal.
 */
function showEditModal(returnFocus) {
    const courseEditor = getCurrentCourseEditor();

    const args = {
        cmid: returnFocus.getAttribute('data-mdl-label-cmid'),
        courseid: courseEditor.courseId,
    };

    const modalForm = new ModalForm({
        modalConfig: {
            title: returnFocus.title ?? getString('edit'),
        },
        args,
        formClass: 'mod_label\\form\\dynamiceditor',
        returnFocus,
    });

    // Force the course editor to refresh the state of the cm.
    modalForm.addEventListener(
        modalForm.events.FORM_SUBMITTED,
        () => courseEditor.dispatch('cmState', [args.cmid])
    );

    modalForm.show();
}

/**
 * Initialise the module.
 */
export const init = () => {
    if (isInitialised) {
        return;
    }
    isInitialised = true;

    document.body.addEventListener('click', (event) => {
        const target = event.target.closest(selectors.editButton);
        if (!target) {
            return;
        }
        event.preventDefault();
        showEditModal(target);
    });
};
