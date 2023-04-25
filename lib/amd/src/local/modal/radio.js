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
 * A radio buttons selector modal.
 *
 * @module     core/local/modal/radio
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Templates from 'core/templates';
import {getFirst} from 'core/normalise';

const selectors = {
    OPTIONSRADIO: `[type='radio']`,
};

/**
 * The Radio selection modal.
 *
 * @class
 * @extends module:core/modal
 */
export default class extends Modal {
    /**
     * The component descriptor data structure.
     *
     * @typedef {Object} RadioOption
     * @property {String} value the option value
     * @property {String} name the displayed option name
     * @property {String} [description] the option optional description
     * @property {String} [id] the optional input element id
     * @property {String} [icon] an optional option icon
     * @property {Boolean} [disable] optional option disable
     * @property {Boolean} [selected] optional option selected
     */

    /**
     * Modal constructor.
     * @param {object} root The root jQuery element for the modal
     */
    constructor(root) {
        // Modal is still using jQuery internally.
        super(root);

        if (!this.getFooter().find(this.getActionSelector('save')).length) {
            Notification.exception({message: 'No save button found'});
        }

        if (!this.getFooter().find(this.getActionSelector('cancel')).length) {
            Notification.exception({message: 'No cancel button found'});
        }

        this.modalSetupPromise = new Pending('core/modal:radioModalSetup' + this.getModalCount());

        this.optionsCount = 0;
        this.selectedValue = null;

        // The save button is not enabled until the user selects an option.
        this.setButtonDisabled('save', true);
    }

    /**
     * Returns the radio options ready promise.
     * @returns {Promise}
     */
    getRadioReadyPromise() {
        return this.modalSetupPromise;
    }

    /**
     * Return the current user selected option.
     * @returns {String|null}
     */
    getSelectedValue() {
        return this.selectedValue;
    }

    /**
     * Register all event listeners.
     */
    registerEventListeners() {
        super.registerEventListeners();
        this.registerCloseOnSave();
        this.registerCloseOnCancel();
    }

    /**
     * Override parent implementation to prevent changing the footer content.
     */
    setFooter() {
        Notification.exception({message: 'Can not change the footer of a radio modal'});
        return;
    }

    /**
     * Set the title of the save button.
     *
     * @param {String|Promise} value The button text, or a Promise which will resolve it
     * @returns{Promise}
     */
    setSaveButtonText(value) {
        return this.setButtonText('save', value);
    }

    /**
     * Return the current selected radio button if any.
     *
     * Note: this method will return undefined once the modal is distroyed.
     *
     * @return {HTMLElement|undefined}
     */
    getSelectedRadio() {
        const modalBody = getFirst(this.getBody());
        return modalBody?.querySelector(`${selectors.OPTIONSRADIO}:checked`);
    }

    /**
     * Set modal body using the array of options.
     * @param {RadioOption[]} options the body options descriptor.
     */
    async setBody(options) {
        if (!Array.isArray(options)) {
            Notification.exception({message: 'Radio modal body should be an array of options'});
        }

        const modalOptions = [];
        for (const option of options) {
            const normalizedOption = await this._normaliseOptionObject(option);
            modalOptions.push(normalizedOption);
        }

        const bodyPromise = Templates.render('core/local/modal/radiobody', {options: modalOptions});

        this.getRoot().on(ModalEvents.bodyRendered, () => {
            if (this.selectedValue !== null) {
                this.setButtonDisabled('save', false);
            }
            this._registerRadioEventListeners();
        });
        super.setBody(bodyPromise);
    }

    /**
     * Get an option data and add return a choice with all necessary data.
     * @private
     * @param {Object} option
     * @returns {Object}
     */
    async _normaliseOptionObject(option) {
        this.optionsCount++;

        if (option.value === undefined || option.name === undefined) {
            Notification.exception({message: 'Missing name or value in radio modal option.'});
        }
        const modalOption = {
            value: option.value,
            name: option.name,
            optionid: option.id ?? `radioModalOption${this.optionsCount}`,
            optionnumber: this.optionsCount,
        };
        if (this.optionsCount == 1) {
            modalOption.first = true;
        }
        // Prevent mustache from missinterpretating any false optional attributes.
        if (option.description) {
            modalOption.description = option.description;
        }
        if (option.icon) {
            modalOption.icon = option.icon;
        }
        if (option.disabled) {
            modalOption.disabled = true;
        }
        if (option.selected) {
            this.selectedValue = option.value;
            modalOption.selected = true;
        }
        // Assume non-string values are Promises to be resolved.
        for (const awaitValue of ['name', 'description', 'icon']) {
            if (modalOption[awaitValue] !== undefined && typeof modalOption[awaitValue] !== 'string') {
                modalOption[awaitValue] = await modalOption[awaitValue];
            }
        }
        return modalOption;
    }

    /**
     * Internal method to register Radio Selection Events.
     * @private
     */
    _registerRadioEventListeners() {
        const modalBody = getFirst(this.getBody());
        const radioOptions = modalBody.querySelectorAll(selectors.OPTIONSRADIO);
        radioOptions.forEach(radio => {
            radio.addEventListener('change', () => {
                this._updateSelectedValue();
                this.setButtonDisabled('save', false);
            });
            radio.parentNode.addEventListener('click', () => {
                if (radio.disabled) {
                    return;
                }
                radio.checked = true;
                this._updateSelectedValue();
                this.setButtonDisabled('save', false);
            });
            const submitHandler = (event) => {
                if (radio.disabled) {
                    return;
                }
                event.preventDefault();
                this._updateSelectedValue();
                this.dispatchActionEvent(ModalEvents.save);
            };
            radio.parentNode.addEventListener('dblclick', submitHandler);
            radio.addEventListener(
                'keydown',
                (event) => {
                    // Old Firefox versions use "Space" instead of " ".
                    if (["Enter", " ", "Space"].includes(event.key)) {
                        submitHandler(event);
                    }
                });
        });
        this.modalSetupPromise.resolve();
    }

    /**
     * Update the selected value attributte.
     * @private
     */
    _updateSelectedValue() {
        const originalValue = this.selectedValue;
        const selectedRadio = this.getSelectedRadio();
        if (selectedRadio) {
            this.selectedValue = selectedRadio.value;
        }
        if (originalValue !== this.selectedValue) {
            this._dispatchValueChangedEvent();
        }
    }

    /**
     * Dispatch a value change event.
     * @private
     */
    _dispatchValueChangedEvent() {
        const target = getFirst(this.getRoot());
        if (target === undefined) {
            target = document;
        }
        target.dispatchEvent(new CustomEvent(
            ModalEvents.radioChanged,
            {bubbles: true, detail: this}
        ));
    }
}
