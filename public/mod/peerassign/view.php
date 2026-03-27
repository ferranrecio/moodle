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
 * Main view for a Peer Review Assignment activity.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

use mod_peerassign\output\activity_view;
use mod_peerassign\manager;
use mod_peerassign\permissions;

// Course module id.
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id(manager::MODULE, $id, 0, false, MUST_EXIST);
$manager = manager::create_from_coursemodule($cm);

$course = $manager->get_course();
$context = $manager->get_context();
$moduleinstance = $manager->get_instance();

permissions::require_view_activity($manager);

$PAGE->set_url('/mod/peerassign/view.php', ['id' => $id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$manager = manager::create_from_coursemodule($cm);

$renderer = $manager->get_renderer();

echo $renderer->header();
$viewoutput = new activity_view($manager);
echo $renderer->render($viewoutput);

echo $renderer->footer();
