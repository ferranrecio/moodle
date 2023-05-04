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

namespace core_enrol\form;

/**
 * Form to customise the course role names.
 *
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renameroles extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $defaults = $this->_customdata['rolenames'];

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        if ($roles = get_all_roles()) {
            $roles = role_fix_names($roles, null, ROLENAME_ORIGINAL);
            // $assignableroles = get_roles_for_contextlevels(CONTEXT_COURSE);
            foreach ($roles as $role) {
                $settingsname = 'role_' . $role->id;
                $mform->addElement('text', $settingsname, get_string('yourwordforx', '', $role->localname));
                $mform->setType($settingsname, PARAM_TEXT);
                if (isset($defaults[$role->id])) {
                    $mform->setDefault($settingsname, $defaults[$role->id]);
                }
            }
        }

        $mform->addElement('submit', 'submit', get_string('update'));
    }
}
