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
 * Editor component for the course_format template.
 *
 * @module     core_course/course_format
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ComponentBase from 'core/reactive/component';
import editor from 'core_course/editor';
import log from 'core/log';


class Component extends ComponentBase {

    /**
     * Create hook method.
     */
    create() {
        // Optional component name.
        this.name = 'course_format';
        // Default component css selectors.
        this.selectors = {
            cmitem: `[data-editor='cmitem']`,
        };
    }

    /**
     * Static method to create a component instance form the mustahce template.
     *
     * We use a static method to prevent mustache templates to know which
     * reactive instance is used.
     *
     * @param {element|string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new Component({
            element: document.getElementById(target),
            reactive: editor,
            selectors,
        });
    }

    getWatchers() {
        return [
            {watch: `cm.visible:updated`, handler: this.cmVisibility},
        ];
    }

    /**
     *
     * @param {*} arg
     */
    cmVisibility({element}) {
        // Find the right element to apply the change.
        const target = this.element.querySelector(`${this.selectors.cmitem}[data-id='${element.id}']`);
        if (target) {
            if (element.visible) {
                target.classList.remove("dimmed_text");
            } else {
                target.classList.add("dimmed_text");
            }
        } else {
            log.debug(`Course module with id ${element.id} not found in page`);
        }
    }
}

export default Component;
