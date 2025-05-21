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

namespace mod_choice\courseformat;

use core\activity_dates;
use core_calendar\output\humandate;
use cm_info;
use core_courseformat\local\overview\overviewitem;
use core\output\action_link;
use core\output\local\properties\text_align;
use core\output\local\properties\button;
use core\url;
use mod_choice\manager;

/**
 * Choice overview integration.
 *
 * @package    mod_choice
 * @copyright  2025 Laurent David <laurent.david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * @var manager the choice manager.
     */
    private manager $manager;

    /**
     * Constructor.
     *
     * @param cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     * @param \core_string_manager $sm the string manager.
     */
    public function __construct(
        cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
        /** @var \core_string_manager $sm the string manager */
        protected readonly \core_string_manager $sm,
    ) {
        parent::__construct($cm);
        $this->manager = manager::create_from_coursemodule($cm);
    }

    #[\Override]
    public function get_due_date_overview(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $closedate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeclose') {
                $closedate = $date['timestamp'];
                break;
            }
        }
        if (empty($closedate)) {
            return new overviewitem(
                name: $this->sm->get_string('choiceclose', 'choice'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($closedate);

        return new overviewitem(
            name: $this->sm->get_string('choiceclose', 'choice'),
            value: $closedate,
            content: $content,
        );
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        global $USER;
        if (!has_capability('mod/choice:readresponses', $this->context)) {
            return null;
        }

        $currentanswerscount = $this->manager->get_all_answers_count($USER->id);

        $content = new action_link(
            url: new \moodle_url('/mod/choice/report.php', ['id' => $this->cm->id]),
            text: $currentanswerscount,
            attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
        );

        return new overviewitem(
            name: $this->sm->get_string('responses', 'choice'),
            value: $currentanswerscount,
            content: $content,
            textalign: text_align::CENTER,
        );
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'responded' => $this->get_extra_status_for_user(),
        ];
    }

    /**
     * Get the response status overview item.
     *
     * @return overviewitem|null An overview item, or null if the user lacks the required capability.
     */
    private function get_extra_status_for_user(): ?overviewitem {
        global $USER;

        if (has_capability('mod/choice:readresponses', $this->cm->context)) {
            return null; // If the user can read responses, we don't show the submission status as it is for the student only.
        }

        $status = $this->manager->get_user_answers_count($USER->id) > 0;
        $statustext = $this->sm->get_string('notanswered', 'choice');
        if ($status) {
            $statustext = $this->sm->get_string('answered', 'choice');
        }
        $corerenderer = $this->rendererhelper->get_core_renderer();
        $submittedstatuscontent = "";
        if ($status) {
            $submittedstatuscontent = $corerenderer->pix_icon(
                'i/checkedcircle',
                $statustext,
                'core',
                ['class' => 'text-success'],
            );
        }
        return new overviewitem(
            name: $this->sm->get_string('responded', 'choice'),
            value: $status,
            content: $submittedstatuscontent,
            textalign: text_align::CENTER,
        );
    }
}
