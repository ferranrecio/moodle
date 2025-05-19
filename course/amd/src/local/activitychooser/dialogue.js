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
 * A type of dialogue used as for choosing options.
 *
 * @module     core_course/local/activitychooser/dialogue
 * @copyright  2019 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {addIconToContainer} from 'core/loadingicon';
import Carousel from 'theme_boost/bootstrap/carousel';
import {debounce} from 'core/utils';
import {end, arrowLeft, arrowRight, home, enter, space} from 'core/key_codes';
import {getFirst} from 'core/normalise';
import {getString} from 'core/str';
import Modal from 'core/modal';
import * as ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import * as Repository from 'core_course/local/activitychooser/repository';
import selectors from 'core_course/local/activitychooser/selectors';
import Tab from 'theme_boost/bootstrap/tab';
import * as Templates from 'core/templates';
const getPlugin = pluginName => import(pluginName);

/**
 * Given an event from the main module 'page' navigate to it's help section via a carousel.
 *
 * @method showModuleHelp
 * @param {Element} carousel Our initialized carousel to manipulate
 * @param {Object} moduleData Data of the module to carousel to
 * @param {jQuery} modal We need to figure out if the current modal has a footer.
 */
const showModuleHelp = (carousel, moduleData, modal = null) => {
    // If we have a real footer then we need to change temporarily.
    if (modal !== null && moduleData.showFooter === true) {
        modal.setFooter(Templates.render('core_course/local/activitychooser/footer_partial', moduleData));
    }
    const help = carousel.querySelector(selectors.regions.help);
    help.innerHTML = '';
    help.classList.add('m-auto');

    // Add a spinner.
    const spinnerPromise = addIconToContainer(help);

    // Used later...
    let transitionPromiseResolver = null;
    const transitionPromise = new Promise(resolve => {
        transitionPromiseResolver = resolve;
    });

    // Build up the html & js ready to place into the help section.
    const contentPromise = Templates.renderForPromise('core_course/local/activitychooser/help', moduleData);

    // Wait for the content to be ready, and for the transition to be complet.
    Promise.all([contentPromise, spinnerPromise, transitionPromise])
        .then(([{html, js}]) => Templates.replaceNodeContents(help, html, js))
        .then(() => {
            help.querySelector(selectors.regions.chooserSummary.header).focus();
            return help;
        })
        .catch(Notification.exception);

    // Move to the next slide, and resolve the transition promise when it's done.
    carousel.addEventListener('slid.bs.carousel', () => {
        transitionPromiseResolver();
    }, {once: true});
    // Trigger the transition between 'pages'.
    Carousel.getInstance(carousel).next();
};

/**
 * Given a user wants to change the favourite state of a module we either add or remove the status.
 * We also propergate this change across our map of modals.
 *
 * @method manageFavouriteState
 * @param {HTMLElement} modalBody The DOM node of the modal to manipulate
 * @param {HTMLElement} caller
 * @param {Function} partialFavourite Partially applied function we need to manage favourite status
 */
const manageFavouriteState = async(modalBody, caller, partialFavourite) => {
    const isFavourite = caller.dataset.favourited;
    const id = caller.dataset.id;
    const name = caller.dataset.name;
    const internal = caller.dataset.internal;
    // Switch on fave or not.
    if (isFavourite === 'true') {
        await Repository.unfavouriteModule(name, id);

        partialFavourite(internal, false, modalBody);
    } else {
        await Repository.favouriteModule(name, id);

        partialFavourite(internal, true, modalBody);
    }

};

/**
 * Register chooser related event listeners.
 *
 * @method registerListenerEvents
 * @param {Promise} modal Our modal that we are working with
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 * @param {Function} partialFavourite Partially applied function we need to manage favourite status
 * @param {Object} footerData Our base footer object.
 * @return {Promise} A promise that resolves when events are registered
 */
async function registerListenerEvents(modal, mappedModules, partialFavourite, footerData) {
    const modalBody = getFirst(await modal.getBodyPromise());

    // Changing the tab should cancell any active search.
    modalBody.addEventListener('shown.bs.tab', (event) => {
        if (event.target.closest(selectors.regions.searchTabNav)) {
            return;
        }
        const searchInput = modalBody.querySelector(selectors.actions.search);
        if (searchInput.value.length > 0) {
            searchInput.value = "";
            toggleSearchResultsView(modal, mappedModules, searchInput.value);
        }
    });

    // Set up the carousel.
    const carousel = document.querySelector(selectors.regions.carousel);
    new Carousel(carousel, {
            interval: false,
            pause: true,
            keyboard: false
    });

    // Add the listener for clicks on the body.
    modalBody.addEventListener(
        'click',
        event => handleBodyClick(event, modal, mappedModules, partialFavourite),
    );

    // Add a listener for an input change in the activity chooser's search bar.
    const searchInput = modalBody.querySelector(selectors.actions.search);
    searchInput.addEventListener('input', debounce(() => {
        toggleSearchResultsView(modal, mappedModules, searchInput.value);
    }, 300));

    // Register event listeners related to the keyboard navigation controls.
    const activeSectionId = modalBody.querySelector(selectors.elements.activetab).getAttribute("href");
    const sectionChooserOptions = modalBody.querySelector(selectors.regions.getSectionChooserOptions(activeSectionId));
    const firstChooserOption = sectionChooserOptions.querySelector(selectors.regions.chooserOption.container);
    toggleFocusableChooserOption(firstChooserOption, true);
    initChooserOptionsKeyboardNavigation(modalBody, mappedModules, sectionChooserOptions, modal);

    const modalFooter = getFirst(await modal.getFooterPromise());

    // Add the listener for clicks on the footer.
    modalFooter.addEventListener(
        'click',
        event => handleFooterClick(event, modal, footerData),
    );
}

/**
 * Handle the click event on the footer of the modal.
 *
 * @param {Object} event The event object
 * @param {Object} modal Our created modal for the section
 * @param {Object} footerData The footer data object
 * @return {Promise} A promise that resolves when the event is handled
 */
async function handleFooterClick(event, modal, footerData) {
    if (footerData.footer === true) {
        const footerjs = await getPlugin(footerData.customfooterjs);
        await footerjs.footerClickListener(event, footerData, modal);
    }
}

/**
 * Modal click handler.
 *
 * @param {Object} event The event object
 * @param {Object} modal Our created modal for the section
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 * @param {Function} partialFavourite Partially applied function we need to manage favourite status
 * @return {Promise} A promise that resolves when the event is handled
 */
async function handleBodyClick(event, modal, mappedModules, partialFavourite) {
    const target = event.target;

    if (target.closest(selectors.actions.optionActions.showSummary)) {
        handleShowSummary(target, modal, mappedModules);
    }

    if (target.closest(selectors.actions.optionActions.manageFavourite)) {
        await handleManageFavourite(target, modal, mappedModules, partialFavourite);
    }

    // From the help screen go back to the module overview.
    if (target.matches(selectors.actions.closeOption)) {
        handleBackToChooser(target, modal);
    }

    // The "clear search" button is triggered.
    if (target.closest(selectors.actions.clearSearch)) {
        handleClearSearch(modal, mappedModules);
    }
}

/**
 * Show the summary of a module when the user clicks on the "show summary" button.
 *
 * @param {HTMLElement} target The target element that triggered the event
 * @param {Object} modal The modal object
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 */
function handleShowSummary(target, modal, mappedModules) {
    const modalBody = getFirst(modal.getBody());
    const carousel = modalBody.querySelector(selectors.regions.carousel);

    const module = target.closest(selectors.regions.chooserOption.container);
    const moduleName = module.dataset.modname;
    const moduleData = mappedModules.get(moduleName);
    // We need to know if the overall modal has a footer so we know when to show a real / vs fake footer.
    moduleData.showFooter = modal.hasFooterContent();
    showModuleHelp(carousel, moduleData, modal);
}

/**
 * Handle the favourite state of a module when the user clicks on the "manage favourite" button.
 *
 * @param {HTMLElement} target The target element that triggered the event
 * @param {Object} modal The modal object
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 * @param {Function} partialFavourite Partially applied function we need to manage favourite status
 * @return {Promise} A promise that resolves when the event is handled
 */
async function handleManageFavourite(target, modal, mappedModules, partialFavourite) {
    const modalBody = getFirst(modal.getBody());
    const caller = target.closest(selectors.actions.optionActions.manageFavourite);
    await manageFavouriteState(modalBody, caller, partialFavourite);
    const activeSectionId = modalBody.querySelector(selectors.elements.activetab).getAttribute("href");
    const sectionChooserOptions = modalBody
        .querySelector(selectors.regions.getSectionChooserOptions(activeSectionId));
    const firstChooserOption = sectionChooserOptions
        .querySelector(selectors.regions.chooserOption.container);
    toggleFocusableChooserOption(firstChooserOption, true);
    initChooserOptionsKeyboardNavigation(modalBody, mappedModules, sectionChooserOptions, modal);
}

/**
 * Handle the "back to chooser" action when the user clicks on the "back" button.
 *
 * @param {HTMLElement} target The target element that triggered the event
 * @param {Object} modal The modal object
 */
function handleBackToChooser(target, modal) {
    const modalBody = getFirst(modal.getBody());
    const carousel = modalBody.querySelector(selectors.regions.carousel);

    // Trigger the transition between 'pages'.
    Carousel.getInstance(carousel).prev();
    carousel.addEventListener('slid.bs.carousel', () => {
        const allModules = modalBody.querySelector(selectors.regions.modules);
        const caller = allModules.querySelector(selectors.regions.getModuleSelector(target.dataset.modname));
        caller.focus();
    });
}

/**
 * Handle a clear search action.
 *
 * @param {Object} modal The modal object
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 */
function handleClearSearch(modal, mappedModules) {
    const modalBody = getFirst(modal.getBody());
    const searchInput = modalBody.querySelector(selectors.actions.search);
    searchInput.value = "";
    searchInput.focus();
    toggleSearchResultsView(modal, mappedModules, searchInput.value);
}

/**
 * Initialise the keyboard navigation controls for the chooser options.
 *
 * @method initChooserOptionsKeyboardNavigation
 * @param {HTMLElement} modalBody Our modal that we are working with
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 * @param {HTMLElement} chooserOptionsContainer The section that contains the chooser items
 * @param {Object} modal Our created modal for the section
 */
const initChooserOptionsKeyboardNavigation = (modalBody, mappedModules, chooserOptionsContainer, modal = null) => {
    const chooserOptions = chooserOptionsContainer.querySelectorAll(selectors.regions.chooserOption.container);

    Array.from(chooserOptions).forEach((element) => {
        return element.addEventListener('keydown', (e) => {

            // Check for enter/ space triggers for showing the help.
            if (e.keyCode === enter || e.keyCode === space) {
                if (e.target.matches(selectors.actions.optionActions.showSummary)) {
                    e.preventDefault();
                    const module = e.target.closest(selectors.regions.chooserOption.container);
                    const moduleName = module.dataset.modname;
                    const moduleData = mappedModules.get(moduleName);
                    const carousel = document.querySelector(selectors.regions.carousel);

                    // We need to know if the overall modal has a footer so we know when to show a real / vs fake footer.
                    moduleData.showFooter = modal.hasFooterContent();
                    showModuleHelp(carousel, moduleData, modal);
                }
            }

            // Next.
            if (e.keyCode === arrowRight) {
                e.preventDefault();
                const currentOption = e.target.closest(selectors.regions.chooserOption.container);
                const nextOption = currentOption.nextElementSibling;
                const firstOption = chooserOptionsContainer.firstElementChild;
                const toFocusOption = clickErrorHandler(nextOption, firstOption);
                focusChooserOption(toFocusOption, currentOption);
            }

            // Previous.
            if (e.keyCode === arrowLeft) {
                e.preventDefault();
                const currentOption = e.target.closest(selectors.regions.chooserOption.container);
                const previousOption = currentOption.previousElementSibling;
                const lastOption = chooserOptionsContainer.lastElementChild;
                const toFocusOption = clickErrorHandler(previousOption, lastOption);
                focusChooserOption(toFocusOption, currentOption);
            }

            if (e.keyCode === home) {
                e.preventDefault();
                const currentOption = e.target.closest(selectors.regions.chooserOption.container);
                const firstOption = chooserOptionsContainer.firstElementChild;
                focusChooserOption(firstOption, currentOption);
            }

            if (e.keyCode === end) {
                e.preventDefault();
                const currentOption = e.target.closest(selectors.regions.chooserOption.container);
                const lastOption = chooserOptionsContainer.lastElementChild;
                focusChooserOption(lastOption, currentOption);
            }
        });
    });
};

/**
 * Focus on a chooser option element and remove the previous chooser element from the focus order
 *
 * @method focusChooserOption
 * @param {HTMLElement} currentChooserOption The current chooser option element that we want to focus
 * @param {HTMLElement|null} previousChooserOption The previous focused option element
 */
const focusChooserOption = (currentChooserOption, previousChooserOption = null) => {
    if (previousChooserOption !== null) {
        toggleFocusableChooserOption(previousChooserOption, false);
    }

    toggleFocusableChooserOption(currentChooserOption, true);
    currentChooserOption.focus();
};

/**
 * Add or remove a chooser option from the focus order.
 *
 * @method toggleFocusableChooserOption
 * @param {HTMLElement} chooserOption The chooser option element which should be added or removed from the focus order
 * @param {Boolean} isFocusable Whether the chooser element is focusable or not
 */
const toggleFocusableChooserOption = (chooserOption, isFocusable) => {
    const chooserOptionLink = chooserOption.querySelector(selectors.actions.addChooser);
    const chooserOptionHelp = chooserOption.querySelector(selectors.actions.optionActions.showSummary);
    const chooserOptionFavourite = chooserOption.querySelector(selectors.actions.optionActions.manageFavourite);

    if (isFocusable) {
        // Set tabindex to 0 to add current chooser option element to the focus order.
        chooserOption.tabIndex = 0;
        chooserOptionLink.tabIndex = 0;
        chooserOptionHelp.tabIndex = 0;
        chooserOptionFavourite.tabIndex = 0;
    } else {
        // Set tabindex to -1 to remove the previous chooser option element from the focus order.
        chooserOption.tabIndex = -1;
        chooserOptionLink.tabIndex = -1;
        chooserOptionHelp.tabIndex = -1;
        chooserOptionFavourite.tabIndex = -1;
    }
};

/**
 * Small error handling function to make sure the navigated to object exists
 *
 * @method clickErrorHandler
 * @param {HTMLElement} item What we want to check exists
 * @param {HTMLElement} fallback If we dont match anything fallback the focus
 * @return {HTMLElement}
 */
const clickErrorHandler = (item, fallback) => {
    if (item !== null) {
        return item;
    } else {
        return fallback;
    }
};

/**
 * Render the search results in a defined container
 *
 * @method renderSearchResults
 * @param {HTMLElement} searchResultsContainer The container where the data should be rendered
 * @param {Object} searchResultsData Data containing the module items that satisfy the search criteria
 */
const renderSearchResults = async(searchResultsContainer, searchResultsData) => {
    const templateData = {
        'searchresultsnumber': searchResultsData.length,
        'searchresults': searchResultsData
    };
    // Build up the html & js ready to place into the help section.
    const {html, js} = await Templates.renderForPromise('core_course/local/activitychooser/search_results', templateData);
    await Templates.replaceNodeContents(searchResultsContainer, html, js);
};

/**
 * Toggle (display/hide) the search results depending on the value of the search query
 *
 * @method toggleSearchResultsView
 * @param {Object} modal Our created modal for the section
 * @param {Map} mappedModules A map of all of the modules we are working with with K: mod_name V: {Object}
 * @param {String} searchQuery The search query
 */
const toggleSearchResultsView = async(modal, mappedModules, searchQuery) => {
    const modalBody = modal.getBody()[0];
    const searchResultsContainer = modalBody.querySelector(selectors.regions.searchResults);
    const clearSearchButton = modalBody.querySelector(selectors.actions.clearSearch);

    if (searchQuery.length > 0) { // Search query is present.
        const searchResultsData = searchModules(mappedModules, searchQuery);
        await renderSearchResults(searchResultsContainer, searchResultsData);
        const searchResultItemsContainer = searchResultsContainer.querySelector(selectors.regions.searchResultItems);
        const firstSearchResultItem = searchResultItemsContainer.querySelector(selectors.regions.chooserOption.container);
        if (firstSearchResultItem) {
            // Set the first result item to be focusable.
            toggleFocusableChooserOption(firstSearchResultItem, true);
            // Register keyboard events on the created search result items.
            initChooserOptionsKeyboardNavigation(modalBody, mappedModules, searchResultItemsContainer, modal);
        }
        clearSearchButton.classList.remove('d-none');
        searchResultsContainer.removeAttribute('hidden');
        activateSearchTab(modalBody, true);
    } else { // Search query is not present.
        clearSearchButton.classList.add('d-none');
        searchResultsContainer.setAttribute('hidden', 'hidden');
        activateSearchTab(modalBody, false);
    }
};

/**
 * Return the list of modules which have a name or description that matches the given search term.
 *
 * @method searchModules
 * @param {Array} modules List of available modules
 * @param {String} searchTerm The search term to match
 * @return {Array}
 */
const searchModules = (modules, searchTerm) => {
    if (searchTerm === '') {
        return modules;
    }
    searchTerm = searchTerm.toLowerCase();
    const searchResults = [];
    modules.forEach((activity) => {
        const activityName = activity.title.toLowerCase();
        const activityDesc = activity.help.toLowerCase();
        if (activityName.includes(searchTerm) || activityDesc.includes(searchTerm)) {
            searchResults.push(activity);
        }
    });

    return searchResults;
};

/**
 * Set up our tabindex information across the chooser.
 *
 * @method setupKeyboardAccessibility
 * @param {Promise} modal Our created modal for the section
 * @param {Map} mappedModules A map of all of the built module information
 */
const setupKeyboardAccessibility = (modal, mappedModules) => {
    modal.getModal()[0].tabIndex = -1;

    modal.getBodyPromise().then(body => {
        document.querySelectorAll(selectors.elements.tab).forEach((tab) => {
            tab.addEventListener('shown.bs.tab', (event) => {
                const activeSectionId = event.target.getAttribute("href");
                const activeSectionChooserOptions = body[0]
                    .querySelector(selectors.regions.getSectionChooserOptions(activeSectionId));

                const prevActiveSectionId = event.relatedTarget.getAttribute("href");
                const prevActiveSectionChooserOptions = body[0]
                    .querySelector(selectors.regions.getSectionChooserOptions(prevActiveSectionId));

                if (prevActiveSectionChooserOptions !== null) {
                    // Disable the focus of every chooser option in the previous active section.
                    disableFocusAllChooserOptions(prevActiveSectionChooserOptions);
                }

                // Only sections that have chooser options needs to be enabled.
                if (activeSectionChooserOptions !== null) {
                    const firstChooserOption = activeSectionChooserOptions
                        .querySelector(selectors.regions.chooserOption.container);
                    // Enable the focus of the first chooser option in the current active section.
                    toggleFocusableChooserOption(firstChooserOption, true);
                    initChooserOptionsKeyboardNavigation(body[0], mappedModules, activeSectionChooserOptions, modal);
                }
            });
        });
        return;
    }).catch(Notification.exception);
};

/**
 * Disable the focus of all chooser options in a specific container (section).
 *
 * @method disableFocusAllChooserOptions
 * @param {HTMLElement} sectionChooserOptions The section that contains the chooser items
 */
const disableFocusAllChooserOptions = (sectionChooserOptions) => {
    const allChooserOptions = sectionChooserOptions.querySelectorAll(selectors.regions.chooserOption.container);
    allChooserOptions.forEach((chooserOption) => {
        toggleFocusableChooserOption(chooserOption, false);
    });
};

/**
 * Display the module chooser.
 *
 * @method displayChooser
 * @param {Promise} modalPromise Our created modal for the section
 * @param {Array} sectionModules An array of all of the built module information
 * @param {Function} partialFavourite Partially applied function we need to manage favourite status
 * @param {Object} footerData Our base footer object.
 */
export const displayChooser = (modalPromise, sectionModules, partialFavourite, footerData) => {
    // Make a map so we can quickly fetch a specific module's object for either rendering or searching.
    const mappedModules = new Map();
    sectionModules.forEach((module) => {
        mappedModules.set(module.componentname + '_' + module.link, module);
    });

    // Register event listeners.
    modalPromise.then(modal => {
        registerListenerEvents(modal, mappedModules, partialFavourite, footerData);

        // We want to focus on the first chooser option element as soon as the modal is opened.
        setupKeyboardAccessibility(modal, mappedModules);

        // We want to focus on the action select when the dialog is closed.
        modal.getRoot().on(ModalEvents.hidden, () => {
            modal.destroy();
        });

        return modal;
    }).catch(Notification.exception);
};

/**
 * Export a curried function where the builtModules has been applied.
 * We have our array of modules so we can rerender the favourites area and have all of the items sorted.
 *
 * @method partiallyAppliedFavouriteManager
 * @param {Array} moduleData This is our raw WS data that we need to manipulate
 * @param {Object} exporter The template data exporter object.
 * @return {Function} partially applied function so we can manipulate DOM nodes easily & update our internal array
 */
const partiallyAppliedFavouriteManager = (moduleData, exporter) => {
    /**
     * Curried function that is being returned.
     *
     * @param {String} internal Internal name of the module to manage
     * @param {Boolean} favourite Is the caller adding a favourite or removing one?
     * @param {HTMLElement} modalBody What we need to update whilst we are here
     */
    return async(internal, favourite, modalBody) => {
        const moduleItem = moduleData.find(({name}) => name === internal);
        if (!moduleItem) {
            return;
        }
        moduleItem.favourite = favourite;

        refreshFavouriteTabContent(modalBody, moduleData, exporter);

        updateItemStarredIcons(modalBody, internal, favourite);
    };
};

/**
 * A small helper function to handle the case where there are no more favourites
 * and we need to mess a bit with the available tabs in the chooser
 *
 * @param {HTMLElement} modalBody Our current modals' body
 * @param {Boolean} displayed Whether we want to show or hide the favourite tab
 */
const toggleFavouriteTabDisplay = (modalBody, displayed) => {
    const favouriteTabNav = modalBody.querySelector(selectors.regions.favouriteTabNav);

    let moveFocusTo;
    if (!displayed && favouriteTabNav.classList.contains('active')) {
        moveFocusTo = showAllActivitiesTab(modalBody);
    }

    favouriteTabNav?.classList.toggle('d-none', !displayed);
    favouriteTabNav.tabIndex = displayed ? 0 : -1;
    // The disabled attribute is used by Boostrap Nav for keyboard navigation.
    if (displayed) {
        favouriteTabNav.removeAttribute('disabled');
    } else {
        favouriteTabNav.setAttribute('disabled', 'true');
    }

    if (moveFocusTo) {
        moveFocusTo.focus();
    }
};

/**
 * Refresh the favourite tab content.
 *
 * @param {HTMLElement} modalBody The modal body element.
 * @param {Array} moduleData The array of module data.
 * @param {Object} exporter The template data exporter object.
 */
async function refreshFavouriteTabContent(modalBody, moduleData, exporter) {
    const favouriteCount = moduleData.filter(mod => mod.favourite === true).length;

    const favouriteArea = modalBody.querySelector(selectors.regions.favouriteTab);
    const templateData = await exporter.getFavouriteTabData(moduleData);
    const {html, js} = await Templates.renderForPromise(
        'core_course/local/activitychooser/tabcontent',
        templateData,
    );
    await Templates.replaceNodeContents(favouriteArea, html, js);

    toggleFavouriteTabDisplay(modalBody, favouriteCount > 0);
}

/**
 * Update the starred icons in the chooser modal.
 *
 * @method updateItemStarredIcons
 * @param {HTMLElement} modalBody The modal body element.
 * @param {String} internal The internal name of the module.
 * @param {Boolean} favourite Whether the module is a favourite or not.
 */
function updateItemStarredIcons(modalBody, internal, favourite) {
    const favouriteButtons = modalBody.querySelectorAll(
        `${selectors.elements.moduleItem(internal)} ${selectors.actions.optionActions.manageFavourite}`
    );
    Array.from(favouriteButtons).forEach((element) => {
        element.classList.toggle('text-muted', !favourite);
        element.classList.toggle('text-primary', favourite);
        element.dataset.favourited = favourite;
        element.setAttribute('aria-pressed', favourite);
        element.querySelector(selectors.elements.favouriteIconActive)?.classList.toggle('d-none', !favourite);
        element.querySelector(selectors.elements.favouriteIconInactive)?.classList.toggle('d-none', favourite);
    });
}

/**
 * Show the "All activities" tab.
 *
 * @method showAllActivitiesTab
 * @param {HTMLElement} modalBody The modal body element.
 * @return {HTMLElement} The "All activities" tab element.
 */
function showAllActivitiesTab(modalBody) {
    const navTab = modalBody.querySelector(selectors.regions.allTabNav);
    Tab.getOrCreateInstance(navTab).show();
    return navTab;
}

/**
 * Show the "All activities" tab.
 *
 * @method showAllActivitiesTab
 * @param {HTMLElement} modalBody The modal body element.
 * @param {Boolean} active Whether to activate the search tab or not.
 * @return {HTMLElement} The "All activities" tab element.
 */
function activateSearchTab(modalBody, active) {
    const navTab = modalBody.querySelector(selectors.regions.searchTabNav);
    if (active) {
        Tab.getOrCreateInstance(navTab).show();
    } else {
        showAllActivitiesTab(modalBody);
    }
    return navTab;
}

/**
 * Display the activity chooser modal.
 *
 * @method displayActivityChooser
 * @param {Object} exporter The template data exporter object.
 * @param {Promise} footerDataPromise Promise for the footer data.
 * @param {Promise} modulesDataPromise Promise for the modules data.
 */
export async function displayActivityChooserModal(
    exporter,
    footerDataPromise,
    modulesDataPromise,
) {
    // We want to show the modal instantly but loading whilst waiting for our data.
    let bodyPromiseResolver;
    const bodyPromise = new Promise(resolve => {
        bodyPromiseResolver = resolve;
    });

    const footerData = await footerDataPromise;

    const sectionModal = Modal.create({
        body: bodyPromise,
        title: getString('addresourceoractivity'),
        footer: footerData.customfootertemplate,
        large: true,
        scrollable: false,
        templateContext: {
            classes: 'modchooser'
        },
        show: true,
    });

    try {
        const modulesData = await modulesDataPromise;

        if (!modulesData) {
            return;
        }

        displayChooser(
            sectionModal,
            modulesData,
            partiallyAppliedFavouriteManager(modulesData, exporter),
            footerData,
        );

        const templateData = await exporter.getModChooserTemplateData(modulesData);
        bodyPromiseResolver(await Templates.render('core_course/activitychooser', templateData));
    } catch (error) {
        const errorTemplateData = {
            'errormessage': error.message
        };
        bodyPromiseResolver(
            await Templates.render('core_course/local/activitychooser/error', errorTemplateData)
        );
        return;
    }
}
