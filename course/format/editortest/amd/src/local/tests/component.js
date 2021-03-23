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

import Reactive from 'core/reactive';
import ComponentBase from 'core/reactive/component';
import TestBase from 'format_editortest/local/tests/testbase';

class Test extends TestBase {

    /**
     * Function to prepare test scenario.
     */
    setUp() {
        // Create a generic reactive module without state.
        this.eventname = 'reactive_changed';

        this.reactive = new Reactive({
            name: 'Test',
            eventname: this.eventname,
            eventdispatch: this.eventdispatch,
            mutations: {
                // A generic function to alter the state.
                alter: (statemanager, name, value) => {
                    const state = statemanager.state;
                    statemanager.setLocked(false);
                    state[name].value = value;
                    statemanager.setLocked(true);
                },
            },
        });
    }

    /**
     * Auxiliar event dispatch method required by the reactive component..
     *
     * @param {*} detail the detail data
     * @param {*} target the element target
     */
    eventdispatch(detail, target) {
        if (target === undefined) {
            target = document;
        }
        target.dispatchEvent(new CustomEvent('reactive_changed', {
            bubbles: false,
            detail: detail,
        }));
    }

    /**
     * Test create method.
     */
    testCreate() {
        const test1 = this.addAssert('Component create hook executed', false);

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }
        }

        Sample.init(this, this.reactive, this.target);

        this.reactive.setInitialState({
            sample: {value: 'OK'},
        });
    }


    /**
     * Test state ready trigger.
     */
    testStateReady() {
        const test1 = this.addAssert('Initial state ready event');

        class Sample extends ComponentBase {
            create(descriptor) {
                // We need the test as a component attribute to do asserts.
                this.test = descriptor.test;
            }

            stateReady() {
                this.test.assertTrue(test1, true);
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }
        }

        Sample.init(this, this.reactive, this.target);

        this.reactive.setInitialState({
            sample: {value: 'OK'},
        });
    }

    /**
     * Test get watchers.
     */
    testGetWatchers() {
        const test1 = this.addAssert('Watchers works with state mutations');

        class Sample extends ComponentBase {
            create(descriptor) {
                // We need the test as a component attribute to do asserts.
                this.test = descriptor.test;
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }

            getWatchers() {
                return [
                    {
                        watch: 'sample.value:updated',
                        handler: ({element}) => {
                            this.test.assertTrue(test1, element.value === 'Perfect');
                        }
                    },
                ];
            }
        }

        Sample.init(this, this.reactive, this.target);

        this.reactive.setInitialState({
            sample: {value: 'OK'},
        });
        this.reactive.dispatch('alter', 'sample', 'Perfect');
    }

    /**
     * Check that "this" in watchers is mantained.
     */
    testGetWatchersKeepThis() {
        const test1 = this.addAssert('Watchers works with state mutations');

        class Sample extends ComponentBase {
            create(descriptor) {
                // We need the test as a component attribute to do asserts.
                this.test = descriptor.test;
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }

            getWatchers() {
                return [
                    {
                        watch: 'sample.value:updated',
                        handler: this.sampleUpdated,
                    },
                ];
            }

            sampleUpdated({element}) {
                this.test.assertTrue(test1, element.value === 'Perfect');
            }
        }

        Sample.init(this, this.reactive, this.target);

        this.reactive.setInitialState({
            sample: {value: 'OK'},
        });
        this.reactive.dispatch('alter', 'sample', 'Perfect');
    }

    /**
     * Test addSelectors
     */
    testAddSelectors() {
        const test1 = this.addAssert('Component create hook executed', false);

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
                this.selectors = {
                    some: 'selectorvalue',
                };
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }
        }

        const component = Sample.init(this, this.reactive, this.target);

        this.assertEquals(null, 'selectorvalue', component.getSelector('some'));

        component.addSelectors({some: 'newvalue'});

        this.assertEquals(null, 'newvalue', component.getSelector('some'));
    }

    /**
     * Test addSelectors
     */
    testAddSelectorsOnCreate() {
        const test1 = this.addAssert('Component create hook executed', false);

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
                this.selectors = {
                    some: 'selectorvalue',
                };
            }

            static init(test, reactive, element, selectors) {
                return new Sample({
                    element,
                    reactive,
                    selectors,
                    test,
                });
            }
        }

        const component = Sample.init(this, this.reactive, this.target, {some: 'newvalue'});

        this.assertEquals(null, 'newvalue', component.getSelector('some'));
    }

    /**
     * Test dispatch event.
     */
    testDispatchEvent() {
        const test1 = this.addAssert('Component create hook executed', false);

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }
        }

        const component = Sample.init(this, this.reactive, this.target);

        const test2 = this.addAssert('Custom event dispatched', false);

        this.target.addEventListener('testevent', ({detail}) => {
            this.assertTrue(test2, detail.value == 'Perfect!');
        });

        component.dispatchEvent('testevent', {value: 'Perfect!'});
    }

    /**
     * Test the typical dispatch event workflow.
     */
    testDispatchEventTypicalFlow() {
        const test1 = this.addAssert('Component create hook executed', false);
        const test2 = this.addAssert('State ready triggered', false);
        const test3 = this.addAssert('Click event triggered', false);
        const test4 = this.addAssert('Custom event triggered', false);

        // We don't have a user so we give the component a method to simulate the used action when it is ready.
        const clicknow = () => {
            this.target.click();
        };

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
                this.test = descriptor.test;
                // Init events list.
                this.events = {
                    sampleevent: 'sampleevent',
                };
            }

            stateReady() {
                this.test.assertTrue(test2, true);
                // Bind some user actions.
                this.element.addEventListener('click', () => {
                    this.test.assertTrue(test3, true);
                    // Trigger custom event. Typically before this it will be some mutation or extra logic.
                    this.dispatchEvent(this.events.sampleevent, {value: 'Perfect!'});
                });
                // Now we sumilate a user click.
                clicknow();
            }

            static init(test, reactive, element) {
                return new Sample({
                    element,
                    reactive,
                    test,
                });
            }
        }

        const component = Sample.init(this, this.reactive, this.target);

        // As a parent component we want to listen the custom event.
        this.target.addEventListener(component.getEvents().sampleevent, ({detail}) => {
            this.assertTrue(test4, detail.value == 'Perfect!');
        });

        // Initialize reactive state to trigger ready state.
        this.reactive.setInitialState({
            sample: {value: 'OK'},
        });
    }

    /**
     * Test descriptor exceptions when creating a new component.
     *
     * @param {boolean} useelement use a valid element
     * @param {boolean} usereactive use a valid reactive
     */
    testDescriptorExceptions({useelement, usereactive}) {
        const element = (useelement) ? this.target : undefined;
        const reactive = (usereactive) ? this.reactive : undefined;

        this.expectException();

        new ComponentBase({
            element,
            reactive,
        });
    }

    dataProviderTestDescriptorExceptions() {
        return {
            withoutelement: {useelement: false, usereactive: true},
            withoutreactive: {useelement: true, usereactive: false},
            withoutreactiveandelement: {useelement: true, usereactive: false},
        };
    }

}

export default new Test();
