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
 * Module form for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

use mod_peerassign\manager;

/**
 * Form for adding/editing a Peer Review Assignment activity.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerassign_mod_form extends moodleform_mod {

    /**
     * Define the form structure.
     */
    public function definition() {
        global $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('activityname', 'core'), ['size' => '64']);
        if (!empty($this->_customdata['grade'])) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_TEXT);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'activityname', 'core');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Blind review option.
        $mform->addElement('header', 'peerassignfieldset', get_string('peerassignfieldset', manager::PLUGINNAME));
        $mform->addElement('advcheckbox', 'blindreview', get_string('blindreview', manager::PLUGINNAME));
        $mform->setType('blindreview', PARAM_INT);
        $mform->setDefault('blindreview', 0);
        $mform->addHelpButton('blindreview', 'blindreview', manager::PLUGINNAME);

        // Group submissions option.
        $mform->addElement('advcheckbox', 'groupsubmissions', get_string('groupsubmissions', manager::PLUGINNAME));
        $mform->setType('groupsubmissions', PARAM_INT);
        $mform->setDefault('groupsubmissions', 0);
        $mform->addHelpButton('groupsubmissions', 'groupsubmissions', manager::PLUGINNAME);

        // Grouping selection (shown only if group submissions are enabled).
        $groupings = groups_get_all_groupings($COURSE->id);
        $groupingsoptions = [];
        foreach ($groupings as $grouping) {
            $groupingsoptions[$grouping->id] = $grouping->name;
        }

        if (!empty($groupingsoptions)) {
            $mform->addElement(
                'select',
                'groupingid',
                get_string('grouping', manager::PLUGINNAME),
                [0 => get_string('none')] + $groupingsoptions
            );
            $mform->setType('groupingid', PARAM_INT);
            $mform->setDefault('groupingid', 0);
            $mform->disabledIf('groupingid', 'groupsubmissions', 'notchecked');
            $mform->addHelpButton('groupingid', 'grouping', manager::PLUGINNAME);
        }

        // Add standard completion elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Perform some validation.
     *
     * @param array $data Array of ("fieldname"=>value) of submitted data
     * @param array $files Array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Add custom validation rules if needed.
        // Ensure that if groupingid is provided, groupsubmissions is enabled.
        if (!empty($data['groupingid']) && empty($data['groupsubmissions'])) {
            $errors['groupingid'] = get_string('error_grouping_without_groupsubmissions', manager::PLUGINNAME);
        }

        return $errors;
    }
}
