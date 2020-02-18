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

use stored_file;
use stdClass;
use coding_exception;

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
    }

    /**
     * Load contents with the given id.
     *
     * @param int $contentid    Id of content to get.
     * @throws \coding_exception if already loaded or invalid ID provided.
     */
    public function load_content_by_id(int $contentid): void {
        global $DB;
        $content = $DB->get_record('contentbank_content', ['id' => $contentid], '*', MUST_EXIST);
        $this->load_content($content);
    }

    /**
     * Load contents from record.
     *
     * @param stdClass $contentid    a contentbanck_content record.
     * @throws \coding_exception if already loaded or invalid ID provided.
     */
    public function load_content (stdClass $content): void {
        global $DB;
        if (empty($this->content)) {
            $this->content = $content;
            $this->context = \context::instance_by_id($this->content->contextid);
        }
        if ($this->content->id != $content->id) {
            throw new coding_exception('Content already loaded with different ID.');
        }
    }

    /**
     * Check if content is loaded
     *
     * @throws \coding_exception if not loaded.
     */
    public function require_loaded (): void {
        if (empty($this->content)) {
            throw new coding_exception('Content is not loaded.');
        }
    }

    /**
     * Fills content_bank table with appropiate information.
     *
     * @param stdClass $content  an optional content record compatible object (default null)
     * @return int|null          Id of the element created or false if the element has not been created.
     */
    public function create_content(stdClass $content = null): ?int {
        global $USER, $DB;
        if (empty($this->content)) {
            $defaults = new stdClass();
        } else {
            $defaults = $this->content;
        }
        $record = new stdClass();
        $record->name = $content->name ?? $defaults->name ?? '';
        $record->itemtype = $content->itemtype ?? $defaults->itemtype ?? '';
        $record->visibility = $content->visibility ?? $defaults->visibility ?? 1;
        $record->contextid = $content->contextid ?? $this->context->id;
        $record->usercreated = $content->usercreated ?? $defaults->usercreated ?? $USER->id;
        $record->timecreated = time();
        $record->usermodified = $record->usercreated;
        $record->timemodified = $record->timecreated;
        $record->configdata = $content->configdata ?? $defaults->configdata ?? null;
        $record->id = $DB->insert_record('contentbank_content', $record);
        if (empty($record->id)) {
            return null;
        }
        $this->content = $record;
        $this->context = \context::instance_by_id($this->content->contextid);
        return $record->id;
    }

    /**
     * Updates content_bank table with information in $this->content.
     *
     * @return boolean  True if the content has been succesfully updated. False otherwise.
     * @throws \coding_exception if not loaded.
     */
    public function update_content(): bool {
        global $USER, $DB;
        $this->require_loaded();
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
     * @throws \coding_exception if not loaded.
     */
    public function get_name(): string {
        $this->require_loaded();
        return $this->content->name;
    }

    /**
     * Returns the content ID.
     *
     * @return int   The content ID.
     * @throws \coding_exception if not loaded.
     */
    public function get_id(): string {
        $this->require_loaded();
        return $this->content->id;
    }

    /**
     * Returns the visibility of the content.
     *
     * @return int   If the element is visible or not
     * @throws \coding_exception if not loaded.
     */
    public function get_visibility(): int {
        $this->require_loaded();
        return $this->content->visibility;
    }

    /**
     * Returns the $itemtype of this content.
     *
     * @return string   $this->itemtype
     * @throws \coding_exception if not loaded.
     */
    public function get_content_type(): string {
        $this->require_loaded();
        return $this->content->itemtype;
    }

    /**
     * Returns the $itemid of this content.
     *
     * @return int   contentbank itemid
     * @throws \coding_exception if not loaded.
     */
    public function get_itemid(): int {
        $this->require_loaded();
        return $this->content->itemid;
    }

    /**
     * Returns the $file related to this content.
     *
     * @return stored_file  File stored in content bank area related to the given itemid.
     * @throws \coding_exception if not loaded.
     */
    public function get_file(): ?stored_file {
        $this->require_loaded();
        $itemid = $this->content->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'contentbank', 'public', $itemid, 'itemid, filepath, filename', false);
        if (!empty($files)) {
            $file = reset($files);
            return $file;
        }
        return null;
    }

    /**
     * Returns the file url related to this content.
     *
     * @param int $itemid   Itemid value to get related file in files.
     * @return string       URL of the file stored in content bank area related to the given itemid.
     * @throws \coding_exception if not loaded.
     */
    public function get_file_url(): string {
        if (!$file = $this->get_file()) {
            return '';
        }
        $fileurl = \moodle_url::make_pluginfile_url(
            $this->context->id,
            'contentbank',
            'public',
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );

        return $fileurl;
    }

    /**
     * Return the content config values.
     *
     * @return mixed   Config information for this content (json decoded)
     * @throws \coding_exception if not loaded.
     */
    public function get_configdata() {
        $this->require_loaded();
        return $this->content->configdata;
    }

    /**
     * Change the content config values.
     *
     * @param string $configdata    New config information for this content
     * @return boolean              True if the configdata has been succesfully updated. False otherwise.
     * @throws \coding_exception if not loaded.
     */
    public function set_configdata(string $configdata): bool {
        $this->require_loaded();
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
     * @param context   Optional context to check (default null)
     * @return bool     True if content could be uploaded. False otherwise.
     */
    public function can_upload(\context $context = null): bool {
        $context = $context ?? $this->context;
        return has_capability('moodle/contentbank:upload', $context);
    }

    /**
     * Returns the URL where the content will be visualized.
     *
     * @return string            URL where to visualize the given content.
     * @throws \coding_exception if not loaded.
     */
    public function get_view_url(): string {
        $this->require_loaded();
        return new \moodle_url('/contentbank/view.php', ['id' => $this->content->id]);
    }

    /**
     * Returns the HTML content to add to view.php visualizer.
     *
     * @param int $contentid     Id of the content to be rendered (default this->content->id).
     * @return string            HTML code to include in view.php.
     * @throws \coding_exception if not loaded.
     */
    public function get_view_content(): string {
        // Plugins would manage visualization. Content bank does visualize any content by itself.
        $this->require_loaded();
        return '';
    }

    /**
     * Returns the HTML code to render the icon for content bank contents.
     *
     * @return string           HTML code to render the icon
     * @throws \coding_exception if not loaded.
     */
    public function get_icon(): string {
        global $OUTPUT;
        $this->require_loaded();
        return $OUTPUT->pix_icon('f/unknown-64', $this->get_name(), 'moodle', ['class' => 'iconsize-big']);
    }
}