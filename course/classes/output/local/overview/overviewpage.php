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

namespace core_course\output\local\overview;

use core\context\course as context_course;
use core\output\named_templatable;
use core\output\renderable;
use core\output\notification;
use core\url;
use core_collator;
use stdClass;

/**
 * Class overview
 *
 * @package    core_course
 * @copyright  2024 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overviewpage implements renderable, named_templatable {
    /** @var context_course the context. */
    protected context_course $context;

    /**
    * Constructor.
    *
    * @param stdClass $course the course object.
    */
    public function __construct(
        /** @var stdClass the course object  */
        protected stdClass $course,
    ) {
        $this->context = context_course::instance($this->course->id);
    }

    #[\Override]
    public function export_for_template(\renderer_base $output): stdClass {
        $modfullnames = $this->get_course_activities_overview_list();

        $elements = [];
        foreach ($modfullnames as $modname => $modfullname) {
            $elements[] = $this->export_activity_overview_section_data($output, $modname, $modfullname);
        }

        return (object) [
            'elements' => $elements,
            'courseid' => $this->course->id,
            'contextid' => $this->context->id,
        ];
    }

    /**
     * Retrieves a list of course activities overview.
     *
     * @return string[] An associative array module name => module plural name.
     */
    private function get_course_activities_overview_list(): array {
        $modinfo = get_fast_modinfo($this->course);
        $modfullnames = [];
        $archetypes = [];

        foreach ($modinfo->cms as $cm) {
            // Exclude activities that aren't visible or have no view link (e.g. label).
            // Account for folder being displayed inline.
            if (!$cm->uservisible || (!$cm->has_view() && strcmp($cm->modname, 'folder') !== 0)) {
                continue;
            }
            if (array_key_exists($cm->modname, $modfullnames)) {
                continue;
            }
            if (!array_key_exists($cm->modname, $archetypes)) {
                $archetypes[$cm->modname] = plugin_supports(
                    type: 'mod',
                    name: $cm->modname,
                    feature: FEATURE_MOD_ARCHETYPE,
                    default: MOD_ARCHETYPE_OTHER
                );
            }
            if ($archetypes[$cm->modname] == MOD_ARCHETYPE_RESOURCE) {
                if (!array_key_exists('resource', $modfullnames)) {
                    $modfullnames['resource'] = get_string('resources');
                }
            } else {
                $modfullnames[$cm->modname] = $cm->modplural;
            }
        }

        core_collator::asort($modfullnames);
        return $modfullnames;
    }

    /**
     * Exports the data for the activity overview section.
     *
     * This function checks if the activity has an overview integration,
     * and return the data accordingly.
     *
     * @param \renderer_base $output
     * @param string $modname The name of the module.
     * @param string $modfullname The full name of the module.
     * @return stdClass The exported data for the activity overview section.
     */
    private function export_activity_overview_section_data(
        \renderer_base $output,
        string $modname,
        string $modfullname
    ): stdClass {
        if (!$this->activity_has_overview_integration($modname)) {
            return $this->export_legacy_overview($output, $modname, $modfullname);
        }

        return (object)[
            'fragment' => $this->export_overview_fragment($modname),
            'icon' => $this->get_activity_overview_icon($output, $modname),
            'name' => $modfullname,
            'shortname' => $modname,
        ];
    }

    /**
     * Generates the activity overview icon for a given module.
     *
     * @param \renderer_base $output
     * @param string $modname The name of the module for which the icon is being generated.
     * @return string The HTML string for the activity overview icon.
     */
    private function get_activity_overview_icon(\renderer_base $output, string $modname): string {
        /** @var \core\output\core_renderer $output */
        if ($modname === 'resource') {
            return $output->pix_icon('monologo', '', 'mod_page', ['class' => 'icon iconsize-medium']);
        }

        return $output->pix_icon('monologo', '', "mod_$modname", ['class' => 'icon iconsize-medium']);
    }

    /**
     * Checks if a given activity module has an overview integration.
     *
     * The method search for an integration class named `\mod_{modname}\course\overview`.
     *
     * @param string $modname The name of the activity module.
     * @return bool True if the activity module has an overview integration, false otherwise.
     */
    private function activity_has_overview_integration(string $modname): bool {
        $classname = 'mod_' . $modname . '\course\overview';
        if ($modname === 'resource') {
            $classname = 'core_course\local\overview\resourceoverview';
        }
        return class_exists($classname);
    }

    /**
     * Exports an overview fragment for a given module name.
     *
     * This function creates and returns an object containing details
     * about the course overview fragment for the specified module.
     *
     * @param string $modname
     * @return stdClass The exported overview fragment data.
     */
    private function export_overview_fragment(string $modname): stdClass {
        return (object)[
            'component' => 'core_course',
            'method' => 'overview_table',
            'course' => $this->course,
            'modname' => $modname,
        ];
    }

    /**
     * Exports the legacy overview for a given module.
     *
     * This export only applies to modules that do not have an overview integration.
     *
     * @param \renderer_base $output
     * @param string $modname
     * @param string $modfullname
     * @return stdClass
     */
    private function export_legacy_overview(
        \renderer_base $output,
        string $modname,
        string $modfullname
    ): stdClass {
        $legacyoverview = ($modname === 'resource') ? 'resources.php' : '/mod/' . $modname . '/index.php';

        $notificaiton = new notification(
            message: get_string('overview_missing_notice', 'core_course'),
            messagetype: \core\notification::INFO,
            closebutton: false,
            title: get_string('overview_missing_title', 'core_course', $modfullname),
            titleicon: 'i/circleinfo',
        );

        return (object)[
            'overviewurl' => new url($legacyoverview, ['id' => $this->course->id]),
            'icon' => $this->get_activity_overview_icon($output, $modname),
            'name' => $modfullname,
            'shortname' => $modname,
            'notification' => $notificaiton->export_for_template($output),
        ];
    }

    /**
    * Get the name of the template to use for this templatable.
    *
    * @param \renderer_base $renderer The renderer requesting the template name
    * @return string
    */
    public function get_template_name(\renderer_base $renderer): string {
        return 'core_course/local/overview/overviewpage';
    }
}
