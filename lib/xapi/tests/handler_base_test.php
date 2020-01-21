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
require_once($CFG->dirroot . '/lib/xapi/tests/fixtures/xapi_handler.php');
require_once($CFG->dirroot . '/lib/xapi/tests/fixtures/xapi_test_statement_post.php');
require_once($CFG->dirroot . '/lib/xapi/tests/helper.php');

/**
 * Contains test cases for testing xAPI statement handler base methods.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_xapi_handler_base_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
        accesslib_clear_all_caches_for_unit_testing();

        $this->testhelper = new core_xapi_test_helper();
        $this->testhelper->init();

        \core_xapi\xapi_handler_base::wipe_static_cache();
    }

    /**
     * Test public methods from xapi_handle_base.
     */
    public function test_xapi_handle_base() {

        // Create one course with a group.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $user->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $user2->id));

        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $user->id));

        $this->setUser($user);

        // Get component xAPI statement handler class.
        $xapihandler = \core_xapi\xapi_helper::get_xapi_handler('core_xapi');
        $this->assertNotEmpty($xapihandler);

        // Get verb.
        $statement = $this->testhelper->generate_statement();
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('http://adlnet.gov/expapi/verbs/answered');
        $this->assertEquals($xapihandler->get_verb($statement), 'http://adlnet.gov/expapi/verbs/answered');
        $this->assertEquals($xapihandler->get_verb($statement->verb), 'http://adlnet.gov/expapi/verbs/answered');
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('cook');
        $this->assertEquals($xapihandler->get_verb($statement), 'cook');
        $this->assertEquals($xapihandler->get_verb($statement->verb), 'cook');
        unset($statement->verb->id);
        $this->assertNull($xapihandler->get_verb($statement));

        // Get object.
        $statement = $this->testhelper->generate_statement();
        $statement->object = \core_xapi\xapi_helper::xapi_object('http://adlnet.gov/expapi/activities/example');
        $this->assertEquals($xapihandler->get_object($statement), 'http://adlnet.gov/expapi/activities/example');
        $this->assertEquals($xapihandler->get_object($statement->object), 'http://adlnet.gov/expapi/activities/example');
        $statement->object = \core_xapi\xapi_helper::xapi_object('paella');
        $this->assertEquals($xapihandler->get_object($statement), 'paella');
        $this->assertEquals($xapihandler->get_object($statement->object), 'paella');
        $statement->object->objectType = 'wrong_type';
        $this->assertNull($xapihandler->get_object($statement));
        $statement->object = \core_xapi\xapi_helper::xapi_object('paella');
        unset($statement->object->id);
        $this->assertNull($xapihandler->get_object($statement));

        // Check valid objects.
        $statement = $this->testhelper->generate_statement();
        $objects = ['paella', 'http://adlnet.gov/expapi/activities/example'];
        $statement->object = \core_xapi\xapi_helper::xapi_object($objects[0]);
        $this->assertEquals($xapihandler->check_valid_object($statement, $objects), $objects[0]);
        $statement->object = \core_xapi\xapi_helper::xapi_object($objects[1]);
        $this->assertEquals($xapihandler->check_valid_object($statement, $objects), $objects[1]);
        $statement->object = \core_xapi\xapi_helper::xapi_object('omelette');
        $this->assertNull($xapihandler->check_valid_object($statement, $objects));

        // Check valid verbs.
        $statement = $this->testhelper->generate_statement();
        $verbs = ['cook', 'http://adlnet.gov/expapi/activities/example'];
        $statement->verb = \core_xapi\xapi_helper::xapi_verb($verbs[0]);
        $this->assertEquals($xapihandler->check_valid_verb($statement, $verbs), $verbs[0]);
        $statement->verb = \core_xapi\xapi_helper::xapi_verb($verbs[1]);
        $this->assertEquals($xapihandler->check_valid_verb($statement, $verbs), $verbs[1]);
        $statement->verb = \core_xapi\xapi_helper::xapi_verb('run');
        $this->assertNull($xapihandler->check_valid_verb($statement, $verbs));

        // Get user.
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        $value = $xapihandler->get_user($statement);
        $this->assertEquals($value->id, $user->id);
        $this->assertEquals($value->username, $user->username);
        $this->assertEquals($value->email, $user->email);

        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        $statement->actor->account->name = -1;
        $this->assertNull($xapihandler->get_user($statement));

        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        unset($statement->actor->account);
        $statement->actor->mbox = "mailto:$user->email";
        $value = $xapihandler->get_user($statement);
        $this->assertEquals($value->id, $user->id);
        $this->assertEquals($value->username, $user->username);
        $this->assertEquals($value->email, $user->email);

        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $this->assertNull($xapihandler->get_user($statement));
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group2);
        $value = $xapihandler->get_user($statement);
        $this->assertEquals($value->id, $user->id);
        $this->assertEquals($value->username, $user->username);
        $this->assertEquals($value->email, $user->email);

        // Get all users.
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        $values = $xapihandler->get_all_users($statement);
        $this->assertEquals(count($values), 1);
        $this->assertArrayHasKey($user->id, $values);

        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        $statement->actor->account->name = -1;
        $this->assertNull($xapihandler->get_all_users($statement));

        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        unset($statement->actor->account);
        $statement->actor->mbox = "mailto:$user->email";
        $values = $xapihandler->get_all_users($statement);
        $this->assertEquals(count($values), 1);
        $this->assertArrayHasKey($user->id, $values);

        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $values = $xapihandler->get_all_users($statement);
        $this->assertEquals(count($values), 2);
        $this->assertArrayHasKey($user->id, $values);
        $this->assertArrayHasKey($user2->id, $values);

        $statement->actor->account->name = -1;
        $this->assertNull($xapihandler->get_all_users($statement));

        // Get group.
        $statement = $this->testhelper->generate_statement();
        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $value = $xapihandler->get_group($statement);
        $this->assertEquals($value->id, $group->id);
        $value = $xapihandler->get_group($statement->actor);
        $this->assertEquals($value->id, $group->id);

        $statement->actor = \core_xapi\xapi_helper::xapi_agent($user);
        $this->assertNull($xapihandler->get_group($statement));
        $this->assertNull($xapihandler->get_group($statement->actor));

        $statement->actor = \core_xapi\xapi_helper::xapi_group($group);
        $statement->actor->account->name = -1;
        $this->assertNull($xapihandler->get_group($statement));
        $this->assertNull($xapihandler->get_group($statement->actor));

        // Minify statement.
        $statement = $this->testhelper->generate_statement();
        $statement->id = 'potato_omelette';
        $statement->timestamp = '2015-11-18T12:17:00+00:00';
        $statement->stored = '2015-11-18T12:17:00+00:00';
        $statement->version = '1.0.0';
        $value = $xapihandler->testing_minify_statement($statement);
        $this->assertArrayNotHasKey('id', $value);
        $this->assertArrayNotHasKey('actor', $value);
        $this->assertArrayNotHasKey('timestamp', $value);
        $this->assertArrayNotHasKey('stored', $value);
        $this->assertArrayNotHasKey('version', $value);
    }
}
