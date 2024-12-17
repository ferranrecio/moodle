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

namespace mod_assign\course;

use assign;
use cm_info;
use core_course\local\overview\overviewitem;
use core\output\action_link;
use core\output\local\properties\text_align;
use core\output\local\properties\button;
use core\url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Class overview
 *
 * @package    mod_assign
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_course\activityoverviewbase {
    /** @var assign $assign the assign instance. */
    private assign $assign;

    protected function __construct(
        /** @var cm_info The course module. */
        protected cm_info $cm,
    ) {
        parent::__construct($cm);
        $this->assign = new assign($this->context, $this->cm, $this->cm->get_course());
    }

    #[\Override]
    public function get_due_date_overview(\renderer_base $output): ?overviewitem {
        $duedate = $this->assign->get_instance()->duedate;

        if (empty($duedate)) {
            return new overviewitem(
                name: get_string('duedate', 'assign'),
                value: null,
                content: '-',
            );
        }

        $content = new \core_calendar\output\humandate($duedate, DAYSECS * 2);

        return new overviewitem(
            name: get_string('duedate', 'assign'),
            value: $this->cm->name,
            content: $content,
        );
    }

    #[\Override]
    public function get_actions_overview(\renderer_base $output): ?overviewitem {
        if (!has_capability('mod/assign:grade', $this->context)) {
            return null;
        }
        $needgrading = $this->assign->count_submissions_need_grading();
        /** @var \core\output\core_renderer $output */
        $badge = ($needgrading > 0) ? $output->notice_badge($needgrading) : '';

        $content = new action_link(
            url: new url('/mod/assign/view.php', ['id' => $this->cm->id, 'action' => 'grading']),
            text: get_string('modgrade', 'grades') . $badge,
            attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
        );

        return new overviewitem(
            name: get_string('actions'),
            value: $needgrading,
            content: $content,
            textalign: text_align::CENTER,
        );
    }

    #[\Override]
    public function get_extra_overview_items(\renderer_base $output): array {
        // mod/assign/locallib.php:3312
        return [
            'submissions' => $this->get_extra_submissions_overview($output),
            'submissionstatus' => $this->get_extra_submission_status_overview($output),
        ];
    }

    /**
     * Retrieves an overview of submissions for the assignment.
     *
     * @param \renderer_base $output
     * @return overviewitem|null An overview item c, or null if the user lacks the required capability.
     */
    private function get_extra_submissions_overview(\renderer_base $output): ?overviewitem {
        global $USER;

        if (!has_capability('mod/assign:grade', $this->cm->context)) {
            return null;
        }

        $submitted = $this->assign->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_SUBMITTED);

        $content = new action_link(
            url: new url('mod/assign/view.php', ['id' => $this->cm->id, 'action' => 'grading']),
            text: $submitted,
            attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
        );

        return new overviewitem(
            name: get_string('submissions', 'assign'),
            value: $submitted,
            content: $content,
            textalign: text_align::CENTER,
        );
    }

    /**
     * Retrieves the submission status overview for the current user.
     *
     * @param \renderer_base $output The renderer base instance.
     * @return overviewitem|null The overview item, or null if the user does not have the required capabilities.
     */
    private function get_extra_submission_status_overview(\renderer_base $output): ?overviewitem {
        global $USER;

        if (
            !has_capability('mod/assign:submit', $this->context)
            || has_capability('moodle/site:config', $this->context)
        ) {
            return null;
        }

        if ($this->assign->get_instance()->teamsubmission) {
            $usersubmission = $this->assign->get_group_submission($USER->id, 0, false);
        } else {
            $usersubmission = $this->assign->get_user_submission($USER->id, false);
        }

        if (!empty($usersubmission->status)) {
            $submittedstatus = get_string('submissionstatus_' . $usersubmission->status, 'assign');
        } else {
            $submittedstatus = get_string('submissionstatus_', 'assign');
        }

        return new overviewitem(
            name: get_string('submissionstatus', 'assign'),
            value: $submittedstatus,
            content: $submittedstatus,
        );
    }
}
