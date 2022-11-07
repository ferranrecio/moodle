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
 * Javascript module for deleting a database as a preset.
 *
 * @module      mod_data/importmappingdialogue
 * @copyright   2022 Amaia Anabitarte <amaia@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import Ajax from 'core/ajax';
import Url from 'core/url';
import Templates from 'core/templates';
import Modal from 'core/modal';

const selectors = {
    selectPresetButton: 'input[name="selectpreset"]',
};

/**
 * Initialize module
 */
export const init = () => {
    registerEventListeners();
};

/**
 * Register events.
 */
const registerEventListeners = () => {
    document.addEventListener('click', (event) => {
        const usepreset = event.target.closest(selectors.selectPresetButton);
        if (usepreset) {
            event.preventDefault();
            mappingusepreset(usepreset);
        }
    });
};

/**
 * Show the confirmation modal for uploading a preset.
 *
 * @param {HTMLElement} usepreset the preset to import.
 */
const mappingusepreset = (usepreset) => {
    const presetName = usepreset.dataset.presetname;
    const cmId = usepreset.dataset.cmid;

    showMappingDialogue(cmId, presetName).then((result) => {
        if (result.data && result.data.needsmapping) {
            result.data = addMappingButtons(result.data, cmId, presetName);
            let modalPromise = Templates.render('mod_data/fields_mapping_modal', result.data);
            modalPromise.then(function(html) {
                return new Modal(html);
            }).fail(Notification.exception)
                .then((modal) => {
                    modal.show();
                    return modal;
                }).fail(Notification.exception);
                return result;
        } else {
            window.location.href = Url.relativeUrl(
                'mod/data/field.php',
                {
                    id: cmId,
                    mode: 'usepreset',
                    fullname: presetName,
                },
                false
            );
        }
    });
};

/**
 * Add buttons to render on mapping modal.
 *
 * @param {array} data Current data to add buttons to.
 * @param {int} cmId The id of the current database activity.
 * @param {string} presetName The preset name to delete.
 * @return {array} Same data with buttons.
 */
const addMappingButtons = (data, cmId, presetName) => {
    const cancelButton = Url.relativeUrl(
       'mod/data/preset.php',
       {
           id: cmId,
       },
       false
    );
    data['cancel'] = cancelButton;

    const mapButton = Url.relativeUrl(
       'mod/data/field.php',
       {
           id: cmId,
           fullname: presetName,
           mode: 'usepreset',
           action: 'select',
       },
       false
    );
    data['mapfieldsbutton'] = mapButton;

    const applyButton = Url.relativeUrl(
       'mod/data/field.php',
       {
           id: cmId,
           fullname: presetName,
           mode: 'usepreset',
           action: 'notmapping'
       },
       false
    );
    data['applybutton'] = applyButton;

    return data;
};

/**
 * Check whether we should show the mapping dialogue or not.
 *
 * @param {int} cmId The id of the current database activity.
 * @param {string} presetName The preset name to delete.
 * @return {promise} Resolved with the result and warnings of deleting a preset.
 */
async function showMappingDialogue(cmId, presetName) {
    var request = {
        methodname: 'mod_data_get_mapping_information',
        args: {
            cmid: cmId,
            import: presetName,
        }
    };
    return Ajax.call([request])[0];
}
