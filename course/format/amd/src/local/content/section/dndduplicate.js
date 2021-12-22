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
 * Mod chooser drag and drop module.
 *
 * @module     core_courseformat/local/courseeditor/dndduplicate
 * @class      core_courseformat/local/courseeditor/dndduplicate
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop} from 'core/reactive';
import {prefetchStrings} from 'core/prefetch';
import {get_string as getString} from 'core/str';

// Load global strings.
prefetchStrings('core', ['duplicatehere']);

export default class extends BaseComponent {

    /**
     * Constructor hook.
     *
     * @param {Object} descriptor
     */
    create(descriptor) {
        // Optional component name for debugging.
        this.name = 'duplicate_activity_dropzone';

        // Default query selectors.
        this.selectors = {
            MODCHOOSER_TEXT: `.activity-add-text`,
        };

        // Get main info from the descriptor.
        this.id = descriptor.id;
        this.course = descriptor.course;
        this.fullregion = descriptor.fullregion;

        this.dragdrop = new DragDrop(this);
        this.classes = this.dragdrop.getClasses();

        // Save the current contents.
        this.modchooserText = this.getElement(this.selectors.MODCHOOSER_TEXT);
        this.defaultText = this.getElement(this.selectors.MODCHOOSER_TEXT).innerHTML;
        this.duplicateText = this.defaultText;
        getString('duplicatehere', 'core').then(duplicateText => {
            this.duplicateText = duplicateText;
            return;
        }).catch();
    }

    /**
     * Remove all subcomponents dependencies.
     */
    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    // Drag and drop methods.

    /**
     * Validate if the drop data can be dropped over the component.
     *
     * @param {Object} dropdata the exported drop data.
     * @returns {boolean}
     */
    validateDropData(dropdata) {
        return dropdata?.type === 'cm';
    }

    /**
     * Display the component dropzone.
     */
    showDropZone() {
        this.element.classList.add(this.classes.DROPZONE);
        // Replace content with a hint text.
        this.modchooserText.innerHTML = this.duplicateText;
    }

    /**
     * Hide the component dropzone.
     */
    hideDropZone() {
        this.element.classList.remove(this.classes.DROPZONE);
        // Restore content.
        this.modchooserText.innerHTML = this.defaultText;
    }

    /**
     * Drop event handler.
     *
     * @param {Object} dropdata the accepted drop data
     */
    drop(dropdata) {
        this.reactive.dispatch('cmDuplicate', [dropdata.id], this.id);
    }

}
