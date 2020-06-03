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
 * Contains class mod_h5pactivity\output\result\longfillin
 *
 * @package   mod_h5pactivity
 * @copyright 2020 Ferran Recio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_h5pactivity\output\result;

defined('MOODLE_INTERNAL') || die();

use mod_h5pactivity\output\result;
use renderer_base;
use stdClass;

/**
 * Class to display H5P long fill in result.
 *
 * @copyright 2020 Ferran Recio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class longfillin extends result {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $result = $this->result;
        $data = parent::export_for_template($output);
        $data->content = reset($this->response);
        // Check if description have HTML tags on it.
        if (strlen($result->description) != strlen(strip_tags($result->description))) {
            $data->description = get_string('result_longfillin', 'mod_h5pactivity');
            $longcontent = (object)[
                'description' => format_text($result->description),
                'response' => $data->content,
            ];
            $data->content = $output->render_from_template('mod_h5pactivity/result/longfillincontent', $longcontent);
        }
        $data->track = true;
        return $data;
    }
}
