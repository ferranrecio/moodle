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
 * The course file uploader.
 *
 * This module is used to upload files directly into the course.
 *
 * @module     core_courseformat/local/courseeditor/fileuploader
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';
import events from 'core_course/events';
import log from 'core/log';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import UploadMonitor from 'core_courseformat/local/courseeditor/uploadmonitor';
import {getFirst} from 'core/normalise';
import {prefetchStrings} from 'core/prefetch';
import {get_string as getString} from 'core/str';
import {getCourseEditor} from 'core_courseformat/courseeditor';
import {Reactive} from 'core/reactive';

// Uploading url.
const uploadUrl = Config.wwwroot + '/course/dndupload.php';

/** @var {FileUploader} uploader the internal reactive uploader.  */
let uploader = null;

const initialState = {
    process: {
        current: 0,
        maxbytes: 0,
    },
    pending: [],
    uploading: [],
};

// Load global strings.
prefetchStrings('moodle', ['addresourceoractivity', 'upload']);

/**
 * The reactive file uploader class.
 *
 * As all the upload queues are reactive, any plugin can implement its own upload monitor.
 *
 * @module     core_courseformat/local/courseeditor/fileuploader
 * @class     core_courseformat/local/courseeditor/fileuploader
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class FileUploader extends Reactive {

    /** @var {Map} editorUpdates the courses pending to be updated. */
    editorUpdates = new Map();

    /** @var {Object} lastHandlers the last handler name. */
    lastHandlers = {};

    /**
     * Refresh all pending course refreshes.
     */
    refreshCourseEditors() {
        // Get pending refreshes.
        const refreshes = this.editorUpdates;
        this.editorUpdates = new Map();
        // Iterate all pending refreshes.
        refreshes.forEach((sectionIds, courseId) => {
            const courseEditor = getCourseEditor(courseId);
            if (courseEditor) {
                courseEditor.dispatch('sectionState', [...sectionIds]);
            }
        });
    }

    /**
     * Add a section to refresh.
     * @param {number} courseId the course id
     * @param {number} sectionId the seciton id
     */
    addRefreshSection(courseId, sectionId) {
        let refresh = this.editorUpdates.get(courseId);
        if (!refresh) {
            refresh = new Set();
        }
        refresh.add(sectionId);
        this.editorUpdates.set(courseId, refresh);
    }

    /**
     * Starts the current file upload.
     */
    async processCurrent() {
        const item = this.currentItem;
        if (!item) {
            return;
        }
        const courseEditor = getCourseEditor(item.courseId);
        if (!courseEditor) {
            return;
        }
        // Get the handlers list from the course editor.
        const allFileHandlers = await courseEditor.getFileHandlersPromise();
        const fileHandlers = getFileHandlers(item.fileInfo, allFileHandlers);
        if (fileHandlers.length == 0) {
            // Show unkown file warning.
        }
        if (fileHandlers.length == 1) {
            this.dispatch('uploadItem', item, fileHandlers[0]);
            return;
        }
        if (fileHandlers.length > 0) {
            this._showUploadModal(fileHandlers);
            return;
        }
    }

    /**
     * Get the file handlers of an specific file.
     * @private
     * @param {Array} fileHandlers the course file handlers
     */
    async _showUploadModal(fileHandlers) {
        const item = uploader.currentItem;
        const extension = getFileExtension(item.fileInfo);
        if (!item) {
            return;
        }
        const data = {
            filename: item.fileInfo.name,
            uploadid: item.id,
            handlers: [],
        };
        let hasDefault = false;
        fileHandlers.forEach((handler, index) => {
            const isDefault = (this.lastHandlers[extension] == handler.module);
            data.handlers.push({
                ...handler,
                selected: isDefault,
                labelid: `fileuploader_${data.uploadid}`,
                value: index,
            });
            hasDefault = hasDefault || isDefault;
        });
        if (!hasDefault && data.handlers.length > 0) {
            const lastHandler = data.handlers.pop();
            lastHandler.selected = true;
            data.handlers.push(lastHandler);
        }
        // Build the modal parameters from the event data.
        const modalParams = {
            title: getString('addresourceoractivity', 'moodle'),
            body: Templates.render('core_courseformat/fileuploader', data),
            type: ModalFactory.types.SAVE_CANCEL,
            saveButtonText: getString('upload', 'moodle'),
        };
        // Create the modal.
        const modal = await modalBodyRenderedPromise(modalParams);
        const modalBody = getFirst(modal.getBody());

        modal.getRoot().on(
            ModalEvents.save,
            event => {
                // Get the selected option.
                const index = modalBody.querySelector('input:checked').value;
                event.preventDefault();
                modal.destroy();
                if (fileHandlers[index]) {
                    this.dispatch('uploadItem', item, fileHandlers[index]);
                    // Save last selected handler.
                    this.lastHandlers[extension] = fileHandlers[index].module;
                }
            }
        );
        modal.getRoot().on(
            ModalEvents.cancel,
            () => {
                this.dispatch('discardItem', item);
            }
        );
    }

    /**
     * Starts a file uploading.
     * @param {Object} item the pending item
     * @param {String} item.id the upload id
     * @param {String} item.courseId the course id
     * @param {String} item.sectionId the section id
     * @param {File} item.fileInfo the file info
     * @param {Object} fileHandler the file handler object
     */
    startUploading(item, fileHandler) {
        if (this.maxbytes > 0 && item.fileInfo.size > this.maxbytes) {
            log.error(`File size over the limit: ${item.fileInfo.name}`);
            uploader.dispatch('finishUpload', item.id, false);
            return;
        }
        uploadFile(item, fileHandler);
    }

    /**
     * Return the current pending item.
     * @return {Object} the current item.
     */
    get currentItem() {
        return this.state.pending.get(this.state.process.current);
    }

    /**
     * Return the maxbytes.
     * @return {number}
     */
    get maxbytes() {
        // TODO: move maxbytes to course state.
        return this.state.process.maxbytes;
    }
}

/** @var {number} lastUploadId the last upload id. */
let lastUploadId = 0;

/**
 * @var {Object} the upload queue mutations.
 */
const mutations = {
    /**
     * Add a new pending file.
     *
     * @param {StateManager} stateManager the current state manager
     * @param {number} courseId the course id
     * @param {number} sectionId the course id
     * @param {number} sectionNum the course number
     * @param {File} fileInfo the file info
     */
    addPending: function(stateManager, courseId, sectionId, sectionNum, fileInfo) {
        // Generate uploadId and data structure.
        lastUploadId++;
        const item = {
            id: lastUploadId,
            courseId,
            sectionId,
            sectionNum,
            fileInfo,
            percent: 0,
        };
        // Adds a new element to the pending queue.
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        state.pending.add(item);
        stateManager.setReadOnly(true);
        // Send the process next pending.
        this.processNextPending(stateManager);
    },
    /**
     * Discards the current pending file and starts the next processing.
     *
     * @param {StateManager} stateManager the current state manager
     * @param {Object} item the item to discard
     * @param {number} item.id the item id
     */
    discardItem: function(stateManager, item) {
        const state = stateManager.state;
        if (!state.pending.has(item.id)) {
            return;
        }
        // Remove the current pending element.
        stateManager.setReadOnly(false);
        state.pending.delete(state.process.current);
        if (state.process.current == item.id) {
            state.process.current = 0;
        }
        stateManager.setReadOnly(true);
        // Send the process next pending.
        if (state.process.current == 0) {
            this.processNextPending(stateManager);
        }
    },
    /**
     * Upload the current file an starts the next processing.
     *
     * @param {StateManager} stateManager the current state manager
     * @param {Object} item the item to discard
     * @param {number} item.id the item id
     * @param {Object} fileHandler the file handler object
     */
    uploadItem: function(stateManager, item, fileHandler) {
        this.discardItem(stateManager, item);
        const state = stateManager.state;
        // Move element to uploading.
        stateManager.setReadOnly(false);
        state.uploading.add({...item});
        stateManager.setReadOnly(true);
        // Init upload process.
        uploader.startUploading(item, fileHandler);
    },

    /**
     * Starts the next processing.
     *
     * @param {StateManager} stateManager the current state manager
     */
    processNextPending: function(stateManager) {
        // Check if there is any ongoing process (exit if so)
        const state = stateManager.state;
        if (state.process.current != 0) {
            return;
        }
        if (state.pending.size == 0) {
            return;
        }
        // Set the new current pending element.
        stateManager.setReadOnly(false);
        const [firstKey] = state.pending.keys();
        state.process.current = firstKey;
        stateManager.setReadOnly(true);
        // Send the process function.
        uploader.processCurrent();
    },
    /**
     * Finishes a uploaded file.
     *
     * @param {StateManager} stateManager the current state manager
     * @param {number} uploadId the upload id to finish
     * @param {boolean} success if the upload is success or not
     */
    finishUpload: function(stateManager, uploadId, success) {
        const state = stateManager.state;
        // Get the file.
        const item = state.uploading.get(uploadId);
        if (!item) {
            return;
        }
        // Add the course section to refresh queue.
        if (success) {
            uploader.addRefreshSection(item.courseId, item.sectionId);
        }
        // Remove from the list.
        stateManager.setReadOnly(false);
        state.uploading.delete(uploadId);
        stateManager.setReadOnly(true);
        // Process the pensing refreshes.
        if (state.uploading.size == 0) {
            uploader.refreshCourseEditors();
        }
    },
    /**
     * Set the upload percentage of a file.
     *
     * @param {StateManager} stateManager the current state manager
     * @param {number} uploadId the upload id to finish
     * @param {number} percent the new percentage
     */
    setPercentage: function(stateManager, uploadId, percent) {
        const state = stateManager.state;
        // Get the file.
        const item = state.uploading.get(uploadId);
        if (!item) {
            return;
        }
        // Alter the percentage.
        stateManager.setReadOnly(false);
        item.percent = percent;
        stateManager.setReadOnly(true);
    },
};

/**
 * Upload a file into the course.
 *
 * @private
 * @param {Object} item the item element to upload
 * @param {File} item.fileInfo the file info
 * @param {number} item.sectionNum the section number.
 * @param {number} item.sectionId the section id.
 * @param {Array} fileHandler the course file handler
 */
async function uploadFile(item, fileHandler) {
    const fileInfo = item.fileInfo;

    const xhr = createXhrRequest(item);

    const formData = createUploadFormData(item, fileHandler, xhr);
    if (!formData) {
        log.error(`File read error: ${fileInfo.name}`);
        uploader.dispatch('finishUpload', item.id, false);
        return;
    }

    // Try reading the file to check it is not a folder, before sending it to the server.
    const reader = new FileReader();
    reader.onload = function() {
        // File was read OK - send it to the server.
        xhr.open("POST", uploadUrl, true);
        xhr.send(formData);
    };
    reader.onerror = function() {
        // Unable to read the file (it is probably a folder) - display an error message.
        log.error(`File read error: ${fileInfo.name}`);
        uploader.dispatch('finishUpload', item.id, false);
    };
    if (fileInfo.size > 0) {
        // If this is a non-empty file, try reading the first few bytes.
        // This will trigger reader.onerror() for folders and reader.onload() for ordinary, readable files.
        reader.readAsText(fileInfo.slice(0, 5));
    } else {
        // If you call slice() on a 0-byte folder, before calling readAsText, then Firefox triggers reader.onload(),
        // instead of reader.onerror().
        // So, for 0-byte files, just call readAsText on the whole file (and it will trigger load/error functions as expected).
        reader.readAsText(fileInfo);
    }
}

/**
 * Generate a upload XHR file request.
 *
 * @private
 * @param {Object} item the item element to upload
 * @param {File} item.fileInfo the file info
 * @param {number} item.sectionNum the section number.
 * @param {number} item.sectionId the section id.
 * @return {XMLHttpRequest} the XHR request
 */
function createXhrRequest(item) {
    const xhr = new XMLHttpRequest();
    // Update the progress bar as the file is uploaded
    xhr.upload.addEventListener(
        'progress',
        (event) => {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded * 100) / event.total);
                uploader.dispatch('setPercentage', item.id, percent);
            }
        },
        false
    );
    // Wait for the AJAX call to complete, then update the
    // dummy element with the returned details
    xhr.onreadystatechange = () => {
        if (xhr.readyState == 1) {
            // Add a 1% just to indicate that it is uploading.
            uploader.dispatch('setPercentage', item.id, 1);
        }
        // State 4 is DONE. Otherwise the connection is still ongoing.
        if (xhr.readyState != 4) {
            return;
        }
        let success = false;
        if (xhr.status == 200) {
            var result = JSON.parse(xhr.responseText);
            if (result && result.error == 0) {
                // All OK.
                uploader.dispatch('setPercentage', item.id, 100);
                success = true;
            }
        }
        if (!success) {
            log.error(`Cannot upload file: ${item.fileInfo.name}`);
        }
        // Remove from uploading list.
        uploader.dispatch('finishUpload', item.id, success);
    };
    return xhr;
}

/**
 * Upload a file into the course.
 *
 * @private
 * @param {Object} item the item element to upload
 * @param {File} item.fileInfo the file info
 * @param {number} item.sectionNum the section number.
 * @param {number} item.sectionId the section id.
 * @param {Array} fileHandler the course file handler
 * @return {FormData|null} the new form data object
 */
function createUploadFormData(item, fileHandler) {
    const fileInfo = item.fileInfo;
    const formData = new FormData();
    try {
        formData.append('repo_upload_file', fileInfo);
    } catch (e) {
        return null;
    }
    formData.append('sesskey', Config.sesskey);
    formData.append('course', item.courseId);
    formData.append('section', item.sectionNum);
    formData.append('module', fileHandler.module);
    formData.append('type', 'Files');
    return formData;
}

/**
 * Get the file handlers of an specific file.
 * @private
 * @param {File} fileInfo the file info
 * @param {Array} allFileHandlers the course file handlers
 * @return {Array} of handlers
 */
const getFileHandlers = function(fileInfo, allFileHandlers) {
    const extension = getFileExtension(fileInfo);
    const fileHandlers = [];
    for (var i = 0; i < allFileHandlers.length; i++) {
        if (allFileHandlers[i].extension == '*' || allFileHandlers[i].extension == extension) {
            fileHandlers.push(allFileHandlers[i]);
        }
    }
    return fileHandlers;
};

/**
 * Extract the file extension from a fileInfo.
 * @param {File} fileInfo
 * @returns {String} the file extension or an empty string.
 */
function getFileExtension(fileInfo) {
    let extension = '';
    const dotpos = fileInfo.name.lastIndexOf('.');
    if (dotpos != -1) {
        extension = fileInfo.name.substring(dotpos + 1, fileInfo.name.length).toLowerCase();
    }
    return extension;
}

/**
 * Trigger a state changed event.
 *
 * This function will be moved to core_course/events module
 * when the file is migrated to the new JS events structure proposed in MDL-70990.
 *
 * @method dispatchStateChangedEvent
 * @param {object} detail the full state
 * @param {object} target the custom event target (document if none provided)
 */
function dispatchStateChangedEvent(detail, target) {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(new CustomEvent(events.uploadStateChanged, {
        bubbles: true,
        detail: detail,
    }));
}

/**
 * Render a modal and return a body ready promise.
 *
 * @private
 * @param {object} modalParams the modal params
 * @return {Promise} the modal body ready promise
 */
function modalBodyRenderedPromise(modalParams) {
    return new Promise((resolve, reject) => {
        ModalFactory.create(modalParams).then((modal) => {
            modal.setRemoveOnClose(true);
            // Handle body loading event.
            modal.getRoot().on(ModalEvents.bodyRendered, () => {
                resolve(modal);
            });
            // Configure some extra modal params.
            if (modalParams.saveButtonText !== undefined) {
                modal.setSaveButtonText(modalParams.saveButtonText);
            }
            modal.show();
            return;
        }).catch(() => {
            reject(`Cannot load modal content`);
        });
    });
}

/**
 * The init method.
 *
 * @return {Promise} state ready promise.
 */
function init() {
    if (uploader === null) {
        uploader = new FileUploader({
            name: `CourseFileUploader`,
            eventName: events.uploadStateChanged,
            eventDispatch: dispatchStateChangedEvent,
            mutations: mutations,
            state: initialState,
        });
        // Create a basic upload monitor.
        UploadMonitor.init(uploader);
    }
    return uploader.getInitialStatePromise();
}

/**
 * Upload a file to the course.
 *
 * This method will show any necesary modal to handle the request.
 *
 * @param {number} courseId the course id.
 * @param {number} sectionId the section id.
 * @param {number} sectionNum the section number.
 * @param {Array} files and array of files
 */
export const uploadFilesToCourse = async function(courseId, sectionId, sectionNum, files) {
    // Wait for the reactive processor.
    await init();
    for (let index = 0; index < files.length; index++) {
        const fileInfo = files[index];
        uploader.dispatch('addPending', courseId, sectionId, sectionNum, fileInfo);
    }
};

export const getUploader = function() {
    return uploader;
};
