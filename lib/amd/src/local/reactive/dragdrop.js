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
 * Course editor drag and drop components.
 *
 * This component is used to delegate generic drag and drop handling in the course pages.
 *
 * To delegate the logic to this particular element the component should create a new instance
 * passing "this" as param. The component will use all the necessary callbacks and add all the
 * necessary listeners to the component element.
 *
 * Component attributes used by dragdrop module:
 * - element: the draggable or dropzone element.
 * - (optional) classes: object with alternative CSS classes
 * - (optional) fullregion: page element affeted by the elementy dragging. Use this attribute if
 *                          the draggable element affects a bigger region (for example a draggable
 *                          title).
 *
 * Methods the parent component should have for making it draggable:
 *
 * - getDraggableData(): Object|data
 *      Return the data that will be passed to any valid dropzone while it is dragged.
 *      If the component has this method, the dragdrop module will enable the dragging,
 *      this is the only required method for dragging.
 *      If at the dragging moment this method returns a false|null|undefined, the dragging
 *      actions won't be captured.
 *
 * - (optional) dragStart(Object dropdata): void
 * - (optional) dragEnd(Object dropdata): void
 *      Callbacks dragdrop will call when the element is dragged and getDraggableData
 *      return some data.
 *
 * Methods the parent component should have for enabling it as a dropzone:
 *
 * - validateDropData(Object dropdata): boolean
 *      If that method exists, the dragdrop module will automathically configure the element as dropzone.
 *      This method will return true if the dropdata is accepted. In case it returns false, no drag and
 *      drop event will be listener for this specific dragged dropdata.
 *
 * - (Optional) dragEnter(Object dropdata): void
 * - (Optional) dragleave(Object dropdata): void
 * - (Optional) dragleave(Object dropdata): void
 * - (Optional) dragover(Object dropdata): void
 * - (Optional) drop(Object dropdata): void
 *      Callbacks dragdrop will call when a valid dragged element passes through or is dropped
 *      over the element. Note that none of this methods will be called if validateDropData
 *      returns a false value.
 *
 * This module will also add or remove several CSS classes from both dragged elements and dropzones.
 * See the "this.classes" in the create method for more details. In case the parent component wants
 * to use the same classes, it can use the getClasses method. On the other hand, if the parent
 * component has an alternative "classes" attribute, this will override the default drag and drop
 * classes.
 *
 * @module     core/local/reactive/dragdrop
 * @class      core/local/reactive/dragdrop
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseComponent from 'core/local/reactive/basecomponent';

// Map with the dragged element generate by an specific reactive applications.
// Potentially, any component can generate a draggable element to interact with other
// page elements. However, the dragged data is specific and could only interact with
// components of the same reactive instance.
let activedropdata = new Map();

export default class extends BaseComponent {

    /**
     * Constructor hook.
     *
     * @param {BaseComponent} parent the parent component.
     */
    create(parent) {
        // Optional component name for debugging.
        this.name = `${parent.name ?? 'unkown'}_dragdrop`;
        // Default drag and drop classes.
        this.classes = {
            // This class indicate a dragging action is active at a page level.
            BODYDRAGGING: 'dragging',

            // Added when draggable and drop are ready.
            DRAGGABLEREADY: parent?.classes?.DRAGGABLEREADY ?? 'draggable',
            DROPREADY: parent?.classes?.DROPREADY ?? 'dropready',

            // When a valid drag element is over the element.
            DRAGOVER: parent?.classes?.DRAGOVER ?? 'dragover',
            // When a the component is dragged.
            DRAGGING: parent?.classes?.DRAGGING ?? 'dragging',

            // Dropzones classes names.
            DROPUP: parent?.classes?.DROPUP ?? 'drop-up',
            DROPDOWN: parent?.classes?.DROPDOWN ?? 'drop-down',
            DROPZONE: parent?.classes?.DROPZONE ?? 'drop-zone',
        };

        // Add the affected region if any.
        this.fullregion = parent.fullregion;

        // Keep parent to execute drap and drop handlers.
        this.parent = parent;

        // Sub HTML elements will trigger extra dragEnter and dragOver all the time.
        // To prevent that from affecting dropzones, we need to count the enters and leaves.
        this.entercount = 0;

        // Stores if the droparea is shown or not.
        this.dropzonevisible = false;

    }

    /**
     * Return the component drag and drop CSS classes.
     *
     * @returns {Object} the dragdrop css classes
     */
    getClasses() {
        return this.classes;
    }

    /**
     * Initial state ready method.
     *
     * This method will add all the necessary event listeners to the component depending on the
     * parent methods.
     *  - Add drop events to the element if the parent component has validateDropData method.
     *  - Configure the elements draggable if the parent component has getDraggableData method.
     */
    stateReady() {
        // Add drop events to the element if the parent component has dropable types.
        if (typeof this.parent.validateDropData === 'function') {
            this.element.classList.add(this.classes.DROPREADY);
            this.addEventListener(this.element, 'dragenter', this._dragEnter);
            this.addEventListener(this.element, 'dragleave', this._dragLeave);
            this.addEventListener(this.element, 'dragover', this._dragOver);
            this.addEventListener(this.element, 'drop', this._drop);
        }

        // Configure the elements draggable if the parent component has dragable data.
        if (typeof this.parent.getDraggableData === 'function') {
            this.element.setAttribute('draggable', true);
            this.addEventListener(this.element, 'dragstart', this._dragStart);
            this.addEventListener(this.element, 'dragend', this._dragEnd);
            this.element.classList.add(this.classes.DRAGGABLEREADY);
        }
    }

    /**
     * Drag start event handler.
     *
     * This method will generate the current dropable data. This data is the one used to determine
     * if a droparea accepts the dropping or not.
     *
     * @param {Event} event the event.
     */
    _dragStart(event) {
        const dropdata = this.parent.getDraggableData();
        if (!dropdata) {
            return;
        }

        // If the drag event is accepted we prevent any other draggable element from interfiere.
        event.stopPropagation();

        // Save the drop data of the current reactive intance.
        activedropdata.set(this.reactive, dropdata);

        // Add some CSS classes to indicate the state.
        document.body.classList.add(this.classes.BODYDRAGGING);
        this.element.classList.add(this.classes.DRAGGING);
        this.fullregion?.classList.add(this.classes.DRAGGING);

        // Force the drag image to the current element. This makes the UX more consistent in case the
        // user dragged an internal element like a link or some other element.
        event.dataTransfer.setDragImage(this.element, 0, 0);

        this._callParentMethod('dragStart', dropdata);
    }

    /**
     * Drag end event handler.
     *
     * @param {Event} event the event.
     */
    _dragEnd() {
        const dropdata = activedropdata.get(this.reactive);
        if (!dropdata) {
            return;
        }

        // Remove the current dropdata.
        activedropdata.delete(this.reactive);

        // Remove the dragging classes.
        document.body.classList.remove(this.classes.BODYDRAGGING);
        this.element.classList.remove(this.classes.DRAGGING);
        this.fullregion?.classList.remove(this.classes.DRAGGING);

        this._callParentMethod('dragEnd', dropdata);
    }

    /**
     * Drag enter event handler.
     *
     * The JS drag&drop API triggers several dragenter events on the same element because it bubbles the
     * child events as well. To prevent this form affecting the dropzones display, this methods use
     * "entercount" to determine if it's one extra child event or a valid one.
     *
     * @param {Event} event the event.
     */
    _dragEnter(event) {
        const dropdata = this._processEvent(event);
        if (dropdata) {
            this.entercount++;
            this.element.classList.add(this.classes.DRAGOVER);
            if (this.entercount == 1 && !this.dropzonevisible) {
                this.dropzonevisible = true;
                this.element.classList.add(this.classes.DRAGOVER);
                this._callParentMethod('showDropZone', dropdata);
            }
        }
    }

    /**
     * Drag over event handler.
     *
     * We only use dragover event when a draggable action starts inside a valid dropzone. In those cases
     * the API won't trigger any dragEnter because the dragged alement was already there. We use the
     * dropzonevisible to determine if the component needs to display the dropzones or not.
     *
     * @param {Event} event the event.
     */
    _dragOver(event) {
        const dropdata = this._processEvent(event);
        if (dropdata && !this.dropzonevisible) {
            this.dropzonevisible = true;
            this.element.classList.add(this.classes.DRAGOVER);
            this._callParentMethod('showDropZone', dropdata);
        }
    }

    /**
     * Drag over leave handler.
     *
     * The JS drag&drop API triggers several dragleave events on the same element because it bubbles the
     * child events as well. To prevent this form affecting the dropzones display, this methods use
     * "entercount" to determine if it's one extra child event or a valid one.
     *
     * @param {Event} event the event.
     */
    _dragLeave(event) {
        const dropdata = this._processEvent(event);
        if (dropdata) {
            this.entercount--;
            if (this.entercount == 0 && this.dropzonevisible) {
                this.dropzonevisible = false;
                this.element.classList.remove(this.classes.DRAGOVER);
                this._callParentMethod('hideDropZone', dropdata);
            }
        }
    }

    /**
     * Drop event handler.
     *
     * This method will call both hideDropZones and drop methods on the parent component.
     *
     * @param {Event} event the event.
     */
    _drop(event) {
        const dropdata = this._processEvent(event);
        if (dropdata) {
            this.entercount = 0;
            if (this.dropzonevisible) {
                this.dropzonevisible = false;
                this._callParentMethod('hideDropZone', dropdata);
            }
            this.element.classList.remove(this.classes.DRAGOVER);
            this._callParentMethod('drop', dropdata);
        }
    }

    /**
     * Process a drag and drop event and delegate logic to the parent component.
     *
     * @param {Event} event the drag and drop event
     * @return {Object|false} the dropdata or null if the event should not be processed
     */
    _processEvent(event) {
        const dropdata = this._getDropData(event);
        if (!dropdata) {
            return null;
        }
        if (this.parent.validateDropData(dropdata)) {
            // All accepted drag&drop event must prevent bubbling and defaults, otherwise
            // parent dragdrop instances could capture it by mistake.
            event.preventDefault();
            event.stopPropagation();
            return dropdata;
        }
        return null;
    }

    /**
     * Convenient method for calling parent component functions if present.
     *
     * @param {string} methodname the name of the method
     * @param {Object} dropdata the current drop data object
     */
    _callParentMethod(methodname, dropdata) {
        if (typeof this.parent[methodname] === 'function') {
            this.parent[methodname](dropdata);
        }
    }

    /**
     * Get the current dropdata for a specific event.
     *
     * The browser can generate drag&drop events related to several user interactions:
     *  - Drag a page elements: this case is registered in the activedropdata map
     *  - Drag some HTML selections: ignored for now
     *  - Drag a file over the browser: file drag may appear in the future but for now they are ignored.
     *
     * @param {Event} event the original event.
     * @returns {Object|undefined} with the dragged data (or undefined if none)
     */
    _getDropData(event) {
        if (this._containsFiles(event)) {
            return undefined;
        }
        return activedropdata.get(this.reactive);
    }

    /**
     * Check if the dragged event contains files.
     *
     * Files dragging does not generate drop data because they came from outsite the page and the component
     * must check it before validating the event.
     *
     * @param {Event} event the original event.
     * @returns {boolean} if the drag dataTransfers contains files.
     */
    _containsFiles(event) {
        if (event.dataTransfer.types) {
            for (var i = 0; i < event.dataTransfer.types.length; i++) {
                if (event.dataTransfer.types[i] == "Files") {
                    return true;
                }
            }
        }
        return false;
    }
}
