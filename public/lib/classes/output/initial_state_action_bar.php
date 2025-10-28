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

namespace core\output;

use core\output\action_menu;
use core\output\renderer_base;
use core\output\single_button;
use core\output\action_link;
use core\url;
use core\output\renderable;
use core\output\named_templatable;

/**
 * Class to render a zero state panel.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class initial_state_action_bar implements named_templatable, renderable  {
    /** @var array The panel tool links. */
    protected array $tools = [];

    public function __construct(
        /** @var string The panel title. */
        protected string $title = "",
        /** @var string The panel intro. */
        protected string $intro = "",
        /** @var url|null The panel top image. */
        protected url|null $image = null,
        /** @var renderable|null An optional displayable to show below the intro. */
        protected renderable|null $displayable = null,
    ) {
    }
    public function set_image(url $image): self {
        $this->image = $image;
        return $this;
    }
    public function set_title(string $title): self {
        $this->title = $title;
        return $this;
    }
    public function set_intro(string $intro): self {
        $this->intro = $intro;
        return $this;
    }

    public function set_displayable(?renderable $displayable): self {
        $this->displayable = $displayable;
        return $this;
    }

    public function add_action_menu(?action_menu $actionmenu): self {
        // We allow null to simplify the calling code and prevent unnecessary spaghetti code.
        if ($actionmenu === null) {
            return $this;
        }
        $this->tools[] = (object)[
            'type' => 'action_menu',
            'instance' => $actionmenu,
        ];
        return $this;
    }

    public function add_single_button(?single_button $singlebutton): self {
        // We allow null to simplify the calling code and prevent unnecessary spaghetti code.
        if ($singlebutton === null) {
            return $this;
        }
        $this->tools[] = (object)[
            'type' => 'single_button',
            'instance' => $singlebutton,
        ];
        return $this;
    }

    public function add_action_link(?action_link $actionlink): self {
        // We allow null to simplify the calling code and prevent unnecessary spaghetti code.
        if ($actionlink === null) {
            return $this;
        }
        $this->tools[] = (object)[
            'type' => 'action_link',
            'instance' => $actionlink,
        ];
        return $this;
    }

    public function add_html_action(string $toolhtml): self {
        $this->tools[] = (object)[
            'type' => 'htmltool',
            'instance' => $toolhtml,
        ];
        return $this;
    }

    protected function export_disabled_panell(): array {
        return [
            'disablepanel' => true,
        ];
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $data = [
            "title"=> $this->title ?: null,
            'intro' => $this->intro ?: null,
            'image' => $this->image ?: null,
            'displayable' => $this->displayable ? $output->render($this->displayable) : null,
            'hasinfo' => (
                !empty($this->title) || !empty($this->intro) || !empty($this->image) || !empty($this->displayable)
            ),
            "tools"=> [],
        ];

        foreach ($this->tools as $tool) {
            $tooldata = ($tool->type == 'htmltool')? $tool->instance : $tool->instance->export_for_template($output);
            $data['tools'][] = [
                $tool->type => $tooldata,
            ];
        }
        $data['hastools'] = !empty($data['tools']);

        $data['disablepanel'] = !$data['hasinfo'] && !$data['hastools'];

        return $data;
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'core/initial_state_action_bar';
    }
}
