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

namespace mod_lesson\output;

use core\output\initial_state_action_bar;
use core\output\action_menu;
use core\output\action_menu\link;
use core\output\single_button;
use core\output\renderer_base;
use core\output\local\properties\button;
use core\url;
use lesson;
use lesson_page_type_manager;

/**
 * Initial state panel for lesson view page.
 *
 * @package    mod_lesson
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class initial_state_panel extends initial_state_action_bar {

    /** @var int The lesson is not ready. */
    public const READY = 0;

    /** @var int The lesson is not open yet. */
    public const NOTOPEN = 1;

    /** @var int The lesson is closed. */
    public const CLOSED = 2;

    /** @var int The lesson is password protected. */

    /** @var int The lesson is not ready. */
    public const NOTREADY = 3;

    /** @var int The lesson has been already submitted and no retakes are allowed. */
    public const NORETAKE = 4;

    public const PASSWORD = 5;

    /** @var int The lesson depends on an external activity. */
    public const DEPENDENCY = 6;

    /** @var int The user has exceeded the time limit but it can make a new attempt. */
    public const TIMEEXCEEDED = 7;

    /** @var int The user has exceeded the time limit and cannot retake the lesson. */
    public const TIMEEXCEEDEDNORETAKE = 8;

    private \stdClass $cm;

    private int $prevpageid = 0;

    private int $currentstate = self::READY;

    private \stdClass|null $dependencies = null;

    public function __construct(
        private lesson $lesson,
        private ?int $pageid = null,
        private string $userpassword = '',
    ) {
        $this->cm = $lesson->get_cm();
        $this->currentstate = $this->determine_current_state();
        parent::__construct();
    }

    public function end_page_after_rendering(): bool {
        return $this->currentstate != self::READY;
    }

    public function force_state(int $state): self {
        $this->currentstate = $state;
        return $this;
    }

    private function determine_current_state(): int {
        global $USER;

        $timerestriction = $this->lesson->get_time_restriction_status();
        if ($timerestriction) {
            return ($timerestriction->reason == 'lessonopen') ? self::NOTOPEN : self::CLOSED;
        }

        $passwordrestriction = $this->lesson->get_password_restriction_status($this->userpassword);
        if ($passwordrestriction) {
            return self::PASSWORD;
        }

        $dependencies = $this->lesson->get_dependencies_restriction_status();
        if ($dependencies) {
            $this->dependencies = (!empty($dependencies)) ? $dependencies : null;
            return self::DEPENDENCY;
        }

        // The rest of restrictions only apply if we don't have a page yet.
        if ($this->lesson->can_manage() || !empty($this->pageid)) {
            return self::READY;
        }

        if (!$this->lesson->firstpage || !$this->lesson->has_pages()) {
            return self::NOTREADY;
        }

        $retries = $this->lesson->count_user_retries($USER->id);
        if ($retries > 0 && !$this->lesson->retake) {
            return self::NORETAKE;
        }

        return self::READY;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        if ($this->currentstate == self::READY) {
            return $this->export_disabled_panell();
        }

        $content = match ($this->currentstate) {
            self::NORETAKE => [
                'title' => get_string('alreadysubmitted', 'mod_lesson'),
                'intro' => get_string('alreadysubmitted_info', 'mod_lesson'),
                'image' => $output->image_url('i/zero_state_completed'),
            ],
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
            self::DEPENDENCY => [
                'title' => get_string('activitynotready', 'course'),
                'intro' => $output->dependancy_errors(
                    $this->dependencies->dependentlesson,
                    $this->dependencies->errors,
                ),
                'image' => $output->image_url('i/zero_state_noentries'),
            ],
            self::PASSWORD => [
                'title' => get_string(
                    'passwordprotectedlesson',
                    'mod_lesson',
                    format_string($this->lesson->name)
                ),
                'intro' => $this->password_form($output),
                'image' => $output->image_url('i/zero_state_input'),
            ],
            self::TIMEEXCEEDED => [
                'title' => get_string('leftduringtimed_title', 'mod_lesson'),
                'intro' => get_string('leftduringtimed', 'mod_lesson'),
                'image' => $output->image_url('i/zero_state_wait'),
                'button' =>new single_button(
                    new url(
                        '/mod/lesson/view.php',
                        [
                            'id' => $this->cm->id,
                            'pageid' => $this->lesson->firstpageid,
                            'startlastseen' => 'no'
                        ]
                    ),
                    get_string('restart', 'lesson'),
                    'get',
                    single_button::BUTTON_PRIMARY,
                ),
            ],
            self::TIMEEXCEEDEDNORETAKE => [
                'title' => get_string('leftduringtimed_title', 'mod_lesson'),
                'intro' => get_string('leftduringtimednoretake', 'mod_lesson'),
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

    private function password_form(renderer_base $output): string {
        return $output->render_from_template(
            'mod_lesson/password_form',
            [
                'action' => (new url('/mod/lesson/view.php', ['id' => $this->cm->id]))->out(false),
                'cmid' => $this->cm->id,
                'sesskey' => sesskey(),
                'loginfailed' => !empty($this->userpassword),
            ],
        );
    }
}
