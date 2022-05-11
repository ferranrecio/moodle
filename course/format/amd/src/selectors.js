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
 * The default course selectors.
 *
 * @module     core_courseformat/selectors
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default {
    content: {
        course: {
            page: `#page`,
            sectionList: `[data-for='course_sectionlist']`,
            activityHeader: `[data-for='page-activity-header']`,
            dragIcon: `.editing_move`,
        },
        section: {
            badges: `[data-region="sectionbadges"]`,
            cmList: `[data-for='cmlist']`,
            info: `[data-for="sectioninfo"]`,
            item: `[data-for='section_title']`,
            node: `[data-for='sectionnode']`,
            section: `[data-for='section']`,
            // Formats can override the section tag but a default one is needed to create new elements.
            tag: 'li',
        },
        cm: {
            cm: `[data-for='cm']`,
            item: `[data-for='cmitem']`,
            // Formats can override the activity tag but a default one is needed to create new elements.
            tag: 'li',
        },
        controls: {
            actionLink: `[data-action]`,
            actionMenu: `.action-menu`,
            actionText: `.menu-action-text`,
            collapse: `[data-toggle="collapse"]`,
            hideSection: `[data-action="sectionHide"]`,
            icon: `.icon`,
            showSection: `[data-action="sectionShow"]`,
            toggler: `[data-action="togglecoursecontentsection"]`,
            toggleAll: `[data-toggle="toggleall"]`,
        },
        modals: {
            addSection: `[data-action='addSection']`,
            contentTree: `#destination-selector`,
            cmLink: `[data-for='cm']`,
            menuToggler: `[data-toggle="dropdown"]`,
            sectionLink: `[data-for='section']`,
            sectionNode: `[data-for='sectionnode']`,
            toggler: `[data-toggle='collapse']`,
            autoDelete: `[name='autodelete']`,
            uploaditem: `[data-for="uploaditem"]`,
            progressbar: `progress`,
        },
        classes: {
            activity: `activity`,
            collapsed: `collapsed`,
            disabled: `disabled`,
            hasDescription: 'description',
            hidden: 'dimmed',
            hide: 'd-none',
            locked: 'editinprogress',
            pageItem: 'pageitem',
            restrictions: 'restrictions',
            section: `section`,
            stateReady: `stateready`,
        },
    },
    courseindex: {
        course: {
            drawer: `.drawer`,
        },
        section: {
            cmLast: `[data-for="cm"]:last-child`,
            cmList: `[data-for='cmlist']`,
            item: `[data-for='section_item']`,
            section: `[data-for='section']`,
            title: `[data-for='section_title']`,
        },
        cm: {
            cm: `[data-for='cm']`,
            completion: `[data-for='cm_completion']`,
            name: `[data-for='cm_name']`,
        },
        controls: {
            collapse: `[data-toggle="collapse"]`,
            toogler: `[data-action="togglecourseindexsection"]`,
        },
        classes: {
            cmHidden: 'dimmed',
            collapsed: `collapsed`,
            locked: 'editinprogress',
            pageItem: 'pageitem',
            restrictions: 'restrictions',
            sectionCurrent: 'current',
            sectionHidden: 'dimmed',
            show: `show`,
        }
    },
};
