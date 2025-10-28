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
 * This file is part of the SCORM module for Moodle.
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_scorm
 */

use mod_scorm\manager;
use mod_scorm\output\initial_state_view;

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/scorm/lib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/course/lib.php');

$id = optional_param('id', 0, PARAM_INT);       // Course Module ID, or
$a = optional_param('a', 0, PARAM_INT);         // scorm ID
$organization = optional_param('organization', '', PARAM_INT); // organization ID.
$action = optional_param('action', '', PARAM_ALPHA);
$preventskip = optional_param('preventskip', '', PARAM_INT); // Prevent Skip view, set by javascript redirects.

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, manager::MODULE);
    $manager = manager::create_from_coursemodule($cm);
    $scorm = $manager->get_instance();
} else { // We must have $d.
    $scorm = $DB->get_record(manager::MODULE, ['id' => $d], '*', MUST_EXIST);
    $manager = manager::create_from_instance($data);
    $cm = $manager->get_coursemodule();
    $course = $manager->get_course();
}

$url = new moodle_url('/mod/scorm/view.php', array('id' => $cm->id));
if ($organization !== '') {
    $url->param('organization', $organization);
}
$PAGE->set_url($url);
$forcejs = get_config('scorm', 'forcejavascript');
if (!empty($forcejs)) {
    $PAGE->add_body_class('forcejavascript');
}

require_login($course, false, $cm);

$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);

$launch = false; // Does this automatically trigger a launch based on skipview.
if (!empty($scorm->popup)) {
    $scoid = 0;
    $orgidentifier = '';

    $result = scorm_get_toc($USER, $scorm, $cm->id, TOCFULLURL);
    // Set last incomplete sco to launch first.
    if (!empty($result->sco->id)) {
        $sco = $result->sco;
    } else {
        $sco = scorm_get_sco($scorm->launch, SCO_ONLY);
    }
    if (!empty($sco)) {
        $scoid = $sco->id;
        if (($sco->organization == '') && ($sco->launch == '')) {
            $orgidentifier = $sco->identifier;
        } else {
            $orgidentifier = $sco->organization;
        }
    }

    if (empty($preventskip) && $scorm->skipview >= SCORM_SKIPVIEW_FIRST &&
        has_capability('mod/scorm:skipview', $contextmodule) &&
        !has_capability('mod/scorm:viewreport', $contextmodule)) { // Don't skip users with the capability to view reports.

        // Do we launch immediately and redirect the parent back ?
        if ($scorm->skipview == SCORM_SKIPVIEW_ALWAYS || !scorm_has_tracks($scorm->id, $USER->id)) {
            $launch = true;
        }
    }
    // Redirect back to the section with one section per page ?

    $courseformat = course_get_format($course)->get_course();
    if ($courseformat->format == 'singleactivity') {
        $courseurl = $url->out(false, array('preventskip' => '1'));
    } else {
        $courseurl = course_get_url($course, $cm->sectionnum)->out(false);
    }
    $PAGE->requires->data_for_js(
        'scormplayerdata',
        [
            'launch' => $launch,
            'currentorg' => $orgidentifier,
            'sco' => $scoid,
            'scorm' => $scorm->id,
            'courseurl' => $courseurl,
            'cwidth' => $scorm->width,
            'cheight' => $scorm->height,
            'popupoptions' => $scorm->options,
        ],
        true
    );
    $PAGE->requires->string_for_js('popupsblocked', 'scorm');
    $PAGE->requires->string_for_js('popuplaunched', 'scorm');
    $PAGE->requires->js('/mod/scorm/view.js', true);
}

if (isset($SESSION->scorm)) {
    unset($SESSION->scorm);
}

$strscorms = get_string("modulenameplural", "scorm");
$strscorm  = get_string("modulename", "scorm");

$shortname = format_string($course->shortname, true, array('context' => $context));
$pagetitle = strip_tags($shortname.': '.format_string($scorm->name));

// Trigger module viewed event.
scorm_view($scorm, $course, $cm, $contextmodule);

if (empty($preventskip) && empty($launch) && (has_capability('mod/scorm:skipview', $contextmodule))) {
    // The simple player will redirect automatically if needed.
    scorm_simple_play($scorm, $USER, $contextmodule, $cm->id);
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');
// Let the module handle the display.
if (!empty($action) && $action == 'delete' && confirm_sesskey() && has_capability('mod/scorm:deleteownresponses', $contextmodule)) {
    $PAGE->activityheader->disable();
}

echo $OUTPUT->header();
if (!empty($action) && confirm_sesskey() && has_capability('mod/scorm:deleteownresponses', $contextmodule)) {
    if ($action == 'delete') {
        $confirmurl = new moodle_url($PAGE->url, array('action' => 'deleteconfirm'));
        echo $OUTPUT->confirm(get_string('deleteuserattemptcheck', 'scorm'), $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
        exit;
    } else if ($action == 'deleteconfirm') {
        // Delete this users attempts.
        scorm_delete_tracks($scorm->id, null, $USER->id);
        scorm_update_grades($scorm, $USER->id, true);
        echo $OUTPUT->notification(get_string('scormresponsedeleted', 'scorm'), 'notifysuccess');
    }
}

if (empty($launch)) {
    $initialstate = new initial_state_view($manager);
    echo $OUTPUT->render($initialstate);
}

// Check if SCORM available. No need to display warnings because activity dates are displayed at the top of the page.
list($available, $warnings) = scorm_get_availability_status($scorm);

if ($available && empty($launch)) {
    scorm_print_launch($USER, $scorm, 'view.php?id='.$cm->id, $cm);
}

if (!empty($forcejs)) {
    $message = $OUTPUT->box(get_string("forcejavascriptmessage", "scorm"), "forcejavascriptmessage");
    echo html_writer::tag('noscript', $message);
}

if (!empty($scorm->popup)) {
    $PAGE->requires->js_init_call('M.mod_scormform.init');
}

echo $OUTPUT->footer();
