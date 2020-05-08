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
 * This is the external method for getting the information needed to present an attempts report.
 *
 * @package    mod_h5pactivity
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_h5pactivity\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use mod_h5pactivity\local\manager;
use mod_h5pactivity\local\attempt;
use mod_h5pactivity\local\report\attempts as report_attempts;
use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use external_warnings;
use moodle_exception;
use context_module;
use stdClass;

/**
 * This is the external method for getting the information needed to present an attempts report.
 *
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_h5pactivity_report_attempts extends external_api {

    /**
     * Webservice parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'h5pactivityid' => new external_value(PARAM_INT, 'h5p activity instance id'),
                'userid' => new external_value(PARAM_INT, 'The user ID', VALUE_DEFAULT),
            ]
        );
    }

    /**
     * Return access information for a given h5p activity.
     *
     * @throws  moodle_exception if the user cannot see the report
     * @param  int $h5pactivityid The h5p activity id
     * @param  int|null $userid The user id (if no provided $USER will be used)
     * @return stdClass report data
     */
    public static function execute(int $h5pactivityid, ?int $userid = null): stdClass {
        global $DB, $USER;

        $params = external_api::validate_parameters(self::execute_parameters(), [
            'h5pactivityid' => $h5pactivityid,
            'userid' => $userid,
        ]);
        $h5pactivityid = $params['h5pactivityid'];
        $userid = $params['userid'];

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Request and permission validation.
        list ($course, $cm) = get_course_and_cm_from_instance($h5pactivityid, 'h5pactivity');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $manager = manager::create_from_coursemodule($cm);

        $report = $manager->get_report($userid);
        if (!$report || !$report instanceof report_attempts) {
            throw new moodle_exception("User {$USER->id} cannot access the attempts report on H5P activity $h5pactivityid");
        }

        $scored = $report->get_scored();
        $attempts = $report->get_attempts();

        $result = (object)[
            'attempts' => [],
        ];

        foreach ($attempts as $attempt) {
            $result->attempts[] = self::export_attempt($attempt);
        }

        if (!empty($scored)) {
            $result->scored = (object)[
                'title' => $scored->title,
                'grademethod' => $scored->grademethod,
                'attempts' => [self::export_attempt($scored->attempt)],
            ];
        }

        return $result;
    }

    /**
     * Describes the get_h5pactivity_access_information return value.
     *
     * @return external_single_structure
     * @since Moodle 3.9
     */
    public static function execute_returns() {
        $structure = [
            'attempts' => new external_multiple_structure(self::get_attempt_returns()),
            'scored' => new external_single_structure([
                'title'    => new external_value(PARAM_NOTAGS, 'Scored attempts title'),
                'grademethod'    => new external_value(PARAM_NOTAGS, 'Scored attempts title'),
                'attempts' => new external_multiple_structure(self::get_attempt_returns()),
            ], 'Attempts used to grade the activity', VALUE_OPTIONAL),
        ];
        return new external_single_structure($structure);
    }

    /**
     * Return the external structure of an attempt
     * @return type
     */
    private static function get_attempt_returns() {

        $result = new external_single_structure([
            'id'    => new external_value(PARAM_INT, 'ID of the context'),
            'h5pactivityid' => new external_value(PARAM_INT, 'ID of the H5P activity'),
            'userid' => new external_value(PARAM_INT, 'ID of the user'),
            'timecreated' => new external_value(PARAM_INT, 'Attempt creation'),
            'timemodified' => new external_value(PARAM_INT, 'Attempt modified'),
            'attempt' => new external_value(PARAM_INT, 'Attempt number'),
            'rawscore' => new external_value(PARAM_INT, 'Attempt score value'),
            'maxscore' => new external_value(PARAM_INT, 'Attempt max score'),
            'duration' => new external_value(PARAM_INT, 'Attempt duration in seconds'),
            'completion' => new external_value(PARAM_INT, 'Attempt completion', VALUE_OPTIONAL),
            'success' => new external_value(PARAM_INT, 'Attempt success', VALUE_OPTIONAL),
            'scaled' => new external_value(PARAM_FLOAT, 'Attempt scaled'),
        ]);
        return $result;
    }

    /**
     * Return a data object from an attempt.
     *
     * @param attempt $attempt the attempt object
     * @return stdClass a WS compatible version of the attempt
     */
    private static function export_attempt(attempt $attempt): stdClass {
        $result =  (object)[
            'id' => $attempt->id,
            'h5pactivityid' => $attempt->h5pactivityid,
            'userid' => $attempt->userid,
            'timecreated' => $attempt->timecreated,
            'timemodified' => $attempt->timemodified,
            'attempt' => $attempt->attempt,
            'rawscore' => $attempt->rawscore,
            'maxscore' => $attempt->maxscore,
            'duration' => $attempt->duration,
            // 'completion' => $attempt->completion,
            // 'success' => $attempt->success,
            'scaled' => $attempt->scaled,
        ];
        if (isset($attempt->completion) && $attempt->completion !== null) {
            $result->completion = $attempt->completion;
        }
        if (isset($attempt->success) && $attempt->success !== null) {
            $result->success = $attempt->success;
        }
        return $result;
    }
}