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
 * Course index lazy load initialize module.
 *
 * @module     core_course/courseindexlazy
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import editor from 'core_course/editor';
import log from 'core/log';
import Templates from 'core/templates';

// Default component css selectors.
let cssselectors = {
    courseindex: '#courseindex',
};

// Prevent multiple initialize.
let initialized = false;

/**
 * Initialize the component.
 *
 * @param {object} newselectors optional selectors override
 * @returns {boolean}
 */
export const init = (newselectors) => {

    if (initialized) {
        return true;
    }
    initialized = true;

    // TODO: for now we replace the default drawer. Dele this when we have a proper
    // course index component.
    document.querySelector('#nav-drawer').innerHTML = 'Loading course index...';

    // Overwrite the components selectors if necessary.
    cssselectors.courseindex = newselectors.courseindex ?? cssselectors.courseindex;

    // Register the component.
    editor.registerComponent({
        name: 'courseindex_lazyload',
        getWatchers,
        stateReady,

    });

    // Bind actions if necessary.

    return true;
};

/**
 * Return a list of state watchers.
 *
 * @returns {array} an array of state watchers functions.
 */
export const getWatchers = () => {
    // In this case, this is just a lazy load. We wait until the state is loaded
    // before rendering the real course index. No watchers needed.
    return [];
};

/**
 * Render the real course index using the course state.
 *
 * @param {object} state the initial state
 */
export const stateReady = (state) => {
    // We are ready to replace the lazy load element with the real course index.
    // Generate mustache data from the current state.
    const data = {
        sections: [],
        editmode: state.course.editmode,
    };
    state.course.sectionlist.forEach(sectionid => {
        const sectioninfo = state.section.get(sectionid);
        const section = {
            title: sectioninfo.title,
            visible: sectioninfo.visible,
            id: sectionid,
            cms: [],
        };
        sectioninfo.cms.forEach(cmid => {
            const cminfo = state.cm.get(cmid);
            section.cms.push({
                name: cminfo.name,
                visible: cminfo.visible,
                id: cmid,
            });
        });
        section.hascms = (section.cms.length != 0);
        data.sections.push(section);
    });
    data.hassections = (data.sections.length != 0);

    Templates.render('core_course/local/courseindex', data)
        .then((html, js) => {
            document.querySelector(cssselectors.courseindex).innerHTML = '';
            Templates.appendNodeContents(cssselectors.courseindex, html, js);
            return true;
        }).fail((ex) => {
            log.error('Cannot load course index template');
            log.error(ex);
        });
};
