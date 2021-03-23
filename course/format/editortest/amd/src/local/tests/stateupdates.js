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
 * Test component to test state updates messages.
 *
 * Important note: this is internal testing. Components should never user state manager or
 * reactive module directly. Only reactive instances can do it this way.
 *
 * @module     format_editortest/local/tests/stateupdates
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import StateManager from 'core/reactive/statemanager';
import TestBase from 'format_editortest/local/tests/testbase';

class Test extends TestBase {

    /**
     * Test setup.
     */
    setUp() {
        // We don't care about events this time.
        this.fakenode = document.createTextNode(null);
        this.statemanager = new StateManager(() => true, this.fakenode);
    }

    /**
     * Process create new state object.
     */
    testUpdateCreate() {
        this.statemanager.setInitialState({
            sample: {
                value: 'OK1',
            }
        });
        this.statemanager.processUpdates([
            {
                name: 'newattribute',
                action: 'create',
                fields: {
                    value: 'OK2',
                },
            },
        ]);
        this.assertEquals(null, 'OK1', this.statemanager.state.sample.value);
        this.assertEquals(null, 'OK2', this.statemanager.state.newattribute.value);
    }

    /**
     * Process create new list element.
     */
    testUpdateCreateList() {
        this.statemanager.setInitialState({
            samples: [
                {
                    id: 3,
                    value: 'first',
                },
                {
                    id: 4,
                    value: 'second',
                },
            ]
        });
        this.statemanager.processUpdates([
            {
                name: 'samples',
                action: 'create',
                fields: {
                    id: 5,
                    value: 'third',
                },
            },
        ]);
        this.assertEquals(null, 'first', this.statemanager.state.samples.get(3).value);
        this.assertEquals(null, 'second', this.statemanager.state.samples.get(4).value);
        this.assertEquals(null, 'third', this.statemanager.state.samples.get(5).value);
    }

    /**
     * Update a state object.
     */
    testUpdateUpdate() {
        this.statemanager.setInitialState({
            sample: {
                same: 'Same',
                value: 'OK1',
            },
            sample2: {
                value: 'OK2',
            },
        });
        this.statemanager.processUpdates([
            {
                name: 'sample',
                action: 'update',
                fields: {
                    value: 'newvalue',
                    newthing: 'newelement',
                },
            },
        ]);
        this.assertEquals(null, 'Same', this.statemanager.state.sample.same);
        this.assertEquals(null, 'newvalue', this.statemanager.state.sample.value);
        this.assertEquals(null, 'newelement', this.statemanager.state.sample.newthing);
        this.assertEquals(null, 'OK2', this.statemanager.state.sample2.value);
    }

    /**
     * Update a state list element.
     */
    testUpdateUpdateList() {
        this.statemanager.setInitialState({
            samples: [
                {
                    id: 3,
                    value: 'first',
                },
                {
                    id: 4,
                    value: 'second',
                },
            ]
        });
        this.statemanager.processUpdates([
            {
                name: 'samples',
                action: 'update',
                fields: {
                    id: 4,
                    value: 'third',
                },
            },
        ]);
        this.assertEquals(null, 'first', this.statemanager.state.samples.get(3).value);
        this.assertEquals(null, 'third', this.statemanager.state.samples.get(4).value);
    }

    /**
     * Delete a state attribute.
     */
    testUpdateDelete() {
        this.statemanager.setInitialState({
            sample: {
                value: 'OK1',
            },
            sample2: {
                value: 'OK2',
            },
        });
        this.statemanager.processUpdates([
            {
                name: 'sample',
                action: 'delete',
                fields: {},
            },
        ]);
        this.assertEquals(null, undefined, this.statemanager.state.sample);
        this.assertEquals(null, 'OK2', this.statemanager.state.sample2.value);
    }

    /**
     * Delete a state list element.
     */
    testUpdateDeleteList() {
        this.statemanager.setInitialState({
            samples: [
                {
                    id: 3,
                    value: 'first',
                },
                {
                    id: 4,
                    value: 'second',
                },
            ]
        });
        this.statemanager.processUpdates([
            {
                name: 'samples',
                action: 'delete',
                fields: {
                    id: 4,
                },
            },
        ]);
        this.assertEquals(null, 'first', this.statemanager.state.samples.get(3).value);
        this.assertEquals(null, undefined, this.statemanager.state.samples.get(4));
    }

    /**
     * Test update exceptions.
     *
     * @param {object} updates the update message to process
     */
    testUpdateExceptions(updates) {
        this.statemanager.setInitialState({
            sample: {
                value: 'OK',
            },
            samples: [
                {id: 1, value: 'OK List'}
            ],
        });

        this.expectException();
        this.statemanager.processUpdates(updates);
    }

    dataProviderTestUpdateExceptions() {
        return {
            // Only arrays are allowed as update lists.
            notarray: 'something',
            // All updates must provide a name attribute.
            missingname: [{
                action: 'update',
                fields: {
                    id: 1,
                    value: 'Not OK',
                },
            }],
            // All updates must provide a fields attribute.
            missingfields: [{
                name: 'sample',
                action: 'update',
            }],
            // Lists updates must provide an id.
            missingid: [{
                name: 'samples',
                action: 'update',
                fields: {
                    value: 'Not OK',
                },
            }],
            // List updates can only be done on nonexistent values.
            nonexistentid: [{
                name: 'samples',
                action: 'update',
                fields: {
                    id: 2,
                    value: 'Not OK',
                },
            }],
        };
    }
}

export default new Test();
