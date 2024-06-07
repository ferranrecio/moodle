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

/*
 * This code is based on the following articles:
 * https://dev.to/joydeep-bhowmik/virtual-dom-diffing-algorithm-implementation-in-vanilla-javascript-2324
 * https://dev.to/joydeep-bhowmik/adding-keys-our-dom-diffing-algorithm-4d7g
 */

/**
 * Return the node type.
 * @param {HTMLElement} node
 * @returns {String}
 */
function getnodeType(node) {
    if (node.nodeType == Node.ELEMENT_NODE) {
        return node.tagName.toLowerCase();
    } else {
        return node.nodeType;
    }
}

/**
 * Clean the node from comments and empty text nodes.
 *
 * @param {Node} node
 */
function clean(node) {
    for (var n = 0; n < node.childNodes.length; n++) {
        var child = node.childNodes[n];
        if (
            child.nodeType === Node.COMMENT_NODE ||
            (child.nodeType === Node.TEXT_NODE && !/\S/.test(child.nodeValue) && child.nodeValue.includes('\n'))
        ) {
            node.removeChild(child);
            n--;
        } else if (child.nodeType === Node.ELEMENT_NODE) {
            clean(child);
        }
    }
}

/**
 * Parse the html string and return the main element.
 * @param {String} html
 * @returns {HTMLElement}
 */
// function parseHTML(html) {
//     const element = document.createElement('div');
//     element.innerHTML = html;

//     window.console.log(html);
//     window.console.log(element);

//     if (element.childElementCount != 1) {
//         throw new Error('The HTML must have only one root element');
//     }

//     return clean(element.firstElementChild);
// }

/**
 * Parse the html string and return the main element.
 * @param {String} html
 * @returns {HTMLElement}
 */
function parseHTML(html) {
    let parser = new DOMParser();
    let doc = parser.parseFromString(html, 'text/html');

    clean(doc.body);

    if (doc.body.childElementCount != 1) {
        throw new Error('The HTML must have only one root element');
    }

    return doc.body.firstChild;
}

/**
 * Create an index of the attributes of the element.
 *
 * @param {HTMLElement} element
 */
function attrbutesIndex(element) {
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
 * Create an index of the attributes of the element.
 * @param {HTMLElement} vdom the virtual dom element
 * @param {HTMLElement} dom the real dom element
 */
function patchAttributes(vdom, dom) {
    let vdomAttributes = attrbutesIndex(vdom);
    let domAttributes = attrbutesIndex(dom);
    if (vdomAttributes == domAttributes) {
        return;
    }
    Object.keys(vdomAttributes).forEach(key => {
        // If the attribute is not present in dom then add it.
        if (!dom.getAttribute(key)) {
            dom.setAttribute(key, vdomAttributes[key]);
        } // If the atrtribute is present than compare it.
        else if (dom.getAttribute(key)) {
            if (vdomAttributes[key] != domAttributes[key]) {
                dom.setAttribute(key, vdomAttributes[key]);
            }
        }
    });
    Object.keys(domAttributes).forEach(key => {
        // If the attribute is not present in vdom than remove it.
        if (!vdom.getAttribute(key)) {
            dom.removeAttribute(key);
        }
    });
}

/**
 * Apply the template to the reactive component main element.
 *
 * @param {HTMLElement} element
 * @param {String} html the template html.
 * @param {String} js the template js.
 */
function applyTemplate(element, html, js) {
    const newContent = parseHTML(html);

    // const keyIndex = getKeyElementsIndex(newContent, element);

    diff(newContent, element);

    // Temporal: remove all child nodes from the element.
    // while (element.firstChild) {
    //     element.removeChild(element.firstChild);
    // }
    // Temporal: append the new content children to the element.
    // while (newContent.firstChild) {
    //     element.appendChild(newContent.firstChild);
    // }

    Templates.runTemplateJS(js);
}

/**
 * Replace all vdom elements with data-mdl-key attribute with the real dom elements.
 *
 * @param {HTMLElement} vdom the virtual dom element
 * @param {HTMLElement} dom the real dom element
 */
// function replaceKeyElements(vdom, dom) {
//     const elementsWithDataMdlKey = vdom.querySelectorAll('[data-mdl-key]');
//     elementsWithDataMdlKey.forEach(element => {
//         const key = element.getAttribute('data-mdl-key');
//         const newElement = dom.querySelector(`[data-mdl-key="${key}"]`);
//         if (!newElement) {
//             return;
//         }
//         // Subcomponents can have the same keys as the parent. We ignore any key that is not part of this component.
//         const parentComponent = newElement.closest('[data-mdl-component-hash]');
//         if (parentComponent.getAttribute('data-mdl-component') != dom.getAttribute('data-mdl-component')) {
//             return;
//         }
//         if (newElement) {
//             element.parentNode.insertBefore(newElement, element);
//             element.remove();
//         }
//     });
// }

/**
 * Scan all key elements present in the virtual dom and return the list of the dom ones.
 *
 * @param {HTMLElement} vdom the virtual dom element
 * @param {HTMLElement} dom the real dom element
 */
// function getKeyElementsIndex(vdom, dom) {
//     const result = new Map();
//     const elementsWithDataMdlKey = vdom.querySelectorAll('[data-mdl-key]');
//     elementsWithDataMdlKey.forEach(element => {
//         const key = element.getAttribute('data-mdl-key');
//         const newElement = dom.querySelector(`[data-mdl-key="${key}"]`);
//         if (!newElement) {
//             return;
//         }
//         // Subcomponents can have the same keys as the parent. We ignore any key that is not part of this component.
//         const parentComponent = newElement.closest('[data-mdl-component-hash]');
//         if (parentComponent.getAttribute('data-mdl-component') != dom.getAttribute('data-mdl-component')) {
//             return;
//         }
//         result.set(key, newElement);
//     });
//     return result;
// }

/**
 * Replace all indexed elements in the dom with the new ones.
 *
 * @param {HTMLElement} dom
 * @param {Map} keyIndex
 */
// function replaceIndexedElements(dom, keyIndex) {
//     keyIndex.forEach((element, key) => {
//         const newElement = dom.querySelector(`[data-mdl-key="${key}"]`);
//         if (!newElement) {
//             return;
//         }
//         element.parentNode.insertBefore(newElement, element);
//         element.remove();
//     });
// }

/**
 * Execute a diff.
 * @param {HTMLElement} vdom virtual dom element
 * @param {HTMLElement} dom real dom element
 */
function diff(vdom, dom) {
    // If dom has no childs then append the childs from vdom.
    if (dom.hasChildNodes() == false && vdom.hasChildNodes() == true) {
        for (var i = 0; i < vdom.childNodes.length; i++) {
            // Appending.
            dom.append(vdom.childNodes[i].cloneNode(true));
        }
    } else {
        // If both nodes are equal then no need to compare farther.
        if (vdom.isEqualNode(dom)) {
            return;
        }
        // If dom has extra child.
        if (dom.childNodes.length > vdom.childNodes.length) {
            let count = dom.childNodes.length - vdom.childNodes.length;
            if (count > 0) {
                for (; count > 0; count--) {
                    dom.childNodes[dom.childNodes.length - count].remove();
                }
            }
        }
        // Now comparing all childs.
        for (let i = 0; i < vdom.childNodes.length; i++) {
            // If the node is not present in dom append it.
            if (dom.childNodes[i] == undefined) {
                dom.append(vdom.childNodes[i].cloneNode(true));
            } else if (getnodeType(vdom.childNodes[i]) == getnodeType(dom.childNodes[i])) {
                // If same node type.
                // If the nodeType is text.
                if (vdom.childNodes[i].nodeType == Node.TEXT_NODE) {
                    // We check if the text content is not same.
                    if (vdom.childNodes[i].textContent != dom.childNodes[i].textContent) {
                        // Replace the text content.
                        dom.childNodes[i].textContent = vdom.childNodes[i].textContent;
                    }
                } else {
                    patchAttributes(vdom.childNodes[i], dom.childNodes[i]);
                }
            } else {
                // Replace.
                dom.childNodes[i].replaceWith(vdom.childNodes[i].cloneNode(true));
            }
            if (vdom.childNodes[i].nodeType != Node.TEXT_NODE) {
                diff(vdom.childNodes[i], dom.childNodes[i]);
            }
        }
    }
}

export default {
    applyTemplate,
};


/**
 * TODO describe module virtualdom
 *
 * @module     core/local/reactive/virtualdom
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// class VirtualDOM {
//     constructor(originalElement, newElement) {
//         this.originalElement = originalElement;
//         this.newElement = newElement;
//     }

//     diff() {
//         if (this.originalElement.childElementCount > 1 || this.newElement.childElementCount > 1) {
//             throw new Error('Both the original and new content must have only one root element');
//         }

//         this.moveElementsWithDataMdlKey();
//         this.updateElements();
//     }

//     moveElementsWithDataMdlKey() {
//         const elementsWithDataMdlKey = this.originalElement.querySelectorAll('[data-mdl-key]');
//         elementsWithDataMdlKey.forEach(element => {
//             const key = element.getAttribute('data-mdl-key');
//             const newElement = this.newElement.querySelector(`[data-mdl-key="${key}"]`);
//             if (newElement) {
//                 newElement.appendChild(element);
//             }
//         });
//     }

//     updateElements() {
//         const elements = this.newElement.querySelectorAll('*:not([data-mdl-reactivecomponent])');
//         elements.forEach(element => {
//             const originalElement = this.originalElement.querySelector(
//                      `[data-mdl-key="${element.getAttribute('data-mdl-key')}"]`
//             );
//             if (originalElement) {
//                 originalElement.replaceWith(element.cloneNode(true));
//             }
//         });
//     }
// }

// const originalElement = document.getElementById('original-element');
// const newElement = document.getElementById('new-element');

// const virtualDOM = new VirtualDOM(originalElement, newElement);
// virtualDOM.diff();
