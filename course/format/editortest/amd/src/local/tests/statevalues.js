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
 * Test component.
 *
 * Important note: this is internal testing. Components should never user state manager or
 * reactive module directly. Only reactive instances can do it this way.
 *
 * @module     format_editortest/local/tests/statevalues
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import StateManager from 'core_course/local/editor/statemanager';
import TestBase from 'format_editortest/local/tests/testbase';

class Test extends TestBase {

    /**
     * Initialize the component.
     *
     * @param {string} resultsid the result element id.
     * @returns {boolean}
     */
    init(resultsid) {
        this.target = document.getElementById(resultsid);
        this.eventname = 'reactive_changed';

        // This test module only tests the reactive module. We don't not need
        // to be registered as an editor component for this.

        this.runTests();

        return true;
    }

    /**
     * Scenario setup.
     */
    setUp() {
        // We don't care about events this time.
        this.fakenode = document.createTextNode(null);
        this.statemanager = new StateManager(() => true, this.fakenode);
    }

    /**
     * Create a valid initial state.
     */
    testAddObject() {
        this.statemanager.setInitialState({
            sample: {
                value: 'OK',
            }
        });
        this.assertEquals(null, 'OK', this.statemanager.state.sample.value);
    }

    /**
     * Create a state list initial state.
     */
    testAddList() {
        this.statemanager.setInitialState({
            samples: [
                {id: 3, name: 'first'},
                {id: 4, name: 'second'},
            ],
        });
        this.assertEquals(null, undefined, this.statemanager.state.samples.get(1));
        this.assertEquals(null, undefined, this.statemanager.state.samples.get(2));
        this.assertEquals(null, 3, this.statemanager.state.samples.get(3).id);
        this.assertEquals(null, 'first', this.statemanager.state.samples.get(3).name);
        this.assertEquals(null, 4, this.statemanager.state.samples.get(4).id);
        this.assertEquals(null, 'second', this.statemanager.state.samples.get(4).name);
    }

    /**
     * Test invalid state values.
     *
     * @param {*} state the initial state
     */
    testInvalidState(state) {
        try {
            this.statemanager.setInitialState(state);
            this.assertTrue(null, false);
        } catch (error) {
            this.assertTrue(null, true);
        }
    }

    /**
     * Data provider for testInvalidState.
     *
     * @returns {object} testting scenarios
     */
    dataProviderTestInvalidState() {
        return {
            // States must be objects.
            plainstate: 'nope',
            // All state objects must be objects, not simple variables.
            simpleattributes1: {
                value: 'Nope',
            },
            simpleattributes2: {
                value: null,
            },
            // List items must provide an ID attribute.
            listwithoutid: {
                samples: [
                    {id: 3, name: 'first'},
                    {name: 'second'},
                ],
            },
            // List element cannot be simple variables.
            listofsimpleitems: {
                samples: ['j', 2, true],
            },
        };
    }

    testSetListValue() {
        this.statemanager.setInitialState({
            samples: [
                {id: 3, name: 'first'},
                {id: 4, name: 'second'},
            ],
        });

        this.assertEquals(null, 3, this.statemanager.state.samples.get(3).id);
        this.assertEquals(null, 'first', this.statemanager.state.samples.get(3).name);
        this.assertEquals(null, 4, this.statemanager.state.samples.get(4).id);
        this.assertEquals(null, 'second', this.statemanager.state.samples.get(4).name);

        this.statemanager.setLocked(false);

        // Add a value.
        this.statemanager.state.samples.set(1, {id: 1, name: 'new'});
        this.assertEquals(null, 1, this.statemanager.state.samples.get(1).id);
        this.assertEquals(null, 'new', this.statemanager.state.samples.get(1).name);

        // Alter a value.
        this.statemanager.state.samples.set(3, {id: 3, name: 'newvalue'});
        this.assertEquals(null, 3, this.statemanager.state.samples.get(3).id);
        this.assertEquals(null, 'newvalue', this.statemanager.state.samples.get(3).name);

        // Check again the 4 element to test if it remains the same.
        this.assertEquals(null, 4, this.statemanager.state.samples.get(4).id);
        this.assertEquals(null, 'second', this.statemanager.state.samples.get(4).name);
    }

    /**
     * Test invalid list element values.
     *
     * @param {*} scenario the scenario key and value
     */
    testSetInvalidListValues({key, value}) {
        this.statemanager.setInitialState({
            samples: [
                {id: 3, name: 'first'},
                {id: 4, name: 'second'},
            ],
        });

        this.statemanager.setLocked(false);

        try {
            this.statemanager.state.samples.set(key, value);
            this.assertTrue(null, false);
        } catch (error) {
            this.assertTrue(null, true);
        }
    }

    /**
     * Data provider for testSetInvalidListValues.
     *
     * @returns {object} the testing sacenarios
     */
    dataProviderTestSetInvalidListValues() {
        return {
            // All element needs a key.
            valuewithoutkey: {
                key: undefined,
                value: {id: 1, name: 'new'},
            },
            // All elements key must be the same as the element id.
            valuedifferentkey: {
                key: 42,
                value: {id: 25, name: 'new'},
            },
            // All list elements must have an id attribute.
            valuewithoutid: {
                key: 43,
                value: {name: 'new'},
            },
        };
    }

    /**
     * Test add list elements function.
     */
    testAddListValue() {
        this.statemanager.setInitialState({
            samples: [
                {id: 3, name: 'first'},
                {id: 4, name: 'second'},
            ],
        });

        this.assertEquals(null, 3, this.statemanager.state.samples.get(3).id);
        this.assertEquals(null, 'first', this.statemanager.state.samples.get(3).name);
        this.assertEquals(null, 4, this.statemanager.state.samples.get(4).id);
        this.assertEquals(null, 'second', this.statemanager.state.samples.get(4).name);

        this.statemanager.setLocked(false);

        // Add a value.
        this.statemanager.state.samples.add({id: 1, name: 'new'});
        this.assertEquals(null, 1, this.statemanager.state.samples.get(1).id);
        this.assertEquals(null, 'new', this.statemanager.state.samples.get(1).name);

        // Alter a value.
        this.statemanager.state.samples.add({id: 3, name: 'newvalue'});
        this.assertEquals(null, 3, this.statemanager.state.samples.get(3).id);
        this.assertEquals(null, 'newvalue', this.statemanager.state.samples.get(3).name);

        // Check again the 4 element to test if it remains the same.
        this.assertEquals(null, 4, this.statemanager.state.samples.get(4).id);
        this.assertEquals(null, 'second', this.statemanager.state.samples.get(4).name);
    }

    /**
     * Test invalid list elements.
     *
     * @param {object} value the invalid value
     */
    testAddInvalidListValues(value) {
        this.statemanager.setInitialState({
            samples: [
                {id: 3, name: 'first'},
                {id: 4, name: 'second'},
            ],
        });

        this.statemanager.setLocked(false);

        try {
            this.statemanager.state.samples.add(value);
            this.assertTrue(null, false);
        } catch (error) {
            this.assertTrue(null, true);
        }
    }

    /**
     * Data provider for testAddInvalidListValues.
     *
     * @returns {object} testing scenarios
     */
    dataProviderTestAddInvalidListValues() {
        return {
            // All element needs a valur key.
            valuewithoutkey: {id: undefined, name: 'new'},
            // All list elements must have an id attribute.
            valuewithoutid: {name: 'new'},
        };
    }
}

export default new Test();
