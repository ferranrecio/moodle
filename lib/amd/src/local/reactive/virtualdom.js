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

import Templates from 'core/templates';

/**
 * @typedef {import('./templatecomponent')} TemplateComponent
 */

/**
 * Attributes that are protected from being removed.
 * @type {Array}
 */
const protectecAttributes = [
    'data-mdl-component-hash',
    'data-mdl-refresh'
];

/**
 * Basic VirtualDOM differ for reactive components.
 *
 * This code is loosely based on the following articles:
 * https://dev.to/joydeep-bhowmik/virtual-dom-diffing-algorithm-implementation-in-vanilla-javascript-2324
 * https://dev.to/joydeep-bhowmik/adding-keys-our-dom-diffing-algorithm-4d7g
 *
 * @module     core/local/reactive/virtualdom
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class {

    /**
     * The constructor.
     *
     * @private
     * @param {TemplateComponent} component
     */
    constructor(component) {
        this.component = component;
        this.keyIndex = null;
    }

    /**
     * Static method to apply the template to a component.
     *
     * @param {TemplateComponent} component the reactive component
     * @param {String} html
     * @param {String} js
     */
    static applyTemplate(component, html, js) {
        const virtualDom = new this(component);
        virtualDom._applyVirtualDomFromTemplate(html, js);
    }

    /**
     * Static method to apply a virtual dom element to a component.
     *
     * @param {TemplateComponent} component the reactive component
     * @param {HTMLElement} vdom the virtual dom element
     */
    static applyHTMLElement(component, vdom) {
        const virtualDom = new this(component);
        virtualDom._applyVirtualDomFromElement(vdom);
    }

    /**
     * Clean the component dom.
     *
     * The method will remove any comment or empty text node from the element.
     *
     * @param {HTMLElement} element
     */
    static cleanDomElement(element) {
        const virtualDom = new this();
        virtualDom.clean(element);
    }

    /**
     * Apply the virtual dom to the component from a loaded template.
     *
     * @private
     * @param {String} newContent the new content
     * @param {String} [newJs] the new js
     */
    _applyVirtualDomFromTemplate(newContent, newJs) {
        const vdom = this._parseHTML(newContent);
        this._applyVirtualDomFromElement(vdom);
        if (newJs !== undefined) {
            Templates.runTemplateJS(newJs);
        }
    }

    /**
     * Scan all key elements present in the virtual dom and return the list of the dom ones.
     *
     * @private
     * @param {HTMLElement} vdom the virtual dom element
     * @param {HTMLElement} dom the real dom element
     * @returns {Map}
     */
    _getKeyElementsIndex(vdom, dom) {
        const result = new Map();
        const elementsWithDataMdlKey = vdom.querySelectorAll('[data-mdl-key]');
        elementsWithDataMdlKey.forEach(element => {
            const key = element.getAttribute('data-mdl-key');
            const domElement = dom.querySelector(`[data-mdl-key="${key}"]`);
            if (!domElement) {
                return;
            }
            // Subcomponents can have the same keys as the parent. We ignore any key that is not part of this component.
            const parentComponent = domElement.closest('[data-mdl-component-hash]');
            if (parentComponent.getAttribute('data-mdl-component-hash') != this.component.getComponentHash()) {
                return;
            }
            result.set(key, domElement);
        });
        return result;
    }

    /**
     * Apply the virtual dom to the component from an virtual dom element.
     *
     * @private
     * @param {HTMLElement} vdom the element to diff
     */
    _applyVirtualDomFromElement(vdom) {
        const dom = this.component.getElement();
        this.keyIndex = this._getKeyElementsIndex(vdom, dom);
        this._diff(vdom, dom);
        this._cleanMismatchedComponents(dom);
    }

    /**
     * Execute a diff.
     *
     * @private
     * @param {HTMLElement} vdom virtual dom element
     * @param {HTMLElement} dom real dom element
     */
    _diff(vdom, dom) {
        if (!this._needsToDiffed(vdom, dom)) {
            return;
        }
        // If dom has no childs then append the childs from vdom.
        if (dom.hasChildNodes() === false && vdom.hasChildNodes() === true) {
            for (let i = 0; i < vdom.childNodes.length; i++) {
                dom.append(vdom.childNodes[i].cloneNode(true));
            }
            return;
        }

        this._diffChilds(vdom, dom);
    }

    /**
     * Validate if the element needs to be diffed.
     *
     * @private
     * @param {HTMLElement} vdom virtual dom element
     * @param {HTMLElement} dom real dom element
     * @returns
     */
    _needsToDiffed(vdom, dom) {
        if (dom.getAttribute('data-mdl-refresh') === 'static') {
            return false;
        }
        if (dom.getAttribute('data-mdl-refresh') === 'inject') {
            return true;
        }

        if (this._injectContentToElementComponent(vdom, dom) === true) {
            // The subcomponent will take care of the diff for us.
            return false;
        }

        if (vdom.isEqualNode(dom)) {
            return false;
        }
        return true;
    }

    /**
     * Try to inject the content into an element component, if any.
     *
     * When injecting the content, the subcomponent will be responsible for the diffing
     * and the diff can stop at this point.
     *
     * @private
     * @param {HTMLElement} vdom
     * @param {HTMLElement} dom
     * @returns {Boolean} if the content was injected or not.
     */
    _injectContentToElementComponent(vdom, dom) {
        if (
            !dom.hasAttribute('data-mdl-component-hash') ||
            dom.getAttribute('data-mdl-component-hash') === this.component.getComponentHash()
        ) {
            return false;
        }

        const subcomponent = this.component.getElementComponent(dom);
        if (!subcomponent) {
            return false;
        }

        // Each template component has it's unique selector (mandatory for creation),
        // if it does not match means that the component is not the same.
        const componentSelector = subcomponent.getMainSelector();
        if (!componentSelector || !vdom.matches(componentSelector)) {
            // Add a mark into the element so we can remove the hash
            // if not processed at the end of the diff.
            dom.addAtribute('data-mdl-vrdom-component-clean', 'true');
            return false;
        }

        if (subcomponent && subcomponent.allowTemplateInjection()) {
            dom.removeAttribute('data-mdl-vrdom-component-clean');
            subcomponent.injectContent(vdom);
        }
        return true;
    }

    /**
     * Forces refreshing components that do not match their selector.
     *
     * This method is called at the end of the full diff to force instantiate new
     * components if the component selector does not match for some reason.
     *
     * @param {HTMLElement} dom
     */
    _cleanMismatchedComponents(dom) {
        const elements = dom.querySelectorAll('[data-mdl-vrdom-component-clean]');
        elements.forEach(element => {
            element.removeAttribute('data-mdl-component-hash');
            element.removeAttribute('data-mdl-vrdom-component-clean');
        });
    }

    /**
     * Move all child elements with data-mdl-key attribute to the same position as the vdom.
     *
     * @private
     * @param {HTMLElement} vdom virtual dom element
     * @param {HTMLElement} dom real dom element
     */
    _sortChildKeyElements(vdom, dom) {
        if (vdom.querySelector(':scope > [data-mdl-key]') === null) {
            return;
        }
        for (let i = 0; i < vdom.childNodes.length; i++) {
            const key = vdom.childNodes[i].getAttribute('data-mdl-key');
            if (!key || !this.keyIndex.has(key)) {
                continue;
            }
            const newElement = this.keyIndex.get(key);
            if (dom.childNodes[i] === newElement) {
                continue;
            }
            dom.insertBefore(newElement, dom.childNodes[i]);
        }
    }

    /**
     * Remove all unnecessary childs from the dom before the diff.
     *
     * @private
     * @param {HTMLElement} vdom virtual dom element
     * @param {HTMLElement} dom real dom element
     */
    _removeUnnecessaryChilds(vdom, dom) {
        if (dom.childNodes.length <= vdom.childNodes.length) {
            return;
        }
        let count = dom.childNodes.length - vdom.childNodes.length;
        for (; count > 0; count--) {
            dom.lastChild.remove();
        }
    }

    /**
     * Diff all childs.
     *
     * @private
     * @param {HTMLElement} vdom virtual dom element
     * @param {HTMLElement} dom real dom element
     */
    _diffChilds(vdom, dom) {
        this._sortChildKeyElements(vdom, dom);
        this._removeUnnecessaryChilds(vdom, dom);
        for (let i = 0; i < vdom.childNodes.length; i++) {
            if (dom.childNodes[i] === undefined) {
                dom.append(vdom.childNodes[i].cloneNode(true));
            } else {
                this._diffElement(vdom.childNodes[i], dom.childNodes[i]);
            }
            if (vdom.childNodes[i].nodeType !== Node.TEXT_NODE) {
                this._diff(vdom.childNodes[i], dom.childNodes[i]);
            }
        }
    }

    /**
     * Diff a single element.
     *
     * @private
     * @param {HTMLElement} vdomNode
     * @param {HTMLElement} domNode
     */
    _diffElement(vdomNode, domNode) {
        if (this._getNodeType(vdomNode) !== this._getNodeType(domNode)) {
            domNode.replaceWith(vdomNode.cloneNode(true));
            return;
        }

        if (vdomNode.nodeType === Node.TEXT_NODE) {
            if (vdomNode.textContent !== domNode.textContent) {
                domNode.textContent = vdomNode.textContent;
            }
        } else {
            this._patchAttributes(vdomNode, domNode);
        }
    }

    /**
     * Return the node type.
     *
     * @private
     * @param {HTMLElement} node
     * @returns {String}
     */
    _getNodeType(node) {
        if (node.nodeType == Node.ELEMENT_NODE) {
            return node.tagName.toLowerCase();
        }
        return node.nodeType;
    }

    /**
     * Create an index of the attributes of the element.
     *
     * @private
     * @param {HTMLElement} vdom the virtual dom element
     * @param {HTMLElement} dom the real dom element
     */
    _patchAttributes(vdom, dom) {
        let vdomAttributes = this._attributesIndex(vdom);
        let domAttributes = this._attributesIndex(dom);
        if (vdomAttributes == domAttributes) {
            return;
        }
        Object.keys(vdomAttributes).forEach(key => {
            this._updateAttribute(dom, key, vdomAttributes[key]);
        });
        Object.keys(domAttributes).forEach(key => {
            if (vdomAttributes[key] === undefined) {
                this._removeAttribute(dom, key);
            }
        });
    }

    /**
     * Set the attribute to the element if allowed to.
     *
     * @param {HTMLElement} dom
     * @param {String} name
     * @param {String} value
     */
    _updateAttribute(dom, name, value) {
        if (dom.hasAttribute(name) && dom.getAttribute(name) === value) {
            return;
        }
        if (!this._canUpdateAttribute(dom, name)) {
            return;
        }
        dom.setAttribute(name, value);
    }

    /**
     * Remove the attribute from the element if allowed to.
     *
     * @param {HTMLElement} dom
     * @param {HTMLElement} name
     * @returns
     */
    _removeAttribute(dom, name) {
        if (!dom.hasAttribute(name)) {
            return;
        }
        if (!this._canUpdateAttribute(dom, name)) {
            return;
        }
        dom.removeAttribute(name);
    }

    /**
     * Check if the attribute can be updated.
     *
     * @param {HTMLElement} dom
     * @param {String} name
     */
    _canUpdateAttribute(dom, name) {
        if (protectecAttributes.includes(name)) {
            return false;
        }
        if (this._isEditableFormElement(dom)) {
            // We don't want to lose the user input when refreshing the template,
            // unless the temaplte explicitly asks for using data-mdl-refresh="inject".
            if (dom.closest('[data-mdl-refresh="inject"]')) {
                return true;
            }
            return name !== 'value' && name !== 'checked' && name !== 'selected';
        }
        return true;
    }

    /**
     * Check if the element is a form element the user can edit or select.
     *
     * @param {HTMLElement} dom
     * @returns
     */
    _isEditableFormElement(dom) {
        return dom.tagName === 'INPUT' ||
            dom.tagName === 'TEXTAREA' ||
            dom.tagName === 'SELECT' ||
            dom.tagName === 'OPTION';
    }

    /**
     * Create an index of the attributes of the element.
     *
     * @private
     * @param {HTMLElement} element
     */
    _attributesIndex(element) {
        var attributes = {};
        if (element.attributes == undefined) {
            return attributes;
        }
        for (var i = 0, atts = element.attributes, n = atts.length; i < n; i++) {
            attributes[atts[i].name] = atts[i].value;
        }
        return attributes;
    }

    /**
     * Parse the html string and return the main element.
     *
     * @private
     * @param {String} html
     * @returns {HTMLElement}
     */
    _parseHTML(html) {
        let parser = new DOMParser();
        let doc = parser.parseFromString(html, 'text/html');

        this.clean(doc.body);

        if (doc.body.childElementCount != 1) {
            throw new Error('The HTML must have only one root element');
        }

        return doc.body.firstChild;
    }

    /**
     * Clean the node from comments and empty text nodes.
     *
     * @private
     * @param {Node} node
     */
    clean(node) {
        for (let n = 0; n < node.childNodes.length; n++) {
            const child = node.childNodes[n];
            if (
                child.nodeType === Node.COMMENT_NODE ||
                (child.nodeType === Node.TEXT_NODE && !/\S/.test(child.nodeValue) && child.nodeValue.includes('\n'))
            ) {
                node.removeChild(child);
                n--;
            } else if (child.nodeType === Node.ELEMENT_NODE) {
                this.clean(child);
            }
        }
    }
}
