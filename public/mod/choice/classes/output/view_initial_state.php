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

namespace mod_choice\output;

use core\output\initial_state_action_bar;
use core\output\action_menu;
use core\output\action_menu\link;
use core\output\single_button;
use core\output\renderer_base;
use core\output\local\properties\button;
use core_course\cm_info;
use core\url;
use lesson;
use lesson_page_type_manager;

/**
 * The users initial state for choice view page.
 *
 * @package    mod_choice
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_initial_state extends initial_state_action_bar {
    /** @var int The lesson is not ready. */
    public const READY = 0;

    /** @var int The lesson is not open yet. */
    public const NOTOPEN = 1;

    /** @var int The lesson is closed. */
    public const CLOSED = 2;

    private int $currentstate = self::READY;

    public function __construct(
        private readonly \stdClass $choice,
        private readonly cm_info $cm,
        private readonly array|null $currentresponse = null
    ) {
        $this->currentstate = $this->determine_current_state();
        parent::__construct();
    }

    public function end_page_after_rendering(): bool {
        if ($this->currentstate === self::NOTOPEN && $this->choice->showpreview) {
            return false;
        }
        return $this->currentstate !== self::READY;
    }

    private function determine_current_state(): int {
        global $USER;

        $context = $this->cm->context;
        $timenow = \core\di::get(\core\clock::class)->time();

        if(has_capability('mod/choice:readresponses', $context)) {
            return self::READY;
        }

        if (!empty($this->choice->timeopen) && $this->choice->timeopen >= $timenow) {
            return self::NOTOPEN;
        }

        if (!empty($this->choice->timeclose) &&  $this->choice->timeclose < $timenow) {
            // print_object($this->choice);
            return self::CLOSED;
        }

        return self::READY;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        if ($this->currentstate == self::READY) {
            return $this->export_disabled_panell();
        }

        $content = match ($this->currentstate) {
            self::NOTOPEN => [
                'title' => get_string('activitynotopen', 'course'),
                'intro' => get_string('activitynotopen_info', 'course'),
                'image' => $output->image_url('i/zero_state_wait'),
            ],
            self::CLOSED => [
                'title' => get_string('activityisclosed', 'course'),
                'intro' => get_string('activityisclosed_info', 'course'),
                'image' => $output->image_url('i/zero_state_wait'),
            ],
            default => [
                'title' => get_string('activitynotready', 'course'),
                'intro' => get_string('activitynotready_info', 'course'),
                'image' => $output->image_url('i/zero_state_noentries'),
            ],
        };

        $this->set_image($content['image']);
        $this->set_title($content['title']);

        if ($content['intro']) {
            $this->set_intro($content['intro']);
        }

        if (isset($content['button'])) {
            $this->add_single_button($content['button']);
        }

        return parent::export_for_template($output);
    }
}
