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
 * H5P activity attempt object
 *
 * @package    mod_h5pactivity
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_h5pactivity;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class attempt for H5P activity
 *
 * @package mod_h5pactivity
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 */
class attempt {

    /**
     * @var stdClass the h5pactivity_attempts record
     */
    private $record;

    /**
     * Create a new attempt object
     *
     * @param stdClass $record the h5pactivity_attempts record
     */
    public function __construct(stdClass $record) {
        $this->record = $record;
        $this->results = null;
    }

    /**
     * Create a new user attempt in a specific H5P activity
     *
     * @param stdClass $user a user record
     * @param stdClass $cm a course_module record
     * @return attempt|null a new attempt object or null if fail
     */
    public static function new_attempt(stdClass $user, stdClass $cm): ?attempt {
        global $DB;
        $record = new stdClass();
        $record->h5pacivityid = $cm->instance;
        $record->userid = $user->id;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->rawscore = 0;
        $record->maxscore = 0;

        // Get last attempt number.
        $conditions = ['h5pacivityid' => $cm->instance, 'userid' => $user->id];
        $countattempts = $DB->count_records('h5pactivity_attempts', $conditions);
        $record->attempt = $countattempts + 1;

        $record->id = $DB->insert_record('h5pactivity_attempts', $record);
        if (!$record->id) {
            return null;
        }
        return new attempt($record);
    }

    /**
     * Get the last user attempt in a specific H5P activity
     *
     * If no previous attempt exists, it generates a new one.
     *
     * @param stdClass $user a user record
     * @param stdClass $cm a course_module record
     * @return attempt|null a new attempt object or null if some problem accured
     */
    public static function last_attempt(stdClass $user, stdClass $cm): ?attempt {
        global $DB;
        $conditions = ['h5pacivityid' => $cm->instance, 'userid' => $user->id];
        $records = $DB->get_records('h5pactivity_attempts', $conditions, 'attempt DESC', '*', 0, 1);
        if (empty($records)) {
            return self::new_attempt($user, $cm);
        }
        return new attempt(array_shift($records));
    }

    /**
     * Wipe all attempt data for specific course_module and an optional user.
     *
     * @param stdClass $cm a course_module record
     * @param stdClass $user a user record
     */
    public static function delete_all_attempts(stdClass $cm, stdClass $user = null): void {
        global $DB;
        $conditions = ['h5pacivityid' => $cm->instance];
        if (!empty($user)) {
            $conditions['userid'] = $user->id;
        }
        $records = $DB->get_records('h5pactivity_attempts', $conditions);
        if (!empty($records)) {
            foreach ($records as $record) {
                $attempt = new attempt($record);
                $attempt->delete_results();
            }
        }
        $DB->delete_records('h5pactivity_attempts', $conditions);
    }

    /**
     * Save a new result statement into the attempt.
     *
     * It also updates the rawscore and maxscore if necessary.
     *
     * @param stdClass $statement the xAPI statement object
     * @param string $subcontent = '' optional subcontent identifier
     * @return type
     */
    public function save_statement (stdClass $statement, string $subcontent = ''): bool {
        global $DB;
        if (!isset($statement->object->definition) || !isset($statement->result)) {
            return false;
        }
        $definition = $statement->object->definition;
        $result = $statement->result;
        $context = $statement->context ?? new stdClass();

        $record = new stdClass();
        $record->attemptid = $this->record->id;
        $record->subcontent = $subcontent;
        $record->timecreated = time();
        $record->interactiontype = $definition->interactionType ?? 'other';
        $record->description = $this->get_description_from_definition($definition);
        $record->correctpattern = $definition->correctResponsesPattern ?? '';
        $record->response = $result->response ?? '';
        $record->additionals = $this->get_additionals($definition, $context);
        $record->rawscore = 0;
        $record->maxscore = 0;
        if (isset($result->score)) {
            $record->rawscore = $result->score->raw ?? 0;
            $record->maxscore = $result->score->max ?? 0;
        }
        print_object($record);
        if (!$DB->insert_record('h5pactivity_attempts_results', $record)) {
            return false;
        }

        // If no subcontent provided, results are propagated to the attempt itself.
        if (empty($subcontent) && $record->rawscore) {
            $this->record->rawscore = $record->rawscore;
            $this->record->maxscore = $record->maxscore;
        }
        // Refresh current attempt.
        return $this->save();
    }

    /**
     * Update the current attempt record into DB.
     *
     * @return bool true if update is succesful
     */
    public function save(): bool {
        global $DB;
        $this->record->timemodified = time();
        return $DB->update_record('h5pactivity_attempts', $this->record);
    }

    /**
     * Update the current attempt record into DB.
     *
     * @return bool true if update is succesful
     */
    public function delete_results(): void {
        global $DB;
        $conditions = ['attemptid' => $this->record->id];
        $DB->delete_records('h5pactivity_attempts_results', $conditions);
    }

    /**
    * Get additonal data for some interaction types.
    *
    * @param stdClass $definition the statement object defintion
    * @param stdClass $context the statement optional context
    * @return string JSON encoded additional information
    */
    private function get_additionals(stdClass $definition, stdClass $context): string {
        $additionals = array();
        $interactiontype = $definition->interactionType ?? 'other';
        switch ($interactiontype) {
            case 'choice':
            case 'long-choice':
                $additionals['choices'] = $definition->choices ?? array();
                $additionals['extensions'] = $definition->extensions ?? new stdClass();
            break;

            case 'matching':
                $additionals['source'] = $definition->source ?? array();
                $additionals['target'] = $definition->target ?? array();
            break;

            default:
                $additionals['extensions'] = $definition->extensions ?? new stdClass();
        }

        // Add context extensions
        $additionals['contextExtensions'] = $context->extensions ?? new stdClass();

        if (empty($additionals)) {
            return '';
        }
        return json_encode($additionals);
    }

    /**
     * Extract the result description from statement object definition.
     *
     * @param stdClass $definition the statement object defintion
     * @return string The available description if any
     */
    private function get_description_from_definition (stdClass $definition): string {
        if (!isset($definition->description)) {
            return '';
        }
        $translations = (array) $definition->description;
        if (empty($translations)) {
            return '';
        }
        return $translations['en-US'] ?? array_shift($translations);
    }

    /**
     * Return the attempt number.
     * @return int the attempt number
     */
    public function get_attempt (): int {
        return $this->record->attempt;
    }

    /**
     * Return the attempt ID.
     * @return int the attempt id
     */
    public function get_id (): int {
        return $this->record->id;
    }

    /**
     * Return the attempt userid.
     * @return int the attempt userid
     */
    public function get_userid (): int {
        return $this->record->userid;
    }

    /**
     * Return the attempt userid.
     * @return int the attempt userid
     */
    public function get_h5pacivityid (): int {
        return $this->record->h5pacivityid;
    }
}
