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
 * H5P Content bank manager class
 *
 * @package    contentbank_h5p
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contentbank_h5p;

use core_contentbank\base;

defined('MOODLE_INTERNAL') || die;

/**
 * H5P Content bank manager class
 *
 * @package    contentbank_h5p
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin extends base {

    /** The component for H5P. */
    public const COMPONENT   = 'contentbank_h5p';


    /**
     * Fills content_bank table with appropiate information.
     *
     * @param string $name  Name of the element to be created.
     * @return int          Id of the element created or false if the element has not been created.
     */
    public function create_content(string $name) {

        $this->itemtype = self::COMPONENT;
        $this->content->itemtype = self::COMPONENT;

        return parent::create_content($name);
    }

    /**
     * Return an array of extensions this plugin could manage.
     *
     * @return array
     */
    public function get_manageable_extensions(): array {
        return array('h5p');
    }

    /**
     * Returns this plugin enables uploading.
     *
     * @return bool     True if content could be uploaded. False otherwise.
     */
    public function can_upload(): bool {
        $hascapability = has_capability('contentbank/h5p:additem', \context_system::instance());
        return ($hascapability && parent::can_upload());
    }

    /**
     * Returns the HTML content to add to view.php visualizer.
     *
     * @param int $contentid    Id of the content to be rendered.
     * @return string           HTML code to include in view.php.
     */
    public function get_view_content(int $contentid): string {
        $this->get_content($contentid);

        $fileurl = $this->get_file_url($contentid);
        $player = new \core_h5p\player($fileurl, new \stdClass());
        $html = \html_writer::tag('h2', $this->get_name());
        $html .= $player->get_embed_code($fileurl, true);
        $html .= $player->get_resize_code();

        return $html;
    }

    /**
     * Returns the HTML code to render the icon for H5P content types.
     *
     * @param int $contentid    Id of the content to be rendered.
     * @return string           HTML code to render the icon
     */
    public function get_icon(int $contentid): string {
        global $OUTPUT;

        return $OUTPUT->pix_icon('f/h5p-64', $this->get_name(), 'moodle', ['class' => 'iconsize-big']);
    }

}