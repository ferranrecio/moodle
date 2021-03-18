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
 * Test base component.
 *
 * @module     format_editortest/local/tests/stateevents
 * @package    core_course
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';

class TestBase {

    addTest(fullname, initialstate) {
        if (this.anoncounter === undefined) {
            this.anoncounter = 0;
        }
        this.anoncounter++;
        let shortname = `Assert#${this.anoncounter}`;

        const item = document.createElement('p');
        item.dataset.shortname = shortname;
        item.innerHTML = `<strong style="min-width:5em;">Pending</strong> - ${fullname} (${this.currenttitle} ${shortname})`;
        this.target.appendChild(item);
        this.addBullet(shortname);
        // Set state if necessary.
        if (initialstate !== undefined) {
            this.assertTrue(shortname, initialstate);
        }

        this.recalcStats();

        return item.dataset.shortname;
    }

    addBullet(shortname) {
        const item = document.createElement('span');
        item.dataset.bulletof = shortname;
        item.innerHTML = '?';
        this.target.prepend(item);
    }

    recalcStats() {
        let element = document.getElementById('assertsummary');

        // Count assert totals.
        const total = document.querySelectorAll(`p[data-shortname]`).length;
        // Count OK.
        const pass = document.querySelectorAll(`p[data-shortname][data-state='pass']`).length;
        // Count errors.
        const fails = document.querySelectorAll(`p[data-shortname][data-state='fail']`).length;
        // Count Exceptions.
        const exceptions = document.querySelectorAll(`p[data-shortname][data-state='exception']`).length;

        let content = {
            pass: `Test passed: ${pass}/${total} (click to display passed test)`,
            fail: `Test failed: ${fails}/${total}`,
            exceptions: `Uncatched exceptions: ${exceptions}/${total}`,
        };

        if (pass === total) {
            content.pass = `<span style="color:green">${content.pass}</span>`;
        }
        if (fails > 0) {
            content.fail = `<span style="color:red">${content.fail}</span>`;
        }
        if (exceptions > 0) {
            content.exceptions = `<span style="color:red">${content.exceptions}</span>`;
        }

        element.innerHTML = `<ul>
            <li id="passedtests">${content.pass}</li>
            <li>${content.fail}</li>
            <li>${content.exceptions}</li>
        <ul>`;

        // Small hack to toogle passed tests.
        const tooglepass = document.getElementById('passedtests');
        tooglepass.addEventListener('click', () => {
            document.getElementById('region-main').classList.toggle('displaypass');
        });
    }

    setBullet(shortname, value) {
        const bullet = this.target.querySelector(`[data-bulletof='${shortname}']`);
        if (bullet) {
            bullet.innerHTML = value;
        }
    }

    addTitle(text) {
        this.currenttitle = text;
        const item = document.createElement('h4');
        item.innerHTML = text;
        this.target.appendChild(item);
    }

    getTestItem(shortname, extraselectors) {
        // Check if it is an anonymous test
        if (shortname === null) {
            shortname = this.addTest('Anonymous assert');
        }
        extraselectors = extraselectors ?? '';
        return this.target.querySelector(`[data-shortname='${shortname}'] ${extraselectors}`);
    }

    markTestFail(shortname, expected, reality) {
        const itemresult = this.getTestItem(shortname, 'strong');
        if (!itemresult) {
            log.error(`Missing ${shortname} test`);
            return;
        }
        itemresult.innerHTML = '<span style="color:red">Failed!</span>';
        this.setBullet(itemresult.parentNode.dataset.shortname, '<span style="color:red">F</span>');
        this.addExpected(itemresult.parentNode, expected, reality);

        itemresult.parentNode.dataset.state = 'fail';

        this.recalcStats();
    }

    markTestException(shortname, error) {
        const itemresult = this.getTestItem(shortname, 'strong');
        if (!itemresult) {
            log.error(`Missing ${shortname} test`);
            return;
        }
        itemresult.innerHTML = '<span style="color:red">Exception!</span>';
        this.setBullet(itemresult.parentNode.dataset.shortname, '<span style="color:red">E</span>');
        this.addExpected(itemresult.parentNode, 'No exceptions', error);

        itemresult.parentNode.dataset.state = 'exception';

        this.recalcStats();
    }

    markTestPass(shortname) {
        const itemresult = this.getTestItem(shortname, 'strong');
        if (!itemresult) {
            log.error(`Missing ${shortname} test`);
            return;
        }
        itemresult.innerHTML = '<span style="color:green">Pass</span>';
        this.setBullet(itemresult.parentNode.dataset.shortname, '<span style="color:green">·</span>');
        this.removeExpected(itemresult.parentNode);

        itemresult.parentNode.dataset.state = 'pass';

        this.recalcStats();
    }

    addExpected(target, expected, reality) {
        const item = document.createElement('div');
        item.classList.add('alert');
        item.classList.add('alert-danger');
        item.innerHTML = `<strong>Expected: </strong> <pre>${JSON.stringify(expected, null, 2)}</pre>`;
        item.innerHTML += `<strong>Reality: </strong> <pre>${JSON.stringify(reality, null, 2)}</pre>`;
        target.appendChild(item);
    }

    removeExpected(target) {
        const expected = target.querySelector('div.alert');
        if (expected) {
            expected.style.display = 'none';
        }
    }

    debug(value) {
        const item = document.createElement('div');
        item.classList.add('alert');
        item.classList.add('alert-info');
        item.innerHTML = `<pre>${JSON.stringify(value, null, 2)}</pre>`;
        this.target.appendChild(item);
    }

    runTests() {
        let tests = this.getAllTests(this);
        tests.forEach(test => {
            // Check for data providers.
            const providerFunction = `dataProvider${test.charAt(0).toUpperCase() + test.slice(1)}`;
            let dataset = {singlescenario: {}};
            if (this[providerFunction] !== undefined) {
                dataset = this[providerFunction]();
            }

            // Execute all scenarios.
            for (const scenario in dataset) {
                if (!dataset.hasOwnProperty(scenario)) {
                    continue;
                }
                let testitle = test;
                if (scenario !== 'singlescenario') {
                    testitle = `${test}[${scenario}]`;
                }
                this.addTitle(testitle);

                // Test setup.
                if (this.setUp !== undefined) {
                    this.setUp();
                }

                // Execute test.
                try {
                    this[test](dataset[scenario]);
                } catch (error) {
                    log.error(`Exception raised in test ${testitle}`);
                    log.error(error);
                    const key = this.addTest(`Exception raised in test ${testitle}`);
                    this.markTestException(key, error);
                }

                // Test tear down.
                if (this.tearDown !== undefined) {
                    this.tearDown();
                }
            }
        });
    }

    getAllTests(obj) {
        let funcs = Object.getOwnPropertyNames(Object.getPrototypeOf(obj));
        return funcs.filter(item => {
            return typeof this[item] === 'function' && item.match(/^test.+/);
        });
    }

    assertTrue(shortname, value) {
        if (value) {
            this.markTestPass(shortname);
        } else {
            this.markTestFail(shortname, true, value);
        }
    }

    assertEquals(shortname, expected, reality) {
        if (expected === reality) {
            this.markTestPass(shortname);
        } else {
            this.markTestFail(shortname, expected, reality);
        }
    }

}

export default TestBase;
