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
 * The file upload monitor component.
 *
 * @module     core_courseformat/local/courseeditor/uploadmonitor
 * @class      core_courseformat/local/courseeditor/uploadmonitor
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import {BaseComponent} from 'core/reactive';
import selectors from 'core_courseformat/selectors';

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'course_format';
        // Default query selectors.
        this.selectors = {
            ITEM: selectors.content.modals.uploaditem,
            PROGRESSBAR: selectors.content.modals.progressbar,
        };
        // Default classes to toggle on refresh.
        this.classes = {
        };
        // The uploading page items.
        this.currentItems = new Map();
    }

    /**
     * Static method to create a component instance form the mustahce template.
     *
     * @param {Reactive} reactive the reactive object
     * @param {string} element the DOM main element or its ID (optional)
     * @param {object} selectors optional css selector overrides (optional)
     * @return {UploadMonitor}
     */
    static init(reactive, element, selectors) {
        if (!element) {
            element = document.querySelector(`[data-queue-monitor]`);
            if (!element) {
                element = document.createElement('div');
                element.setAttribute('data-queue-monitor', 'true');
                element.classList.add('file-upload-queue-monitor');
                document.body.appendChild(element);
            }
        }
        return new Component({
            element,
            reactive,
            selectors,
        });
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
     */
    stateReady(state) {
        this._refreshItemsList({state});
    }

    /**
     * Return the component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            // State changes that require to reload some course modules.
            {watch: `uploading:created`, handler: this._refreshItemsList},
            {watch: `uploading:deleted`, handler: this._removeElement},
            {watch: `uploading.percent:updated`, handler: this._updateElement},
        ];
    }

    /**
     * Create all the necessary items from ujploading list.
     *
     * @param {object} args the event args
     * @param {Object} args.state the full state data
     */
    _refreshItemsList({state}) {
        const uploading = state.uploading ?? new Map();
        // Create all the necessary elements.
        for (const item of uploading.values()) {
            this._createItem(item);
        }
    }

    /**
     * Create a monitor item.
     * @param {Object} item the item data
     * @param {String} item.id the item id
     * @param {File} item.fileInfo the file data
     * @return {boolean} if the item is created ot not
     */
    async _createItem(item) {
        if (this.currentItems.has(item.id)) {
            return this.currentItems.get(item.id);
        }
        // Create a fake element in case the upload is finished while creating the element.
        this.currentItems.set(item.id, document.createElement('div'));
        // Render the item.
        const data = {
            id: item.id,
            filename: item.fileInfo.name,
            percent: item.fileInfo.percent,
        };
        const {html, js} = await Templates.renderForPromise('core_courseformat/uploadqueue', data);
        if (this.currentItems.has(item.id)) {
            Templates.appendNodeContents(this.element, html, js);
            const element = this.getElement(this.selectors.ITEM, data.id);
            this.currentItems.set(item.id, element);
            return true;
        }
        return false;
    }

    /**
     * Update a monitor item.
     *
     * @param {object} args the event args
     * @param {Object} args.element The element to update
     */
    _updateElement({element}) {
        if (!this.currentItems.has(element.id)) {
            return;
        }
        const target = this.currentItems.get(element.id);
        const progress = target.querySelector(this.selectors.PROGRESSBAR);
        if (progress) {
            progress.value = element.percent;
        }
    }

    /**
     * Remove a monitor item.
     *
     * @param {object} args the event args
     * @param {Object} args.element The element to update
     */
    _removeElement({element}) {
        if (!this.currentItems.has(element.id)) {
            return;
        }
        const target = this.currentItems.get(element.id);
        if (target && target.parentNode) {
            target.parentNode.removeChild(target);
        }
        this.currentItems.delete(element.id);
    }
}
