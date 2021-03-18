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
 * @module     format_editortest/local/tests/stateevents
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Iimport log from 'core/log';
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
        this.eventname = 'stateevents_changed';

        // This test module only tests the state events. We don't not need
        // to be registered as an editor component for this.

        this.runTests();

        return true;
    }

    /**
     * Function to prepare test scenario.
     */
    setUp() {
        this.fakenode = document.createTextNode(null);
        this.statemanager = new StateManager((detail, target) => {
            if (target === undefined) {
                target = document;
            }
            target.dispatchEvent(new CustomEvent(detail.action, {
                bubbles: false,
                detail: detail,
            }));
        }, this.fakenode);
    }

    /**
     * Test initial loaded event.
     */
    testInitialLoaded() {

        // Some tests are async so we cannot declare from within the specific method.
        const test1 = this.addTest('Initial state loaded event');
        const test2 = this.addTest('Check initial event has state data');
        const test3 = this.addTest('Check initial has state object');
        const test4 = this.addTest('Check initial has state object attribute');
        const test5 = this.addTest('Check initial has state object array');
        const test6 = this.addTest('Check initial has state map');
        const test7 = this.addTest('Check initial has state map attribute');
        const test8 = this.addTest('Check initial has state map array');

        this.fakenode.addEventListener('state:loaded', ({detail}) => {
            const state = detail.state;
            this.assertTrue(test1, true);
            // Check state contents.
            this.assertTrue(test2, state !== undefined);

            // Object attributes.
            this.assertTrue(test3, state.sampleobj !== undefined);
            this.assertTrue(test4, state.sampleobj.name === 'sample');
            this.assertTrue(test5, state.sampleobj.arr.length === 2 && state.sampleobj.arr[0] === 'one');

            // Map attributes.
            const sample = state.samplemap.get('id1');
            this.assertTrue(test6, state.samplemap.size == 2 && sample);
            this.assertTrue(test7, sample.title === 'Title01');
            this.assertTrue(test8, sample.info.length === 2 && sample.info[0] === 'uno');
        });

        this.statemanager.setInitialState({
            sampleobj: {
                name: 'sample',
                arr: ['one', 'two'],
            },
            samplemap: [
                {id: 'id1', title: 'Title01', info: ['uno', 'dos']},
                {id: 'id2', title: 'Title02', info: ['tres', 'cuatro']},
            ],
        });
    }

    /**
     * Add an object to state.
     */
    testAlterStateObjectCreateSimple() {

        this.statemanager.setInitialState({
            sample: {
                name: 'other',
                list: ['yi', 'er', 'san'],
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Check initial has state map');

        let state = this.statemanager.state;

        // Create a new state object.
        this.fakenode.addEventListener('state.newthing:created', ({detail}) => {
            this.assertTrue(test1, detail.state.newthing.name == 'myname');
        });
        state.newthing = {name: 'myname', mylist: ['one', 'two']};
    }

    /**
     * Update a state object.
     */
    testAlterStateObjectUpdateSimple() {

        this.statemanager.setInitialState({
            sample: {
                name: 'other',
                list: ['yi', 'er', 'san'],
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Check initial has state map attribute');

        let state = this.statemanager.state;

        // Alter a value
        this.fakenode.addEventListener('state.sample:updated', ({detail}) => {
            this.assertTrue(test1, detail.state.sample.name == 'anewname');
        });
        state.sample = {name: 'anewname', list: ['uno', 'dos', 'tres']};
    }

    /**
     * Delete a state object.
     */
    testAlterStateObjectDeleteSimple() {

        this.statemanager.setInitialState({
            sample: {
                name: 'whatever',
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Check initial has state map array');

        let state = this.statemanager.state;

        // Delete object
        this.fakenode.addEventListener('state.sample:deleted', ({detail}) => {
            this.assertTrue(
                test1,
                detail.state.sample === undefined
            );
        });
        delete state.sample;
    }

    /**
     * Create a new object list in the state.
     */
    testAlterStateObjectCreateList() {

        this.statemanager.setInitialState({
            samples: [
                {id: 'oid1', name: 'some'},
                {id: 'oid2', name: 'other'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Check initial has state map');
        const test2 = this.addTest('List item #1 updated');
        const test3 = this.addTest('List item #2 updated');

        let state = this.statemanager.state;

        // Create a list.
        this.fakenode.addEventListener('state.newthing2:created', ({detail}) => {
            this.assertTrue(test1, detail.state.newthing2.size == 2);
        });
        this.fakenode.addEventListener('newthing2[1]:created', ({detail}) => {
            this.assertTrue(test2, detail.state.newthing2.size == 2);
        });
        this.fakenode.addEventListener('newthing2[2]:created', ({detail}) => {
            this.assertTrue(test3, detail.state.newthing2.size == 2);
        });

        state.newthing2 = [{id: 1}, {id: 2}];
    }

    /**
     * Update a object list.
     */
    testAlterStateObjectUpdateList() {

        this.statemanager.setInitialState({
            samples: [
                {id: 'oid1', name: 'some'},
                {id: 'oid2', name: 'other'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Check initial has state map attribute');
        // We are replacing the full list. This is considered a creation.
        const test2 = this.addTest('List item #1 created');
        const test3 = this.addTest('List item #2 created');
        const test4 = this.addTest('List item #3 created');

        let state = this.statemanager.state;

        // Alter a list.
        this.fakenode.addEventListener('state.samples:updated', ({detail}) => {
            this.assertTrue(test1, detail.state.samples.size == 3);
        });
        this.fakenode.addEventListener('samples[1]:created', ({detail}) => {
            this.assertTrue(test2, detail.state.samples.size == 3);
        });
        this.fakenode.addEventListener('samples[2]:created', ({detail}) => {
            this.assertTrue(test3, detail.state.samples.size == 3);
        });
        this.fakenode.addEventListener('samples[3]:created', ({detail}) => {
            this.assertTrue(test4, detail.state.samples.size == 3);
        });

        state.samples = [{id: 1}, {id: 2}, {id: 3}];

    }

    /**
     * Delete a list from the state.
     */
    testAlterStateObjectDeleteList() {

        this.statemanager.setInitialState({
            samples: [
                {id: 23, name: 'notanymore'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Check initial has state map array');

        let state = this.statemanager.state;

        // Delete a list.
        this.fakenode.addEventListener('state.samples:deleted', ({detail}) => {
            this.assertTrue(
                test1,
                detail.state.samples === undefined
            );
        });

        delete state.samples;
    }

    /**
     * Add a new attribute to an state object.
     */
    testPropEventAddSimple() {

        this.statemanager.setInitialState({
            propevents: {
                name: 'something',
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('State variable general update event');
        const test2 = this.addTest('Add a new attribute to state variable');

        let state = this.statemanager.state;

        // General state variable event.
        this.fakenode.addEventListener('propevents:updated', ({detail}) => {
            this.assertTrue(test1, detail.element.name === detail.state.propevents.name);
        });

        // Create a new object attribute.
        this.fakenode.addEventListener('propevents.newthing:created', ({detail}) => {
            this.assertTrue(test2, detail.element.newthing == 'newvalue');
        });
        state.propevents.newthing = 'newvalue';
    }

    /**
     * Update an attribute of a state object.
     */
    testPropEventsUpdateSimple() {

        this.statemanager.setInitialState({
            propevents: {
                name: 'something',
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('State variable general update event');
        const test2 = this.addTest('Update and existing attribute to state variable');

        let state = this.statemanager.state;

        // General state variable event.
        this.fakenode.addEventListener('propevents:updated', ({detail}) => {
            this.assertTrue(test1, detail.element.name === detail.state.propevents.name);
        });

        // Alter an existing attribute.
        this.fakenode.addEventListener('propevents.name:updated', ({detail}) => {
            this.assertTrue(test2, detail.element.name == 'newname');
        });
        state.propevents.name = 'newname';
    }

    /**
     * Delete an attribute from a state object.
     */
    testPropEventsDeleteSimple() {

        this.statemanager.setInitialState({
            propevents: {
                name: 'something',
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('State variable general update event');
        const test2 = this.addTest('Delete and existing attribute to state variable');

        let state = this.statemanager.state;

        // General state variable event.
        this.fakenode.addEventListener('propevents:updated', ({detail}) => {
            this.assertTrue(test1, detail.element.name === detail.state.propevents.name);
        });

        // Delete an attribute.
        this.fakenode.addEventListener('propevents.name:deleted', ({detail}) => {
            this.assertTrue(
                test2,
                detail.element.name === undefined
            );
        });
        delete state.propevents.name;
    }

    /**
     * Add an array into a state object.
     */
    testPropEventsAddArray() {

        this.statemanager.setInitialState({
            propevents: {
                arr: ['yi', 'er', 'san', 'si', 'wu'],
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('State variable general update event');
        const test2 = this.addTest('Add a new attribute to state variable');

        let state = this.statemanager.state;

        // General state variable event.
        this.fakenode.addEventListener('propevents:updated', ({detail}) => {
            this.assertTrue(test1, detail.element.name === detail.state.propevents.name);
        });

        // Add an array as an attribute.
        this.fakenode.addEventListener('propevents.newthing2:created', ({detail}) => {
            this.assertTrue(test2, detail.element.newthing2.length == 3);
        });
        state.propevents.newthing2 = ['Un', 'Dos', 'Tres'];
    }

    /**
     * Update an array of a state object.
     */
    testPropEventsUpdateArray() {

        this.statemanager.setInitialState({
            propevents: {
                arr: ['yi', 'er', 'san', 'si', 'wu'],
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('State variable general update event');
        const test2 = this.addTest('Update and existing attribute to state variable');

        let state = this.statemanager.state;

        // General state variable event.
        this.fakenode.addEventListener('propevents:updated', ({detail}) => {
            this.assertTrue(test1, detail.element.name === detail.state.propevents.name);
        });

        // Alter an existing array.
        this.fakenode.addEventListener('propevents.arr:updated', ({detail}) => {
            this.assertTrue(test2, detail.element.arr.length == 3);
        });

        state.propevents.arr = ['Un', 'Dos', 'Tres'];
    }

    /**
     * Delete an array from a state object.
     */
    testPropEventsDeleteArray() {

        this.statemanager.setInitialState({
            propevents: {
                arr: ['yi', 'er', 'san', 'si', 'wu'],
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('State variable general update event');
        const test2 = this.addTest('Delete and existing attribute to state variable');

        let state = this.statemanager.state;

        // General state variable event.
        this.fakenode.addEventListener('propevents:updated', ({detail}) => {
            this.assertTrue(test1, detail.element.name === detail.state.propevents.name);
        });

        // Delete an array.
        this.fakenode.addEventListener('propevents.arr:deleted', ({detail}) => {
            this.assertTrue(test2, detail.element.arr === undefined);
        });
        delete state.propevents.arr;
    }

    /**
     * Add an element to a state list.
     */
    testMapEventsAddElement() {

        this.statemanager.setInitialState({
            mapevents: [
                {id: 'id1', name: 'some'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('General map update event');
        const test2 = this.addTest('Add a new value to a map');

        let state = this.statemanager.state;

        // General update variable event.
        this.fakenode.addEventListener('mapevents:created', ({detail}) => {
            this.assertTrue(
                test1,
                detail.element.id == detail.state.mapevents.get(detail.element.id).id
            );
        });

        // Set a new value into the list.
        this.fakenode.addEventListener('mapevents[new]:created', ({detail}) => {
            this.assertTrue(test2, detail.element.name == 'me');
        });
        state.mapevents.add({id: 'new', name: 'me'});
    }

    /**
     * Update an element of a state list.
     */
    testMapEventsUpdateValue() {

        this.statemanager.setInitialState({
            mapevents: [
                {id: 'id1', name: 'some'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('General map update event');
        const test2 = this.addTest('Update an element from a map');

        let state = this.statemanager.state;

        // General update variable event.
        this.fakenode.addEventListener('mapevents:updated', ({detail}) => {
            this.assertTrue(
                test1,
                detail.element.id == detail.state.mapevents.get(detail.element.id).id
            );
        });

        // Alter an existing value.
        this.fakenode.addEventListener('mapevents[id1]:updated', ({detail}) => {
            if (detail.element.id === 'id1') {
                this.assertTrue(test2, detail.element.name == 'updateme');
            }
        });
        state.mapevents.add({id: 'id1', name: 'updateme'});
    }

    /**
     * Delete a value from a state list.
     */
    testMapEventsDeleteValue() {

        this.statemanager.setInitialState({
            mapevents: [
                {id: 'id1', name: 'some'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('General map update event');
        const test2 = this.addTest('Delete an element form a map');

        let state = this.statemanager.state;

        // General update variable event.
        this.fakenode.addEventListener('mapevents:deleted', ({detail}) => {
            this.assertTrue(
                test1,
                detail.element.id == 'id1' && !detail.state.mapevents.has('id1')
            );
        });

        // Delete a value.
        this.fakenode.addEventListener('mapevents[id1]:deleted', ({detail}) => {
            this.assertTrue(
                test2,
                detail.element.id == 'id1' && !detail.state.mapevents.has('id1')
            );
        });
        state.mapevents.delete('id1');
    }

    /**
     * Add an atrtribute to an element of a state list.
     */
    testMapEventsAddAttribute() {

        this.statemanager.setInitialState({
            mapevents: [
                {id: 'id1', name: 'some'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('General map update event');
        const test2 = this.addTest('Create an attrribute form an element of a map');
        const test3 = this.addTest('Create an attrribute form a specific element of a map');

        let state = this.statemanager.state;

        // General update variable event.
        this.fakenode.addEventListener('mapevents:updated', ({detail}) => {
            this.assertTrue(
                test1,
                detail.element.id == detail.state.mapevents.get(detail.element.id).id
            );
        });

        // Add attribute to a list element.
        this.fakenode.addEventListener('mapevents.newthing:created', ({detail}) => {
            this.assertTrue(test2, detail.element.newthing === 'Yeah');
            this.assertTrue(null, detail.element.id === 'id1');
        });
        this.fakenode.addEventListener('mapevents[id1].newthing:created', ({detail}) => {
            this.assertTrue(test3, detail.element.newthing === 'Yeah');
            this.assertTrue(null, detail.element.id === 'id1');
        });
        state.mapevents.get('id1').newthing = 'Yeah';
    }

    /**
     * Update an attribute of an element of a state list.
     */
    testMapEventsUpdateAttribute() {

        this.statemanager.setInitialState({
            mapevents: [
                {id: 'id1', name: 'some'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('General map update event');
        const test2 = this.addTest('Update an attrribute form an element of a map');
        const test3 = this.addTest('Update an attrribute form an specific element of a map');

        let state = this.statemanager.state;

        // General update variable event.
        this.fakenode.addEventListener('mapevents:updated', ({detail}) => {
            this.assertTrue(
                test1,
                detail.element.id == detail.state.mapevents.get(detail.element.id).id
            );
        });

        // Alter an attribute from an existing element.
        this.fakenode.addEventListener('mapevents.name:updated', ({detail}) => {
            this.assertTrue(test2, detail.element.name === 'Maybe');
            this.assertTrue(null, detail.element.id === 'id1');
        });
        this.fakenode.addEventListener('mapevents[id1].name:updated', ({detail}) => {
            this.assertTrue(test3, detail.element.name === 'Maybe');
            this.assertTrue(null, detail.element.id === 'id1');
        });
        state.mapevents.get('id1').name = 'Maybe';
    }

    /**
     * Delete an attribute from an element of a state list.
     */
    testMapEventsDeleteAttribute() {

        this.statemanager.setInitialState({
            mapevents: [
                {id: 'id1', name: 'some'},
            ],
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('General map update event');
        const test2 = this.addTest('Delete an attrribute form an element of a map');
        const test3 = this.addTest('Delete an attrribute form an element of a map');

        let state = this.statemanager.state;

        // General update variable event.
        this.fakenode.addEventListener('mapevents:updated', ({detail}) => {
            this.assertTrue(
                test1,
                detail.element.id == detail.state.mapevents.get(detail.element.id).id
            );
        });

        // Delete an attribute from an exisiting element.
        this.fakenode.addEventListener('mapevents.name:deleted', ({detail}) => {
            this.assertTrue(test2, detail.element.name === undefined);
            this.assertTrue(null, detail.element.id === 'id1');
        });
        this.fakenode.addEventListener('mapevents[id1].name:deleted', ({detail}) => {
            this.assertTrue(test3, detail.element.name === undefined);
            this.assertTrue(null, detail.element.id === 'id1');
        });
        delete state.mapevents.get('id1').name;
    }

    /**
     * Check assigning the same value does not trigger any change event.
     *
     * @param {*} value the value to update.
     */
    testSameValue(value) {
        this.statemanager.setInitialState({
            samevalue: {
                value: value,
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Assign the same value does not trigger a general event', true);
        const test2 = this.addTest('Assign same value does not trigger event', true);

        let state = this.statemanager.state;

        // General event.
        this.fakenode.addEventListener('samevalue:updated', () => {
            this.assertTrue(test1, false);
        });

        this.fakenode.addEventListener('samevalue.value:updated', () => {
            this.assertTrue(test2, false);
        });
        state.samevalue.value = value;
    }

    /**
     * Data provider for testSameValue.
     *
     * @returns {object} list of scenarios
     */
    dataProviderTestSameValue() {
        return {
            // List updates can only be done on nonexistent values.
            simplevalue1: ['sample'],
            simplevalue2: [true],
            simplevalue3: [null],
            simplevalue4: [false],
            simplevalue5: [123],
            arrayvalue1: [[1, 2, 3]],
            arrayvalue2: [['hi', 'there']],
            arrayvalue4: [[true, null, 'here']],
            objectvalue1: [{firstname: 'John', lastname: 'Doe'}],
            objectvalue2: [{some: true, other: false}],
            objectvalue3: [{some: null, other: 12}],
        };
    }

    /**
     * Test that alter a value trigger an event.
     *
     * @param {*} initial the initial value
     * @param {*} compare the value to set
     */
    testDifferentValue(initial, compare) {
        this.statemanager.setInitialState({
            diffvalue: {
                value: initial,
            },
        });

        // For this test we need an unlocked state.
        this.statemanager.setLocked(false);

        const test1 = this.addTest('Assign the same does not trigger a general event2', false);
        const test2 = this.addTest('Assign same value does not trigger event', false);

        let state = this.statemanager.state;

        // General event.
        this.fakenode.addEventListener('diffvalue:updated', () => {
            this.assertTrue(test1, true);
        });

        this.fakenode.addEventListener('diffvalue.value:updated', () => {
            this.assertTrue(test2, true);
        });

        state.diffvalue.value = compare;
    }

    /**
     * Data provider for testDifferentValue.
     *
     * @returns {object} test scenarios
     */
    dataProviderTestDifferentValue() {
        return {
            // List updates can only be done on nonexistent values.
            simplevalue1: ['sample', 'something'],
            simplevalue2: [true, false],
            simplevalue3: [null, 'hi!'],
            simplevalue4: [false, 23],
            simplevalue5: [123, 124],
            arrayvalue1: [[1, 2, 3], [1, 2, 4]],
            arrayvalue2: [['hi', 'there'], ['hi', 'me']],
            arrayvalue4: [[true, null, 'here'], [true, 12, 'here']],
            objectvalue1: [{firstname: 'John', lastname: 'Doe'}, {firstname: 'John', lastname: 'None'}],
            objectvalue2: [{some: true, other: false}, {some: true, other: true}],
            objectvalue3: [{some: null, other: 12}, {some: 22, other: 23}],
        };
    }
}

export default new Test();
