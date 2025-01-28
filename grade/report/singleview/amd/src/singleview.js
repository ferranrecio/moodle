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
 * Allow navigation through table cells using Ctrl + arrow keys and handle override toggles.
 *
 * @module    gradereport_singleview/singleview
 * @copyright The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const GradeReportSingleView = {
    /**
     * Indicates if the module has been initialized.
     * @type {boolean}
     */
    initialised: false,

    /**
     * Initializes the module, setting up event listeners for table cell navigation and override toggles.
     */
    init() {
        if (this.initialised) {
            return;
        }
        this.initialised = true;

        /**
         * Helper function to get the column index of a cell.
         *
         * @param {HTMLElement} cell The table cell element.
         * @returns {number} The index of the cell within its row.
         */
        const getColumnIndex = (cell) => {
            const rowNode = cell.closest('tr');
            if (!rowNode || !cell) {
                return -1;
            }
            const cells = Array.from(rowNode.querySelectorAll('td, th'));
            return cells.indexOf(cell);
        };

        /**
         * Helper function to get the next cell in the table.
         *
         * @param {HTMLElement} cell The current table cell element.
         * @returns {HTMLElement|null} The next navigable cell or null if none found.
         */
        const getNextCell = (cell) => {
            const n = cell || document.activeElement;
            const next = n.nextElementSibling?.matches('td.cell, th.cell') ? n.nextElementSibling : null;
            if (!next) {
                return null;
            }
            // Continue until we find a navigable cell
            if (!next.querySelector('input:not([type="hidden"]):not([disabled="DISABLED"]), select, a')) {
                return getNextCell(next);
            }
            return next;
        };

        /**
         * Helper function to get the previous cell in the table.
         *
         * @param {HTMLElement} cell The current table cell element.
         * @returns {*|Element|null}
         */
        const getPrevCell = (cell) => {
            const n = cell || document.activeElement;
            const prev = n.previousElementSibling?.matches('td.cell, th.cell') ? n.previousElementSibling : null;
            if (!prev) {
                return null;
            }
            // Continue until we find a navigable cell
            if (!prev.querySelector('input:not([type="hidden"]):not([disabled="DISABLED"]), select, a')) {
                return getPrevCell(prev);
            }
            return prev;
        };

        /**
         * Helper function to get the cell above the current cell in the table.
         *
         * @param {HTMLElement} cell The current table cell element.
         * @returns {HTMLElement|null} The cell above or null if none found.
         */
        const getAboveCell = (cell) => {
            const n = cell || document.activeElement;
            const tr = n.closest('tr').previousElementSibling;
            const columnIndex = getColumnIndex(n);
            if (!tr) {
                return null;
            }
            const next = tr.querySelectorAll('td, th')[columnIndex];
            // Continue until we find a navigable cell
            if (!next?.querySelector('input:not([type="hidden"]):not([disabled="DISABLED"]), select, a')) {
                return getAboveCell(next);
            }
            return next;
        };

        /**
         * Helper function to get the cell below the current cell in the table.
         *
         * @param {HTMLElement} cell The current table cell element.
         * @returns {HTMLElement|null} The cell below or null if none found.
         */
        const getBelowCell = (cell) => {
            const n = cell || document.activeElement;
            const tr = n.closest('tr').nextElementSibling;
            const columnIndex = getColumnIndex(n);
            if (!tr) {
                return null;
            }
            const next = tr.querySelectorAll('td, th')[columnIndex];
            // Continue until we find a navigable cell
            if (!next?.querySelector('input:not([type="hidden"]):not([disabled="DISABLED"]), select, a')) {
                return getBelowCell(next);
            }
            return next;
        };

        // Add ctrl+arrow controls for navigation.
        document.body.addEventListener('keydown', (e) => {
            if (!e.ctrlKey) {
                return;
            }

            const activeElement = document.activeElement;
            if (!activeElement.matches('table input, table select, table a')) {
                return;
            }

            let next = null;
            switch (e.keyCode) {
                case 37: // Left arrow
                    next = getPrevCell(activeElement.closest('td, th'));
                    break;
                case 38: // Up arrow
                    next = getAboveCell(activeElement.closest('td, th'));
                    break;
                case 39: // Right arrow
                    next = getNextCell(activeElement.closest('td, th'));
                    break;
                case 40: // Down arrow
                    next = getBelowCell(activeElement.closest('td, th'));
                    break;
            }

            if (next) {
                e.preventDefault();
                e.stopPropagation();
                next.querySelector('input:not([type="hidden"]):not([disabled="DISABLED"]), select, a')?.focus();
            } else {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        // Handle override toggles
        document.querySelectorAll('input[name^=override_]').forEach(input => {
            input.addEventListener('change', () => {
                const checked = input.checked;
                const [, itemid, userid] = input.getAttribute('name').split('_');
                const interest = `_${itemid}_${userid}`;

                // Handle text inputs
                document.querySelectorAll(`input[name$='${interest}'][data-uielement='text']`)
                    .forEach(text => {
                        text.disabled = !checked;
                    });

                // Handle select elements
                document.querySelectorAll(`select[name$='${interest}']`)
                    .forEach(select => {
                        select.disabled = !checked;
                    });
            });
        });
    }
};

export default GradeReportSingleView;
