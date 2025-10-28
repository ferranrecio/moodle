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

use core\output\renderer_base;
use core\output\renderable;
use core\output\templatable;
use core_course\cm_info;
use core\url;

/**
 * Class display_options
 *
 * @package    mod_choice
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class display_options implements renderable, templatable {
    public function __construct(
        private \stdClass $choice,
        private cm_info $cm,
        private array $allresponses,
        private \stdClass|null $user = null,
    ) {
        global $USER;
        if ($user === null) {
            $this->user = $USER;
        }
    }

    public function export_for_template(renderer_base $output): array {
        $options = choice_prepare_options($this->choice, $this->user, $this->cm, $this->allresponses);
        // echo $renderer->display_options($options, $cm->id, $choice->display, $choice->allowmultiple);

        $optionsdata = $this->normalize_options($options);

        $hasavailableoptions = false;
        foreach ($optionsdata as $option) {
            if (!$option['disabled']) {
                $hasavailableoptions = true;
            }
        }

        $canremoveoptions = !empty($options['allowupdate']) && ($options['allowupdate']);

        $removeoptionsurl = new url(
            '/mod/choice/view.php',
            ['id' => $this->cm->id, 'action' => 'delchoice', 'sesskey' => sesskey()],
        );

        $data = [
            'allowmultiple' => ($this->choice->allowmultiple) ? true : null,
            'canchoose' => !empty($options['hascapability']),
            'candelete' => !empty($options['allowupdate']),
            'canremoveoptions' => $canremoveoptions,
            'coursemoduleid' => $this->cm->id,
            'infomessage' => $this->publish_information_message($options),
            'isfull' => $hasavailableoptions,
            'ispreviewonly' => !empty($options['previewonly']),
            'isvertical' => ($this->choice->display) ? true : null,
            'limitanswers' => !empty($options['limitanswers']),
            'multiple' => !empty($this->choice->allowmultiple),
            'options' => $optionsdata,
            'removeoptionsurl' => $removeoptionsurl->out(false),
            'sesskey' => sesskey(),
            'showavailable' => !empty($options['showavailable']),
            'viewurl' => new url('/mod/choice/view.php', ['id'=> $this->cm->id]),
        ];

        return $data;
    }

    private function publish_information_message(array $options): ?string {
        $cansubmit = !empty($options['previewonly']) || !empty($options['hascapability']);
        switch ($this->choice->showresults) {
            case CHOICE_SHOWRESULTS_NOT:
                if (!$cansubmit) {
                    return null;
                }
                return get_string('publishinfonever', 'choice');

            case CHOICE_SHOWRESULTS_AFTER_ANSWER:
                if ($this->choice->publish == CHOICE_PUBLISH_ANONYMOUS) {
                    return get_string('publishinfoanonafter', 'choice');
                } else {
                    return get_string('publishinfofullafter', 'choice');
                }

            case CHOICE_SHOWRESULTS_AFTER_CLOSE:
                if ($this->choice->publish == CHOICE_PUBLISH_ANONYMOUS) {
                    return get_string('publishinfoanonclose', 'choice');
                } else {
                    return get_string('publishinfofullclose', 'choice');
                }
        }

        return null;
    }

    private function normalize_options(array $options): array {
        $normalized = [];
        $current = 1;
        foreach ($options['options'] as $option) {
            $normalized[] = [
                'text' => $option->text,
                'value' => $option->attributes->value,
                'disabled' => !empty($option?->attributes?->disabled) || !empty($options['previewonly']),
                'checked' => !empty($option?->attributes?->checked),
                'countanswers' => $option->countanswers,
                'maxanswers' => $option->maxanswers,
                'allowupdate' => $this->choice->allowupdate,
                'optionnumber' => $current++,
            ];
        }
        return $normalized;
    }
}
