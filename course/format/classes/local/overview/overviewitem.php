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

namespace core_courseformat\local\overview;

use core\output\renderable;
use core\output\renderer_base;
use core\output\local\properties\text_align;
use core\output\externable;
use stdClass;

/**
 * Class overviewitem
 *
 * @package    core_courseformat
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overviewitem implements externable {
    /**
     * Internal key for the overview item, if any.
     *
     * This value may be provided by the caller to uniquely identify the item inside a list of items.
     * Used mostly for web services or API responses where items need to be referenced.
     *
     * @var string|null
     */
    private string|null $key = null;

    /**
     * Overview item constructor.
     *
     * @param string $name The overview item name.
     * @param int|string|bool|null $value Internal value of the item (used for filtering and export).
     * @param string|renderable|null $content The item content to display.
     * @param text_align $textalign The preferred text alignment for the parent container.
     * @param int $alertcount an optional numeric indicator for alerts (zero means no alerts).
     * @param string $alertlabel the meaning to show with the alert count.
     * @param stdClass|null $extradata any extra optional item data to export.
     */
    public function __construct(
        /** @var string The overview item name */
        protected string $name,
        /** @var string Internal value of the item (used for filtering and export) */
        protected int|string|bool|null $value,
        /** @var string The item content to display */
        protected string|renderable|null $content = null,
        /** @var text_align The preferred text alignment for the parent container. */
        protected text_align $textalign = text_align::START,
        /** @var int an optional numeric indicator for alerts (zero means no alerts). */
        protected int $alertcount = 0,
        /** @var string the badge label an optional label for the badge. */
        protected string $alertlabel = '',
        /** @var stdClass any extra optional item data to export */
        protected stdClass|null $extradata = null,
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
     * Gets the alert count for the item.
     *
     * Alert count is an optional numeric indicator for alerts used for filtering,
     * highlighting, or the mobile APP badge display.
     *
     * @return int The alert count.
     */
    public function get_alert_count(): int {
        return $this->alertcount;
    }

    /**
     * Gets the alert label for the item.
     *
     * @return string The alert label.
     */
    public function get_alert_label(): string {
        return $this->alertlabel;
    }

    /**
     * Gets the key for the overview item.
     *
     * The key is an optional internal identifier that can be used to reference the item.
     *
     * @return string|null The key or null if not set.
     */
    public function get_key(): ?string {
        return $this->key;
    }

    /**
     * Gets the extra data for the overview item.
     *
     * This method returns any additional data that may be associated with the item.
     *
     * @return stdClass|null The extra data or null if not set.
     */
    public function get_extra_data(): ?stdClass {
        return $this->extradata;
    }

    /**
     * Sets the content for this item.
     *
     * Items can utilize either a renderable object or a pre-rendered string as their content.
     *
     * @param string|renderable|null $content
     * @return $this
     */
    public function set_content(string|renderable|null $content): static {
        $this->content = $content;
        return $this;
    }

    /**
     * Sets the preferred text alignment of the item.
     *
     * @param text_align $textalign
     * @return $this
     */
    public function set_text_align(text_align $textalign): static {
        $this->textalign = $textalign;
        return $this;
    }

    /**
     * Sets the value of the overview item.
     *
     * @param int|string|bool|null $value
     * @return $this
     */
    public function set_value(int|string|bool|null $value): static {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets the name of the overview item.
     *
     * @param string $name
     * @return $this
     */
    public function set_name(string $name): static {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the alert count and alert label for the item.
     *
     * @param int $alertcount
     * @param string $alertlabel
     * @return $this
     */
    public function set_alert(int $alertcount, string $alertlabel): static {
        $this->alertcount = $alertcount;
        $this->alertlabel = $alertlabel;
        return $this;
    }

    /**
     * Sets the key for the overview item.
     *
     * The key is an optional internal identifier that can be used to reference the item.
     *
     * @param string|null $key
     * @return $this
     */
    public function set_key(?string $key): static {
        $this->key = $key;
        return $this;
    }

    /**
     * Sets the extra data for the overview item.
     *
     * This method allows setting additional data that may be associated with the item.
     *
     * @param stdClass|null $extradata
     * @return $this
     */
    public function set_extra_data(?stdClass $extradata): static {
        $this->extradata = $extradata;
        return $this;
    }

    /**
     * Gets the content type of the overview item.
     */
    public function get_content_type(): string {
        if ($this->content instanceof renderable) {
            return $this->content::class;
        }
        return 'basic';
    }

    #[\Override]
    public function export_for_external(renderer_base $output): stdClass {
        return (object)[
            'name' => $this->get_name(),
            'key' => $this->get_key(),
            'contenttype' => $this->get_content_type(),
            'alertlabel' => $this->get_alert_label(),
            'alertcount' => $this->get_alert_count(),
            'contentdata' => $this->export_content_for_external($output),
            'extradata' => $this->extradata,
        ];
    }

    /**
     * Exports the content of the overview item for external use.
     *
     * This method is used to prepare the content for external APIs or web services.
     *
     * @param renderer_base $output The renderer to use for rendering the content.
     * @return stdClass The exported content data.
     */
    public function export_content_for_external(renderer_base $output): stdClass {
        // Renderables are meant to be used in UI and it is not stable enough for a web service,
        // we can only trust the data provided by an externable object.
        if ($this->content instanceof externable) {
            return $this->content->export_for_external($output);
        }

        $itemdata = (object) [
            'value' => $this->value,
            'datatype' => gettype($this->value),
        ];
        return $itemdata;
    }
}
