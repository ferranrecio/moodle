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
 * Class for the zero state when the lesson has no pages.
 *
 * @package    mod_lesson
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class empty_lesson_action_bar extends initial_state_action_bar {
    private \stdClass $cm;

    private int $prevpageid = 0;

    public function __construct(
        private lesson $lesson,
    ) {
        $this->cm = $lesson->get_cm();
        parent::__construct();
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {

        $this->set_title(get_string('startbuildingactivity', 'course'));
        $this->set_intro(get_string('whatdofirst', 'lesson'));
        $this->set_image($output->image_url('i/zero_state'));

        $this->add_single_button($this->get_add_question_button());
        $this->add_action_menu($this->add_content_types_action_menu());

        return parent::export_for_template($output);
    }

    private function get_add_question_button(): single_button {
        return new single_button(
            new url('/mod/lesson/import.php', ['id' => $this->cm->id, 'pageid' => $this->prevpageid]),
            get_string('importquestions', 'lesson'),
            'get',
            single_button::BUTTON_SECONDARY,
        );
    }

    private function add_content_types_action_menu(): action_menu {
        $fieldselect = new action_menu();
        $fieldselect->set_additional_classes('singlebutton');
        $fieldselect->set_menu_trigger(
            get_string('add'),
            button::PRIMARY->classes(),
        );

        $addquestionurl = new url(
            '/mod/lesson/editpage.php',
            ['id' => $this->cm->id, 'pageid' => $this->prevpageid, 'firstpage' => 1],
        );
        $fieldselect->add(
            new link(
                url: $addquestionurl,
                icon: null,
                text: get_string('addaquestionpage', 'lesson'),
                primary: false,
            )
        );

        $manager = lesson_page_type_manager::get($this->lesson);
        foreach ($manager->get_add_page_type_links($this->prevpageid) as $link) {
            $link['addurl']->param('firstpage', 1);
            $fieldselect->add(
                new link(
                    url: $link['addurl'],
                    icon: null,
                    text: $link['name'],
                    primary: false,
                )
            );
        }

        return $fieldselect;
    }
}
