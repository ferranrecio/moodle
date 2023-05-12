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
 * Template renderer for Moodle. Load and render Moodle templates with Mustache.
 *
 * @module     theme_boost/loader
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import $ from 'jquery';
import * as Aria from './aria';
import Bootstrap from './index';
import log from 'core/log';
import Pending from 'core/pending';
import setupBootstrapPendingChecks from './pending';

/**
 * Rember the last visited tabs.
 */
const rememberTabs = () => {
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var hash = $(e.target).attr('href');
        if (history.replaceState) {
            history.replaceState(null, null, hash);
        } else {
            location.hash = hash;
        }
    });
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector('[role="tablist"] [href="' + hash + '"]');
        if (tab) {
            tab.click();
        }
    }
};

/**
 * Enable all popovers
 *
 */
const enablePopovers = () => {
    enableTooltips();
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && e.target.closest('[data-toggle="popover"]')) {
            $(e.target).popover('hide');
        }
    });
};

/**
 * Enable tooltips
 *
 */
const enableTooltips = () => {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    for (var tooltipTriggerEl of tooltipTriggerList) {
        if (!Bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
            new Bootstrap.Tooltip(tooltipTriggerEl);
        }
    }
    // All Bootstrap 4 minimal backward compatibility will be removed when MDL-XXXXX is integrated.
    const legacyBs4Tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    for (var legacyBs4Tooltip of legacyBs4Tooltips) {
        if (!Bootstrap.Tooltip.getInstance(legacyBs4Tooltip)) {
            log.debug('data-toggle="tooltip" is deprecated, use data-bs-toggle instead.');
            new Bootstrap.Tooltip(legacyBs4Tooltip);
        }
    }
};

const pendingPromise = new Pending('theme_boost/loader:init');

// Add pending promise event listeners to relevant Bootstrap custom events.
setupBootstrapPendingChecks();

// Setup Aria helpers for Bootstrap features.
Aria.init();

// Remember the last visited tabs.
rememberTabs();

// Enable all popovers.
enablePopovers();

// Enable all tooltips.
enableTooltips();

// Disables flipping the dropdowns up or dynamically repositioning them along the Y-axis (based on the viewport)
// to prevent the dropdowns getting hidden behind the navbar or them covering the trigger element.
$.fn.dropdown.Constructor.Default.popperConfig = {
    modifiers: {
        flip: {
            enabled: false,
        },
        storeTopPosition: {
            enabled: true,
            // eslint-disable-next-line no-unused-vars
            fn(data, options) {
                data.storedTop = data.offsets.popper.top;
                return data;
            },
            order: 299
        },
        restoreTopPosition: {
            enabled: true,
            // eslint-disable-next-line no-unused-vars
            fn(data, options) {
                data.offsets.popper.top = data.storedTop;
                return data;
            },
            order: 301
        }
    },
};

pendingPromise.resolve();

export {
    Bootstrap,
};
