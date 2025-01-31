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

namespace core_course\local\overview;

use core\output\renderable;
use core\output\renderer_base;
use core\output\local\properties\text_align;

/**
 * Class overviewitem
 *
 * @package    core_course
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overviewitem {
    /**
     * Overview item constructor.
     *
     * @param string $name The name of the activity.
     * @param int|string|bool|null $value The section name.
     * @param string|renderable|null $content The item content.
     * @param text_align $textalign The preferred text alignment.
     */
    public function __construct(
        /** @var string the name of the activity */
        protected string $name,
        /** @var string the section name */
        protected int|string|bool|null $value,
        /** @var string the item content */
        protected string|renderable|null $content = null,
        /** @var text_align the preferred text alignment. */
        protected text_align $textalign = text_align::START,
    ) {
    }

    /**
     * Retrieves the name of the overview item.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Retrieves the value of the overview item.
     *
     * @return int|string|bool|null
     */
    public function get_value(): int|string|bool|null {
        return $this->value;
    }

    /**
     * Gets the content for this item.
     *
     * Items can utilize either a renderable object or a pre-rendered string as their content.
     *
     * - For simple items, a plain string is sufficient and can be used in any context.
     * - For more complex items, a renderable object is preferable. This allows the item
     *   to be rendered differently depending on the context, providing greater flexibility.
     *
     * @return string|\core\output\renderable|null
     */
    public function get_content(): string|renderable|null {
        return $this->content ?? (string) $this->value ?? null;
    }

    /**
     * Gets the rendered content for this item.
     *
     * This method is used when the context does not have any specific requirements
     * and could use the default item content rendering.
     *
     * @param \core\output\renderer_base $output
     * @return string
     */
    public function get_rendered_content(renderer_base $output): string {
        if ($this->content instanceof renderable) {
            return $output->render($this->content);
        }
        return $this->get_content() ?? '';
    }

    /**
     * Gets the preferred text alignment of the item.
     *
     * @return text_align The text alignment.
     */
    public function get_text_align(): text_align {
        return $this->textalign;
    }

    /**
     * Sets the content for this item.
     *
     * Items can utilize either a renderable object or a pre-rendered string as their content.
     *
     * @param string|renderable|null $content
     */
    public function set_content(string|renderable|null $content): void {
        $this->content = $content;
    }

    /**
     * Sets the preferred text alignment of the item.
     *
     * @param text_align $textalign
     */
    public function set_text_align(text_align $textalign): void {
        $this->textalign = $textalign;
    }

    /**
     * Sets the value of the overview item.
     *
     * @param int|string|bool|null $value
     */
    public function set_value(int|string|bool|null $value): void {
        $this->value = $value;
    }

    /**
     * Sets the name of the overview item.
     *
     * @param string $name
     */
    public function set_name(string $name): void {
        $this->name = $name;
    }
}
