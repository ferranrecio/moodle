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
 * External function for creating phases.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use mod_peerassign\manager;
use mod_peerassign\permissions;

require_once($CFG->libdir . '/externallib.php');

/**
 * External function class for creating phases in mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_phase extends external_api {

    /**
     * Execute parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'peerassignid' => new external_value(
                PARAM_INT,
                'The ID of the peerassign activity',
                VALUE_REQUIRED
            ),
            'phasetype' => new external_value(
                PARAM_INT,
                'Phase type: 0=sample, 1=validation, 2=submission, 3=peer_review, 4=teacher_eval',
                VALUE_REQUIRED
            ),
            'sequencenumber' => new external_value(
                PARAM_INT,
                'Phase sequence number (1-based)',
                VALUE_REQUIRED
            ),
            'title' => new external_value(
                PARAM_TEXT,
                'Phase display name',
                VALUE_REQUIRED
            ),
            'customdata' => new external_value(
                PARAM_RAW,
                'JSON-encoded phase-specific settings',
                VALUE_REQUIRED
            ),
            'description' => new external_value(
                PARAM_RAW,
                'Phase description',
                VALUE_DEFAULT,
                ''
            ),
            'required' => new external_value(
                PARAM_INT,
                'Whether phase is required',
                VALUE_DEFAULT,
                1
            ),
            'unlockmethod' => new external_value(
                PARAM_TEXT,
                'Unlock method: manual or date',
                VALUE_DEFAULT,
                'manual'
            ),
            'unlockdate' => new external_value(
                PARAM_INT,
                'Unlock date timestamp',
                VALUE_DEFAULT,
                0
            ),
            'allowfiles' => new external_value(
                PARAM_INT,
                'Whether file uploads are allowed',
                VALUE_DEFAULT,
                1
            ),
            'filetypes' => new external_value(
                PARAM_TEXT,
                'Comma-separated file extensions',
                VALUE_DEFAULT,
                ''
            ),
            'maxfilesize' => new external_value(
                PARAM_INT,
                'Maximum file size in bytes',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $peerassignid The ID of the peerassign activity
     * @param int $phasetype The phase type
     * @param int $sequencenumber The sequence number
     * @param string $title The phase title
     * @param string $customdata JSON-encoded custom data
     * @param string $description Phase description
     * @param int $required Whether the phase is required
     * @param string $unlockmethod The unlock method
     * @param int $unlockdate Timestamp to unlock the phase
     * @param int $allowfiles Whether files are allowed
     * @param string $filetypes Allowed file types
     * @param int $maxfilesize Maximum file size
     * @return array The response
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(
        $peerassignid,
        $phasetype,
        $sequencenumber,
        $title,
        $customdata,
        $description = '',
        $required = 1,
        $unlockmethod = 'manual',
        $unlockdate = 0,
        $allowfiles = 1,
        $filetypes = '',
        $maxfilesize = 0
    ) {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'peerassignid' => $peerassignid,
            'phasetype' => $phasetype,
            'sequencenumber' => $sequencenumber,
            'title' => $title,
            'customdata' => $customdata,
            'description' => $description,
            'required' => $required,
            'unlockmethod' => $unlockmethod,
            'unlockdate' => $unlockdate,
            'allowfiles' => $allowfiles,
            'filetypes' => $filetypes,
            'maxfilesize' => $maxfilesize,
        ]);

        // Get the activity.
        $peerassign = $DB->get_record(manager::MODULE, ['id' => $params['peerassignid']], '*', MUST_EXIST);

        // Get the course module and context.
        $cm = get_coursemodule_from_instance(manager::MODULE, $params['peerassignid']);
        $context = \context_module::instance($cm->id);

        // Check capability.
        self::validate_context($context);
        if (!permissions::can_add_phase($context)) {
            throw new \required_capability_exception(
                $context,
                'mod/peerassign:addphase',
                'nopermissions',
                ''
            );
        }

        // Validate unlockmethod.
        if (!in_array($params['unlockmethod'], ['manual', 'date'])) {
            throw new \invalid_parameter_exception('Invalid unlock method');
        }

        // If unlockmethod is 'date', unlockdate must be provided.
        if ($params['unlockmethod'] === 'date' && $params['unlockdate'] === 0) {
            throw new \invalid_parameter_exception('Unlock date is required when unlock method is date');
        }

        // Validate and normalize customdata.
        try {
            json_decode($params['customdata'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \invalid_parameter_exception('Invalid JSON in customdata: ' . $e->getMessage());
        }

        // Create the phase record.
        $phase = new \stdClass();
        $phase->peerassignid = $params['peerassignid'];
        $phase->phasetype = $params['phasetype'];
        $phase->sequencenumber = $params['sequencenumber'];
        $phase->title = $params['title'];
        $phase->description = !empty($params['description']) ? $params['description'] : null;
        $phase->required = $params['required'];
        $phase->unlockmethod = $params['unlockmethod'];
        $phase->unlockdate = $params['unlockdate'] > 0 ? $params['unlockdate'] : null;
        $phase->allowfiles = $params['allowfiles'];
        $phase->filetypes = !empty($params['filetypes']) ? $params['filetypes'] : null;
        $phase->maxfilesize = $params['maxfilesize'];
        $phase->extras = $params['customdata'];
        $phase->visible = 1;
        $phase->timecreated = time();
        $phase->timemodified = $phase->timecreated;

        $phaseid = $DB->insert_record('peerassign_phases', $phase);

        return [
            'status' => 'success',
            'phaseid' => $phaseid,
            'peerassignid' => $params['peerassignid'],
            'message' => 'Phase created successfully',
        ];
    }

    /**
     * Execute returns definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the operation'),
            'phaseid' => new external_value(PARAM_INT, 'The ID of the created phase'),
            'peerassignid' => new external_value(PARAM_INT, 'The ID of the peerassign activity'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
