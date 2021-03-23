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
 * Important note: this is just an example of how a component can be instantiated
 * several times in the same page (one per course-module in this case). In this case
 * having one component for each course-module does not have any sense as we are
 * losing performance and watching too many state events.
 *
 * To handle generic lists like this the component should be initialized
 * in one of the parent elements (the course_format in this case) and use this
 * component as a submodule that knows how to fins a specific course-module in the
 * course structure.
 *
 * @module     core_course/cm_format
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ComponentBase from 'core/reactive/component';
import editor from 'core_course/editor';


class Component extends ComponentBase {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'cm_format';
        // Save dom internal data.
        this.id = this.element.dataset.id;
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
            {watch: `cm[${this.id}].locked:updated`, handler: this.cmLocked},
        ];
    }

    /**
     *
     * @param {*} arg
     */
    cmVisibility({element}) {
        // If this wasn't a multiple instance object we will use this.selectors
        // to find the specific course-mdoule element in the DOM, intead of altering
        // the this.element directly.
        if (element.visible) {
            this.element.classList.remove("dimmed_text");
        } else {
            this.element.classList.add("dimmed_text");
        }
    }

    /**
     *
     * @param {*} arg
     */
    cmLocked({element}) {
        // If this wasn't a multiple instance object we will use this.selectors
        // to find the specific course-mdoule element in the DOM, intead of altering
        // the this.element directly.
        if (element.locked) {
            this.element.classList.add("locked");
        } else {
            this.element.classList.remove("locked");
        }
    }
}

export default Component;
