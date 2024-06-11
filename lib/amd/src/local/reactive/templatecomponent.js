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

import VirtualDOM from 'core/local/reactive/virtualdom';
import BaseComponent from 'core/local/reactive/basecomponent';
import WeightedQueue from 'core/local/reactive/weightedqueue';
import Notification from 'core/notification';
import Templates from 'core/templates';

const instances = new WeakMap();
const renderQueue = new WeightedQueue();

/**
 * Template component base class.
 *
 * A template component is a reactive component that uses virtual DOM to refresh the
 * template when the state is updated.
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
        this.renderPriority = this.calculateRenderPriority();
        this.injectedTemplateContent = null;
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

    /**
     * Return the template component controlling the element, if any.
     *
     * @param {HTMLElement} element
     * @returns {TemplateComponent|undefined}
     */
    getElementComponent(element) {
        return instances.get(element);
    }

    /**
     * Return the template name.
     *
     * Components must override this method.
     *
     * @returns {string}
     */
    getTemplateName() {
        return undefined;
    }

    /**
     * Return the template data.
     *
     * Components must override this method. Returning a null will remove the component.
     *
     * @returns {Object|Array|null}
     */
    getTemplateData() {
        return undefined;
    }

    /**
     * Determine if the parent component can inject the template..
     *
     * By default, each component will refresh their own template on every
     * state:updated event. However, if a parent component is responsible
     * for the template, the child component can allow the parent to inject
     * the template and avoid unnecessary template refreshes.
     *
     * @returns {boolean} true if the component template can be injected.
     */
    allowTemplateInjection() {
        return false;
    }

    /**
     * Request a template refresh.
     */
    refreshTemplate() {
        renderQueue.add(
            this._executeRefreshTemplate.bind(this),
            this.renderPriority
        );
        renderQueue.executeDebounce();
    }

    /**
     * Execute the refresh template if needed.
     *
     * This is an auxiliar method executed when the component is being refreshed.
     * The rendering queue determines the order of the components to be rendered
     * and allows parent compoments to inject the template into the child components.
     *
     * @private
     * @returns {Promise<void>}
     */
    async _executeRefreshTemplate() {
        // It is possible a parent component has already removed this component.
        if (!this.element.isConnected) {
            this.reactive.unregisterComponent(this);
            return;
        }

        // Also, it is possible some parent component has injected the template.
        if (this.injectedTemplateContent !== null) {
            VirtualDOM.applyHTMLElement(this, this.injectedTemplateContent);
            this.injectedTemplateContent = null;
            return;
        }

        await this._reloadTemplateIfNeeded();
    }

    /**
     * Inject the template content into the component.
     *
     * This method is called when a parent component is applying a virtual dom
     * and fins a subcomponent that allows template injection.
     *
     * @param {HTMLElement} newContent content to apply.
     */
    injectContent(newContent) {
        if (!this.allowTemplateInjection()) {
            return;
        }
        this.injectedTemplateContent = newContent;
    }

    /**
     * Reload the template if needed.
     *
     * @private
     * @returns {Promise<void>}
     */
    async _reloadTemplateIfNeeded() {
        let templateData = this.getTemplateData();

        // If the component cannot generate its own data is because it expects
        // a parent component to inject the template. However, if we are here means
        // we do not have injected content. This could happen because the base element
        // has been changed and the component is replaced.
        if (templateData === undefined && this.allowTemplateInjection()) {
            this.reactive.unregisterComponent(this);
            return;
        }

        if (templateData === undefined) {
            throw new Error(
                'Method getTemplateData must return the template data, or null to remove  (' + this.name ?? 'unkown' + ').'
            );
        }

        // Null data means the component should be removed.
        if (templateData === null) {
            this.reactive.unregisterComponent(this);
            return;
        }

        // Reactive data cannot be altered outsite a mutation. However, the template library
        // will add some new data. We need to copy all the data to a full copy before continuing.
        // Furthermore, we also need the currentTemplateData to be a full copy to compare new values.
        const templateDataJson = JSON.stringify(templateData);
        templateData = JSON.parse(templateDataJson);

        if (templateDataJson === JSON.stringify(this.currentTemplateData)) {
            return;
        }

        const templateName = this.getTemplateName();
        if (!templateName) {
            throw new Error(
                'Method getTemplateName must return the template name (' + this.name ?? 'unkown' + ').'
            );
        }

        try {
            const {html, js} = await Templates.renderForPromise(templateName, templateData);
            VirtualDOM.applyTemplate(this, html, js);
            // Await Templates.replaceNodeContents(favouriteArea, html, js);
            this.currentTemplateData = templateData;
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Calculate the component render priority.
     *
     * @returns {number} zero for independent components, or depth for injectable components.
     */
    calculateRenderPriority() {
        // Components without template injections will be rendered first because
        // they will be the ones that will inject the template into the parent.
        if (!this.allowTemplateInjection()) {
            return 0;
        }

        // For components with template injection the priority is how deep is the component tree.
        let priority = 0;
        let parent = this.element.parentElement;
        while (parent) {
            if (parent.getAttribute('data-mdl-component-hash')) {
                priority++;
            }
            parent = parent.parentElement;
        }
        return priority;
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
