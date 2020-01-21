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
 * The core_xapi test class for xAPI statements.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi;

use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Class xapi_handler testing dummie class.
 *
 * @package core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 */
class xapi_handler extends xapi_handler_base {

    /**
     * Convert a statmenet object into a Moodle xAPI Event. If a statement is accepted
     * by validate_statement the component must provide a event to handle that statement,
     * otherwise the statement will be rejected
     *
     * @param \stdClass $statement
     * @return ?\core\event\base a Moodle event to trigger
     */
    public function statement_to_event(\stdClass $statement): ?\core\event\base {
        // Validate verb.
        $validvalues = [
                'cook',
                'http://adlnet.gov/expapi/verbs/answered'
            ];
        if (!$this->check_valid_verb($statement, $validvalues)) {
            return null;
        }
        // Validate object.
        $validvalues = [
                'cake',
                'http://adlnet.gov/expapi/activities/example'
            ];
        if (!$this->check_valid_object($statement, $validvalues)) {
            return null;
        }
        $user = $this->get_user_from_agent($statement->actor);
        // Convert into a Moodle event.
        $minstatement = $this->minify_statement($statement);
        $params = array(
            'other' => $minstatement,
            'context' => context_system::instance()
        );
        return event\xapi_test_statement_post::create($params);
    }

    /**
     * Return true if group actor is enabled.
     *
     * NOTE: the use of a global is only for testing. We need to change
     * the behaviour from the PHPUnitTest to test all possible scenarios.
     *
     * Note: this method must be overridden by the plugins which want to
     * use groups in statements.
     *
     * @return bool
     */
    public function is_group_actor_enabled(): bool {
        global $CFG;
        if (isset($CFG->xapitestforcegroupactors)) {
            return $CFG->xapitestforcegroupactors;
        }
        return true;
    }

    /**
     * Testing method to make public minify statement for testing.
     *
     * @param \stdClass $statement
     * @return array the minimal statement needed to be stored a part from logstore data
     */
    public function testing_minify_statement(\stdClass $statement) {
        return $this->minify_statement ($statement);
    }
}
