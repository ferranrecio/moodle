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
 * The xapi_handler for xAPI statements.
 *
 * @package    mod_h5pactivity
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_h5pactivity;

use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Class xapi_handler for H5P statements.
 *
 * @package mod_h5pactivity
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 */
class xapi_handler extends \core_xapi\xapi_handler_base {

    /**
     * Convert a statmenet object into a Moodle xAPI Event. If a statement is accepted
     * by validate_statement the component must provide a event to handle that statement,
     * otherwise the statement will be rejected
     *
     * @param \stdClass $statement
     * @return ?\core\event\base a Moodle event to trigger
     */
    public function statement_to_event(\stdClass $statement): ?\core\event\base {
        // Only process statements with results.
        if (!isset($statement->result)) {
            return null;
        }

        // Validate verb.
        $validvalues = [
                'http://adlnet.gov/expapi/verbs/answered',
                'http://adlnet.gov/expapi/verbs/completed',
            ];
        $xapiverb = $this->check_valid_verb($statement, $validvalues);
        if (!$xapiverb) {
            return null;
        }

        // Validate object.
        $xapiobject = $this->get_object($statement);

        // H5P add some extra params to ID to define subcontents.
        $parts = explode('?', $xapiobject, 2);
        $contextid = array_shift($parts);
        $subcontent = str_replace('subContentId=', '', array_shift($parts));
        if (empty($contextid) || !is_numeric($contextid)) {
            return null;
        }
        $context = \context::instance_by_id($contextid);
        if (!$context instanceof \context_module) {
            return null;
        }
        $cm = get_coursemodule_from_id('h5pactivity', $context->instanceid, 0, false);
        if (!$cm) {
            return null;
        }

        // Validate user.
        $user = $this->get_user_from_agent($statement->actor);
        if (!has_capability('mod/h5pactivity:view', $context)) {
            return null;
        }

        // Save result.
        if (empty($subcontent)) {
            $attempt = attempt::new_attempt($user, $cm);
        } else {
            $attempt = attempt::last_attempt($user, $cm);
        }
        if (!$attempt) {
            return null;
        }
        $result = $attempt->save_statement($statement, $subcontent);
        if (!$result) {
            if (!$attempt->count_results()) {
                attempt::delete_attempt($attempt);
            }
            return null;
        }

        // TODO: update grading if necessary.

        // Convert into a Moodle event.
        $minstatement = $this->minify_statement($statement);
        $params = array(
            'other' => $minstatement,
            'context' => $context,
            'objectid' => $cm->instance,
        );
        return event\statement_received::create($params);
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
