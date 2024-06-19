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

namespace core_report\output;

/**
 * Course sections, subsections and activities structure for reports.
 *
 * @package    core_report
 * @copyright  2024 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursestructure implements \renderable, \templatable {

    /**
     * @var \course_modinfo
     */
    protected $modinfo;

    /**
     * Constructor
     *
     * @param \course_modinfo $modinfo
     */
    public function __construct(\course_modinfo $modinfo) {
        $this->modinfo = $modinfo;
    }

    /**
     * Exports the data.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output) {

        $headers = $this->export_headers($output);
        $data = [
            'class' => 'generaltable boxaligncenter no-bg',
            'headers' => $headers,
            'headerscount' => count($headers),
            'actitivities' => [],
        ];

        $delegatedsections = $this->modinfo->get_sections_delegated_by_cm();
        $allsections = $this->modinfo->get_sections();
        foreach ($allsections as $sectionnum => $sectionmodules) {
            // Add the section row.
            if ($sectionnum > 0) {
                $sectioninfo = $this->modinfo->get_section_info($sectionnum);

                // Don't show subsections here. We are showing them in the corresponding module.
                if (!is_null($sectioninfo->component)) {
                    continue;
                }

                if (!$sectioninfo->uservisible) {
                    continue;
                }

                $data['activities'][] = $this->export_section_data($output, $sectioninfo, false);
            }

            // Add section modules and possibly subsections.
            foreach ($sectionmodules as $cmid) {
                $cm = $this->modinfo->cms[$cmid];

                // Check if the module is delegating a section.
                if (key_exists($cm->id, $delegatedsections)) {
                    $subsectioninfo = $delegatedsections[$cm->id];
                    // Only non-empty are listed in allsections. We don't show empty sections.
                    if (!array_key_exists($subsectioninfo->sectionnum, $allsections)) {
                        continue;
                    }

                    $data['activities'][] = $this->export_section_data($output, $subsectioninfo, true);

                    // Show activities inside the section.
                    $subsectionmodules = $allsections[$subsectioninfo->sectionnum];
                    foreach ($subsectionmodules as $subsectioncmid) {
                        $cm = $this->modinfo->cms[$subsectioncmid];
                        $data['activities'][] = $this->export_activity_data($output, $cm, true);
                    }
                } else {
                    // It's simply a module.
                    $data['activities'][] = $this->export_activity_data($output, $cm);
                }
            }
        }

        return $data;
    }

    /**
     * Exports the headers for report table.
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function export_headers(\renderer_base $output): array {
        return [get_string('activity')];
    }


    /**
     * Exports the data for a single section.
     *
     * @param \renderer_base $output
     * @param \section_info $sectioninfo Section to export information from.
     * @param bool $isdelegated Whether the section is a delegated subsection or not.
     * @return array
     */
    public function export_section_data(\renderer_base $output, \section_info $sectioninfo, bool $isdelegated = false): array {
        $datasection = [
            'issection' => true,
            'isdelegated' => $isdelegated,
            'visible' => $sectioninfo->visible,
            'class' => 'section',
            'text' => get_section_name($sectioninfo->course, $sectioninfo->sectionnum),
        ];

        return $datasection;
    }

    /**
     * Exports the data for a single activity.
     *
     * @param \renderer_base $output
     * @param \cm_info $cm
     * @param bool $indelegated Whether the activity is part of a delegated section or not.
     * @return array
     */
    public function export_activity_data(\renderer_base $output, \cm_info $cm, bool $indelegated = false): array {
        global $CFG;

        if (!$cm->has_view()) {
            return [];
        }
        if (!$cm->uservisible) {
            return [];
        }

        $modulename = get_string('modulename', $cm->modname);

        $dataactivity = [
            'isactivity' => true,
            'indelegated' => $indelegated,
            'visible' => $cm->visible,
            'cells' => [],
        ];
        $dataactivity['activitycolum'] = [
                'activityicon' => $output->pix_icon('monologo', $modulename, $cm->modname, ['class' => 'icon']),
                'link' => "$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id",
                'text' => $cm->name,
        ];
        $dataactivity['cells'] = [];
        return $dataactivity;
    }
}
