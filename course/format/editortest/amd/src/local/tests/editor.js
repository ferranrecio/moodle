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

import editor from 'core_course/editor';
import ComponentBase from 'core/reactive/component';
import TestBase from 'format_editortest/local/tests/testbase';

class Test extends TestBase {

    /**
     * Setup before execute the first test.
     */
    setUpBeforeTests() {

        // The editor object is a singleton. If we want to override the mutations class
        // we need to do it before starting the tests, otherwise the mutations will affect
        // all tests if they execute some mutations on events or promises.
        class NewMutations {

            constructor(test) {
                this.internalvalue = 'Internal';
                this.test = test;
            }

            mutationtest(statemanager, testid) {
                this.test.assertTrue(testid, true);
                this.test.assertEquals(null, 'Internal', this.internalvalue);
            }
        }

        editor.setMutations(new NewMutations(this));
    }

    /**
     * Test editor components state ready is called.
     */
    testCreate() {
        const test1 = this.addAssert('Editor component create hook executed', false);

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
            }

            static init(test, element) {
                return new Sample({
                    reactive: editor,
                    element,
                    test,
                });
            }
        }

        Sample.init(this, this.target);
    }

    /**
     * Validate editor component gets the plugin specific state.
     */
    testInitialState() {
        const test1 = this.addAssert('Editor component create hook executed', false);
        const test2 = this.addAssert('Editor component state ready executed', false);

        class Sample extends ComponentBase {
            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
                this.test = descriptor.test;
            }

            static init(test, element) {
                return new Sample({
                    reactive: editor,
                    element,
                    test,
                });
            }

            stateReady(state) {
                this.test.assertTrue(test2, true);
                // Validate some general values.
                this.test.assertTrue(null, state.course.sectionlist !== undefined);
                this.test.assertTrue(null, state.course.editmode !== undefined);
                this.test.assertTrue(null, state.course.id !== undefined);
                // Validate plugin specific value.
                this.test.assertTrue(null, state.course.textvalue === 'Plugin value');
            }
        }

        Sample.init(this, this.target);
    }

    /**
     * Add mutations functions to the editor.
     */
    testAddMutations() {
        const test1 = this.addAssert('Editor component create hook executed', false);
        const test2 = this.addAssert('Editor component state ready executed', false);
        const test3 = this.addAssert('Mutation executed', false);
        const test4 = this.addAssert('Watcher executed', false);

        class Sample extends ComponentBase {

            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
                this.test = descriptor.test;
            }

            static init(test, element) {
                return new Sample({
                    reactive: editor,
                    element,
                    test,
                });
            }

            stateReady() {
                this.test.assertTrue(test2, true);

                // Trigger state mutation.
                this.reactive.dispatch('alter', 'newvalue', 'Perfect!');
            }

            getWatchers() {
                return [
                    {
                        watch: 'course.newvalue:created',
                        handler: ({element}) => {
                            this.test.assertTrue(test4, element.newvalue === 'Perfect!');
                        }
                    },
                ];
            }
        }

        // Add some mutations.
        editor.addMutations({
            alter: (statemanager, name, value) => {
                this.assertTrue(test3, true);
                const state = statemanager.state;
                statemanager.setLocked(false);
                state.course[name] = value;
                statemanager.setLocked(true);
            },
        });

        Sample.init(this, this.target);
    }

    /**
     * Add a mutation class into the editor.
     */
    testAddMutationsClass() {
        const test1 = this.addAssert('Mutation executed', false);

        // Editor is a singleton instance, we add the
        editor.dispatch('mutationtest', test1);
    }

    /**
     * Test isEditing method.
     *
     * Note: the editor module load the "is editing" value form the initial state but this could change
     * over time if the user turn edit off in some other browser tab. We don't want this value to change suddenly
     * so the editor offers a method to check the inmutable value.
     */
    testIsEditing() {
        const test1 = this.addAssert('Editor component create hook executed', false);
        const test2 = this.addAssert('Editor component state ready executed', false);
        const test3 = this.addAssert('Mutation executed', false);
        const test4 = this.addAssert('Is editing does not change over time', false);

        class Sample extends ComponentBase {

            create(descriptor) {
                descriptor.test.assertTrue(test1, true);
                this.test = descriptor.test;
            }

            static init(test, element) {
                return new Sample({
                    reactive: editor,
                    element,
                    test,
                });
            }

            stateReady() {
                this.test.assertTrue(test2, true);

                // Save current value.
                this.editing = editor.isEditing();

                // Trigger state mutation.
                editor.dispatch('toggleedit');
            }

            getWatchers() {
                return [
                    {
                        watch: 'course.editmode:updated',
                        handler: ({element}) => {
                            this.test.assertEquals(test4, this.editing, editor.isEditing());
                            this.test.assertEquals(null, !this.editing, element.editmode);
                        }
                    },
                ];
            }
        }

        // Add some mutations.
        editor.addMutations({
            toggleedit: (statemanager) => {
                this.assertTrue(test3, true);
                const state = statemanager.state;
                statemanager.setLocked(false);
                state.course.editmode = !state.course.editmode;
                statemanager.setLocked(true);
            },
        });

        Sample.init(this, this.target);
    }
}

export default new Test();
