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
 * Schema selector javascript controls.
 *
 * This module controls:
 * - The select all feature.
 * - Disabling activities checkboxes when the section is not selected.
 * - Move the delegated section to the correct place.
 *
 * @module     core_backup/schema_backup_form
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';

/**
 * Adds select all/none links to the top of the backup/restore/import schema page.
 */
class BackupFormController {
    /**
     * Creates a new instance of the SchemaBackupForm class.
     * @param {Array<string>} modNames - The names of the modules.
     */
    constructor(modNames) {
        this.selectors = {
            firstSection: 'fieldset#id_coursesettings .fcontainer .grouped_settings.section_level',
            checkboxes: 'input[type="checkbox"]',
            allIncluded: '#backup-all-included',
            noneIncluded: '#backup-none-included',
            allUserdata: '#backup-all-userdata',
            noneUserdata: '#backup-none-userdata',
            modsToggler: '#mod_select_links_toggler',
            parentingFields: '[data-form-parent]',
            parentingRegion: '[data-region="structure_element"]',
        };
        this.selectorGenerators = {
            cmAll: (modName) => `#backup-all-mod_${modName}`,
            cmNone: (modName) => `#backup-none-mod_${modName}`,
            cmAllUserdata: (modName) => `#backup-all-userdata-mod_${modName}`,
            cmNoneUserdata: (modName) => `#backup-none-userdata-mod_${modName}`,
        };
        this.modNames = modNames;
        this.formId = null;
        this.withuserdata = false;
    }

    /**
     * Fetches the required strings for the backup form.
     * @returns {Promise<void>} A promise that resolves when the strings are fetched.
     */
    async fetchStrings() {
        const stringsToLoad = [
            {key: 'all', component: 'moodle'},
            {key: 'none', component: 'moodle'},
            {key: 'select', component: 'moodle'},
            {key: 'showtypes', component: 'backup'},
            {key: 'hidetypes', component: 'backup'},
        ];
        const loadedStrings = await getStrings(stringsToLoad);

        let count = 0;
        this.strings = stringsToLoad.reduce((strings, stringData) => {
            strings[stringData.key] = loadedStrings[count];
            count++;
            return strings;
        }, {});
    }

    /**
     * Handles the click event for the select all/none checkboxes.
     *
     * @param {Event} event - The event object.
     * @param {boolean} checked - The checked state for the checkboxes.
     * @param {string} type - The type of checkbox.
     * @param {string} [modName] - The module name.
     */
    clickSelectorLinkHandler(event, checked, type, modName) {
        event.preventDefault();

        const prefix = modName ? `setting_activity_${modName}_` : null;

        const checkboxes = document.querySelectorAll(this.selectors.checkboxes);
        for (const checkbox of checkboxes) {
            if (prefix && !checkbox.name.startsWith(prefix)) {
                continue;
            }
            if (checkbox.name.endsWith(type)) {
                checkbox.checked = checked;
            }
        }

        // At this point, we really need to persuade the form we are part of to
        // update all of its disabledIf rules. However, as far as I can see,
        // given the way that lib/form/form.js is written, that is impossible.
        if (this.formId && M.form) {
            M.form.updateFormState(this.formId);
        }
    }

    /**
     * Returns the HTML markup for a select all/none checkbox field.
     *
     * @param {string} classname - The class name for the container div.
     * @param {string} typeName - The ID type for the checkbox.
     * @param {string} heading - The heading for the checkbox field.
     * @param {string} [extra] - Additional HTML markup to include.
     * @returns {string} The HTML markup for the select all/none checkbox field.
     */
    getSelectAllNoneCheckboxField(classname, typeName, heading, extra) {
        extra = extra || '';
        return `<div class="${classname}" id="backup_selectors_${typeName}">
            <div class="fitem fitem_fcheckbox backup_selector">
                <div class="fitemtitle">${heading}</div>
                <div class="felement">
                    <a id="backup-all-${typeName}" href="#">${this.strings.all}</a> /
                    <a id="backup-none-${typeName}" href="#">${this.strings.none}</a>
                    ${extra}
                </div>
            </div>
        </div>`;
    }

    /**
     * Returns a collapse link HTML.
     *
     * @param {string} elementId - The ID of the element to collapse.
     * @param {string} linkText - The text to display for the link.
     * @returns {string} The collapse link HTML element.
     */
    getCollapseLink(elementId, linkText) {
        return `<a
            id="${elementId}_toggler"
            data-toggle="collapse"
            href="#${elementId}"
            aria-expanded="false"
            aria-controls="${elementId}"
        >
            ${linkText}
        </a>`;
    }

    /**
     * Generate DOM element containing a list of modules with select all/none checkboxes.
     * @returns {HTMLElement} The DOM element representing the module list.
     */
    generateModulesSelectorsElement() {
        const modlist = document.createElement('div');
        modlist.id = 'mod_select_links';
        modlist.className = 'collapse';
        modlist.currentlyshown = false;

        for (const modName in this.modNames) {
            if (!this.modNames.hasOwnProperty(modName)) {
                continue;
            }
            let html = this.getSelectAllNoneCheckboxField(
                'include_setting section_level',
                'mod_' + modName,
                this.modNames[modName]
            );
            if (this.withuserdata) {
                html += this.getSelectAllNoneCheckboxField(
                    'normal_setting',
                    'userdata-mod_' + modName,
                    this.modNames[modName]
                );
            }

            const modlinks = document.createElement('div');
            modlinks.className = 'grouped_settings section_level';
            modlinks.innerHTML = html;
            this.initModulesSelectorsEvents(modlinks, modName);
            modlist.appendChild(modlinks);
        }
        return modlist;
    }

    /**
     * Initializes the event listeners for the module selectors in the UI.
     *
     * @param {HTMLElement} element - The parent element containing the module selectors.
     * @param {string} modName - The name of the module.
     */
    initModulesSelectorsEvents(element, modName) {
        const backupAll = element.querySelector(this.selectorGenerators.cmAll(modName));
        backupAll.addEventListener('click', (e) => {
            this.clickSelectorLinkHandler(e, true, '_included', modName);
        });

        const backupNone = element.querySelector(this.selectorGenerators.cmNone(modName));
        backupNone.addEventListener('click', (e) => {
            this.clickSelectorLinkHandler(e, false, '_included', modName);
        });

        if (this.withuserdata) {
            const backupAllUserdata = element.querySelector(this.selectorGenerators.cmAllUserdata(modName));
            backupAllUserdata.addEventListener('click', (e) => {
                this.clickSelectorLinkHandler(e, true, this.withuserdata, modName);
            });

            const backupNoneUserdata = element.querySelector(this.selectorGenerators.cmNoneUserdata(modName));
            backupNoneUserdata.addEventListener('click', (e) => {
                this.clickSelectorLinkHandler(e, false, this.withuserdata, modName);
            });
        }
    }

    /**
     * Returns the global select all/none container element.
     * @returns {HTMLElement} The global selectors element.
     */
    generateGlobalSelectorsElement() {
        let html = this.getSelectAllNoneCheckboxField(
            'include_setting section_level',
            'included',
            this.strings.select,
            this.getCollapseLink('mod_select_links', `(${this.strings.showtypes})`)
        );
        if (this.withuserdata) {
            html += this.getSelectAllNoneCheckboxField(
                'normal_setting',
                'userdata',
                this.strings.select
            );
        }
        const links = document.createElement('div');
        links.className = 'grouped_settings section_level';
        links.innerHTML = html;
        this.initGlobalSelectorsEvents(links);

        return links;
    }

    /**
     * Initializes the global selectors events.
     *
     * @param {HTMLElement} element - The element to attach the events to.
     */
    initGlobalSelectorsEvents(element) {
        const modSelectLinksToggler = element.querySelector(this.selectors.modsToggler);
        modSelectLinksToggler.addEventListener('click', () => {
            // Wait a bit to let the collapse animation finish.
            setTimeout(this.refreshModulesSelectorToggler.bind(this), 100);
        });

        element.querySelector(this.selectors.allIncluded).addEventListener('click', (e) => {
            this.clickSelectorLinkHandler(e, true, '_included');
        });
        element.querySelector(this.selectors.noneIncluded).addEventListener('click', (e) => {
            this.clickSelectorLinkHandler(e, false, '_included');
        });
        if (this.withuserdata) {
            element.querySelector(this.selectors.allUserdata).addEventListener('click', (e) => {
                this.clickSelectorLinkHandler(e, true, this.withuserdata);
            });
            element.querySelector(this.selectors.noneUserdata).addEventListener('click', (e) => {
                this.clickSelectorLinkHandler(e, false, this.withuserdata);
            });
        }
    }

    /**
     * Refreshes the modules selector toggler.
     *
     * The toggler uses Bootstrap collapsible.
     * However, the link text is not updated automatically.
     */
    refreshModulesSelectorToggler() {
        const modSelectLinksToggler = document.querySelector(this.selectors.modsToggler);
        let linkText;
        if (modSelectLinksToggler.getAttribute('aria-expanded') === 'true') {
            linkText = this.strings.hidetypes;
        } else {
            linkText = this.strings.showtypes;
        }
        modSelectLinksToggler.textContent = `(${linkText})`;
    }

    /**
     * Generates the full selectors element to add to the page.
     *
     * @returns {HTMLElement} The selectors element.
     */
    generateSelectorsElement() {
        const links = this.generateGlobalSelectorsElement();
        // Add select all/none for each module type.
        links.appendChild(this.generateModulesSelectorsElement());
        return links;
    }

    /**
     * Adds select all/none functionality to the backup form.
     */
    addSelectors() {
        const firstSection = document.querySelector(this.selectors.firstSection);
        if (!firstSection) {
            // This is not a relevant page.
            return;
        }
        if (!firstSection.querySelector(this.selectors.checkboxes)) {
            // No checkboxes.
            return;
        }

        this.formId = firstSection.closest('form').getAttribute('id');

        const checkboxes = document.querySelectorAll(this.selectors.checkboxes);
        checkboxes.forEach((checkbox) => {
            const name = checkbox.name;
            if (name.endsWith('_userdata')) {
                this.withuserdata = '_userdata';
            } else if (name.endsWith('_userinfo')) {
                this.withuserdata = '_userinfo';
            }
        });

        // Add global select all/none options.
        firstSection.parentNode.insertBefore(this.generateSelectorsElement(), firstSection);
    }

    moveChildrenToParent() {
        window.console.log('moveChildrenToParent');
        const parentingFields = document.querySelectorAll(this.selectors.parentingFields);
        for (const field of parentingFields) {
            window.console.log('field', field);
            const parentName = field.getAttribute('data-form-parent');
            const fieldRegion = field.closest(this.selectors.parentingRegion);
            const parentElement = document.querySelector(`[name='${parentName}']`)?.closest(this.selectors.parentingRegion);
            window.console.log('fieldRegion', fieldRegion, this.selectors.parentingRegion);
            window.console.log('parentElement', parentElement);
            if (!fieldRegion || !parentElement) {
                continue;
            }
            this.moveInnerStructureToParent(fieldRegion, parentElement);
            fieldRegion.classList.add('d-none');
        }
        window.console.log('/moveChildrenToParent');
    }

    moveInnerStructureToParent(fieldRegion, parentElement) {
        const innerStructureNodes = fieldRegion.querySelectorAll(this.selectors.parentingRegion);
        // Elements will be added as siblings of the parent elements.
        const grandParent = parentElement.parentNode;
        let beforeElement = parentElement.nextSibling;
        for (const node of innerStructureNodes) {
            this.addSubElementClasses(node);
            grandParent.insertBefore(node, beforeElement);
            beforeElement = node.nextSibling;
        }
    }

    addSubElementClasses(node) {
        node.classList.add('sub-element');
        node.classList.add('pl-3');
    }


    /**
     * Initializes the schema backup form.
     * @returns {Promise<void>} A promise that resolves when the initialization is complete.
     */
    async init() {
        await this.fetchStrings();
        this.addSelectors();
        this.moveChildrenToParent();
    }
}

/**
 * Initializes the backup form.
 *
 * @param {Array<string>} modNames - The names of the modules.
 */
export const init = (modNames) => {
    const formController = new BackupFormController(modNames);
    formController.init();
};
