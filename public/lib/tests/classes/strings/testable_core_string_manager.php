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

namespace core\tests\strings;

use core\strings\standard_string_manager;

/**
 * Helper class providing testable string_manager.
 *
 * @package    core
 * @copyright 2013 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_core_string_manager extends standard_string_manager {
    /**
     * Factory method
     *
     * @param string $otherroot full path to the location of installed upstream language packs
     * @param string|null $localroot full path to the location of locally customized language packs, defaults to $otherroot
     * @return testable_core_string_manager
     */
    public static function instance(
        string $otherroot,
        ?string $localroot = null,
    ): self {
        global $CFG;

        if (is_null($otherroot)) {
            $otherroot = $CFG->langotherroot;
        }

        if (is_null($localroot)) {
            $localroot = $otherroot;
        }

        $manager = \core\di::get_container()->make(self::class);

        return $manager
            ->set_other_root($otherroot)
            ->set_local_root($localroot);
    }

    /**
     * Set the other root directory.
     *
     * @param string $otherroot The location of downloaded lang packs - usually $CFG->dataroot/lang
     * @return self
     */
    public function set_other_root(string $otherroot): self {
        $this->otherroot = $otherroot;
        return $this;
    }

    /**
     * Set the local root directory.
     *
     * @param string $localroot The location of locally customized lang packs - usually $CFG->dataroot/lang
     * @return self
     */
    public function set_local_root(string $localroot): self {
        $this->localroot = $localroot;
        return $this;
    }

    /**
     * Get all deprecated strings.
     *
     * @return array
     */
    public function get_all_deprecated_strings() {
        return array_flip($this->load_deprecated_strings());
    }
}
