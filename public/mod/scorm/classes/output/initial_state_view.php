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

namespace mod_scorm\output;

use core\output\initial_state_action_bar;
use mod_scorm\manager;
use core\output\renderer_base;

/**
 * The SCORM initial page state view.
 *
 * @package    mod_scorm
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class initial_state_view extends initial_state_action_bar {
    private bool $canviewreports = false;
    private bool $showstatus = true;
    private bool $showtoc = true;

    public function __construct(
        private manager $manager,
    ) {
        global $USER;
        $instance = $this->manager->get_instance();
        $context = $manager->get_context();

        $this->canviewreports = has_capability('mod/scorm:viewreport', $context, $USER->id);

        $this->showstatus = (
            $instance->displayattemptstatus == SCORM_DISPLAY_ATTEMPTSTATUS_ALL
            || $instance->displayattemptstatus == SCORM_DISPLAY_ATTEMPTSTATUS_ENTRY
        );

        $this->showtoc = ($instance->displaycoursestructure == 1);

        parent::__construct();
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {

        $this->load_state_information($output);

        if ($this->showstatus) {
            $this->set_displayable(new attempt_status($this->manager));
        }

        return parent::export_for_template($output);
    }

    private function load_state_information(renderer_base $output): void {
        // Those are the cases where the used is not allowed to see any information
        // related to the activity, so we replace all the information with textual
        // messages.
        if (!$this->showstatus && !$this->showtoc) {
            $this->load_textual_state_information($output);
        }

        // Teachers does not need initial state information.
        if ($this->canviewreports) {
            return;
        }

        $instance = $this->manager->get_instance();
        $timenow = \core\di::get(\core\clock::class)->now();

        $isopen = (empty($instance->timeopen) || $instance->timeopen >= $timenow);
        if (!$isopen) {
            $this->set_title(get_string('activitynotopen', 'course'));
            $this->set_intro(get_string('activitynotopen_info', 'course'));
            $this->set_image($output->image_url('i/zero_state_wait'));
            // If the student cannot access the activity,
            // the status does not proivide any useful information.
            $this->showstatus = false;
            return;
        }

        $isclose = (!empty($instance->timeclose) && $instance->timeclose < $timenow);
        if ($isclose) {
            $this->set_title(get_string('activityisclosed', 'course'));
            $this->set_intro(get_string('activityisclosed_info', 'course'));
            $this->set_image($output->image_url('i/zero_state_wait'));
            return;
        }
    }

    private function load_textual_state_information(renderer_base $output): void {
        global $USER;

        $instance = $this->manager->get_instance();
        /** @var int $attemptcount */
        $attemptcount = scorm_get_attempt_count($USER->id, $instance, false, true);

        if (
            $instance->maxattempt > 0
            && $attemptcount >= $instance->maxattempt
        ) {
            $this->set_title(get_string('exceededmaxattempts_title', 'mod_scorm'));
            $this->set_intro(get_string('exceededmaxattempts', 'scorm'));
            $this->set_image($output->image_url('i/zero_state_completed'));
            return;
        }

        $this->set_title(get_string('welcome', 'mod_scorm'));
        $this->set_intro(get_string('welcome_info', 'mod_scorm'));
        $this->set_image($output->image_url('i/zero_state_start'));
    }
}
