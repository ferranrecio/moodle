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

/**
 * Renderable for the availability info.
 *
 * @package   core_availability
 * @copyright 2021 Bas Brands <bas@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_availability\output;
use core_availability_multiple_messages;
use renderable;
use templatable;
use stdClass;

/**
 * Base class to render availability info.
 *
 * @package   core_availability
 * @copyright 2021 Bas Brands <bas@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_info implements renderable, templatable {

    /** @var availabilitymessages the course format class */
    protected $availabilitymessages;

    /** @var int counts number of conditions */
    protected static $count = 0;

    /** @var int templateid unique id for this template */
    protected static $templateid = 0;

    /** @var bool If these conditions have parent conditions, ie. are part of a subset  */
    protected $hasparent = false;

    /** @var int Maximum number of lines of availability info */
    protected const MAXVISIBLE = 4;

    /**
     * Constructor.
     *
     * @param core_availability_multiple_messages $renderable the availability messages
     * @param bool $hasparent does this availability set have a parent availability set
     */
    public function __construct(core_availability_multiple_messages $renderable, bool $hasparent = false) {
        $this->availabilitymessages = $renderable;
        $this->hasparent = $hasparent;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        // Make the list.
        $template = (object)[];
        // Get initial message.
        $template->header = get_string('list_' . ($this->availabilitymessages->root ? 'root_' : '') .
                ($this->availabilitymessages->andoperator ? 'and' : 'or') .
                ($this->availabilitymessages->treehidden ? '_hidden' : ''),
                'availability');
        $template->items = [];

        if (!$this->hasparent) {
            $this->hasparent = true;
            self::$count = 0;
            self::$templateid = uniqid();
            $template->parent = true;
        }

        $template->id = self::$templateid;

        foreach ($this->availabilitymessages->items as $item) {
            $message = (object)[];

            if (is_string($item)) {

                self::$count++;
                $message->title = $item . self::$count;
                if (self::$count === self::MAXVISIBLE) {
                    $message->abbreviate = true;
                }
                if (self::$count > self::MAXVISIBLE) {
                    $message->hidden = true;
                }
            } else {
                if (self::$count >= self::MAXVISIBLE) {
                    $message->hidden = true;
                }
                $subitem = new \core_availability\output\availability_info($item, $this->hasparent);
                $message->title = $output->render($subitem);
            }

            $template->items[] = $message;

            if (self::$count === self::MAXVISIBLE) {
                $template->showmorelink = true;
                self::$count += 1;
            }
        }
        return $template;
    }
}
