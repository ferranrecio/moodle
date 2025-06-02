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

namespace mod_h5pactivity\courseformat;

use cm_info;
use core_courseformat\local\overview\overviewitem;
use core\output\local\dropdown\dialog;
use core\output\local\properties\button;
use core\url;
use mod_h5pactivity\local\manager;

/**
 * Class overview
 *
 * @package    mod_h5pactivity
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /** @var manager $manager the manager instance. */
    private manager $manager;

    /**
     * Constructor.
     *
     * @param cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        cm_info $cm,
        /** @var \core_string_manager $sm the string manager */
        protected readonly \core_string_manager $sm,
    ) {
        // http://localhost/m/MDL-83895/course/overview.php?id=2&expand[]=h5pactivity
        parent::__construct($cm);
        $this->manager = manager::create_from_coursemodule($cm);
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'submitted' => $this->get_extra_type_overview(),
        ];
    }

    public function get_actions_overview(): overviewitem|null {
        if (!$this->manager->can_view_all_attempts()) {
            return null;
        }

        $attempscount = $this->manager->count_attempts();

        $users = $this->manager->count_users_attempts();
        if (empty($users)) {
            $avegare = null;
        } else {
            $totalusers = count($users);
            $avegare = (int) round($attempscount / $totalusers);
        }

        $content = $this->sm->get_string('attempts', 'mod_h5pactivity');

        $dialog = new dialog(
            buttoncontent: $attempscount,
            dialogcontent:$content,
            definition: [
                'classes' => 'mb-4',
                'buttonclasses' => 'dropdown-toggle' . button::SECONDARY_OUTLINE->classes(),
            ]
        );

        return new overviewitem(
            name: $this->sm->get_string('contenttype', 'mod_h5pactivity'),
            value: $attempscount,
            content: $dialog,
        );
    }

    /**
     * Get the H5P type overview item.
     *
     * @return overviewitem|null The overview item (or null if the user cannot complete the feedback).
     */
    private function get_extra_type_overview(): ?overviewitem {
        global $DB;

        if (!$this->manager->can_view_all_attempts()) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_h5pactivity', 'package', 0, 'id', false);
        $file = reset($files);

        $h5p = \core_h5p\api::get_content_from_pathnamehash($file->get_pathnamehash());

        if (empty($h5p)) {
            return new overviewitem(
                name: $this->sm->get_string('contenttype', 'mod_h5pactivity'),
                value: $this->sm->get_string('unkowntype', 'mod_h5pactivity'),
                content: $this->sm->get_string('unkowntype', 'mod_h5pactivity'),
            );
        }

        $h5plib = \core_h5p\api::get_library($h5p->mainlibraryid);

        // If the content is not yet deployed we cannot show the content type.
        if (empty($h5plib)) {
            return new overviewitem(
                name: $this->sm->get_string('contenttype', 'mod_h5pactivity'),
                value: $this->sm->get_string('unkowntype', 'mod_h5pactivity'),
                content: $this->sm->get_string('unkowntype', 'mod_h5pactivity'),
            );
        }

        return new overviewitem(
            name: $this->sm->get_string('contenttype', 'mod_h5pactivity'),
            value: $h5plib->title,
            content: $h5plib->title,
        );
    }
}
