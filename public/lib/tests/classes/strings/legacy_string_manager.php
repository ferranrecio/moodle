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

/**
 * Legacy string manager for testing purposes.
 *
 * @package    core
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class legacy_string_manager extends \core\strings\standard_string_manager {
    /**
     * Simulate the legacy string manager constructor with many parameters.
     *
     * @param string $otherroot location of downloaded lang packs - usually $CFG->dataroot/lang
     * @param string $localroot usually the same as $otherroot
     * @param array $translist limit list of visible translations
     * @param array $transaliases aliases to use for the languages in the language selector
     */
    public function __construct(
        string $otherroot,
        string $localroot,
        array $translist,
        array $transaliases = [],
    ) {
        $this->otherroot = $otherroot;
        $this->localroot = $localroot;
        $this->translist = array_combine($translist, $translist);
        $this->transaliases = $transaliases;
        parent::__construct();
    }
}
