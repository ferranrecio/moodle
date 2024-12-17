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
 * Controls the fragment overview loadings.
 *
 * @module     core_course/local/overview/overviewpage
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fragment from 'core/fragment';
import Templates from 'core/templates';
import Pending from 'core/pending';
import {eventTypes as collapsableSectionEventTypes} from 'core/local/collapsable_section/events';

export const init = async(selector) => {
    const pageElement = document.querySelector(selector);
    if (!pageElement) {
        throw new Error('No elements found with the selector: ' + selector);
    }

    pageElement.addEventListener(
        collapsableSectionEventTypes.shown,
        event => {
            const fragmentElement = getFragmentContainer(event.target);
            if (!fragmentElement) {
                return;
            }
            loadFragmentContent(fragmentElement);
        }
    );
};

const loadFragmentContent = (element) => {
    if (element.dataset.loaded) {
        return;
    }

    const pendingReload = new Pending(`course_overviewtable_${element.dataset.modname}`);

    const promise = Fragment.loadFragment(
        'core_course',
        'course_overview',
        element.dataset.contextid,
        {
            courseid: element.dataset.courseid,
            modname: element.dataset.modname,
        }
    );

    promise.then(async(html, js) => {
        Templates.runTemplateJS(js);
        element.innerHTML = html;
        // Templates.replaceNode(element, html, js);
        element.dataset.loaded = true;
        pendingReload.resolve();
        return true;
    }).catch(() => {
        pendingReload.resolve();
    });
};

const getFragmentContainer = (element) => {
    const result = element.querySelector('[data-region="loading-icon-container"]');
    if (!result) {
        return null;
    }
    if (!result.dataset.contextid || !result.dataset.courseid || !result.dataset.modname) {
        throw new Error('The element is missing required data attributes.');
    }
    return result;
};
