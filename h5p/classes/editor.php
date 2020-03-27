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
 * H5P editor class.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

use core_h5p\local\library\autoloader;
use core_h5p\output\h5peditor as editor_renderer;
use H5PCore;
use H5peditor;
use stdClass;
use coding_exception;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

/**
 * H5P editor class, for editing local H5P content.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor {

    /** @var string The H5P Editor form id. */
    public const FORMID   = 'h5peditor-form';

    /**
     * @var core The H5PCore object.
     */
    private $core;

    /**
     * @var H5peditor $h5peditor The H5P Editor object.
     */
    private $h5peditor;

    /**
     * @var int ID from h5p table.
     */
    private $id = null;

    /**
     * @var array loaded content from current H5P entry.
     */
    private $oldcontent = null;

    /**
     * @var stored_file current H5P file.
     */
    private $oldfile = null;

    /**
     * @var array file area information to save the resulting file.
     */
    private $filearea = null;

    /**
     * @var string H5P Library name
     */
    private $library = null;



    /**
     * Inits the H5P editor.
     */
    public function __construct() {
        autoloader::register();

        $factory = new factory();
        $this->h5peditor = $factory->get_editor();
        $this->core = $factory->get_core();
    }

    /**
     * Point the editor to a specific H5P content from H5P table
     *
     * @param int $id ID from H5P table
     */
    public function set_id(int $id): void {
        $this->id = $id;
        // Load content.
        $this->oldcontent = $this->core->loadContent($id);
        if ($this->oldcontent === null) {
            print_error('invalidelementid');
        }
        // Load library.
        $this->library = H5PCore::libraryToString($this->oldcontent['library']);
        // Load file area.
        $pathnamehash = $this->oldcontent['pathnamehash'];
        $fs = get_file_storage();
        $oldfile = $fs->get_file_by_hash($pathnamehash);
        if (!$oldfile) {
            print_error('invalidelementid');
        }
        $this->set_filearea(
            $oldfile->get_contextid(),
            $oldfile->get_component(),
            $oldfile->get_filearea(),
            $oldfile->get_itemid(),
            $oldfile->get_filepath(),
            $oldfile->get_filename(),
            $oldfile->get_userid()
        );
        $this->oldfile = $oldfile;
    }

    /**
     * Set the editor to a specific library and a filearea to create the H5P file.
     *
     * Note: this method must be used to create new content, to edit an existing
     * H5P content use only set_id with the ID from the H5P table.
     *
     * @param string $library H5P content library to use
     * @param int $contextid This parameter and the next two identify the file area to copy files from.
     * @param string $component the component name
     * @param string $filearea the filearea name
     * @param int $itemid the file itemid
     * @param string $filepath the file path (default "/")
     * @param string $filename the file name (default will invent it on creation)
     * @param int $userid the file owner userid (default will use $USER->id)
     */
    public function set_library(string $library, int $contextid, string $component, string $filearea,
            int $itemid, string $filepath = '/', string $filename = null, int $userid = null): void {

        $this->library = $library;
        $this->set_filearea($contextid, $component, $filearea, $itemid, $filepath, $filename, $userid);
    }

    /**
     * Set the upload file area for this editor.
     *
     * Note: this set is only needed when saving new content.
     *
     * @param int $contextid This parameter and the next two identify the file area to copy files from.
     * @param string $component the component name
     * @param string $filearea the filearea name
     * @param int $itemid the file itemid
     * @param string $filepath the file path (default "/")
     * @param string $filename the file name (default will invent it on creation)
     * @param int $userid the file owner userid (default will use $USER->id)
     */
    private function set_filearea(int $contextid, string $component, string $filearea,
            int $itemid, string $filepath = '/', string $filename = null, int $userid = null): void {
        global $USER;

        $this->filearea = [
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename,
            'userid' => $userid ?? $USER->id,
        ];

    }

    /**
     * Adds an editor to a form.
     *
     * @param MoodleQuickForm $mform Moodle Quick Form
     */
    public function add_editor_to_form(MoodleQuickForm $mform): void {
        global $PAGE;

        $this->add_assets_to_page();

        $data = $this->data_preprocessing();

        // Hidden fields used bu H5P editor.
        $mform->addElement('hidden', 'h5plibrary', $data->h5plibrary);
        $mform->setType('h5plibrary', PARAM_RAW);

        $mform->addElement('hidden', 'h5pparams', $data->h5pparams);
        $mform->setType('h5pparams', PARAM_RAW);

        $mform->addElement('hidden', 'h5paction');
        $mform->setType('h5paction', PARAM_ALPHANUMEXT);

        // Render H5P editor.
        $ui = new editor_renderer($data);
        $editorhtml = $PAGE->get_renderer('core_h5p')->render($ui);
        $mform->addElement('html', $editorhtml);
    }

    /**
     * Creates or updates an H5P content.
     *
     * @param stdClass $content Object containing all the necessary data.
     *
     * @return int Content id
     */
    public function save_content(stdClass $content): int {

        if (empty($content->h5pparams)) {
            throw new coding_exception('Missing H5P params.');
        }
        if (!isset($content->h5plibrary)) {
            throw new coding_exception('Missing H5P library.');
        }
        if ($content->h5plibrary != $this->library) {
            throw new coding_exception("Wrong H5P library.");
        }

        $content->params = $content->h5pparams;

        if (!empty($this->oldcontent)) {
            $content->id = $this->oldcontent['id'];
            // Load existing content to get old parameters for comparison.
            $oldparams = json_decode($this->oldcontent['params']) ?? null;
            // Keep the existing display options.
            $content->disable = $this->oldcontent['disable'];
            $oldlib = $this->oldcontent['library'];
        } else {
            $oldcontent = [];
            $oldparams = null;
            $oldlib = null;
        }

        // Make params and library available for core to save.
        $content->library = H5PCore::libraryFromString($content->h5plibrary);
        $content->library['libraryId'] = $this->core->h5pF->getLibraryId(
            $content->library['machineName'],
            $content->library['majorVersion'],
            $content->library['minorVersion']
        );

        // Prepare current parameters.
        $params = json_decode($content->params);
        $modified = false;
        if (empty($params->metadata)) {
            $params->metadata = new stdClass();
            $modified = true;
        }
        if (empty($params->metadata->title)) {
            // Use a default string if not available.
            $params->metadata->title = 'Untitled';
            $modified = true;
        }
        if (!isset($content->title)) {
            $content->title = $params->metadata->title;
        }
        if ($modified) {
            $content->params = json_encode($params);
        }

        // Save contents.
        $content->id = $this->core->saveContent((array)$content);

        // Move any uploaded images or files. Determine content dependencies.
        $this->h5peditor->processParameters($content, $content->library, $params->params, $oldlib, $oldparams);

        $this->update_h5p_file($content);

        return $content->id;
    }

    /**
     * Creates or updates the H5P file and the related database data.
     *
     * @param stdClass $content
     *
     * @return void
     */
    private function update_h5p_file(stdClass $content): void {

        // Keep title before filtering params.
        $title = $content->title;
        $contentarray = $this->core->loadContent($content->id);
        $contentarray['title'] = $title;

        // Generates filtered params and export file.
        $this->core->filterParameters($contentarray);

        $slug = isset($contentarray['slug']) ? $contentarray['slug'] . '-' : '';
        $filename = $contentarray['id'] ?? $contentarray['title'];
        $filename = $slug . $filename . '.h5p';
        $file = $this->core->fs->get_export_file($filename);
        $fs = get_file_storage();
        if ($file) {
            $fields['contenthash'] = $file->get_contenthash();

            // Delete old file if any.
            if (!empty($this->oldfile)) {
                $this->oldfile->delete();
            }
            // Create new file.
            if (empty($this->filearea['filename'])) {
                $this->filearea['filename'] = $contentarray['slug'] . '.h5p';
            }
            $newfile = $fs->create_file_from_storedfile($this->filearea, $file);
            if (empty($this->oldcontent)) {
                $pathnamehash = $newfile->get_pathnamehash();
            } else {
                $pathnamehash = $this->oldcontent['pathnamehash'];
            }
            // Update hash fields in the h5p table.
            $fields['pathnamehash'] = $pathnamehash;
            $this->core->h5pF->updateContentFields($contentarray['id'], $fields);
        }
    }

    /**
     * Add required assets for displaying the editor.
     *
     * @throws coding_exception if page header is already printed.º
     */
    private function add_assets_to_page(): void {
        global $PAGE, $CFG;

        if ($PAGE->headerprinted) {
            throw new coding_exception('H5P assets cannot be added when header is already printed.');
        }

        $context = \context_system::instance();

        $settings = helper::get_core_assets();

        // Use jQuery and styles from core.
        $assets = [
            'css' => $settings['core']['styles'],
            'js' => $settings['core']['scripts']
        ];

        // Use relative URL to support both http and https.
        $url = autoloader::get_h5p_editor_library_url()->out();
        $url = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $url);

        // Make sure files are reloaded for each plugin update.
        $cachebuster = helper::get_cache_buster();

        // Add editor styles.
        foreach (H5peditor::$styles as $style) {
            $assets['css'][] = $url . $style . $cachebuster;
        }

        // Add editor JavaScript.
        foreach (H5peditor::$scripts as $script) {
            // We do not want the creator of the iframe inside the iframe.
            if ($script !== 'scripts/h5peditor-editor.js') {
                $assets['js'][] = $url . $script . $cachebuster;
            }
        }

        // Add JavaScript with library framework integration (editor part).
        $PAGE->requires->js(autoloader::get_h5p_editor_library_url('scripts/h5peditor-editor.js' . $cachebuster), true);
        $PAGE->requires->js(autoloader::get_h5p_editor_library_url('scripts/h5peditor-init.js' . $cachebuster), true);

        // Add translations.
        $language = framework::get_language();
        $languagescript = "language/{$language}.js";

        if (!file_exists("{$CFG->dirroot}" . autoloader::get_h5p_editor_library_base($languagescript))) {
            $languagescript = 'language/en.js';
        }
        $PAGE->requires->js(autoloader::get_h5p_editor_library_url($languagescript . $cachebuster),
            true);

        // Add JavaScript settings.
        $root = $CFG->wwwroot;
        $filespathbase = "{$root}/pluginfile.php/{$context->id}/core_h5p/";

        $factory = new factory();
        $contentvalidator = $factory->get_content_validator();

        $editorajaxtoken = H5PCore::createToken(editor_ajax::EDITOR_AJAX_TOKEN);
        $settings['editor'] = [
            'filesPath' => $filespathbase . 'editor',
            'fileIcon' => [
                'path' => $url . 'images/binary-file.png',
                'width' => 50,
                'height' => 50,
            ],
            'ajaxPath' => $CFG->wwwroot . '/h5p/' . "ajax.php?contextId={$context->id}&token={$editorajaxtoken}&action=",
            'libraryUrl' => $url,
            'copyrightSemantics' => $contentvalidator->getCopyrightSemantics(),
            'metadataSemantics' => $contentvalidator->getMetadataSemantics(),
            'assets' => $assets,
            'apiVersion' => H5PCore::$coreApi,
            'language' => $language,
        ];

        if (!empty($this->id)) {
            $settings['editor']['nodeVersionId'] = $this->id;

            // Override content URL.
            $contenturl = "{$root}/pluginfile.php/{$context->id}/core_h5p/content/{$this->id}";
            $settings['contents']['cid-' . $this->id]['contentUrl'] = $contenturl;
        }

        $PAGE->requires->data_for_js('H5PIntegration', $settings, true);
    }

    /**
     * Preprocess the data sent through the form to the H5P JS Editor Library.
     *
     * @return array of editor parameters needed for the editor presentation
     */
    private function data_preprocessing(): stdClass {
        // If there is a content id, it's an update: load the content data.
        $defaultvalues = [
            'id' => $this->id,
            'h5plibrary' => $this->library,
        ];

        // In case both contentid and library have values, content(edition) takes precedence over library(creation).
        if (empty($this->oldcontent)) {
            $maincontentdata = ['params' => (object)[]];
        } else {
            $params = $this->core->filterParameters($this->oldcontent);
            $maincontentdata = ['params' => json_decode($params)];
            if (isset($this->oldcontent['metadata'])) {
                $maincontentdata['metadata'] = $this->oldcontent['metadata'];
            }
        }

        // Combine params and metadata in one JSON object.
        // H5P JS Editor library expects a JSON object with the parameters and the metadata.
        $defaultvalues['h5pparams'] = json_encode($maincontentdata, true);

        return (object) $defaultvalues;
    }
}
