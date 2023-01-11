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
 * The bulk editor tools bar.
 *
 * @module     core_courseformat/local/content/bulkedittoggler
 * @class      core_courseformat/local/content/bulkedittoggler
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {disableStickyFooter, enableStickyFooter} from 'core/sticky-footer';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import {get_string as getString} from 'core/str';
import {prefetchStrings} from 'core/prefetch';
import actions from './actions';

// Load global strings.
prefetchStrings(
    'core_courseformat',
    ['bulkselection']
);

export default class Component extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'bulk_editor_tools';
        // Default query selectors.
        this.selectors = {
            ACTIONS: `[data-for="bulkaction"]`,
            ACTIONTOOL: `[data-for="bulkactions"] li`,
            CANCEL: `[data-for="bulkcancel"]`,
            COUNT: `[data-for='bulkcount']`,
            SELECTALL: `[data-for="selectall"]`,
        };
        // Most classes will be loaded later by DndCmItem.
        this.classes = {
            HIDE: 'd-none',
            DISABLED: 'disabled',
        };
    }

    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {string} target optional altentative DOM main element CSS selector
     * @param {object} selectors optional css selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new this({
            element: document.querySelector(target),
            reactive: getCurrentCourseEditor(),
            selectors
        });
    }

    /**
     * Initial state ready method.
     */
    stateReady() {
        const cancelBtn = this.getElement(this.selectors.CANCEL);
        if (cancelBtn) {
            this.addEventListener(cancelBtn, 'click', this._cancelBulk);
        }
        const selectAll = this.getElement(this.selectors.SELECTALL);
        if (selectAll) {
            this.addEventListener(selectAll, 'change', this._selectAllClick);
        }
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `bulk.enabled:updated`, handler: this._refreshEnabled},
            {watch: `bulk:updated`, handler: this._refreshTools},
        ];
    }

    /**
     * Hide and show the bulk edit tools.
     *
     * @param {object} param
     * @param {Object} param.element details the update details (state.bulk in this case).
     */
    _refreshEnabled({element}) {
        if (element.enabled) {
            enableStickyFooter();
        } else {
            disableStickyFooter();
        }
    }

    /**
     * Refresh the tools depending on the current selection.
     *
     * @param {object} param the state watcher information
     * @param {Object} param.state the full state data.
     * @param {Object} param.element the affected element (bulk in this case).
     */
    _refreshTools(param) {
        this._refreshSelectCount(param);
        this._refreshSelectAll(param);
        this._refreshActions(param);
    }

    /**
     * Refresh the selection count.
     *
     * @param {object} param
     * @param {Object} param.element the affected element (bulk in this case).
     */
    async _refreshSelectCount({element: bulk}) {
        const selectedCount = await getString('bulkselection', 'core_courseformat', bulk.selection.length);
        const selectedElement = this.getElement(this.selectors.COUNT);
        if (selectedElement) {
            selectedElement.innerHTML = selectedCount;
        }
    }

    /**
     * Refresh the select all element.
     *
     * @param {object} param
     * @param {Object} param.state the full state data.
     * @param {Object} param.element the affected element (bulk in this case).
     */
    _refreshSelectAll({state, element: bulk}) {
        const selectall = this.getElement(this.selectors.SELECTALL);
        if (!selectall) {
            return;
        }
        if (bulk.selectedType === '') {
            selectall.checked = false;
            selectall.disabled = true;
            return;
        }
        selectall.disabled = false;
        const maxSelection = (bulk.selectedType === 'cm') ? state.cm.size : state.section.size;
        selectall.checked = (bulk.selection.length == maxSelection);
    }

    /**
     * Refresh the visible action buttons depending on the selection type.
     *
     * @param {object} param
     * @param {Object} param.element the affected element (bulk in this case).
     */
    _refreshActions({element: bulk}) {
        // By default, we show the section options.
        const displayType = (bulk.selectedType == 'cm') ? 'cm' : 'section';
        const enabled = (bulk.selectedType !== '');
        this.getElements(this.selectors.ACTIONS).forEach(action => {
            window.console.log();
            action.classList.toggle(this.classes.DISABLED, !enabled);

            const actionTool = action.closest(this.selectors.ACTIONTOOL);
            const isHidden = (action.dataset.bulk != displayType);
            actionTool?.classList.toggle(this.classes.HIDE, isHidden);
        });
    }

    _cancelBulk() {
        this.reactive.dispatch('bulkEnable', false);
    }

    _selectAllClick(event) {
        const target = event.target;
        const bulk = this.reactive.get('bulk');
        if (bulk.selectedType === '') {
            return;
        }
        const state = this.reactive.state;
        const allElements = state[bulk.selectedType];
        if (!allElements) {
            return;
        }
        if (target.checked) {
            const mutation = (bulk.selectedType === 'cm') ? 'cmSelect' : 'sectionSelect';
            this.reactive.dispatch(mutation, allElements.keys());
        } else {
            // Re-enable bulk will clean the selection and the selection type.
            this.reactive.dispatch('bulkEnable', true);
        }
    }
}
