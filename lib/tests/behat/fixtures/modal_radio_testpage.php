<?php
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
 * Test page for radio modal.
 *
 * @copyright 2023 Ferran Recio <ferran@moodle.com>
 * @package   core_form
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

defined('BEHAT_SITE_RUNNING') || die();

global $CFG, $PAGE, $OUTPUT;
$PAGE->set_url('/lib/tests/behat/fixtures/modal_radio_testpage.php');
$PAGE->add_body_class('limitedwidth');
require_login();
$PAGE->set_context(core\context\system::instance());

echo $OUTPUT->header();

echo "<h2>Radio modal test page</h2>";

// This is just a temporal test so we do not care about standarization.
echo '<div id="manual_test">';
?>
<h2>Manual test for modal radio module</h2>

<p>
    <button class="btn btn-secondary" id="showRadioModal">Show modal</button>
</p>
<div id="eventmonitor">
    <h3>Event monitor</h3>
    <ul>
        <li>On change value: <span id="currentValue"></span></li>
        <li>Saved value: <span id="savedValue"></span></li>
        <li>Cancel value: <span id="cancelValue"></span></li>
        <li>Close value: <span id="closeValue"></span></li>
    </ul>
</div>
<?php

$inlinejs = <<<EOF
require(
    ['core/modal_factory', 'core/str', 'core/modal_events', 'core/templates'],
    (ModalFactory, Str, ModalEvents, Templates) => {
        const currentValue = document.querySelector(`#currentValue`);
        const savedValue = document.querySelector(`#savedValue`);
        const cancelValue = document.querySelector(`#cancelValue`);
        const closeValue = document.querySelector(`#closeValue`);

        const options = [
            {
                "value": "first",
                "name": Str.get_string('addpagehere', 'core'),
                "description": Str.get_string('adminhelpcourses', 'core'),
                "selected": true,
            },
            {
                "value": "second",
                "name": "Second option",
                "icon": Templates.renderPix('e/save', 'core'),
                "description": "Second option description",
            },
            {
                "value": "third",
                "name": "Third option",
                "description": "Third option description",
                "icon": Templates.renderPix('e/cancel', 'core'),
                "disabled": true,
            },
        ];

        const modalParams = {
            title: "Test modal",
            body: options,
            type: ModalFactory.types.RADIO,
        };

        const resetValues = () => {
            currentValue.innerHTML = '';
            savedValue.innerHTML = '';
            cancelValue.innerHTML = '';
            closeValue.innerHTML = '';
        };

        const showModal = (modalParams) => {
            resetValues();
            ModalFactory.create(modalParams).then((modal) => {
                modal.setRemoveOnClose(true);
                modal.show();
                addModalEventHandlers(modal);
                return;
            }).catch(() => {
                Console.log(`Cannot load modal content`);
            });
        };

        const addModalEventHandlers = (modal) => {
            modal.getRoot().on(
                ModalEvents.save,
                () => {
                    savedValue.innerHTML = modal.getSelectedValue();
                }
            );
            modal.getRoot().on(
                ModalEvents.cancel,
                () => {
                    cancelValue.innerHTML = modal.getSelectedValue();
                }
            );
            modal.getRoot().on(
                ModalEvents.hidden,
                () => {
                    closeValue.innerHTML = modal.getSelectedValue();
                }
            );
            modal.getRoot().on(
                ModalEvents.radioChanged,
                (event) => {
                    const eventModal = event.detail;
                    currentValue.innerHTML = modal.getSelectedValue();
                }
            );
        };

        document.querySelector(`#showRadioModal`).addEventListener(
            'click',
            () => {
                showModal(modalParams);
            }
        );
    }
);
EOF;

$PAGE->requires->js_amd_inline($inlinejs);

echo '</div>';

echo $OUTPUT->footer();
