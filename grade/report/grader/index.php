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
 * The gradebook grader report
 *
 * @package   gradereport_grader
 * @copyright 2007 Moodle Pty Ltd (http://moodle.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/user/renderer.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/grader/lib.php');

$courseid      = required_param('id', PARAM_INT);        // course id
$page          = optional_param('page', 0, PARAM_INT);   // active page
$edit          = optional_param('edit', -1, PARAM_BOOL); // sticky editting mode

$sortitemid    = optional_param('sortitemid', 0, PARAM_ALPHANUM); // sort by which grade item
$action        = optional_param('action', 0, PARAM_ALPHAEXT);
$move          = optional_param('move', 0, PARAM_INT);
$type          = optional_param('type', 0, PARAM_ALPHA);
$target        = optional_param('target', 0, PARAM_ALPHANUM);
$toggle        = optional_param('toggle', null, PARAM_INT);
$toggle_type   = optional_param('toggle_type', 0, PARAM_ALPHANUM);

$graderreportsifirst  = optional_param('sifirst', null, PARAM_NOTAGS);
$graderreportsilast   = optional_param('silast', null, PARAM_NOTAGS);

$PAGE->set_url(new moodle_url('/grade/report/grader/index.php', array('id'=>$courseid)));
$PAGE->set_pagelayout('report');
$PAGE->requires->js_call_amd('gradereport_grader/stickycolspan', 'init');

// basic access checks
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new \moodle_exception('invalidcourseid');
}
require_login($course);
$context = context_course::instance($course->id);

// The report object is recreated each time, save search information to SESSION object for future use.
if (isset($graderreportsifirst)) {
    $SESSION->gradereport["filterfirstname-{$context->id}"] = $graderreportsifirst;
}
if (isset($graderreportsilast)) {
    $SESSION->gradereport["filtersurname-{$context->id}"] = $graderreportsilast;
}

require_capability('gradereport/grader:view', $context);
require_capability('moodle/grade:viewall', $context);

// return tracking object
$gpr = new grade_plugin_return(
    array(
        'type' => 'report',
        'plugin' => 'grader',
        'course' => $course,
        'page' => $page
    )
);

// last selected report session tracking
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'grader';

// Build editing on/off buttons.
$buttons = '';

$PAGE->set_other_editing_capability('moodle/grade:edit');
if ($PAGE->user_allowed_editing() && !$PAGE->theme->haseditswitch) {
    if ($edit != - 1) {
        $USER->editing = $edit;
    }

    // Page params for the turn editing on button.
    $options = $gpr->get_options();
    $buttons = $OUTPUT->edit_button(new moodle_url($PAGE->url, $options), 'get');
}

$gradeserror = array();

// Handle toggle change request
if (!is_null($toggle) && !empty($toggle_type)) {
    set_user_preferences(array('grade_report_show'.$toggle_type => $toggle));
}

// Perform actions
if (!empty($target) && !empty($action) && confirm_sesskey()) {
    grade_report_grader::do_process_action($target, $action, $courseid);
}

$reportname = get_string('pluginname', 'gradereport_grader');

// Do this check just before printing the grade header (and only do it once).
grade_regrade_final_grades_if_required($course);

// Print header
print_grade_page_head($COURSE->id, 'report', 'grader', $reportname, false, $buttons);

//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
$report = new grade_report_grader($courseid, $gpr, $context, $page, $sortitemid);
$numusers = $report->get_numusers(true, true);

// make sure separate group does not prevent view
if ($report->currentgroup == -2) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    exit;
}

$warnings = [];
$isediting = has_capability('moodle/grade:edit', $context) && isset($USER->editing) && $USER->editing;
if ($isediting && ($data = data_submitted()) && confirm_sesskey()) {
    // Processing posted grades & feedback here.
    $warnings = $report->process_data($data);
}

// Final grades MUST be loaded after the processing.
$report->load_users();
$report->load_final_grades();
echo $report->group_selector;

// User search
$url = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
$firstinitial = $SESSION->gradereport["filterfirstname-{$context->id}"] ?? '';
$lastinitial  = $SESSION->gradereport["filtersurname-{$context->id}"] ?? '';
$totalusers = $report->get_numusers(true, false);
$renderer = $PAGE->get_renderer('core_user');
echo $renderer->user_search($url, $firstinitial, $lastinitial, $numusers, $totalusers, $report->currentgroupname);

//show warnings if any
foreach ($warnings as $warning) {
    echo $OUTPUT->notification($warning);
}

$displayaverages = true;
if ($numusers == 0) {
    $displayaverages = false;
}

$reporthtml = $report->get_grade_table($displayaverages);

// start report form
$editing = !empty($USER->editing) && ($report->get_pref('showquickfeedback') || $report->get_pref('quickgrading'));
if ($editing) {
    echo '<form action="index.php" enctype="application/x-www-form-urlencoded" method="post" id="gradereport_grader">'; // Enforce compatibility with our max_input_vars hack.
    echo '<div>';
    echo '<input type="hidden" value="'.s($courseid).'" name="id" />';
    echo '<input type="hidden" value="'.sesskey().'" name="sesskey" />';
    echo '<input type="hidden" value="'.time().'" name="timepageload" />';
    echo '<input type="hidden" value="grader" name="report"/>';
    echo '<input type="hidden" value="'.$page.'" name="page"/>';
    echo $gpr->get_form_fields();
}

echo $reporthtml;

// Sticky footer option 1: can be created as a regular output element by passing the rendered content
// and the css classes directly ad creation params.
$footer = new \gradereport_grader\output\footer($report, $numusers);
$stickyfooter = new core\output\sticky_footer(
    $OUTPUT->render($footer),
    'd-flex align-items-center'
);
echo $OUTPUT->render($stickyfooter);

// End report form.
if ($editing) {
    echo '</div></form>';
}

$event = \gradereport_grader\event\grade_report_viewed::create(
    array(
        'context' => $context,
        'courseid' => $courseid,
    )
);
$event->trigger();

echo $OUTPUT->footer();
