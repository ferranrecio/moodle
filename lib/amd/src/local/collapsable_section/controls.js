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
 * The collapsable sections controls.
 *
 * @module     core/local/collapsable_section/controls
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @example <caption>Example of controlling a collapsable section.</caption>
 *
 * import CollapsableSection from 'core/local/collapsable_section/controls';
 *
 * const section = CollapsableSection.instanceFromSelector('#MyCollapsableSection');
 *
 * // Use hide, show and toggle methods to control the section.
 * section.hide();
 */

import {
    eventTypes,
    notifyCollapsableSectionHidden,
    notifyCollapsableSectionShown
} from 'core/local/collapsable_section/events';

// The jQuery module is only used for interacting with Boostrap 4. It can we removed when MDL-71979 is integrated.
import jQuery from 'jquery';

let initialized = false;

export default class {

    static instanceFromSelector(selector) {
        const elements = document.querySelector(selector);
        if (!elements) {
            throw new Error('No elements found with the selector: ' + selector);
        }
        return new this(elements);
    }

    static init() {
        if (initialized) {
            return;
        }
        initialized = true;

        // We want to add extra events to the standard bootstrap collapsable events.
        // TODO: change all jquery events to custom events once MDL-71979 is integrated.
        jQuery(document).on(eventTypes.hiddenBsCollapse, event => {
            notifyCollapsableSectionHidden(event.target);
        });
        jQuery(document).on(eventTypes.shownBsCollapse, event => {
            notifyCollapsableSectionShown(event.target);
        });
    }

    constructor(element) {
        this.element = element;
    }

    hide() {
        jQuery(this.element).collapse('hide');
    }

    show() {
        jQuery(this.element).collapse('show');
    }

    toggle() {
        jQuery(this.element).collapse('toggle');
    }
}
