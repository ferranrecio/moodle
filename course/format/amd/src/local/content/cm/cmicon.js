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
 * Activity icon component.
 *
 * @module     core_courseformat/local/content/cm/cmicon
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';


export default class extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'cmicon';
        this.id = this.element.dataset.id;

        this.classes = {
            CLICKABLE: 'clickable',
        };
    }

    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {string} target the DOM main element query selector
     * @param {object} [cssSelectors] optional extra css selector overrides
     * @return {Component}
     */
    static init(target, cssSelectors) {
        return new this({
            element: document.querySelector(target),
            reactive: getCurrentCourseEditor(),
            cssSelectors,
        });
    }

    /**
     * Initial state ready method.
     * @param {object} state the initial state
     */
    stateReady(state) {
        this.addEventListener(
            this.element,
            'click',
            this._dispatchClick
        );
        if (state.cm.get(this.id)?.url) {
            this.element.classList.add(this.classes.CLICKABLE);
        }
    }

    /**
     * Handle the activity icon click.
     */
    _dispatchClick() {
        const cminfo = this.reactive.get('cm', this.id);
        // Not all activities have a URL.
        if (!cminfo?.url) {
            return;
        }
        document.location.href = cminfo.url;
    }
}
