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
 * Site level default activity completion settings
 *
 * @package     core_completion
 * @category    completion
 * @copyright   2023 Amaia Anabitarte <amaia@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../config.php");

$modids = optional_param_array('modids', [], PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/course/sitedefaultcompletion.php');

$pageheading = format_string($SITE->fullname, true, ['context' => $context]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$PAGE->set_title(get_string('defaultcompletion', 'completion'));
$PAGE->set_heading($pageheading);

require_login();
require_capability('moodle/course:manageactivities', $context);

// Get list of modules that have been sent in the form.
$manager = new \core_completion\manager($SITE->id);
[$allmodules, $modules] = $manager->get_manageable_activities_and_resources($modids, false);

$form = null;
if (!empty($modules)) {
    $form = new core_completion_defaultedit_form(null, ['course' => $SITE->id, 'modules' => $modules, 'displaycancel' => false]);
    if (!$form->is_cancelled() && $data = $form->get_data()) {
        $data->modules = $modules;
        $manager->apply_default_completion($data, $form->has_custom_completion_rules(), $form->get_suffix());
    }
}

$renderer = $PAGE->get_renderer('core_course', 'bulk_activity_completion');

// Print the form.
echo $OUTPUT->header();
echo $renderer->defaultcompletion($allmodules, $modules, $form);
echo $renderer->footer();
