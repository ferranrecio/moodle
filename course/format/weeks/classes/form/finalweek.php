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

namespace format_weeks\form;

use core\context;
use core\context\course as context_course;
use core\url;
use core_courseformat\base as courseformat;
use core_courseformat\formatactions;
use core_form\dynamic_form;
use moodle_exception;


/**
 * Final week dynamic form.
 *
 * @package    format_weeks
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finalweek extends dynamic_form {

    /** @var int Indicate all contents of weeks after the final week date will be moved to the final week. */
    const FINALWEEK_MERGE_MOVE = 0;

    /** @var int Indicate all contents of weeks after the final week date will be deleted. */
    const FINALWEEK_MERGE_DELETE = 1;

    /**
     * @var courseformat the course format.
     */
    protected courseformat $format;

    /**
     * @var context the form context.
     */
    protected context_course $context;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'datepicker', get_string('finalweek_dates', 'format_weeks'));

        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'moodle'));
        $mform->addHelpButton('startdate', 'startdate', 'moodle');

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('date_selector', 'finalweek', get_string('finalweek_date', 'format_weeks'));
        $mform->addHelpButton('finalweek', 'finalweek_date', 'format_weeks');

        $options = $this->format->get_format_options();
        if (empty($options['automaticenddate'])) {
            $mform->addElement('checkbox', 'updateenddate', '', get_string('finalweek_updateenddate', 'format_weeks'));
        }

        $mform->addElement('header', 'moresettings', get_string('finalweek_more', 'format_weeks'));
        $mform->setExpanded('moresettings', false);

        $options = [
            self::FINALWEEK_MERGE_MOVE => get_string('finalweek_merge_move', 'format_weeks'),
            self::FINALWEEK_MERGE_DELETE => get_string('finalweek_merge_delete', 'format_weeks'),
        ];
        $mform->addElement('select', 'mergemethod', get_string('finalweek_merge', 'format_weeks'), $options);
        $mform->setDefault('mergemethod', self::FINALWEEK_MERGE_MOVE);
        $mform->addHelpButton('mergemethod', 'finalweek_merge', 'format_weeks');
        // $mform->setAdvanced('mergemethod');

        $renderer = \core\di::get(\core\output\renderer_helper::class)->get_core_renderer();
        $deletewarning = $renderer->container(
            get_string('finalweek_merge_delete_warning', 'format_weeks'),
            \core\output\local\properties\text::DANGER->classes(),
        );
        $mform->addElement('static', 'warning', '', $deletewarning);
        $mform->hideif('warning', 'mergemethod', 'eq', self::FINALWEEK_MERGE_MOVE);

        $options = [
            0 => get_string('hiddensectionscollapsed'),
            1 => get_string('hiddensectionsinvisible'),
        ];
        $mform->addElement('select', 'hiddensections', get_string('hiddensections'), $options);
        $mform->setDefault('hiddensections', 0);
        $mform->addHelpButton('hiddensections', 'hiddensections', 'moodle');

        $options = [
            COURSE_DISPLAY_SINGLEPAGE => get_string('coursedisplay_single'),
            COURSE_DISPLAY_MULTIPAGE => get_string('coursedisplay_multi')
        ];
        $mform->addElement('select', 'coursedisplay', get_string('coursedisplay'), $options);
        $mform->setDefault('coursedisplay', COURSE_DISPLAY_SINGLEPAGE);
        $mform->addHelpButton('coursedisplay', 'coursedisplay', 'moodle');
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $course = $this->format->get_course();
        $options = $this->format->get_format_options();

        $data = (object) [
            'courseid' => $this->optional_param('courseid', null, PARAM_INT),
            'coursedisplay' => $options['coursedisplay'],
            'hiddensections' => $options['hiddensections'],
            'startdate' => $course->startdate,
        ];

        $lastsection = $this->format->get_section(
            $this->format->get_last_section_number(),
        );

        if (!$lastsection->sectionnum !== 0) {
            $dates = $this->format->get_section_dates($lastsection);
            // The week end date timestamps is not included in the week itself. We substract 1 days
            // to get the real last date of the week. This prevent the user from incrementing
            // one week by submitting the form if submitted by accident.
            $data->finalweek = $dates->end - 1*DAYSECS;
        }

        $this->set_data($data);
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): context {
        if (!empty($this->context)) {
            return $this->context;
        }

        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        if (empty($courseid)) {
            throw new moodle_exception('invalidcourse', 'error');
        }

        $this->format = courseformat::instance($courseid);

        $this->context = context_course::instance($courseid);
        return $this->context;
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        require_all_capabilities(
            [
                'moodle/course:movesections',
                'moodle/course:manageactivities',
                'moodle/course:update'
            ],
            $this->get_context_for_dynamic_submission()
        );
    }

    #[\Override]
    public function validation($formdata, $files): array {
        $errors = [];

        $course = $this->format->get_course();
        if ($formdata['finalweek'] < $course->startdate) {
            $errors['finalweek'] = get_string('finalweek_error_before_start', 'format_weeks');
        }
        return $errors;
    }

    #[\Override]
    public function process_dynamic_submission() {
        $db = \core\di::get(\moodle_database::class);
        $data = $this->get_data();
        $course = $this->format->get_course();

        $options = $this->format->get_format_options();
        if (empty($options['automaticenddate']) && !empty($data->updateenddate)) {
            $db->set_field('course', 'enddate', $data->finalweek, ['id' => $data->courseid]);
            $iscoursemodified = true;
        }

        if ($data->startdate != $course->startdate) {
            $db->set_field('course', 'startdate', $data->startdate, ['id' => $data->courseid]);
            $iscoursemodified = true;
        }

        $iscoursemodified = $iscoursemodified || formatactions::section($data->courseid)->set_final_week_date(
            $data->finalweek,
            $data->mergemethod == self::FINALWEEK_MERGE_MOVE,
        );

        $iscoursemodified = $iscoursemodified || $this->format->update_course_format_options($data);

        return [
            'result' => (bool) $iscoursemodified,
            'url' => $this->get_page_url_for_dynamic_submission()->out(),
            'errors' => [],
        ];
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): url {
        return new url(
            '/course/view.php',
            ['id' => $this->optional_param('courseid', null, PARAM_INT)],
        );
    }
}
