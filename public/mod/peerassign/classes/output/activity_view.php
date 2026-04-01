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
 * Renderable activity view output for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use mod_peerassign\manager;

/**
 * Renderable activity view output for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_view implements renderable, templatable {
    /** @var stdClass Peerassign record. */
    protected $peerassign;

    /** @var stdClass Course module record. */
    protected $cm;

    /**
     * Constructor.
     *
     * @param manager $manager Activity manager instance.
     */
    public function __construct(
        /** @var manager Activity manager instance. */
        protected manager $manager,
    ) {
        $this->peerassign = $this->manager->get_instance();
        $this->cm = $this->manager->get_coursemodule();
    }

    /**
     * Export template context.
     *
     * @param renderer_base $output Renderer instance.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $hasintro = !empty($this->peerassign->intro);

        return [
            'name' => format_string($this->peerassign->name),
            'hasintro' => $hasintro,
            'intro' => $hasintro ? format_module_intro(manager::MODULE, $this->peerassign, $this->cm->id) : '',
            'uiplaceholder' => get_string('activityuiplaceholder', manager::PLUGINNAME),
        ];
    }
}
