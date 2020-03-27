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
 * Class \core_h5p\editor_framework
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>, base on code by Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

use H5peditorStorage;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle's implementation of the H5P Editor storage interface.
 *
 * Makes it possible for the editor's core library to communicate with the
 * database used by Moodle.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>, base on code by Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor_framework implements H5peditorStorage {

    /**
     * Load language file(JSON).
     * Used to translate the editor fields(title, description etc.)
     *
     * @param string $name The machine readable name of the library(content type)
     * @param int $major Major part of version number
     * @param int $minor Minor part of version number
     * @param string $lang Language code
     *
     * @return string|boolean Translation in JSON format if available, false otherwise
     */
    public function getLanguage($name, $major, $minor, $lang) {
        // To be implemented when translations are introduced.
        return false;
    }

    /**
     * Load a list of available language codes.
     *
     * Until translations is implemented, only returns the "en" language.
     *
     * @param string $machinename The machine readable name of the library(content type)
     * @param int $major Major part of version number
     * @param int $minor Minor part of version number
     *
     * @return array List of possible language codes
     */
    public function getAvailableLanguages($machinename, $major, $minor) {
        $defaultcode = 'en';
        $codes = [];

        // Semantics is 'en' by default.
        array_unshift($codes, $defaultcode);

        return $codes;
    }

    /**
     * "Callback" for mark the given file as a permanent file.
     *
     * Used when saving content that has new uploaded files.
     *
     * @param int $fileid
     */
    public function keepFile($fileid) {
        // Temporal files will be removed on a task when they are in the "editor" file area and and are at least one day older.
    }

    /**
     * Decides which content types the editor should have.
     *
     * Two usecases:
     * 1. No input, will list all the available content types.
     * 2. Libraries supported are specified, load additional data and verify
     * that the content types are available. Used by e.g. the Presentation Tool
     * Editor that already knows which content types are supported in its
     * slides.
     *
     * @param array $libraries List of library names + version to load info for
     * @return array List of all libraries loaded
     */
    public function getLibraries($libraries = null) {
        global $DB;

        if ($libraries !== null) {
            // Get details for the specified libraries only.
            $librarieswithdetails = [];
            foreach ($libraries as $library) {
                $sql = 'SELECT title, runnable
                          FROM {h5p_libraries}
                         WHERE machinename = ?
                           AND majorversion = ?
                           AND minorversion = ?
                           AND semantics IS NOT NULL';
                $params = [$library->name, $library->majorVersion, $library->minorVersion];
                // Look for library.
                $details = $DB->get_record_sql($sql, $params);

                if ($details) {
                    $library->title = $details->title;
                    $library->runnable = $details->runnable;
                    $librarieswithdetails[] = $library;
                }
            }

            // Done, return list with library details.
            return $librarieswithdetails;
        }

        // Load all libraries.
        $libraries = [];
        $librariesresult = $DB->get_records_sql(
            "SELECT id,
                    machinename AS name,
                    title,
                    majorversion,
                    minorversion
               FROM {h5p_libraries}
              WHERE runnable = 1
                AND semantics IS NOT NULL
           ORDER BY title, majorversion DESC, minorversion DESC"
        );

        $added = [];
        foreach ($librariesresult as $library) {
            // Remove unique index.
            unset($library->id);

            // Convert snakes to camels.
            $library->majorVersion = (int) $library->majorversion;
            unset($library->major_version);
            $library->minorVersion = (int) $library->minorversion;
            unset($library->minorversion);

            // If we already add this library means that it is an old version.
            if (isset($added[$library->name])) {
                $library->isOld = true;
            } else {
                $added[$library->name] = true;
            }

            // Add new library.
            $libraries[] = $library;
        }
        return $libraries;
    }

    /**
     * Allow for other plugins to decide which styles and scripts are attached.
     *
     * This is useful for adding and/or modifing the functionality and look of
     * the content types.
     *
     * @param array $files
     *  List of files as objects with path and version as properties
     * @param array $libraries
     *  List of libraries indexed by machineName with objects as values. The objects
     *  have majorVersion and minorVersion as properties.
     */
    public function alterLibraryFiles(&$files, $libraries) {
        // This is to be implemented when the renderer is used.
    }

    /**
     * Saves a file or moves it temporarily.
     *
     * This is often necessary in order to validate and store uploaded or fetched H5Ps.
     *
     * @param string $data Uri of data that should be saved as a temporary file
     * @param boolean $movefile Can be set to TRUE to move the data instead of saving it
     *
     * @return bool|object Returns false if saving failed or an object with path
     * of the directory and file that is temporarily saved
     */
    public static function saveFileTemporarily($data, $movefile = false) {
        global $CFG;

        // Generate local tmp file path.
        $uniqueh5pid = uniqid('h5p-');
        $filename = $uniqueh5pid . '.h5p';
        $directory = $CFG->tempdir . '/' . $uniqueh5pid;
        $filepath = $directory . '/' . $filename;

        check_dir_exists($directory);

        // Move file or save data to new file so core can validate H5P.
        if ($movefile) {
            $result = move_uploaded_file($data, $filepath);
        } else {
            $result = file_put_contents($filepath, $data);
        }

        if ($result) {
            // Add folder and file paths to H5P Core.
            $h5pfactory = new factory();
            $framework = $h5pfactory->get_framework();
            $framework->getUploadedH5pFolderPath($directory);
            $framework->getUploadedH5pPath($directory . '/' . $filename);
            $result = new \stdClass();
            $result->dir = $directory;
            $result->fileName = $filename;
        }

        return $result;
    }

    /**
     * Marks a file for later cleanup.
     *
     * Useful when files are not instantly cleaned up. E.g. for files that are uploaded through the editor.
     *
     * @param int $file Id of file that should be cleaned up
     * @param int|null $contentid Content id of file
     */
    public static function markFileForCleanup($file, $contentid = null) {
        // Temporal files will be removed on a task when they are in the "editor" file area and and are at least one day older.
    }

    /**
     * Clean up temporary files
     *
     * @param string $filepath Path to file or directory
     */
    public static function removeTemporarilySavedFiles($filepath) {
        if (is_dir($filepath)) {
            \H5PCore::deleteFileTree($filepath);
        } else {
            @unlink($filepath);
        }
    }
}
