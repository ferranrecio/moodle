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
 * Course section format component.
 *
 * @module     core_course/local/section_format
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop} from 'core/reactive';
import courseeditor from 'core_course/courseeditor';
import Header from 'core_course/local/section_format/header';

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     *
     * @param {Object} descriptor the component descriptor.
     */
    create(descriptor) {
        // Optional component name for debugging.
        this.name = 'section_format';
        // All selectors are taken form the descriptor (course_format module).
        // Default query selectors.
        this.selectors = {
            SECTION_TITLE: `[data-for='section_item']`,
            CM_LAST: `${descriptor?.selectors?.CM ?? '[data-for="cmitem"]'}:last-child`,
        };
        // We need our id to watch specific events.
        this.id = this.element.dataset.id;
    }

    /**
     * Static method to create a component instance form the mustahce template.
     *
     * @param {string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new Component({
            element: document.getElementById(target),
            reactive: courseeditor,
            selectors,
        });
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
     */
    stateReady(state) {
        this.section = state.section.get(this.id);
        this.course = state.course;

        // Drag and drop is only available for components compatible course formats.
        if (this.reactive.isEditing() && this.reactive.supportComponents()) {
            // Section zero and other formats sections may not have a title to drag.
            const title = this.getElement(this.selectors.SECTION_TITLE);
            if (title) {
                // Init the inner dragable element.
                this.titleitem = new Header({
                    ...this,
                    element: title,
                });
                // Init the dropzone.
                this.dragdrop = new DragDrop(this);
                // Save dropzone classes.
                this.classes = this.dragdrop.getClasses();
            }
        }
    }

    // Drag and drop methods.

    /**
     * Validate if the drop data can be dropped over the component.
     *
     * @param {Object} dropdata the exported drop data.
     * @returns {boolean}
     */
    validateDropData(dropdata) {
        // We accept any course module.
        if (dropdata?.type === 'cm') {
            return true;
        }
        // We accept any section bu the section 0 or ourself
        if (dropdata?.type === 'section') {
            const sectionzeroid = this.course.sectionlist[0];
            return dropdata?.id != this.id && dropdata?.id != sectionzeroid && this.id != sectionzeroid;
        }
        return false;
    }

    /**
     * Display the component dropzone.
     *
     * @param {Object} dropdata the accepted drop data
     */
    showDropZone(dropdata) {
        if (dropdata.type == 'cm') {
            this.getElement(this.selectors.CM_LAST)?.classList.add(this.classes.DROPDOWN);
        }
        if (dropdata.type == 'section') {
            // The relative move of section depends on the section number.
            if (this.section.number > dropdata.number) {
                this.element.classList.remove(this.classes.DROPUP);
                this.element.classList.add(this.classes.DROPDOWN);
            } else {
                this.element.classList.add(this.classes.DROPUP);
                this.element.classList.remove(this.classes.DROPDOWN);
            }
        }
    }

    /**
     * Hide the component dropzone.
     */
    hideDropZone() {
        this.getElement(this.selectors.CM_LAST)?.classList.remove(this.classes.DROPDOWN);
        this.element.classList.remove(this.classes.DROPUP);
        this.element.classList.remove(this.classes.DROPDOWN);
    }

    /**
     * Drop event handler.
     *
     * @param {Object} dropdata the accepted drop data
     */
    drop(dropdata) {
        // Call the move mutation.
        if (dropdata.type == 'cm') {
            this.reactive.dispatch('cmMove', [dropdata.id], this.id);
        }
        if (dropdata.type == 'section') {
            this.reactive.dispatch('sectionMove', [dropdata.id], this.id);
        }
    }

    /**
     * Remove all subcomponents dependencies.
     */
    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

}
