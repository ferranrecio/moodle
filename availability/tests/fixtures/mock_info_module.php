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
 * For use in unit tests that require an info module which isn't really used.
 *
 * @package core_availability
 * @copyright 2019 Ferran Recio
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_availability;

defined('MOODLE_INTERNAL') || die();

/**
 * For use in unit tests that require an info module which isn't really used.
 *
 * @package core_availability
 * @copyright 2019 Ferran Recio <ferran@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_info_module extends info_module {
    /** @var int User id for modinfo */
    protected $userid;

    /** @var \cm_info Activity. */
    protected $cm;

    /**
     * Constructs with item details.
     *
     * @param \stdClass $course Optional course param (otherwise uses $SITE)
     * @param int $userid Userid for modinfo (if used)
     */
    public function __construct($userid = 0, \cm_info $cm = null) {
        global $SITE;
        parent::__construct($cm);
        $this->userid = $userid;
        $this->cm = $cm;
    }

    protected function get_thing_name() {
        return 'Mock Module';
    }

    public function get_context() {
        return \context_course::instance($this->get_course()->id);
    }

    protected function get_view_hidden_capability() {
        return 'moodle/course:ignoreavailabilityrestrictions';
    }

    protected function set_in_database($availability) {
    }

    public function get_modinfo() {
        // Allow modinfo usage outside is_available etc., so we can use this
        // to directly call into condition is_available.
        if (!$this->userid) {
            throw new \coding_exception('Need to set mock_info userid');
        }
        return get_fast_modinfo($this->course, $this->userid);
    }

    public function set_cm (\cm_info $cm) {
        $this->cm = $cm;
    }
}
