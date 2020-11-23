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
 * Group ifs component.
 *
 * @module     core_group/grouplist/store
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Set up general reactive class.
 *
 * @return {void}
 */

const ifs = {
    name: 'core_group/grouplist/ifs',
    data() {
        return {
            bool1: true,
            bool2: false,
        };
    },
    template: `<div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">If/Else bindings</h5>
                    <p class="mt-3">
                        Check first boolean:
                        <span data-if="somebool" class="text-success">is true</span>
                        <span data-else="somebool" class="text-danger">is false</span>
                        <div>
                            <button class="btn btn-primary" data-onclick="toogle1">Toogle</button>
                        </div>
                    </p>
                    <p class="mt-3">
                        Check first boolean:
                        <span data-if="somebool2" class="text-success">is true</span>
                        <span data-else="somebool2" class="text-danger">is false</span>
                        <div>
                            <button class="btn btn-primary" data-onclick="toogle2">Toogle</button>
                        </div>
                    </p>
                </div>
                <!--<div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Direct binding</h5>
                            <div data-bind="something">
                                <p>This is a static text indise the binding.</p>
                                <p>Bind newtitle: <span data-bind-text="newtitle">Errror!</span></p>
                                <p>Global oldtitle: <span data-bind-text="oldtitle">Errror!</span></p>
                            </div>
                        </div>
                    </div>-->
                <!---<div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Camputed methods</h5>
                            <p>Brava: <span class="w-100" data-bind-text="brava">Errror!</span></p>
                        </div>
                    </div>-->
            </div>`,
    methods: {
        toogle1: function() {
            this.bool1 = !this.bool1.valueOf();
        },
        toogle2: function() {
            this.bool2 = !this.bool2;
        },
    },
};



export default ifs;
