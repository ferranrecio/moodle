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

import {applyTemplate} from 'core/local/reactive/virtualdom';
import BaseComponent from 'core/local/reactive/basecomponent';
import Notification from 'core/notification';
import Templates from 'core/templates';

const instances = new WeakMap();

/**
 * TODO describe module templatecomponent
 *
 * @module     core/local/reactive/templatecomponent
 * @class     core/local/reactive/templatecomponent
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default class extends BaseComponent {
    /**
     * The class constructor.
     *
     * The only param this method gets is a constructor with all the mandatory
     * and optional component data. Component will receive the same descriptor
     * as create method param.
     *
     * This method will call the "create" method before registering the component into
     * the reactive module. This way any component can add default selectors and events.
     *
     * @param {descriptor} descriptor data to create the object.
     */
    constructor(descriptor) {
        super(descriptor);
        this.currentTemplateData = null;
    }

    /**
     * Static method to init a template component instance.
     *
     * This method is used to prevent creating multiple template components
     * for the same DOM element.
     *
     * @param {descriptor} descriptor data to create the object.
     */
    static createTemplateComponent(descriptor) {
        if (descriptor.element === undefined || !(descriptor.element instanceof HTMLElement)) {
            throw Error(`Missing a main DOM element to create a template component.`);
        }
        if (instances.has(descriptor.element)) {
            return instances.get(descriptor.element);
        }
        const newInstance = new this(descriptor);
        instances.set(descriptor.element, newInstance);
        return newInstance;
    }

    getTemplateName() {
        return null;
    }

    getTemplateData() {
        return null;
    }

    async refreshTemplate() {
        const templateName = this.getTemplateName();
        if (!templateName) {
            throw new Error('The getTemplateName method must return the template name.');
        }
        let templateData = this.getTemplateData();
        if (templateData === null) {
            throw new Error('The getTemplateData method must return some template data.');
        }

        // Reactive data cannot be altered outsite a mutation. However, the template library
        // will add some new data. We need to copy all the data to a full copy before continuing.
        // Furthermore, we also need the currentTemplateData to be a full copy to compare new values.
        const templateDataJson = JSON.stringify(templateData);
        templateData = JSON.parse(templateDataJson);

        if (templateDataJson === JSON.stringify(this.currentTemplateData)) {
            return;
        }

        window.console.log('Refreshing template', templateName, templateData);
        try {
            const {html, js} = await Templates.renderForPromise(templateName, templateData);
            applyTemplate(this.element, html, js);
            // Await Templates.replaceNodeContents(favouriteArea, html, js);
            this.currentTemplateData = templateData;
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Component watchers.
     *
     * By default, all template components will watch the state:updated event to refresh the template.
     * However, for complex reactive applications, the rendering could be optimized by watching only
     * the specific state properties that affect the template.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `state:updated`, handler: this.refreshTemplate},
        ];
    }
}
