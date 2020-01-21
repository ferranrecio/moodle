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
 * This file contains unit test related to xAPI library
 *
 * @package    core_xapi
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/lib/xapi/tests/fixtures/xapi_handler.php');
require_once($CFG->dirroot . '/lib/xapi/tests/fixtures/xapi_test_statement_post.php');
require_once($CFG->dirroot . '/lib/xapi/tests/helper.php');

/**
 * Unit tests for xAPI statement processing webservice.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_xapi_request_testcase extends externallib_advanced_testcase {

    public function setUp() {
        global $CFG;

        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        accesslib_clear_all_caches_for_unit_testing();

        $this->testhelper = new core_xapi_test_helper();
        $this->testhelper->init();

        // We enable group actors on the xapi_handler to test the single Agent scenarios.
        $CFG->xapitestforcegroupactors = true;
        \core_xapi\xapi_handler_base::wipe_static_cache();
    }

    /**
     * Test all sorts af wrong parameters that xAPI statments
     * has to handle accordingly.
     */
    public function test_xapi_statement_post_invalid_params() {
        global $CFG, $USER;

        $this->setAdminUser();

        // Worng json format.
        $json = '{\'This\' [] is not a JSON.}';
        try {
            $result = \core_xapi\external::post_statement('core_xapi', $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: JSON parse, Syntax error.');
        }

        // Use an invalid component.
        $statement = $this->testhelper->generate_statement();
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('fake_component', $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Component fake_component not available.');
        }

        // Invalid statement actor structure.
        $statement = $this->testhelper->generate_statement();
        $statement->actor->objectType = 'potato_omelette';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: unsupported actor potato_omelette.');
        }

        // Not supported Agent selectors (openid).
        $statement = $this->testhelper->generate_statement();
        unset($statement->actor->account);
        $statement->actor->openid = $CFG->wwwroot.'/openid';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: unsupported Actor openid.');
        }

        // Not supported Agent selectors (mbox_sha1sum).
        $statement = $this->testhelper->generate_statement();
        $statement->actor = new stdClass();
        $statement->actor->objectType = 'Agent';
        $statement->actor->mbox_sha1sum = sha1('potato-omelette@moodle.com');
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: unsupported Actor mbox_sha1sum.');
        }

        // Use more than one selector on an Agent.
        $statement = $this->testhelper->generate_statement();
        $statement->actor->mbox = 'mailto:potato-omelette@moodle.com';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: more than one Agent identifier found.');
        }

        // Not supported anonymous group Actor.
        $statement = $this->testhelper->generate_statement();
        $group = (object) ['name' => 'Potato Omelette', 'id' => 32];
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        unset($statement->actor->account);
        $statement->actor->member = [\core_xapi\xapi_helper::xapi_agent($USER)];
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: anonynous groups are not supported.');
        }

        // Invalid statement verb structure.
        $statement = $this->testhelper->generate_statement();
        $statement->verb = \core_xapi\xapi_helper::xapi_agent($USER);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: missing verb id.');
        }

        // Invalid statement verb IRI format.
        $statement = $this->testhelper->generate_statement();
        $statement->verb->id = 'potato-omelette';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: verb id potato-omelette is not a valid IRI.');
        }

        // Not supported statement object options.
        $statement = $this->testhelper->generate_statement();
        $statement->object = \core_xapi\xapi_helper::xapi_agent($USER);
        unset($statement->object->objectType);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: missing Activity id.');
        }

        // Invalid statement object IRI format.
        $statement = $this->testhelper->generate_statement();
        $statement->object->id = 'potato-omelette';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: Activity id potato-omelette is not a valid IRI.');
        }

        // Not supported statement object type (StatementRef).
        $statement = $this->testhelper->generate_statement();
        $statement->object = new stdClass();
        $statement->object->objectType = 'StatementRef';
        $statement->object->id = $CFG->wwwroot.'/xapi/potato-omelette';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: unsupported object type StatementRef.');
        }

        // Not supported statement object type (SubStatement).
        $statement = $this->testhelper->generate_statement();
        $statement->object = $this->testhelper->generate_statement();
        $statement->object->objectType = 'SubStatement';
        $statement->object->id = $CFG->wwwroot.'/xapi/potato-omelette';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: unsupported object type SubStatement.');
        }

        // Wrong object definitions.
        $statement = $this->testhelper->generate_statement();
        $statement->object->definition = new stdClass();
        $statement->object->definition->interactionType = 'potato-omelette';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: definition unsupported potato-omelette.');
        }

        // Non existent user actor.
        $statement = $this->testhelper->generate_statement();
        $statement->actor->account->name = 'invalidID';
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: Agent user not found.');
        }

        // Non existent Group actor.
        $statement = $this->testhelper->generate_statement();
        $group = (object) ['name' => 'Potato Omelette', 'id' => 0];
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #0 error: Group not found.');
        }
    }

    /**
     * Test component xAPI handler and event generation
     * has to handle accordingly.
     */
    public function test_xapi_statement_post_handler() {
        global $CFG, $USER;

        // Create one course with a group.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $user->id));

        // Generate another user and group.
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $user2->id));

        $this->setUser($user);

        // Use an valid statement.
        $statement = $this->testhelper->generate_statement();
        $json = json_encode($statement);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $log = $this->testhelper->get_last_log_entry();
        $this->assertFalse(empty($log));
        // Validate statement information.
        $value = $log->get_name();
        $this->assertEquals($value, 'xAPI test statement');
        $value = $log->get_description();
        $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
        $this->assertTrue($log->compare_statement ($statement));

        // Use an valid statement with mbox user identification.
        $statement = $this->testhelper->generate_statement();
        unset($statement->actor->account);
        $statement->actor->mbox = 'mailto:'.$user->email;
        $json = json_encode($statement);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $log = $this->testhelper->get_last_log_entry();
        $this->assertFalse(empty($log));
        // Validate statement information.
        $value = $log->get_name();
        $this->assertEquals($value, 'xAPI test statement');
        $value = $log->get_description();
        $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
        $this->assertTrue($log->compare_statement ($statement));

        // Use statement with a group actor.
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $json = json_encode($statement);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $log = $this->testhelper->get_last_log_entry();
        $this->assertFalse(empty($log));
        // Validate statement information.
        $value = $log->get_name();
        $this->assertEquals($value, 'xAPI test statement');
        $value = $log->get_description();
        $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
        $this->assertTrue($log->compare_statement ($statement));

        // Valid IRI verb statement.
        $statement = $this->testhelper->generate_statement();
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('http://adlnet.gov/expapi/verbs/answered');
                $json = json_encode($statement);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $log = $this->testhelper->get_last_log_entry();
        $this->assertFalse(empty($log));
        // Validate statement information.
        $value = $log->get_name();
        $this->assertEquals($value, 'xAPI test statement');
        $value = $log->get_description();
        $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
        $this->assertTrue($log->compare_statement ($statement));

        // Valid IRI object statement.
        $statement = $this->testhelper->generate_statement();
        $statement->object = \core_xapi\xapi_helper::xapi_object('http://adlnet.gov/expapi/activities/example');
        $json = json_encode($statement);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $log = $this->testhelper->get_last_log_entry();
        $this->assertFalse(empty($log));
        // Validate statement information.
        $value = $log->get_name();
        $this->assertEquals($value, 'xAPI test statement');
        $value = $log->get_description();
        $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
        $this->assertTrue($log->compare_statement ($statement));

        // Invalid user Agent (with group actors disabled).
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user2);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: current user is not an actor of the statement');
        }

        // Invalid group.
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group2);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: current user is not an actor of the statement');
        }

        // Invalid verb.
        $statement = $this->testhelper->generate_statement();
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('walk');
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: invalid verb walk');
        }

        // Invalid IRI verb.
        $statement = $this->testhelper->generate_statement();
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('http://adlnet.gov/expapi/verbs/fight');
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: invalid verb http://adlnet.gov/expapi/verbs/fight');
        }

        // Use an invalid xAPI object.
        $statement = $this->testhelper->generate_statement();
        $statement->object = \core_xapi\xapi_helper::xapi_object('potato_omelette');
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: invalid object potato_omelette');
        }

        // Invalid IRI object.
        $statement = $this->testhelper->generate_statement();
        $statement->object = \core_xapi\xapi_helper::xapi_object('http://adlnet.gov/expapi/activities/wrong');
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: invalid object http://adlnet.gov/expapi/activities/wrong');
        }

        // We disable group actors on the xapi_handler to test the single Agent scenarios.
        $CFG->xapitestforcegroupactors = false;

        // Invalid user Agent (with group actors disabled).
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user2);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: statement Agent is not the current user');
        }

        // Invalid actor group (with group actors disabled).
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $json = json_encode($statement);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement error: statement Agent is not the current user');
        }
    }

    /**
     * Test component xAPI with multiple statement posts.
     */
    public function test_xapi_statement_post_array() {
        global $USER;

        // Create one course with a group.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $user->id));

        $this->setUser($user);

        // Both valid statements.
        $statement = $this->testhelper->generate_statement();
        $statements = [$statement, $statement];
        $json = json_encode($statements);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $this->assertEquals(count($result), 2);
        $this->assertTrue($result[0]);
        $this->assertTrue($result[1]);
        $logs = $this->testhelper->get_n_last_log_entries(2);
        $this->assertFalse(empty($logs));
        // Validate statement information.
        $num = 0;
        foreach ($logs as $log) {
            $value = $log->get_name();
            $this->assertEquals($value, 'xAPI test statement');
            $value = $log->get_description();
            $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
            $this->assertTrue($log->compare_statement ($statements[$num]));
            $num++;
        }

        // Both invalid statements.
        $statement = $this->testhelper->generate_statement();
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('walk');
        $statements = [$statement, $statement];
        $json = json_encode($statements);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'No statement can be processed.');
        }

        // One statement with wrong structure.
        $statement1 = $this->testhelper->generate_statement();
        $statement2 = $this->testhelper->generate_statement();
        $statement2->actor->objectType = 'potato_omelette';
        $statements = [$statement1, $statement2];
        $json = json_encode($statements);
        try {
            $result = \core_xapi\external::post_statement('core_xapi',  $json);
        } catch (\core_xapi\invalid_xapi_request_exception $e) {
            $this->assertEquals($e->errorcode, 'Statement #1 error: unsupported actor potato_omelette.');
        }

        // One invalid statement.
        $statement1 = $this->testhelper->generate_statement();
        $statement1->verb = \core_xapi\xapi_helper::xapi_verb('walk');
        $statement2 = $this->testhelper->generate_statement();
        $statements = [$statement1, $statement2];
        $json = json_encode($statements);
        $result = \core_xapi\external::post_statement('core_xapi',  $json);
        $this->assertEquals(count($result), 2);
        $this->assertFalse($result[0]);
        $this->assertTrue($result[1]);
        $logs = $this->testhelper->get_n_last_log_entries(1);
        $this->assertFalse(empty($logs));
        // Validate statement information.
        $num = 0;
        foreach ($logs as $log) {
            $value = $log->get_name();
            $this->assertEquals($value, 'xAPI test statement');
            $value = $log->get_description();
            $this->assertEquals($value, 'User \''.$USER->id.'\' send a statement to component \'core_xapi\'.');
            $this->assertTrue($log->compare_statement ($statement2));
            $num++;
        }
    }
}
