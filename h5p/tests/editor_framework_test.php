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
 * Testing the H5peditorStorage interface implementation.
 *
 * @package    core_h5p
 * @category   test
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

use core_h5p\local\library\autoloader;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Test class covering the H5peditorStorage interface implementation.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
class editor_framework_testcase extends \advanced_testcase {

    /** @var editorframework H5P editor framework instance */
    protected $editorframework;

    /**
     * Set up function for tests.
     */
    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);

        autoloader::register();

        $this->editorframework = new editor_framework();
    }

    /**
     * Test that the method saveFileTemporarily saves a file in a temporary location.
     */
    public function test_save_file_temporarily() {
        // Create temp folder.
        $tempfolder = make_request_directory(false);

        // Create H5P content folder.
        $filename = 'fake.png';

        $file = $tempfolder . '/' . $filename;
        touch($file);

        $this->assertFileExists($file);
        // Save file in a temporary location.
        $tempfile = editor_framework::saveFileTemporarily($file);
        $this->assertIsObject($tempfile);
        $this->assertFileExists($file);
        $this->assertFileExists($tempfile->dir . '/' . $tempfile->fileName);
    }

    /**
     * Test that the method getLibraries get the specified libraries or all the content types (runnable = 1).
     */
    public function test_get_libraries() {
        $generator = \testing_util::get_data_generator();
        $h5pgenerator = $generator->get_plugin_generator('core_h5p');

        // Generate some h5p related data.
        $data = $h5pgenerator->generate_h5p_data();

        $expectedlibraries = [];
        foreach ($data as $key => $value) {
            if (isset($value->data)) {
                $value->data->name = $value->data->machinename;
                $value->data->majorVersion = $value->data->majorversion;
                $value->data->minorVersion = $value->data->minorversion;
                $expectedlibraries[$value->data->title] = $value->data;
            }
        }
        ksort($expectedlibraries);

        // Get all libraries.
        $libraries = $this->editorframework->getLibraries();
        foreach ($libraries as $library) {
            $actuallibraries[] = $library->title;
        }
        sort($actuallibraries);

        $this->assertEquals(array_keys($expectedlibraries), $actuallibraries);

        // Get a subset of libraries.
        $librariessubset = array_slice($expectedlibraries, 0, 4);

        $actuallibraries = [];
        $libraries = $this->editorframework->getLibraries($librariessubset);
        foreach ($libraries as $library) {
            $actuallibraries[] = $library->title;
        }

        $this->assertEquals(array_keys($librariessubset), $actuallibraries);
    }

    /**
     * Test that the method removeTemporarilySavedFiles deletes files in a temporary location.
     */
    public function test_remove_temporarily_saved_files() {
        // Create temp folder.
        $tempfolder = make_request_directory(false);

        // Create H5P folder.
        $h5pfolder = $tempfolder . '/folder';
        if (!check_dir_exists($h5pfolder, true)) {
            throw new moodle_exception('error_creating_temp_dir', 'error', $h5pfolder);
        }

        // Create several subfolders and files inside folder.
        $numfolders = random_int(2, 5);
        for ($numfolder = 1; $numfolder < $numfolders; $numfolder++) {
            $foldername = '/folder' . $numfolder;
            $newfolder = $h5pfolder . $foldername;
            if (!check_dir_exists($newfolder, true, true)) {
                throw new moodle_exception('error_creating_temp_dir', 'error', $newfolder);
            }
            $numfiles = random_int(2, 5);
            for ($numfile = 1; $numfile < $numfiles; $numfile++) {
                $filename = '/file' . $numfile . '.ext';
                touch($newfolder . $filename);
            }
        }

        $this->assertDirectoryExists($h5pfolder);

        $this->editorframework::removeTemporarilySavedFiles($h5pfolder);

        $this->assertDirectoryNotExists($h5pfolder);
    }
}
