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
 * Contains test cases for testing xAPI helper class.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_xapi_helper_testcase extends advanced_testcase {

    /**
     * Test xAPI helper class
     * has to handle accordingly.
     */
    public function test_xapi_helper() {
        global $CFG;
        $this->resetAfterTest();
        // Create one course with a group.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        // Get an existent handler.
        $handler = \core_xapi\xapi_helper::get_xapi_handler('core_xapi');
        $this->assertEquals(get_class($handler), 'core_xapi\\xapi_handler');

        // Get a non existent handler.
        $value = \core_xapi\xapi_helper::get_xapi_handler('potato_omelette');
        $this->assertNull($value);

        // Generate a fake IRI from a non IRI element.
        $value = \core_xapi\xapi_helper::generate_iri('paella');
        $this->assertEquals($value, $CFG->wwwroot.'/xapi/element/paella');

        $value = \core_xapi\xapi_helper::generate_iri('paella', 'dish');
        $this->assertEquals($value, $CFG->wwwroot.'/xapi/dish/paella');

        // Generate an IRI from a valid IRI element.
        $value = \core_xapi\xapi_helper::generate_iri('http://adlnet.gov/expapi/activities/example');
        $this->assertEquals($value, 'http://adlnet.gov/expapi/activities/example');

        $value = \core_xapi\xapi_helper::generate_iri('http://adlnet.gov/expapi/activities/example', 'ignored_param');
        $this->assertEquals($value, 'http://adlnet.gov/expapi/activities/example');

        // Extract element from a fake IRI.
        $iri = \core_xapi\xapi_helper::generate_iri('paella');
        $value = \core_xapi\xapi_helper::extract_iri_value($iri);
        $this->assertEquals($value, 'paella');

        $iri = \core_xapi\xapi_helper::generate_iri('paella', 'dish');
        $value = \core_xapi\xapi_helper::extract_iri_value($iri, 'dish');
        $this->assertEquals($value, 'paella');

        // Extract real IRI from an IRI.
        $iri = \core_xapi\xapi_helper::generate_iri('http://adlnet.gov/expapi/activities/example');
        $value = \core_xapi\xapi_helper::extract_iri_value($iri);
        $this->assertEquals($value, 'http://adlnet.gov/expapi/activities/example');

        $iri = \core_xapi\xapi_helper::generate_iri($iri, 'ignored_param');
        $this->assertEquals($value, 'http://adlnet.gov/expapi/activities/example');

        // Agent xAPI from user.
        $value = \core_xapi\xapi_helper::xapi_agent($user);
        $this->assertEquals($value->objectType, 'Agent');
        $this->assertEquals($value->account->homePage, $CFG->wwwroot);
        $this->assertEquals($value->account->name, $user->id);

        // Group xAPI.
        $value = \core_xapi\xapi_helper::xapi_group($group);
        $this->assertEquals($value->objectType, 'Group');
        $this->assertEquals($value->account->homePage, $CFG->wwwroot);
        $this->assertEquals($value->account->name, $group->id);

        // Verb xAPI from a non IRI verb.
        $value = \core_xapi\xapi_helper::xapi_verb('cook');
        $this->assertEquals($value->id, $CFG->wwwroot.'/xapi/verb/cook');

        // Verb xAPI from a valid IRI verb.
        $value = \core_xapi\xapi_helper::xapi_verb('http://adlnet.gov/expapi/verb/example');
        $this->assertEquals($value->id, 'http://adlnet.gov/expapi/verb/example');

        // Object xAPI from a non IRI verb.
        $value = \core_xapi\xapi_helper::xapi_object('paella');
        $this->assertEquals($value->id, $CFG->wwwroot.'/xapi/object/paella');

        // Object xAPI from a valir IRI verb.
        $value = \core_xapi\xapi_helper::xapi_verb('http://adlnet.gov/expapi/activity/paella');
        $this->assertEquals($value->id, 'http://adlnet.gov/expapi/activity/paella');
    }
}
