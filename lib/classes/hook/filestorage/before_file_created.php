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

namespace core\hook\filestorage;

use core\attribute;

/**
 * Class before_file_created
 *
 * @package   core
 * @copyright Meirza <meirza.arson@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[attribute\label('Allows subscribers to modify file before it is created')]
#[attribute\tags('file')]
#[attribute\hook\replaces_callbacks('before_file_created')]
final class before_file_created {
    /**
     * Hook to allow subscribers to modify file before it is created.
     *
     * @param \stdClass|null $filerecord File record.
     * @param array $extra Extra information (pathname and content) from the hook.
     */
    public function __construct(
        /** @var \stdClass|null $filerecord File record. */
        public readonly ?\stdClass $filerecord,
        /** @var array $extra Extra information (pathname and content) from the hook. */
        public readonly array $extra,
    ) {
    }
}
