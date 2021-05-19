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
 * Course section header component.
 *
 * This component is used to control specific course section interactions like drag and drop.
 *
 * @module     core_course/local/section_format/header
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop} from 'core/reactive';

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     *
     * @param {Object} descriptor
     */
    create(descriptor) {
        // Optional component name for debugging.
        this.name = 'section_format_header';
        // We need our id to watch specific events.

        // Get main info from the descriptor.
        this.id = descriptor.id;
        this.section = descriptor.section;
        this.course = descriptor.course;

        // Prevent topic zero from being draggable.
        if (this.section.number > 0) {
            this.getDraggableData = this._getDraggableData;
        }
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
            // Init the dropzone.
            this.dragdrop = new DragDrop(this);
            // Save dropzone classes.
            this.classes = this.dragdrop.getClasses();
        }
    }

    // Drag and drop methods.

    /**
     * Get the draggable data of this component.
     *
     * @returns {Object} exported course module drop data
     */
    _getDraggableData() {
        const exporter = this.reactive.getExporter();
        return exporter.sectionDraggableData(this.reactive.getState(), this.id);
    }

    /**
     * Validate if the drop data can be dropped over the component.
     *
     * @param {Object} dropdata the exported drop data.
     * @returns {boolean}
     */
    validateDropData(dropdata) {
        // Course module validation.
        if (dropdata?.type === 'cm') {
            // The first section element is already there so we can ignore it.
            const firstcmid = this.section?.cmlist[0];
            return dropdata.id !== firstcmid;
        }
        return false;
    }

    /**
     * Display the component dropzone.
     *
     * @param {Object} dropdata the accepted drop data
     */
    showDropZone() {
        this.element.classList.add(this.classes.DROPZONE);
    }

    /**
     * Hide the component dropzone.
     */
    hideDropZone() {
        this.element.classList.remove(this.classes.DROPZONE);
    }

    /**
     * Drop event handler.
     *
     * @param {Object} dropdata the accepted drop data
     */
    drop(dropdata) {
        // Call the move mutation.
        if (dropdata.type == 'cm') {
            this.reactive.dispatch('cmMove', [dropdata.id], this.id, this.section?.cmlist[0]);
        }
    }
}
