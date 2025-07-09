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

namespace core_courseformat\external;

use core\external\exporter;
use renderer_base;

/**
 * The overviewtable output data exporter for Webservice.
 *
 * This class is used to define the get_overview_information web service response structure
 * and normalize the data for external use.
 *
 * @package    core_courseformat
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overviewtable_exporter extends exporter {
    #[\Override]
    protected static function define_properties(): array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'description' => 'The ID of the course this overview table belongs to.',
            ],
            'hasintegration' => [
                'type' => PARAM_BOOL,
                'null' => NULL_NOT_ALLOWED,
                'description' => 'Indicates if there is any integration available for this overview table.',
            ],
            'headers' => [
                'type' => [
                    'name' => [
                        'type' => PARAM_TEXT,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'The name of the header.',
                    ],
                    'key' => [
                        'type' => PARAM_TEXT,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'The key of the header, used to identify it.',
                    ],
                    'textalign' => [
                        'type' => PARAM_TEXT,
                        'null' => NULL_ALLOWED,
                        'default' => null,
                        'description' => 'The text alignment of the header.',
                    ],
                ],
                'multiple' => true,
            ],
        ];
    }

    #[\Override]
    protected static function define_related() {
        return [
            'context' => 'context',
        ];
    }

    #[\Override]
    protected static function define_other_properties() {
        return [
            'activities' => [
                'type' => [
                    'name' => [
                        'type' => PARAM_TEXT,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'The name of the activity.',
                    ],
                    'modname' => [
                        'type' => PARAM_PLUGIN,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'The module name of the activity.',
                    ],
                    'contextid' => [
                        'type' => PARAM_INT,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'The context ID of the activity.',
                    ],
                    'cmid' => [
                        'type' => PARAM_INT,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'The course module ID of the activity.',
                    ],
                    'url' => [
                        'type' => PARAM_URL,
                        'null' => NULL_ALLOWED,
                        'default' => null,
                        'description' => 'The URL of the activity, if available.',
                    ],
                    'haserror' => [
                        'type' => PARAM_BOOL,
                        'null' => NULL_NOT_ALLOWED,
                        'description' => 'Indicate if the activity has an error.',
                    ],
                    'items' => [
                        'type' => overviewitem_exporter::read_properties_definition(),
                        'multiple' => true,
                        'description' => 'The items associated with the activity, exported using overviewitem_exporter.',
                    ],
                ],
                'multiple' => true,
            ],
        ];
    }

    #[\Override]
    protected function get_other_values(renderer_base $output) {
        $othervalues = [
            'activities' => $this->export_activities($output),
        ];
        return $othervalues;
    }

    /**
     * Export the activities data.
     *
     * @param renderer_base $output The renderer to use for exporting.
     * @return array The exported activities data.
     */
    private function export_activities(renderer_base $output): array {
        $activities = [];
        foreach ($this->data->overviews as $overview) {
            $activityinfo = (object)[
                'name' => $overview->name,
                'modname' => $overview->modname,
                'contextid' => $overview->contextid,
                'cmid' => $overview->cmid,
                'url' => $overview->url ?? null,
                'haserror' => $overview->haserror ?? false,
            ];

            $items = [];
            foreach ($overview->items as $item) {
                $exporter = new overviewitem_exporter($item, ['context' => $this->related['context']]);
                $items[] = $exporter->export($output);
            }
            $activityinfo->items = $items;
            $activities[] = $activityinfo;
        }
        return $activities;
    }
}
