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
 * Editor component for the cm_format template.
 *
 * @module     core_course/cm_format
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import editor from 'core_course/editor';


class CmFormat {

    /**
     * The class constructor.
     */
    constructor() {
        // Optional component name.
        this.name = 'cm_format';
        // Default component css selectors.
        this.selectors = {
            cm: '.cm_format',
        };
    }

    /**
     * Initialize the component.
     *
     * @param {object} newselectors optional selectors override
     * @returns {boolean}
     */
    init(newselectors) {
        // TODO: for now we replace the default drawer. Dele this when we have a proper
        // course index component.
        document.querySelector('#nav-drawer').innerHTML = 'Loading course index...';

        // Overwrite the components selectors if necessary.
        this.selectors.cm = newselectors.cm ?? this.selectors.cm;

        // Register the component.
        editor.registerComponent(this);

        // Bind actions if necessary.

        return true;
    }

    getWatchers() {
        return [
            {watch: 'cm.visible:updated', handler: this.cmVisibility},
            {watch: 'cm.locked:updated', handler: this.cmLocked},
        ];
    }

    /**
     *
     * @param {*} arg
     */
    cmVisibility({element}) {
        // Get DOM element.
        let domelement = document.querySelector(`${this.selectors.cm}[data-id='${element.id}']`);
        if (!domelement) {
            return;
        }
        if (element.visible) {
            domelement.classList.remove("dimmed_text");
        } else {
            domelement.classList.add("dimmed_text");
        }
    }

    /**
     *
     * @param {*} arg
     */
    cmLocked({element}) {
        // Get DOM element.
        let domelement = document.querySelector(`${this.selectors.cm}[data-id='${element.id}']`);
        if (!domelement) {
            return;
        }
        if (element.locked) {
            domelement.classList.add("locked");
        } else {
            domelement.classList.remove("locked");
        }
    }
}

export default new CmFormat();
