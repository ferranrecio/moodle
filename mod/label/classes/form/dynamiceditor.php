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

namespace mod_label\form;

use cm_info;
use core\context;
use core\context\module;
use core\url;
use core_form\dynamic_form;
use moodle_exception;

/**
 * Label dynamic form.
 *
 * @package    mod_label
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dynamiceditor extends dynamic_form {
    /**
     * @var cm_info the course module information.
     */
    protected cm_info $cm;

    /**
     * @var context the context of the course module.
     */
    protected module $context;

    #[\Override]
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('labelname', 'label'), ['size' => '64', 'maxlength' => 255]);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'labelname', 'label');

        $mform->addElement(
            'editor',
            'intro_editor',
            get_string('labeltext', 'mod_label'),
            ['rows' => 20],
            [
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true,
                'context' => $this->get_context_for_dynamic_submission(),
                'subdirs' => true,
            ],
        );
        // No XSS prevention here, users must be trusted.
        $mform->setType('intro_editor', PARAM_RAW);
    }

    /**
     * Returns the options for the editor.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    private function intro_editor_options(): array {
        return [
            'trusttext' => true,
            'noclean' => true,
            'subdirs' => true,
            'context' => $this->get_context_for_dynamic_submission(),
            'maxfiles' => EDITOR_UNLIMITED_FILES,
        ];
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $instance = $this->cm->get_instance_record();
        $context = $this->get_context_for_dynamic_submission();

        $data = (object) [
            'cmid' => $this->cm->id,
            'courseid' => $this->optional_param('courseid', null, PARAM_INT),
            'returnurl' => $this->optional_param('returnurl', null, PARAM_URL),
            'name' => $this->cm->name,
            'intro' => $instance->intro,
            'introformat' => $instance->introformat,
        ];

        $instance = file_prepare_standard_editor(
            data: $data,
            field: 'intro',
            options: $this->intro_editor_options(),
            context: $context,
            component: 'mod_' . $this->cm->modname,
            filearea: 'intro',
            itemid: 0,
        );

        $this->set_data($data);
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): context {
        if (!empty($this->context)) {
            return $this->context;
        }
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        if (empty($cmid)) {
            throw new moodle_exception('invalidcoursemodule', 'error');
        }
        $this->context = module::instance($cmid);

        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        if (empty($courseid)) {
            throw new moodle_exception('invalidcourse', 'error');
        }

        $this->cm = get_fast_modinfo($courseid)->get_cm(
            $this->optional_param('cmid', null, PARAM_INT),
        );

        return $this->context;
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:manageactivities', $this->get_context_for_dynamic_submission());
    }

    #[\Override]
    public function process_dynamic_submission() {
        global $CFG;
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/label/lib.php');

        $data = $this->get_data();

        $cmdata = get_coursemodule_from_id(
            modulename: 'label',
            cmid: $data->cmid,
            strictness: MUST_EXIST,
        );

        $course = get_course($cmdata->course);
        $context = module::instance($data->cmid);

        $data = file_postupdate_standard_editor(
            data: $data,
            field: 'intro',
            options: $this->intro_editor_options(),
            context: $context,
            component: 'mod_' . $cmdata->modname,
            filearea: 'intro',
            itemid: 0,
        );

        [$cm, , , $moduleinfo, ] = get_moduleinfo_data($cmdata, $course);
        $moduleinfo->name = $data->name;
        $moduleinfo->introeditor = $data->intro_editor;

        update_moduleinfo($cm, $moduleinfo, $course);

        return [];
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): url {
        return new url('/course/view.php', ['id' => $this->optional_param('courseid', null, PARAM_INT)]);
    }
}
