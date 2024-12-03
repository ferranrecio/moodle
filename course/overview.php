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
 * TODO describe file overview
 *
 * @package    core_course
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

require_once('../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/completionlib.php');

$courseid = required_param('id', PARAM_INT);

$PAGE->set_url('/course/overview.php', ['id' => $courseid]);

$course = get_course($courseid);

$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);


$modinfo = get_fast_modinfo($course);
$modfullnames = [];
$archetypes = [];

foreach ($modinfo->cms as $cm) {
    // Exclude activities that aren't visible or have no view link (e.g. label). Account for folder being displayed inline.
    if (!$cm->uservisible || (!$cm->has_view() && strcmp($cm->modname, 'folder') !== 0)) {
        continue;
    }
    if (array_key_exists($cm->modname, $modfullnames)) {
        continue;
    }
    if (!array_key_exists($cm->modname, $archetypes)) {
        $archetypes[$cm->modname] = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
    }
    if ($archetypes[$cm->modname] == MOD_ARCHETYPE_RESOURCE) {
        if (!array_key_exists('resources', $modfullnames)) {
            $modfullnames['resources'] = get_string('resources');
        }
    } else {
        $modfullnames[$cm->modname] = $cm->modplural;
    }
}

core_collator::asort($modfullnames);

$elements = [];
foreach ($modfullnames as $modname => $modfullname) {
    if ($modname === 'resources') {
        $elements[] = (object)[
            'overviewurl' => new url('/course/resources.php', ['id' => $course->id, 'forcedembedlayout' => 1]),
            'icon' => $OUTPUT->pix_icon('monologo', '', 'mod_page', ['class' => 'icon']),
            'name' => $modfullname,
            'shortname' => 'resources',
        ];
    } else {
        $elements[] = (object)[
            'overviewurl' => new url('/mod/' . $modname . '/index.php', ['id' => $course->id, 'forcedembedlayout' => 1]),
            'icon' => $OUTPUT->image_icon('monologo', $modfullname, $modname),
            'name' => $modfullname,
            'shortname' => $modname,
        ];
    }
}

$PAGE->set_pagelayout('incourse');

$output = $PAGE->get_renderer('format_' . $course->format);

$PAGE->set_title('Course overview');
$PAGE->set_heading('Course overview');
echo $output->header();

$data = (object) [
    'elements' => $elements,
];
echo $output->render_from_template('core_course/local/overview', $data);

echo $OUTPUT->footer();
