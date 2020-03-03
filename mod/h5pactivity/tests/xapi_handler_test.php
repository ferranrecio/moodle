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
 * mod_h5pactivity generator tests
 *
 * @package    mod_h5pactivity
 * @category   test
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_h5pactivity;

use context_module;
use stdClass;
use \core_xapi\xapi_helper;
use \core_xapi\xapi_handler_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Attempt tests class for mod_h5pactivity.
 *
 * @package    mod_h5pactivity
 * @category   test
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_h5pactivity_xapi_handler_testcase extends \advanced_testcase {

    /**
     * Generate a valid scenario for each tests.
     *
     * @return stdClass an object with all scenario data in it
     */
    private function generate_testing_scenario(): stdClass {

        $this->resetAfterTest();
        xapi_handler_base::wipe_static_cache();
        $this->setAdminUser();

        $data = new stdClass();

        $data->course = $this->getDataGenerator()->create_course();

        // Generate 2 users, one enroled into course and one not.
        $data->student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');
        $data->otheruser = $this->getDataGenerator()->create_user();

        // H5P activity.
        $data->activity = $this->getDataGenerator()->create_module('h5pactivity', array('course' => $data->course));
        $data->context = context_module::instance($data->activity->cmid);

        $data->xapihandler = xapi_helper::get_xapi_handler('mod_h5pactivity');
        $this->assertNotEmpty($data->xapihandler);
        $this->assertInstanceOf('\mod_h5pactivity\xapi_handler', $data->xapihandler);

        $this->setUser($data->student);

        return $data;
    }

    /**
     * Test for xapi_handler with valid statements.
     */
    public function test_xapi_handler() {
        global $DB;

        $data = $this->generate_testing_scenario();
        $xapihandler = $data->xapihandler;
        $context = $data->context;
        $student = $data->student;
        $otheruser = $data->otheruser;

        // Check we hace 0 entries in the attempts tables.
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(0, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(0, $count);

        $statements = $this->generate_statements($context, $student);

        // Insert first statement.
        $event = $xapihandler->statement_to_event($statements[0]);
        $this->assertNotNull($event);
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(1, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(1, $count);

        // Insert second statement.
        $event = $xapihandler->statement_to_event($statements[1]);
        $this->assertNotNull($event);
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(1, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(2, $count);

        // Insert again first statement.
        $event = $xapihandler->statement_to_event($statements[0]);
        $this->assertNotNull($event);
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(2, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(3, $count);

        // Insert again second statement.
        $event = $xapihandler->statement_to_event($statements[1]);
        $this->assertNotNull($event);
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(2, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(4, $count);
    }

    /**
     * Testing wrong statements scenarios.
     *
     * @dataProvider test_xapi_handler_errors_data
     * @param bool $hasverb valid verb
     * @param bool $hasdefinition generate definition
     * @param bool $hasresult generate result
     * @param bool $hascontext valid context
     * @param bool $hasuser valid user
     */
    public function test_xapi_handler_errors(bool $hasverb, bool $hasdefinition, bool $hasresult,
            bool $hascontext, bool $hasuser) {
        global $DB;

        $data = $this->generate_testing_scenario();
        $xapihandler = $data->xapihandler;
        $context = $data->context;
        $student = $data->student;
        $otheruser = $data->otheruser;

        // Check we hace 0 entries in the attempts tables.
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(0, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(0, $count);

        $statements = $this->generate_statements($context, $student);

        // Insert first statement.
        $statement = $statements[0];
        if (!$hasverb) {
            $statement->verb = xapi_helper::xapi_verb('cook');
        }
        if (!$hasdefinition) {
            unset($statement->object->definition);
        }
        if (!$hasresult) {
            unset($statement->result);
        }
        if (!$hascontext) {
            $statement->object = xapi_helper::xapi_object('paella');
        }
        if ($hasuser) {
            $this->setUser($student);
        } else {
            $this->setUser($otheruser);
            $statement->actor = xapi_helper::xapi_agent($otheruser);
        }
        $event = $xapihandler->statement_to_event($statement);
        $this->assertNull($event);
        // No enties should be generated.
        $count = $DB->count_records('h5pactivity_attempts');
        $this->assertEquals(0, $count);
        $count = $DB->count_records('h5pactivity_attempts_results');
        $this->assertEquals(0, $count);
    }

    /**
     * Data provider for data request creation tests.
     *
     * @return array
     */
    public function test_xapi_handler_errors_data(): array {
        return [
            // Invalid Definitions and results possibilities.
            'Invalid definition and result' => [
                true, false, false, true, true
            ],
            'Invalid result' => [
                true, true, false, true, true
            ],
            'Invalid definition' => [
                true, false, true, true, true
            ],
            // Invalid verb possibilities.
            'Invalid verb, definition and result' => [
                false, false, false, true, true
            ],
            'Invalid verb and result' => [
                false, true, false, true, true
            ],
            'Invalid verb and result' => [
                false, false, true, true, true
            ],
            // Invalid context possibilities.
            'Invalid definition, result and context' => [
                true, false, false, false, true
            ],
            'Invalid result' => [
                true, true, false, false, true
            ],
            'Invalid result and context' => [
                true, false, true, false, true
            ],
            'Invalid verb, definition result and context' => [
                false, false, false, false, true
            ],
            'Invalid verb, result and context' => [
                false, true, false, false, true
            ],
            'Invalid verb, result and context' => [
                false, false, true, false, true
            ],
            // Invalid user possibilities.
            'Invalid definition, result and user' => [
                true, false, false, true, false
            ],
            'Invalid result and user' => [
                true, true, false, true, false
            ],
            'Invalid definition and user' => [
                true, false, true, true, false
            ],
            'Invalid verb, definition, result and user' => [
                false, false, false, true, false
            ],
            'Invalid verb, result and user' => [
                false, true, false, true, false
            ],
            'Invalid verb, result and user' => [
                false, false, true, true, false
            ],
            'Invalid definition, result, context and user' => [
                true, false, false, false, false
            ],
            'Invalid result, context and user' => [
                true, true, false, false, false
            ],
            'Invalid definition, context and user' => [
                true, false, true, false, false
            ],
            'Invalid verb, definition, result, context and user' => [
                false, false, false, false, false
            ],
            'Invalid verb, result, context and user' => [
                false, true, false, false, false
            ],
            'Invalid verb, result, context and user' => [
                false, false, true, false, false
            ],
        ];
    }

    /**
     * Returns a basic xAPI statements simulating a H5P content.
     *
     * @param context_module $context activity context
     * @param stdClass $user user record
     * @return array of xAPI statements
     */
    private function generate_statements(context_module $context, stdClass $user): array {
        $statements = [];

        $statement = new stdClass();
        $statement->actor = xapi_helper::xapi_agent($user);
        $statement->verb = xapi_helper::xapi_verb('http://adlnet.gov/expapi/verbs/completed');
        $statement->object = xapi_helper::xapi_object($context->id);
        $statement->object->definition = (object) [
                    'interactionType' => 'compound',
                    'correctResponsesPattern' => '1',

                ];
        $statement->result = (object) ['completion' => true, 'success' => true];
        $statement->result->score = (object) ['min' => 0, 'max' => 2, 'raw' => 2, 'scaled' => 1];
        $statements[] = $statement;

        $statement = new stdClass();
        $statement->actor = xapi_helper::xapi_agent($user);
        $statement->verb = xapi_helper::xapi_verb('http://adlnet.gov/expapi/verbs/completed');
        $statement->object = xapi_helper::xapi_object($context->id.'?subContentId=111-222-333');
        $statement->object->definition = (object) [
                    'interactionType' => 'matching',
                    'correctResponsesPattern' => '1',

                ];
        $statement->result = (object) ['completion' => true, 'success' => true];
        $statement->result->score = (object) ['min' => 0, 'max' => 1, 'raw' => 0, 'scaled' => 0];
        $statements[] = $statement;

        return $statements;
    }
}
