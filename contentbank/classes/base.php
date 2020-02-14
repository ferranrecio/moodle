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
 * Content bank manager class
 *
 * @package    core_contentbank
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_contentbank;

defined('MOODLE_INTERNAL') || die();

/**
 * Content bank manager class
 *
 * @package    core_contentbank
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base {

    /** @var int Public visibility **/
    public const PUBLIC = 1;

    /** @var string $itemtype The type of the content managed. **/
    protected $itemtype  = '';

    /** @var int $itemid The id of the content managed. **/
    protected $itemid = 0;

    /** @var stdClass This content's context. **/
    protected $context = null;

    /** @var string $name The name of this content. **/
    protected $name  = '';

    /** @var stdClass $content The object to manage this content. **/
    protected $content  = null;

    /**
     * Content bank constructor
     *
     */
    public function __construct() {
        $this->context = \context_system::instance();

        $content = new \stdClass();
        $content->visibility = self::PUBLIC;
        $content->contextid = $this->context->id;

        $this->content = $content;
    }

    /**
     * Get contents with the given id.
     *
     * @param int $contentid    Id of content to get.
     */
    public function get_content(int $contentid) {
        global $DB;

        $this->content = $DB->get_record('contentbank_content', ['id' => $contentid]);
        $this->name = $this->content->name;
        $this->itemid = $this->content->itemid;
        $this->itemtype = $this->content->itemtype;
    }

    /**
     * Fills content_bank table with appropiate information.
     *
     * @param string $name  Name of the element to be created.
     * @return int          Id of the element created or false if the element has not been created.
     */
    public function create_content(string $name) {
        global $USER, $DB;

        $this->name = $name;

        $content = $this->content;
        $content->name = $name;
        $content->usercreated = $USER->id;
        $content->timecreated = time();
        $content->usermodified = $USER->id;
        $content->timemodified = $content->timecreated;

        $newid = $DB->insert_record('contentbank_content', $content);
        $this->content->id = $newid;

        return $newid;
    }

    /**
     * Updates content_bank table with information in $this->content.
     *
     * @return boolean  True if the content has been succesfully updated. False otherwise.
     */
    public function update_content(): bool {
        global $USER, $DB;

        $content = $this->content;
        if (empty($content->id)) {
            return false;
        }
        $content->usermodified = $USER->id;
        $content->timemodified = time();

        return $DB->update_record('contentbank_content', $content);

    }

    /**
     * Returns the name of the content.
     *
     * @return string   The name of the content.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Returns the $itemtype of this content.
     *
     * @return string   $this->itemtype
     */
    public function get_content_type(): string {
        return $this->$itemtype;
    }

    /**
     * Returns the $itemid of this content.
     *
     * @return int   $this->itemid
     */
    public function get_itemid(): int {
        return $this->$itemid;
    }

    /**
     * Returns the $file related to this content.
     *
     * @param int $itemid   Itemid value to get related file in files.
     * @return stored_file  File stored in content bank area related to the given itemid.
     */
    public function get_file(int $itemid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'contentbank', 'public', $itemid, 'itemid, filepath, filename', false);
        if (!empty($files)) {
            $file = reset($files);
            return $file;
        }

        return false;
    }

    /**
     * Returns the file url related to this content.
     *
     * @param int $itemid   Itemid value to get related file in files.
     * @return string       URL of the file stored in content bank area related to the given itemid.
     */
    public function get_file_url(int $itemid): string {
        if (!$file = $this->get_file($itemid)) {
            return '';
        }

        $fileurl = \moodle_url::make_pluginfile_url(
            $this->context->id,
            'contentbank',
            'public',
            $itemid,
            $file->get_filepath(),
            $file->get_filename()
        );

        return $fileurl;
    }

    /**
     * Return the content config values.
     *
     * @return string   Config information for this content
     */
    public function get_configdata(): string {
        return $this->content->configdata;
    }

    /**
     * Change the content config values.
     *
     * @param string $configdata    New config information for this content
     * @return boolean              True if the configdata has been succesfully updated. False otherwise.
     */
    public function set_configdata(string $configdata): bool {
        $this->content->configdata = $configdata;
        return $this->update_content();
    }

    /**
     * Return an array of extensions the plugin could manage.
     *
     * @return array
     */
    public function get_manageable_extensions(): array {
        // Plugins would manage extensions. Content bank does not manage any extension by itself.
        return array();
    }

    /**
     * Returns the content type enables uploading.
     *
     * @return bool     True if content could be uploaded. False otherwise.
     */
    public function can_upload(): bool {
        return has_capability('moodle/contentbank:upload', \context_system::instance());
    }

    /**
     * Returns the URL where the content will be visualized.
     *
     * @param int $contentid    Id of the content to be rendered.
     * @return string           URL where to visualize the given content.
     */
    public function get_view_url(int $contentid): string {
        return new \moodle_url('/contentbank/view.php', ['id' => $contentid]);
    }

    /**
     * Returns the HTML content to add to view.php visualizer.
     *
     * @param int $contentid    Id of the content to be rendered.
     * @return string           HTML code to include in view.php.
     */
    public function get_view_content(int $contentid): string {
        // Plugins would manage visualization. Content bank does visualize any content by itself.
        return '';
    }

    /**
     * Returns the HTML code to render the icon for content bank contents.
     *
     * @param int $contentid    Id of the content to be rendered.
     * @return string           HTML code to render the icon
     */
    public function get_icon(int $contentid): string {
        global $OUTPUT;

        return $OUTPUT->pix_icon('f/unknown-64', $this->get_name(), 'moodle', ['class' => 'iconsize-big']);
    }
}