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
 * Reactive module debug panel.
 *
 * @module     core/local/reactive/debugpanel
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop, debug} from 'core/reactive';
import log from 'core/log';

/**
 * Init the main reactive panel.
 *
 * @param {element|string} target the DOM main element or its ID
 * @param {object} selectors optional css selector overrides
 */
export const init = (target, selectors) => {
    const element = document.getElementById(target);
    // Check if the debug reactive module is available.
    if (debug === undefined) {
        element.remove();
        return;
    }
    // Create the main component.
    new GlobalDebugPanel({
        element,
        reactive: debug,
        selectors,
    });
};

/**
 * Init an instance reactive subpanel.
 *
 * @param {element|string} target the DOM main element or its ID
 * @param {object} selectors optional css selector overrides
 */
export const initsubpanel = (target, selectors) => {
    const element = document.getElementById(target);
    // Check if the debug reactive module is available.
    if (debug === undefined) {
        element.remove();
        return;
    }
    // Create the main component.
    new DebugInstanceSubpanel({
        element,
        reactive: debug,
        selectors,
    });
};

/**
 * Component for the main reactive dev panel.
 *
 * This component shows the list of reactive instances and handle the buttons
 * to open a specific instance panel.
 */
class GlobalDebugPanel extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'GlobalDebugPanel';
        // Default query selectors.
        this.selectors = {
            LOADERS: `[data-for='loaders']`,
            SUBPANEL: `[data-for='subpanel']`,
            LOG: `[data-for='log']`,
        };
    }

    /**
     * Initial state ready method.
     *
     * @param {object} state the initial state
     */
    stateReady(state) {
        // Generate loading buttons.
        state.reactives.forEach(
            instance => {
                this._createLoader(instance);
            }
        );
        // Remove loading wheel.
        this.getElement(this.selectors.SUBPANEL).innerHTML = '';
    }

    _createLoader(instance) {
        const loaders = this.getElement(this.selectors.LOADERS);
        const btn = document.createElement("button");
        btn.innerHTML = instance.id;
        btn.dataset.id = instance.id;
        loaders.appendChild(btn);
        // Add click event.
        this.addEventListener(btn, 'click', () => this._openPanel(btn, instance));
    }

    async _openPanel(btn, instance) {
        try {
            const target = this.getElement(this.selectors.SUBPANEL);
            const data = {...instance};
            await this.renderComponent(target, 'core/local/reactive/debuginstancepanel', data);
        } catch (error) {
            log.error('Cannot load reactive debug subpanel');
            throw error;
        }
    }
}

/**
 * Component for the main reactive dev panel.
 *
 * This component shows the list of reactive instances and handle the buttons
 * to open a specific instance panel.
 */
class DebugInstanceSubpanel extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'DebugInstanceSubpanel';
        // Default query selectors.
        this.selectors = {
            NAME: `[data-for='name']`,
            CLOSE: `[data-for='close']`,
            READMODE: `[data-for='readmode']`,
            HIGHLIGHT: `[data-for='highlight']`,
            LOG: `[data-for='log']`,
            STATE: `[data-for='state']`,
            CLEAN: `[data-for='clean']`,
            PIN: `[data-for='pin']`,
        };
        this.id = this.element.dataset.id;
        this.controller = M.reactive[this.id];

        // The component is created always pinned.
        this.draggable = false;
        // We want the element to be dragged like modal.
        this.relativeDrag = true;
    }

    /**
     * Initial state ready method.
     *
     */
    stateReady() {
        // Enable drag and drop.
        this.dragdrop = new DragDrop(this);

        // Close button.
        this.addEventListener(
            this.getElement(this.selectors.CLOSE),
            'click',
            this.remove
        );
        // Highlight button.
        if (this.controller.highlight) {
            this._toggleButtonText(this.getElement(this.selectors.HIGHLIGHT));
        }
        this.addEventListener(
            this.getElement(this.selectors.HIGHLIGHT),
            'click',
            () => {
                this.controller.highlight = !this.controller.highlight;
                this._toggleButtonText(this.getElement(this.selectors.HIGHLIGHT));
            }
        );
        // Edit mode button.
        this.addEventListener(
            this.getElement(this.selectors.READMODE),
            'click',
            this._toggleEditMode
        );
        // Clean log and state.
        this.addEventListener(
            this.getElement(this.selectors.CLEAN),
            'click',
            this._cleanAreas
        );
        // Add current state.
        this._refreshState();
        // Unpin panel.
        this.addEventListener(
            this.getElement(this.selectors.PIN),
            'click',
            this._togglePin
        );
    }

    /**
     * Remove all subcomponents dependencies.
     */
    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `reactives[${this.id}].lastChanges:updated`, handler: this._refreshLog},
            {watch: `reactives[${this.id}].modified:updated`, handler: this._refreshState},
            {watch: `reactives[${this.id}].readOnly:updated`, handler: this._refreshReadOnly},
        ];
    }

    _refreshLog({element}) {
        const list = element?.lastChanges ?? [];

        const logContent = list.join("\n");
        // Append last log.
        const target = this.getElement(this.selectors.LOG);
        target.value += `\n\n= Transaction =\n ${logContent}`;
        target.scrollTop = target.scrollHeight;
    }

    _cleanAreas() {
        let target = this.getElement(this.selectors.LOG);
        target.value = '';

        this._refreshState();
    }

    _refreshState() {
        const target = this.getElement(this.selectors.STATE);
        target.value = JSON.stringify(this.controller.state, null, 4);
    }

    _refreshReadOnly() {
        // Toggle the read mode button.
        const target = this.getElement(this.selectors.READMODE);
        if (target.dataset.readonly === undefined) {
            target.dataset.readonly = target.innerHTML;
        }
        if (this.controller.readOnly) {
            target.innerHTML = target.dataset.readonly;
        } else {
            target.innerHTML = target.dataset.alt;
        }
    }

    _toggleEditMode() {
        this.controller.readOnly = !this.controller.readOnly;
    }

    // Drag and drop methods.

    /**
     * Get the draggable data of this component.
     *
     * @returns {Object} exported course module drop data
     */
    getDraggableData() {
        return this.draggable;
    }

    /**
     * The element drop end hook.
     *
     * @param {Object} dropdata the dropdata
     * @param {Event} event the dropdata
     */
    dragEnd(dropdata, event) {
        log.debug(event);
        this.element.style.top = `${event.newTop}px`;
        this.element.style.left = `${event.newLeft}px`;
    }

    _togglePin() {
        this.draggable = !this.draggable;
        this.dragdrop.setDraggable(this.draggable);
        if (this.draggable) {
            this._unpin();
        } else {
            this._pin();
        }
    }

    _unpin() {
        // Find the initial spot.
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const pageCenter = window.innerWidth / 2;
        // Put the element in the middle of the screen
        const style = {
            position: 'absolute',
            resize: 'both',
            overflow: 'auto',
            height: '400px',
            width: '400px',
            top: `${scrollTop + 100}px`,
            left: `${pageCenter - 200}px`,
        };
        Object.assign(this.element.style, style);
        // Small also the text areas.
        this.getElement(this.selectors.STATE).style.height = '50px';
        this.getElement(this.selectors.LOG).style.height = '50px';

        this._toggleButtonText(this.getElement(this.selectors.PIN));
    }

    _pin() {
        const props = [
            'position',
            'resize',
            'overflow',
            'top',
            'left',
            'height',
            'width',
        ];
        props.forEach(
            prop => this.element.style.removeProperty(prop)
        );
        this._toggleButtonText(this.getElement(this.selectors.PIN));
    }

    _toggleButtonText(element) {
        [element.innerHTML, element.dataset.alt] = [element.dataset.alt, element.innerHTML];
    }

}
