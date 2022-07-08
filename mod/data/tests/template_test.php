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

namespace mod_data;

use context_module;
use rating_manager;
use stdClass;

/**
 * Template tests class for mod_data.
 *
 * @package    mod_data
 * @category   test
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_data\template
 */
class template_test extends \advanced_testcase {
    /**
     * Setup to ensure that fixtures are loaded.
     */
    public static function setupBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/rating/lib.php');
    }

    /**
     * Test for static create methods.
     *
     * @covers ::parse_entries
     * @dataProvider parse_entries_provider
     * @param string $templatestr the template string
     * @param string $expected expected output
     * @param string $rolename the user rolename
     * @param bool $enableexport is portfolio export is enabled
     * @param bool $approved if the entry is approved
     * @param bool $enablecomments is comments are enabled
     * @param bool $enableratings if ratings are enabled
     * @param array $options extra parser options
     * @param bool $otherauthor if the entry is from another user
     */
    public function test_parse_entries(
        string $templatestr,
        string $expected,
        string $rolename = 'editingteacher',
        bool $enableexport = false,
        bool $approved = true,
        bool $enablecomments = false,
        bool $enableratings = false,
        array $options = [],
        bool $otherauthor = false
    ) {
        global $DB, $PAGE;
        // Comments, tags, approval, user role.
        $this->resetAfterTest();

        $params = ['approval' => true];

        // Enable comments.
        if ($enablecomments) {
            set_config('usecomments', 1);
            $params['comments'] = true;
            $PAGE->reset_theme_and_output();
            $PAGE->set_url('/mod/data/view.php');
        }

        $course = $this->getDataGenerator()->create_course();
        $params['course'] = $course;
        $activity = $this->getDataGenerator()->create_module('data', $params);
        $cm = get_coursemodule_from_id('data', $activity->cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $user = $this->getDataGenerator()->create_user();
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleids[$rolename]);
        $author = $user;

        if ($otherauthor) {
            $user2 = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user2->id, $course->id, $roleids[$rolename]);
            $author = $user2;
        }

        // Generate an entry.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_data');
        $fieldrecord = (object)[
            'name' => 'myfield',
            'type' => 'text',
        ];
        $field = $generator->create_field($fieldrecord, $activity);

        $this->setUser($user);

        $entryid = $generator->create_entry(
            $activity,
            [$field->field->id => 'Example entry'],
            0,
            ['Cats', 'Dogs'],
            ['approved' => $approved]
        );

        if ($enableexport) {
            $this->enable_portfolio($user);
        }

        $manager = manager::create_from_instance($activity);

        $entry = (object)[
            'id' => $entryid,
            'approved' => $approved,
            'timecreated' => 1657618639,
            'timemodified' => 1657618650,
            'userid' => $author->id,
            'groupid' => 0,
            'dataid' => $activity->id,
            'picture' => 0,
            'firstname' => $author->firstname,
            'lastname' => $author->lastname,
            'firstnamephonetic' => $author->firstnamephonetic,
            'lastnamephonetic' => $author->lastnamephonetic,
            'middlename' => $author->middlename,
            'alternatename' => $author->alternatename,
            'imagealt' => 'PIXEXAMPLE',
            'email' => $author->email,
        ];
        $entries = [$entry];

        if ($enableratings) {
            $entries = $this->enable_ratings($context, $activity, $entries, $user);
        }

        // Some cooked variables for the regular expression.
        $userfullname = fullname($user);
        $timeadded = userdate($entry->timecreated);
        $timemodified = userdate($entry->timemodified);
        $fieldid = $field->field->id;
        $replace = [
            '{authorfullname}' => fullname($author),
            '{timeadded}' => userdate($entry->timecreated),
            '{timemodified}' => userdate($entry->timemodified),
            '{fieldid}' => $field->field->id,
            '{entryid}' => $entry->id,
            '{cmid}' => $cm->id,
            '{courseid}' => $course->id,
            '{authorid}' => $author->id
        ];

        $parser = new template($manager, $templatestr, $options);
        $result = $parser->parse_entries($entries);

        // We don't want line breaks for the validations.
        $result = str_replace("\n", '', $result);
        $regexp = str_replace(array_keys($replace), array_values($replace), $expected);
        $this->assertMatchesRegularExpression($regexp, $result);
    }

    /**
     * Data provider for test_parse_entries().
     *
     * @return array of scenarios
     */
    public function parse_entries_provider(): array {
        return [
            // Teacher scenarios.
            'Teacher id tag' => [
                'templatestr' => 'Some ##id## tag',
                'expected' => '|Some {entryid} tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher delete tag' => [
                'templatestr' => 'Some ##delete## tag',
                'expected' => '|Some .*delete.*{entryid}.*sesskey.*Delete.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher edit tag' => [
                'templatestr' => 'Some ##edit## tag',
                'expected' => '|Some .*edit.*{entryid}.*sesskey.*Edit.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher more tag' => [
                'templatestr' => 'Some ##more## tag',
                'expected' => '|Some .*more.*{cmid}.*rid.*{entryid}.*More.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher moreurl tag' => [
                'templatestr' => 'Some ##moreurl## tag',
                'expected' => '|Some .*/mod/data/view.*{cmid}.*rid.*{entryid}.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher delcheck tag' => [
                'templatestr' => 'Some ##delcheck## tag',
                'expected' => '|Some .*input.*checkbox.*value.*{entryid}.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher user tag' => [
                'templatestr' => 'Some ##user## tag',
                'expected' => '|Some .*user/view.*{authorid}.*course.*{courseid}.*{authorfullname}.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher userpicture tag' => [
                'templatestr' => 'Some ##userpicture## tag',
                'expected' => '|Some .*user/view.*{authorid}.*course.*{courseid}.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher export tag' => [
                'templatestr' => 'Some ##export## tag',
                'expected' => '|Some .*portfolio/add.* tag|',
                'rolename' => 'editingteacher',
                'enableexport' => true,
            ],
            'Teacher export tag not configured' => [
                'templatestr' => 'Some ##export## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
            ],
            'Teacher timeadded tag' => [
                'templatestr' => 'Some ##timeadded## tag',
                'expected' => '|Some {timeadded} tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher timemodified tag' => [
                'templatestr' => 'Some ##timemodified## tag',
                'expected' => '|Some {timemodified} tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher approve tag approved entry' => [
                'templatestr' => 'Some ##approve## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
            ],
            'Teacher approve tag disapproved entry' => [
                'templatestr' => 'Some ##approve## tag',
                'expected' => '|Some .*approve.*{entryid}.*sesskey.*Approve.* tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => false,
            ],
            'Teacher disapprove tag approved entry' => [
                'templatestr' => 'Some ##disapprove## tag',
                'expected' => '|Some .*disapprove.*{entryid}.*sesskey.*Undo approval.* tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
            ],
            'Teacher disapprove tag disapproved entry' => [
                'templatestr' => 'Some ##disapprove## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => false,
            ],
            'Teacher approvalstatus tag approved entry' => [
                'templatestr' => 'Some ##approvalstatus## tag',
                'expected' => '|Some Approved tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
            ],
            'Teacher approvalstatus tag disapproved entry' => [
                'templatestr' => 'Some ##approvalstatus## tag',
                'expected' => '|Some .*not approved.* tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => false,
            ],
            'Teacher approvalstatusclass tag approved entry' => [
                'templatestr' => 'Some ##approvalstatusclass## tag',
                'expected' => '|Some approved tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
            ],
            'Teacher approvalstatusclass tag disapproved entry' => [
                'templatestr' => 'Some ##approvalstatusclass## tag',
                'expected' => '|Some notapproved tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => false,
            ],
            'Teacher tags tag' => [
                'templatestr' => 'Some ##tags## tag',
                'expected' => '|Some .*Cats.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher field name tag' => [
                'templatestr' => 'Some [[myfield]] tag',
                'expected' => '|Some .*Example entry.* tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher field#id name tag' => [
                'templatestr' => 'Some [[myfield#id]] tag',
                'expected' => '|Some {fieldid} tag|',
                'rolename' => 'editingteacher',
            ],
            'Teacher comments name tag with comments enabled' => [
                'templatestr' => 'Some ##comments## tag',
                'expected' => '|Some .*Comments.* tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => true,
            ],
            'Teacher comments name tag with comments disabled' => [
                'templatestr' => 'Some ##comments## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
            ],
            'Teacher comment forced with comments enables' => [
                'templatestr' => 'No tags',
                'expected' => '|No tags.*Comments.*|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => true,
                'enableratings' => false,
                'options' => ['comments' => true],
            ],
            'Teacher comment forced without comments enables' => [
                'templatestr' => 'No tags',
                'expected' => '|^No tags$|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => ['comments' => true],
            ],
            'Teacher adding ratings without ratings configured' => [
                'templatestr' => 'No tags',
                'expected' => '|^No tags$|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => ['ratings' => true],
            ],
            'Teacher adding ratings with ratings configured' => [
                'templatestr' => 'No tags',
                'expected' => '|^No tags.*Average of ratings|',
                'rolename' => 'editingteacher',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => true,
                'options' => ['ratings' => true],
            ],
            // Student scenarios.
            'Student id tag' => [
                'templatestr' => 'Some ##id## tag',
                'expected' => '|Some {entryid} tag|',
                'rolename' => 'student',
            ],
            'Student delete tag' => [
                'templatestr' => 'Some ##delete## tag',
                'expected' => '|Some .*delete.*{entryid}.*sesskey.*Delete.* tag|',
                'rolename' => 'student',
            ],
            'Student delete tag on other author entry' => [
                'templatestr' => 'Some ##delete## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => [],
                'otherauthor' => true,
            ],
            'Student edit tag' => [
                'templatestr' => 'Some ##edit## tag',
                'expected' => '|Some .*edit.*{entryid}.*sesskey.*Edit.* tag|',
                'rolename' => 'student',
            ],
            'Student edit tag on other author entry' => [
                'templatestr' => 'Some ##edit## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => [],
                'otherauthor' => true,
            ],
            'Student more tag' => [
                'templatestr' => 'Some ##more## tag',
                'expected' => '|Some .*more.*{cmid}.*rid.*{entryid}.*More.* tag|',
                'rolename' => 'student',
            ],
            'Student moreurl tag' => [
                'templatestr' => 'Some ##moreurl## tag',
                'expected' => '|Some .*/mod/data/view.*{cmid}.*rid.*{entryid}.* tag|',
                'rolename' => 'student',
            ],
            'Student delcheck tag' => [
                'templatestr' => 'Some ##delcheck## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
            ],
            'Student user tag' => [
                'templatestr' => 'Some ##user## tag',
                'expected' => '|Some .*user/view.*{authorid}.*course.*{courseid}.*{authorfullname}.* tag|',
                'rolename' => 'student',
            ],
            'Student userpicture tag' => [
                'templatestr' => 'Some ##userpicture## tag',
                'expected' => '|Some .*user/view.*{authorid}.*course.*{courseid}.* tag|',
                'rolename' => 'student',
            ],
            'Student export tag' => [
                'templatestr' => 'Some ##export## tag',
                'expected' => '|Some .*portfolio/add.* tag|',
                'rolename' => 'student',
                'enableexport' => true,
            ],
            'Student export tag not configured' => [
                'templatestr' => 'Some ##export## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
            ],
            'Student export tag on other user entry' => [
                'templatestr' => 'Some ##export## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => true,
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => [],
                'otherauthor' => true,
            ],
            'Student timeadded tag' => [
                'templatestr' => 'Some ##timeadded## tag',
                'expected' => '|Some {timeadded} tag|',
                'rolename' => 'student',
            ],
            'Student timemodified tag' => [
                'templatestr' => 'Some ##timemodified## tag',
                'expected' => '|Some {timemodified} tag|',
                'rolename' => 'student',
            ],
            'Student approve tag approved entry' => [
                'templatestr' => 'Some ##approve## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
            ],
            'Student approve tag disapproved entry' => [
                'templatestr' => 'Some ##approve## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => false,
            ],
            'Student disapprove tag approved entry' => [
                'templatestr' => 'Some ##disapprove## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
            ],
            'Student disapprove tag disapproved entry' => [
                'templatestr' => 'Some ##disapprove## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => false,
            ],
            'Student approvalstatus tag approved entry' => [
                'templatestr' => 'Some ##approvalstatus## tag',
                'expected' => '|Some Approved tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
            ],
            'Student approvalstatus tag disapproved entry' => [
                'templatestr' => 'Some ##approvalstatus## tag',
                'expected' => '|Some .*not approved.* tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => false,
            ],
            'Student approvalstatusclass tag approved entry' => [
                'templatestr' => 'Some ##approvalstatusclass## tag',
                'expected' => '|Some approved tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
            ],
            'Student approvalstatusclass tag disapproved entry' => [
                'templatestr' => 'Some ##approvalstatusclass## tag',
                'expected' => '|Some notapproved tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => false,
            ],
            'Student tags tag' => [
                'templatestr' => 'Some ##tags## tag',
                'expected' => '|Some .*Cats.* tag|',
                'rolename' => 'student',
            ],
            'Student field name tag' => [
                'templatestr' => 'Some [[myfield]] tag',
                'expected' => '|Some .*Example entry.* tag|',
                'rolename' => 'student',
            ],
            'Student field#id name tag' => [
                'templatestr' => 'Some [[myfield#id]] tag',
                'expected' => '|Some {fieldid} tag|',
                'rolename' => 'student',
            ],
            'Student comments name tag with comments enabled' => [
                'templatestr' => 'Some ##comments## tag',
                'expected' => '|Some .*Comments.* tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => true,
            ],
            'Student comments name tag with comments disabled' => [
                'templatestr' => 'Some ##comments## tag',
                'expected' => '|Some  tag|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
            ],
            'Student comment forced with comments enables' => [
                'templatestr' => 'No tags',
                'expected' => '|No tags.*Comments.*|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => true,
                'enableratings' => false,
                'options' => ['comments' => true]
            ],
            'Student comment forced without comments enables' => [
                'templatestr' => 'No tags',
                'expected' => '|^No tags$|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => ['comments' => true]
            ],
            'Student adding ratings without ratings configured' => [
                'templatestr' => 'No tags',
                'expected' => '|^No tags$|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => false,
                'options' => ['ratings' => true]
            ],
            'Student adding ratings with ratings configured' => [
                'templatestr' => 'No tags',
                'expected' => '|^No tags$|',
                'rolename' => 'student',
                'enableexport' => false,
                'approved' => true,
                'enablecomments' => false,
                'enableratings' => true,
                'options' => ['ratings' => true]
            ],
        ];
    }

    /**
     * Create all the necessary data to enable portfolio export in mod_data
     *
     * @param stdClass $user the current user record.
     */
    protected function enable_portfolio(stdClass $user) {
        global $DB;
        set_config('enableportfolios', 1);

        $plugin = 'download';
        $name = 'Download';

        $portfolioinstance = (object) [
            'plugin' => $plugin,
            'name' => $name,
            'visible' => 1
        ];
        $portfolioinstance->id = $DB->insert_record('portfolio_instance', $portfolioinstance);
        $userinstance = (object) [
            'instance' => $portfolioinstance->id,
            'userid' => $user->id,
            'name' => 'visible',
            'value' => 1
        ];
        $DB->insert_record('portfolio_instance_user', $userinstance);

        $DB->insert_record('portfolio_log', [
            'portfolio' => $portfolioinstance->id,
            'userid' => $user->id,
            'caller_class' => 'data_portfolio_caller',
            'caller_component' => 'mod_data',
            'time' => time(),
        ]);
    }

    /**
     * Enable the ratings on the database entries.
     *
     * @param context_module $context the activity context
     * @param stdClass $activity the activity record
     * @param array $entries database entries
     * @param stdClass $user the current user record
     * @return stdClass the entries with the rating attribute
     */
    protected function enable_ratings(context_module $context, stdClass $activity, array $entries, stdClass $user) {
        global $CFG;
        $ratingoptions = (object)[
            'context' => $context,
            'component' => 'mod_data',
            'ratingarea' => 'entry',
            'items' => $entries,
            'aggregate' => RATING_AGGREGATE_AVERAGE,
            'scaleid' => $activity->scale,
            'userid' => $user->id,
            'returnurl' => $CFG->wwwroot . '/mod/data/view.php',
            'assesstimestart' => $activity->assesstimestart,
            'assesstimefinish' => $activity->assesstimefinish,
        ];
        $rm = new rating_manager();
        return $rm->get_ratings($ratingoptions);
    }
}
