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

namespace mod_feedback\output;

use core\output\initial_state_action_bar;
use core\output\action_link;
use core_course\cm_info;
use mod_feedback\manager;
use mod_feedback_completion;
use core\output\renderer_base;
use core\url;
use core\output\local\properties\button;

/**
 * Module view action bar.
 *
 * @package    mod_feedback
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_action_bar extends initial_state_action_bar {
    protected bool $canviewcompletion;
    protected bool $canedititems;

    protected bool $isready = false;

    public function __construct(
        protected cm_info $cm,
        protected mod_feedback_completion $feedbackinfo,
    ) {
        $this->canviewcompletion = manager::can_view_completion($this->feedbackinfo);
        $this->isready = !empty($this->feedbackinfo->get_items(true));
        $this->canedititems = has_capability('mod/feedback:edititems', $this->cm->context);

        parent::__construct();
    }

    public function end_page_after_rendering(): bool {
        return !$this->isready;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $this->setup_toolbar_extra_information($output);

        $this->add_action_link($this->get_edit_item());
        $this->add_action_link($this->get_preview_item());
        $this->add_action_link($this->get_continue_item());

        return parent::export_for_template($output);
    }

    private function setup_toolbar_extra_information(renderer_base $output): void {
        if (!$this->isready) {
            $this->setup_not_ready_state_information($output);
            return;
        }

        // For teacher we only show the available options.
        if ($this->canedititems) {
            return;
        }

        if (!$this->feedbackinfo->can_complete()) {
            return;
        }

        if (!$this->feedbackinfo->is_open()) {
            $this->set_title(get_string('activitynotopen', 'course'));
            $this->set_intro(get_string('activitynotopen_info', 'course'));
            $this->set_image($output->image_url('i/zero_state_wait'));
            return;
        }

        if (!$this->feedbackinfo->can_submit()) {
            $this->set_title(get_string('this_feedback_is_already_submitted', 'feedback'));
            $this->set_intro(get_string('thanks', 'mod_feedback'));
            $this->set_image($output->image_url('i/zero_state_completed'));
            return;
        }

        $this->set_title(get_string('welcome', 'mod_feedback'));
        $this->set_image($output->image_url('i/zero_state_start'));
    }

    private function setup_not_ready_state_information(renderer_base $output): void {
        if ($this->canedititems) {
            $this->set_title(get_string('startbuildingactivity', 'course'));
            $this->set_intro(get_string('createactivity', 'mod_feedback'));
            $this->set_image($output->image_url('i/zero_state'));
            return;
        }

        $this->set_title(get_string('activitynotready', 'course'));
        $this->set_intro(get_string('activitynotready_info', 'course'));
        $this->set_image($output->image_url('i/zero_state_noentries'));
    }

    private function get_edit_item(): ?action_link {
        if (!$this->canedititems) {
            return null;
        }

        $editurl = new url('/mod/feedback/edit.php', ['id' => $this->cm->id]);
        $classes = ($this->canviewcompletion) ? button::SECONDARY->classes() : button::PRIMARY->classes();
        return new action_link(
            $editurl,
            get_string('edit_items', 'feedback'),
            null,
            ['class' => $classes],
        );
    }

    private function get_preview_item(): ?action_link {
        if (!$this->isready) {
            return null;
        }

        if (!has_any_capability(
            ['mod/feedback:edititems', 'mod/feedback:viewreports'],
            $this->cm->context,
        )) {
            return null;
        }

        $previewlnk = new url(
            '/mod/feedback/print.php',
            ['id' => $this->cm->id, 'course'=> $this->cm->course],
        );
        return new action_link(
            $previewlnk,
            get_string('previewquestions', 'feedback'),
            null,
            ['class' => button::SECONDARY->classes()],
        );
    }

    private function get_continue_item(): ?action_link {
        if (!$this->isready || !$this->canviewcompletion) {
            return null;
        }

        $completeurl = new url(
            '/mod/feedback/complete.php',
            ['id' => $this->cm->id, 'courseid' => $this->cm->course],
        );

        $startpage = $this->feedbackinfo->get_resume_page();
        if ($startpage) {
            $completeurl->param('gopage', $startpage);
            $label = get_string('continue_the_form', 'feedback');
        } else {
            $label = get_string('complete_the_form', 'feedback');
        }

        return new action_link(
            $completeurl,
            $label,
            null,
            ['class' => button::PRIMARY->classes()],
        );
    }
}
