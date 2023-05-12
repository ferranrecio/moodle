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
 * Backward compatibility for Bootstrap 5.
 *
 * This module adapts the current page to Bootstrap 5. For now it does it silently as
 * many Moodle templates are still using Bootstrap 4 classes. However, when MDL-XXXXX
 * is integrated, this module will console log a warning message to inform the developer.
 * When the Boostrap 4 backward compatibility period ends in MDL-XXXXX,
 * this module will be removed.
 *
 * @module     theme_boost/bootstrap4migration
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.3
 */

const replaceBootstrap4Classes = () => {
    fullClassReplacements();
    partialClassReplacements();
};

const fullClassReplacements = () => {
    const classReplacements = [
        {bs4: 'custom-check', bs5: 'form-check'},
        {bs4: 'custom-switch', bs5: 'form-switch'},
        {bs4: 'custom-range', bs5: 'form-range'},
        {bs4: 'custom-control', bs5: 'form-check'},
        {bs4: 'custom-select', bs5: 'form-select'},
        {bs4: 'custom-file', bs5: 'form-control'},
        {bs4: 'badge-primary', bs5: 'bg.primary'},
        {bs4: 'badge-secondary', bs5: 'bg.secondary'},
        {bs4: 'badge-success', bs5: 'bg.success'},
        {bs4: 'badge-danger', bs5: 'bg.danger'},
        {bs4: 'badge-warning', bs5: 'bg.warning'},
        {bs4: 'badge-info', bs5: 'bg.info'},
        {bs4: 'badge-light', bs5: 'bg.light'},
        {bs4: 'close', bs5: 'btn-close'},
        {bs4: 'arrow', bs5: 'tooltip-arrow'},
    ];
    for (var classReplacement of classReplacements) {
        const elements = document.querySelectorAll('.' + classReplacement.bs4);
        for (const element of elements) {
            window.console.log(`BS5 class replacement`);
            element.classList.remove(classReplacement.bs4);
            element.classList.add(classReplacement.bs5);
        }
    }
};

const partialClassReplacements = () => {
    const partialClassReplacements = [
        {bs4: 'left-', bs5: 'start-'},
        {bs4: 'right-', bs5: 'end-'},
        {bs4: 'float-left', bs5: 'float-start'},
        {bs4: 'float-right', bs5: 'float-end'},
        {bs4: 'text-left', bs5: 'text-start'},
        {bs4: 'text-right', bs5: 'text-end'},
        {bs4: 'border-left', bs5: 'border-start'},
        {bs4: 'border-right', bs5: 'border-end'},
        {bs4: 'rounded-left', bs5: 'rounded-start'},
        {bs4: 'rounded-right', bs5: 'rounded-end'},
        {bs4: 'ml-', bs5: 'ms-'},
        {bs4: 'mr-', bs5: 'me-'},
        {bs4: 'pl-', bs5: 'ps-'},
        {bs4: 'pr-', bs5: 'pe-'},
    ];
    for (var partialClassReplacement of partialClassReplacements) {
        const elements = document.querySelectorAll('[class*="' + partialClassReplacement.bs4 + '"]');
        for (const element of elements) {
            window.console.log(`BS5 partial class replacement`);
            const classes = element.className.split(' ');
            // eslint-disable-next-line no-loop-func
            const bs4Classes = classes.filter(cls => cls.includes(partialClassReplacement.bs4));
            for (const bs4Class of bs4Classes) {
                const bs5Class = bs4Class.replace(partialClassReplacement.bs4, partialClassReplacement.bs5);
                element.classList.remove(bs4Class);
                element.classList.add(bs5Class);
            }
        }
    }
};

const replaceBootstrap4Attributes = () => {
    const attributeReplacements = [
        {bs4: 'data-toggle', bs5: 'data-bs-toggle'},
    ];
    for (var attributeReplacement of attributeReplacements) {
        const elements = document.querySelectorAll('[' + attributeReplacement.bs4 + ']');
        for (const element of elements) {
            element.setAttribute(attributeReplacement.bs5, element.getAttribute(attributeReplacement.bs4));
            element.removeAttribute(attributeReplacement.bs4);
        }
    }
};

const constReplaceBootstrap4Structures = () => {
    replaceMediaObject();
};

const replaceMediaObject = () => {
    // See: https://getbootstrap.com/docs/4.0/layout/media-object/ for more information.
    const media = document.querySelectorAll('.media');
    for (const element of media) {
        window.console.log(`BS4 media fix`);
        element.classList.remove('media');
        element.classList.add('d-flex');
    }
    const mediaBodies = document.querySelectorAll('.media-body');
    for (const element of mediaBodies) {
        element.classList.remove('media-body');
        element.classList.add('flex-grow-1');
    }
};

// All Bootstrap 4 minimal backward compatibility will be removed when MDL-XXXXX is integrated.
export const fixBootstrap4BackwardCompatibility = (ignore) => {
    if (ignore) {
        return;
    }
    replaceBootstrap4Classes();
    replaceBootstrap4Attributes();
    constReplaceBootstrap4Structures();

    // TODO: convert cards to grids. Yuhu.
    // TODO: convert whiteList to allowList.
    // TODO: convert fallbackPlacements to top, right, bottom, left.
};
