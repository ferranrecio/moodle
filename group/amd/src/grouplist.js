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
 * Reactive groups visualization app.
 *
 * @module     core_group/grouplist
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import reactive from 'core_group/grouplist/reactive';
import toolbar from 'core_group/grouplist/toolbar';
import ifs from 'core_group/grouplist/ifs';
import store from 'core_group/grouplist/store';
import log from 'core/log';
/*import CustomEvents from 'core/custom_interaction_events';
import * as ModalFactory from 'core/modal_factory';
import jQuery from 'jquery';
import Pending from 'core/pending';
import {enter, space} from 'core/key_codes';*/

/**
 * Set up general reactive element.
 *
 * @return {void}
 */
export const init = (elementid) => {
    const app = new reactive({
        el: document.getElementById(elementid),
        template: `
                <div>
                    <div class="mb-3">Reactive checking:</div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">If/Else bindings</h5>
                            <p class="mt-3">
                                Check first boolean:
                                <span data-if="somebool" class="text-success">is true</span>
                                <span data-else="somebool" class="text-danger">is false</span>
                                <div>
                                    <button class="btn btn-primary" data-onclick="toogle">Toogle</button>
                                </div>
                            </p>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Content Binds</h5>
                            <p>Oldtitle text: <span data-bind-text="oldtitle">Errror!</span></p>
                            <p>Oldtitle HTML: <span data-bind-html="oldtitle">Errror!</span></p>
                            <p><input type="text" data-model="oldtitle" name="oldtitle" class="w-100"/></p>
                            <p><button class="btn btn-primary" data-onclick="click1">Add wiii!</button></p>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Component loading</h5>
                            <component data-name="core_group/grouplist/toolbar" data-extra="oldtitle">
                                Fail loading core_group/toolbar!
                            </component>
                        </div>
                    </div>
                </div>`,
        store: store,
        data: () => {
            return {
                oldtitle: 'A basic <i>text</i>',
                somebool: true,
            };
        },
        methods: {
            click1: function() {
                this.oldtitle = this.oldtitle + ' <b>Wiii!</b>';
            },
            toogle: function() {
                this.somebool = !this.somebool.valueOf();
            },
        },
        mounted: function() {
            // This is just for the demo.
            globalThis.demo = this.data;
            log.debug('Component mounted');
        }
    });
    // Register all components.
    app.component(toolbar);
    app.component(ifs);
    // Generate UI.
    app.render();
    return app;
};
