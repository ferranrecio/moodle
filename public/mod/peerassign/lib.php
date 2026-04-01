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
 * Library file for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_peerassign\manager;
use mod_peerassign\local\models\peerassign as peerassign_model;
use mod_peerassign\permissions;

/**
 * Add a new Peer Review Assignment instance.
 *
 * @param stdClass $data The data from the form submission
 * @param moodleform|null $mform The form object (optional)
 * @return int The ID of the newly created instance
 * @throws moodle_exception
 */
function peerassign_add_instance($data, $mform = null) {
    $instance = new peerassign_model(0, $data);
    $instance->create();
    $id = (int)$instance->get('id');

    try {
        // Create the initial Sample & Description phase.
        manager::create_initial_sample_phase($id);
    } catch (Exception $e) {
        // If phase creation fails, delete the created activity record and re-throw the exception.
        $instance->delete();
        throw $e;
    }

    return $id;
}

/**
 * Update an existing Peer Review Assignment instance.
 *
 * @param stdClass $data The data from the form submission
 * @param moodleform|null $mform The form object (optional)
 * @return bool True on success
 * @throws moodle_exception
 */
function peerassign_update_instance($data, $mform = null) {
    $instance = new peerassign_model((int)$data->instance);
    $instance->from_record((object)array_merge(
        (array)$instance->to_record(),
        (array)$data,
        ['id' => (int)$data->instance]
    ));
    $instance->update();

    return true;
}

/**
 * Delete a Peer Review Assignment instance.
 *
 * @param int $id The ID of the instance to delete
 * @return bool True on success
 * @throws moodle_exception
 */
function peerassign_delete_instance($id) {
    $instance = peerassign_model::get_record(['id' => (int)$id]);
    if (!$instance) {
        return false;
    }

    try {
        manager::delete_activity_cascade($id);
        $instance->delete();
    } catch (Exception $e) {
        // Log the error but allow deletion to proceed.
        debugging('Error during cascade deletion: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }

    return true;
}

/**
 * Check if the module supports a specific feature.
 *
 * @param string $feature The feature to check
 * @return mixed The support level for the feature
 */
function peerassign_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_ADVANCED_GRADING => false,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => false,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_OTHER,
        default => null,
    };
}

/**
 * Get file areas for the plugin.
 *
 * @param stdClass $course The course
 * @param stdClass $cm The course module
 * @param stdClass $context The context
 * @return array|null Array of file areas, or null if none
 */
function peerassign_get_file_areas($course, $cm, $context) {
    $areas = [
        'submission' => get_string('submissions', manager::PLUGINNAME),
        'reviewattachment' => get_string('reviewattachments', manager::PLUGINNAME),
        'sampledescription' => get_string('sampledescription', manager::PLUGINNAME),
    ];

    return $areas;
}

/**
 * Serve files from the plugin.
 *
 * @param stdClass $course The course
 * @param stdClass $cm The course module
 * @param stdClass $context The context
 * @param string $filearea The file area
 * @param array $args Additional arguments
 * @param bool $forcedownload Force download or display inline
 * @param array $options Additional options
 * @return bool False if file not found
 */
function peerassign_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Verify user has access to the file area.
    if (!permissions::can_view_files($cm, $context, $filearea)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, manager::PLUGINNAME, $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}
