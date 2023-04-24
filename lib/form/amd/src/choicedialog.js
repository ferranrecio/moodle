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
 * Choice Dialog Form element.
 *
 * @module core_form/choicedialog
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.3
 */

import {get_string as getString} from 'core/str';
import {markFormAsDirty} from 'core_form/changechecker';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {prefetchStrings} from 'core/prefetch';

prefetchStrings('core', ['apply']);

const CLASSES = {
    NOTCLICKABLE: 'not-clickable',
    HIDDEN: 'd-none',
};

const SELECTORS = {
    PREVIEWICON: `[data-for='choicedialog-icon']`,
    PREVIEWTEXT: `[data-for='choicedialog-selected']`,
};

/**
 * Internal form element class.
 *
 * @private
 * @class     ChoiceDialog
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ChoiceDialog {
    /**
     * Class constructor.
     *
     * @param {String} elementId Form element id
     */
    constructor(elementId) {
        this.elementId = elementId;
        this.mainSelect = document.getElementById(this.elementId);
        this.preview = document.getElementById(`${this.elementId}_preview`);
        this.modal = null;

        const label = document.querySelector(`label[for='${this.mainSelect?.id}']`);
        if (label) {
            this.fieldName = label.innerText;
        } else {
            this.fieldName = '';
        }
    }

    /**
     * Add form element event listener.
     */
    addEventListeners() {
        if (!this.mainSelect || !this.preview) {
            return;
        }
        this.preview.addEventListener(
            'click',
            this.showModal.bind(this)
        );
        this.mainSelect.addEventListener(
            'change',
            this.updateChoicePreview.bind(this)
        );
        // Enabling or disabling the select does not trigger any JS event.
        const observerCallback = (mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type !== 'attributes' || mutation.attributeName !== 'disabled') {
                    return;
                }
                this.updateChoicePreview();
            });
        };
        new MutationObserver(observerCallback).observe(
            this.mainSelect,
            {attributes: true, attributeFilter: ['disabled']}
        );
    }

    /**
     * Show the choice modal.
     * @param {Event} event the click event
     */
    showModal(event) {
        event.preventDefault();
        if (this.isDisabled()) {
            return;
        }
        const setupPending = new Pending('core_form:choiceDialogSetup');
        if (this.modal !== null) {
            this.modal.show().then(() => {
                this.modal.getSelectedRadio()?.focus();
                setupPending.resolve();
                return;
            }).catch(Notification.exception);
            return;
        }
        const buttonTextPromise = getString('apply', 'core');
        const modalParams = {
            title: this.fieldName,
            body: this.generateRadioOptions(),
            type: ModalFactory.types.RADIO,
        };
        ModalFactory.create(modalParams).then(async(modal) => {
            const applyText = await buttonTextPromise;
            modal.setSaveButtonText(applyText);
            modal.show();
            this.addModalEventHandlers(modal);
            modal.getRadioReadyPromise().then(() => {
                modal.getSelectedRadio()?.focus();
                setupPending.resolve();
                return;
            }).catch(Notification.exception);
            this.modal = modal;
            return;
        }).catch(Notification.exception);
    }

    /**
     * Generate the radio modal options array.
     * @return {Array}
     */
    generateRadioOptions() {
        const radioOptions = [];

        const selectedOptionIndex = this.mainSelect.selectedIndex;
        let optionIndex = 0;

        const options = this.mainSelect.querySelectorAll(`option`);
        for (const option of options) {
            const radioOption = this.getOptionPreviewData(option);
            radioOption.value = optionIndex;
            if (optionIndex === selectedOptionIndex) {
                radioOption.selected = true;
            }
            radioOptions.push(radioOption);
            optionIndex++;
        }
        return radioOptions;
    }

    /**
     * Generate the preview data of a specific select option.
     * @param {HTMLElement} option the select option element
     * @returns {Object} the preview template data
     */
    getOptionPreviewData(option) {
        const optionPeview = {
            "name": option.innerText,
        };
        if (this.isDisabled() || option.disabled) {
            optionPeview.disabled = true;
        }
        if (option.dataset?.description) {
            optionPeview.description = decodeURIComponent(option.dataset.description);
        }
        if (option.dataset?.icon) {
            optionPeview.icon = decodeURIComponent(option.dataset.icon);
        }
        return optionPeview;
    }

    /**
     * Get the field icon.
     * @returns {String}
     */
    getFieldIcon() {
        if (this.mainSelect?.dataset?.fieldicon) {
            return decodeURIComponent(this.mainSelect.dataset.fieldicon);
        }
        return '';
    }

    /**
     * Setup radio modal events.
     * @param {Modal} modal the loaded modal event
     */
    addModalEventHandlers(modal) {
        modal.getRoot().on(
            ModalEvents.save,
            () => {
                const previousSelection = this.mainSelect.selectedIndex;
                const selectedOptionIndex = modal.getSelectedValue();

                if (previousSelection === selectedOptionIndex) {
                    return;
                }
                this.mainSelect.selectedIndex = selectedOptionIndex;
                markFormAsDirty(this.mainSelect.closest('form'));
                // Change the select element via JS does not trigger the standard change event.
                this.mainSelect.dispatchEvent(new Event('change'));
            }
        );
    }

    /**
     * Check if the field is disabled.
     * @returns {Boolean}
     */
    isDisabled() {
        return this.mainSelect?.hasAttribute('disabled');
    }

    /**
     * Update selected option preview in form.
     */
    async updateChoicePreview() {
        if (!this.mainSelect || !this.preview) {
            return;
        }

        this.preview.disabled = this.isDisabled();
        this.preview.classList.toggle(CLASSES.NOTCLICKABLE, this.preview.disabled);

        const selectedIndex = this.mainSelect.selectedIndex;
        if (this.preview.dataset.selectedIndex == selectedIndex) {
            return;
        }
        const selectedOption = this.mainSelect.options[selectedIndex];
        const context = this.getOptionPreviewData(selectedOption);
        this.preview.querySelector(SELECTORS.PREVIEWTEXT).innerHTML = context.name;
        this.preview.querySelector(SELECTORS.PREVIEWICON).innerHTML = context.icon ?? this.getFieldIcon();
        this.preview.dataset.selectedIndex = selectedIndex;
    }

    /**
     * Disable the choice dialog and convert it into a regular select field.
     */
    disableInteractiveDialog() {
        this.mainSelect?.classList.remove(CLASSES.HIDDEN);
        this.preview?.classList.remove(...this.preview.classList);
        this.preview?.classList.add(CLASSES.HIDDEN);
    }

    /**
     * Check if the field has a force dialog attribute.
     *
     * The force dialog is a setting to force the javascript control even in
     * behat test.
     *
     * @returns {Boolean} if the dialog modal should be forced or not
     */
    hasForceDialog() {
        return !!this.mainSelect?.dataset.forceDialog;
    }
}

/**
 * Initialises a choice dialog field.
 *
 * @method init
 * @param {String} elementId Form element id
 * @listens event:uploadStarted
 * @listens event:uploadCompleted
 */
export const init = (elementId) => {
    const choicedialog = new ChoiceDialog(elementId);
    // This field is just a select wrapper. To optimize tests, we don't want to keep behat
    // waiting for extra loadings in this case. The set field steps are about testing other
    // stuff, not to test fancy javascript form fields. However, we keep the possibility of
    // testing the javascript part using behat when necessary.
    if (document.body.classList.contains('behat-site') && !choicedialog.hasForceDialog()) {
        choicedialog.disableInteractiveDialog();
        return;
    }
    choicedialog.addEventListeners();
    choicedialog.updateChoicePreview();
};
