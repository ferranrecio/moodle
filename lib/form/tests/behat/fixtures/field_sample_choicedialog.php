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
 * Test page for choice dialog field type.
 *
 * @copyright 2023 Ferran Recio <ferran@moodle.com>
 * @package   core_form
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');

defined('BEHAT_SITE_RUNNING') || die();

global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/formslib.php');
$PAGE->set_url('/lib/form/tests/behat/fixtures/field_sample_choicedialog.php');
$PAGE->add_body_class('limitedwidth');
require_login();
$PAGE->set_context(core\context\system::instance());

/**
 * Class test_choice_dialog
 * @package core_form
 */
class test_choice_dialog extends moodleform {
    /**
     * Define the export form.
     */
    public function definition() {
        $mform = $this->_form;

        $options = [
            'option1' => [
                'text' => 'Text option 1',
                'description' => 'Description option 1',
                'icon' => ['t/groupv', 'core'],
            ],
            'option2' => [
                'text' => 'Text option 2',
                'description' => 'Description option 2',
                'icon' => ['t/groups', 'core'],
            ],
        ];

        $mform->addElement('header', 'database', "Basic example");
        $mform->addElement('choicedialog', 'example0', "Basic choice dialog", $options);

        $mform->addElement('header', 'database', "Disable choice dialog");
        $mform->addElement('checkbox', 'disableme', 'Check to disable the first choice dialog field.');
        $mform->addElement('choicedialog', 'example1', "Disable if example", $options);
        $mform->disabledIf('example1', 'disableme', 'checked');

        $mform->addElement('header', 'database', "Hide choice dialog");
        $mform->addElement('checkbox', 'hideme', 'Check to hide the first choice dialog field.');
        $mform->addElement('choicedialog', 'example2', "Hide if example", $options);
        $mform->hideIf('example2', 'hideme', 'checked');

        $mform->addElement('header', 'database', "Use choice dialog to hide or disable other fields");
        $mform->addElement('choicedialog', 'example3', "Control choice dialog", [
            'hide' => 'Hide or disable subelements', 'show' => 'Show or enable subelements'
        ]);

        $mform->addElement('text', 'hideinput', 'Hide if element', ['maxlength' => 80, 'size' => 50]);
        $mform->hideIf('hideinput', 'example3', 'eq', 'hide');
        $mform->setDefault('hideinput', 'Is this visible?');
        $mform->setType('hideinput', PARAM_TEXT);

        $mform->addElement('text', 'disabledinput', 'Disabled if element', ['maxlength' => 80, 'size' => 50]);
        $mform->disabledIf('disabledinput', 'example3', 'eq', 'hide');
        $mform->setDefault('disabledinput', 'Is this enabled?');
        $mform->setType('disabledinput', PARAM_TEXT);

        // In behat the choice dialog element is a regular select input to ensure behat runs fast.
        // However, we want to test also the javascript part so we force the fancy javascript interaction
        // in one of the fields.
        $options = [
            'option1' => [
                'text' => 'Forced text option 1',
                'description' => 'Description option 1',
                'icon' => ['t/groupv', 'core'],
            ],
            'option2' => [
                'text' => 'Forced text option 2',
                'description' => 'Description option 2',
                'icon' => ['t/groups', 'core'],
            ],
        ];
        $mform->addElement('header', 'database', "Test javascript choice dialog");
        $mform->addElement('button', 'focubutton', "Quick focus button");
        $mform->addElement('choicedialog', 'example4', "Forced choice dialog", $options, ['data-force-dialog' => true]);

        $this->add_action_buttons(false, 'Send form');
    }
}

echo $OUTPUT->header();

echo "<h2>Quickform integration test</h2>";

$form = new test_choice_dialog();

$data = $form->get_data();
if ($data) {
    echo "<h3>Submitted data</h3>";
    echo '<div id="submitted_data"><ul>';
    $data = (array) $data;
    foreach ($data as $field => $value) {
        echo "<li id=\"sumbmitted_{$field}\">$field: $value</li>";
    }
    echo '</ul></div>';
}
$form->display();

echo $OUTPUT->footer();
