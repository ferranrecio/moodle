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

import {debounce} from 'core/utils';

/**
 * A priority weighted execution queue.
 *
 * This class is used to sort the template components by weight
 * and prevent unnecessary template rendering.
 *
 * @module     core/local/reactive/weightedqueue
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class {

    /**
     * The class constructor.
     */
    constructor() {
        this.queue = [];

        /**
         * Debounced version of the execute method.
         *
         * @type {Function}
         * @public
         */
        this.executeDebounce = debounce(
            this.execute.bind(this),
            100
        );
    }

    /**
     * Add a function to the queue.
     *
     * The weight is used to sort the functions by priority, zero will execute first.
     *
     * @param {Function} functionToExecute
     * @param {Number} [weight] The weight of the function (zero by default).
     */
    add(functionToExecute, weight = 0) {
        this.queue.push({
            functionToExecute,
            weight: weight,
        });
    }

    /**
     * Get the queue by weight.
     *
     * The method is used to generate the execution order. The first
     * array is the weight level, the second array is the functions to
     * execute on that level.
     *
     * @private
     * @returns {Array<Array<Function>>}
     */
    _getWeightedQueue() {
        const result = this.queue.reduce(
            (acc, item) => {
                if (!acc[item.weight]) {
                    acc[item.weight] = [];
                }
                acc[item.weight].push(item.functionToExecute);
                return acc;
            },
            {}
        );
        const keys = Object.keys(result).sort((a, b) => a - b);
        return keys.map(key => result[key]);
    }

    /**
     * Execute the queue.
     *
     * This method will execute all the functions by weight, however,
     * it won't allow to execute the next level until the previous one
     * has finished.
     */
    execute() {
        const queue = this._getWeightedQueue();
        this.clear();

        const executeTopLevel = async(pendingQueue) => {
            if (pendingQueue.length === 0) {
                return;
            }

            const functions = pendingQueue.shift();
            const pendingPromises = [];

            for (const func of functions) {
                const funcResult = func();
                if (funcResult instanceof Promise) {
                    pendingPromises.push(funcResult);
                }
            }

            await Promise.all(pendingPromises);
            executeTopLevel(pendingQueue);
        };

        executeTopLevel(queue);
    }

    /**
     * Clear the queue.
     */
    clear() {
        this.queue = [];
    }
}
