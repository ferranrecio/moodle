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

import ComponentBase from 'core_course/local/editor/component';
import editor from 'core_course/editor';
import log from 'core/log';
import Templates from 'core/templates';


class Component extends ComponentBase {

    /**
     * Static method to create a component instance form the mustahce template.
     *
     * We use a static method to prevent mustache templates to know which
     * reactive instance is used.
     *
     * @param {element|string} target the DOM main element or its ID
     * @param {object} newselectors optional css selector overrides
     * @return {Component}
     */
    static init(target, newselectors) {
        let newcomponent = new Component(editor);
        return newcomponent.register(target, newselectors);
    }

    /**
     * Component create hook.
     *
     * @returns {boolean}
     */
    create() {
        // TODO: for now we replace the default drawer. Dele this when we have a proper
        // course index component.
        this.getElement().innerHTML = 'Loading course index...';
        return true;
    }

    /**
     * Render the real course index using the course state.
     *
     * @param {object} state the initial state
     */
    stateReady(state) {
        // We are ready to replace the lazy load element with the real course index.
        // Generate mustache data from the current state.
        const data = {
            sections: [],
            editmode: this.reactive.isEditing(),
        };
        const sectionlist = state.course.sectionlist ?? [];
        sectionlist.forEach(sectionid => {
            const sectioninfo = state.section.get(sectionid) ?? {};
            const section = {
                title: sectioninfo.title,
                visible: sectioninfo.visible,
                id: sectionid,
                cms: [],
            };
            const cmlist = sectioninfo.cmlist ?? [];
            cmlist.forEach(cmid => {
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
                this.getElement().innerHTML = '';
                Templates.appendNodeContents(this.getElement(), html, js);
                return true;
            }).fail((ex) => {
                log.error('Cannot load course index template');
                log.error(ex);
            });
    }
}

export default Component;
