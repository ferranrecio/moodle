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
 * The core_xapi statement validation and tansformation.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi;

use stdClass;
use InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

/**
 * Class handler handles basic xapi statements.
 *
 * @package core_xapi
 * @copyright  2020 Ferran Recio
 */
class handler {

    /** @var string last check error. */
    protected $lasterror;

    /** @var string component name in frankenstyle. */
    protected $component;

    /**
     * Constructor for a xAPI handler base class.
     *
     * @param string $component the component name
     */
    final public function __construct(string $component) {
        $this->lasterror = '';
        $this->component = $component;
    }

    /**
     * Returns the xAPI handler of a specific component.
     *
     * @param string $component the component name in frankenstyle.
     * @return handler|null a handler object or null if none found.
     * @throws InvalidArgumentException
     */
    final static function create(string $component): self {
        $classname = "\\$component\\xapi\\handler";
        if (class_exists($classname)) {
          return new $classname($component);
        }
        throw new InvalidArgumentException('Unknown handler');
    }

    /**
     * Convert a statmenet object into a Moodle xAPI Event. If a statement is accepted
     * by validate_statement the component must provide a event to handle that statement,
     * otherwise the statement will be rejected.
     *
     * Note: this method must be overridden by the plugins which want to use xAPI.
     *
     * @param stdClass $statement
     * @return \core\event\base|null a Moodle event to trigger
     */
    public function statement_to_event(stdClass $statement): ?\core\event\base {
        return null;
    }

    /**
     * Return true if group actor is enabled.
     *
     * Note: this method must be overridden by the plugins which want to
     * use groups in statements.
     *
     * @return bool
     */
    public function supports_group_actors(): bool {
        return false;
    }

    /**
     * Return the last error occured during the last xAPI statement validation done.
     *
     * @return string the last error message.
     */
    public function get_last_error_msg(): string {
        return $this->lasterror;
    }

    /**
     * Process a bunch of statements sended to a specific component.
     *
     * @param stdClass[] $statements an array with all statement to process.
     * @return int[] return an specifying what statements are being stored.
     */
    public function process_statements(array $statements): array {
        $result = [];
        // All Statement need to be accepted before process them.
        foreach ($statements as $key => $statement) {
            $this->lasterror = '';
            // All users in the statement must exists.
            if (!$this->check_statement_actor($statement)) {
                $result[$key] = false;
                continue;
            }
            // Ask the plugin to convert into an event.
            $event = $this->statement_to_event($statement);
            if ($event) {
                $event->trigger();
                // TODO: process result atribute and give to the component.
                $result[$key] = true;
            } else {
                $result[$key] = false;
            }
        }
        return $result;
    }

    /**
     * Check if the field actor conains only valid users and if tyhe current user
     * is in the list.
     *
     * Note: Group actors will only be available if supports_group_actors.
     *
     * @param stdClass $statement The statement object.
     * @return bool True if all users are valid and $USER is in the list.
     */
    private function check_statement_actor(stdClass $statement): bool {
        global $USER;
        if ($this->supports_group_actors()) {
            $users = $this->get_all_users($statement);
            if (!empty($users) && isset($users[$USER->id])) {
                return true;
            } else {
                $this->lasterror = "current user is not an actor of the statement";
            }
        } else {
            $user = $this->get_user($statement);
            if ($user && $user->id == $USER->id) {
                return true;
            } else {
                $this->lasterror = "statement Agent is not the current user";
            }
        }
        return false;
    }

    /**
     * Check if the value of the object field is permitted.
     *
     * @param stdClass $statement The statement object.
     * @param string[] $validvalues Array of possible object IDs.
     * @return string|null The current object or null if it is not a valid one.
     */
    public function check_valid_object(stdClass $statement, array $validvalues): ?string {
        $token = $this->get_object($statement->object);
        if (in_array($token, $validvalues)) {
            return $token;
        }
        $this->lasterror = "invalid object $token";
        return null;
    }

    /**
     * Check if the value of the object field is permitted.
     *
     * @param stdClass $statement The statement object.
     * @param string[] Array of possible object IDs.
     * @return string|null The current verb or null if it is not a valid one.
     */
    public function check_valid_verb(stdClass $statement, array $validvalues): ?string {
        $token = $this->get_verb($statement->verb);
        if (in_array($token, $validvalues)) {
            return $token;
        }
        $this->lasterror = "invalid verb $token";
        return null;
    }

    /**
     * Try to get a Moodle user from a xAPI element (typical the actor attribute).
     *
     * @param stdClass $actor the xAPI node to extract users (full statement or a actor/object node).
     * @return stdClass|null a Moodle user record or null if there isn't just one user.
     */
    public function get_user(stdClass $actor): ?stdClass {
        $users = $this->get_all_users($actor);
        if (empty($users) || count($users) != 1) {
            $this->lasterror = "invalid Agent or Group entity";
            return null;
        }
        return reset($users);
    }

    /**
     * Try to get a list of Moodle users from a xAPI element (typical the actor attribute).
     *
     * @param stdClass $actor the xAPI node to extract users (full statement or a actor/object node).
     * @return stdClass[]|null array of Moodle user records or null if ANY of the
     * users does not exist.
     */
    public function get_all_users(stdClass $actor): ?array {
        if (isset($actor->actor)) {
            return $this->get_all_users($actor->actor);
        }
        if (!isset($actor->objectType)) {
            $this->lasterror = "missing Agent or Group";
            return null;
        }
        switch ($actor->objectType) {
            case 'Group':
                $result = $this->get_users_from_agent_group($actor);
                break;
            default:
                $user = $this->get_user_from_agent($actor);
                if (empty($user)) {
                    $this->lasterror = "Agent not found";
                    return null;
                }
                $result = [$user->id => $user];
        }
        return $result;
    }

    /**
     * Try to convert an xAPI agent to a user record
     *
     * Note: for now, only 'mbox' and 'account' are supported
     *
     * @param stdClass $agent the xAPI agent structure.
     * @return stdClass|null user record if found, else null.
     */
    protected function get_user_from_agent(stdClass $agent): ?stdClass {
        global $CFG;
        if (!empty($agent->account)) {
            if ($agent->account->homePage != $CFG->wwwroot) {
                return null;
            }
            if (!is_numeric($agent->account->name)) {
                return null;
            }
            $result = \core_user::get_user($agent->account->name);
            if (empty($result)) {
                return null;
            }
        }
        if (!empty($agent->mbox)) {
            $mbox = str_replace('mailto:', '', $agent->mbox);
            $result = \core_user::get_user_by_email($mbox);
            if (empty($result)) {
                return null;
            }
        }
        return $result;
    }

    /**
     * Returns an array of users defined by a group.
     *
     * NOTE: anonymous groups are not allowed so "member" attribute is ignored.
     *
     * @param stdClass $group a group xAPI structure.
     * @return array[stdClass]|null array of users or null if ANY user does not exist.
     */
    protected function get_users_from_agent_group(stdClass $group): ?array {
        $grouprecord = $this->get_group($group);
        if (!$grouprecord) {
            return null;
        }
        $result = groups_get_members($grouprecord->id);
        if (empty($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Returns a moodle group record from an group xAPI field.
     *
     * @param stdClass $group a group xAPI structure.
     * @return stdClass|null group record of null if none found.
     */
    public function get_group(stdClass $group): ?stdClass {
        global $CFG;
        if (isset($group->actor)) {
            return $this->get_group($group->actor);
        }
        if ($group->objectType != 'Group') {
            return null;
        }
        if ($group->account->homePage != $CFG->wwwroot) {
            return null;
        }
        $group = groups_get_group($group->account->name);
        if (empty($group)) {
            return null;
        }
        return $group;
    }

    /**
     * Try to convert an xAPI object to a token record.
     *
     * @param stdClass $object Statement object (or full statement).
     * @return string|null The original object ID used as a xAPI object or null.
     */
    public function get_object(stdClass $object): ?string {
        if (isset($object->object)) {
            return $this->get_object($object->object);
        }
        if (empty($object->id) || $object->objectType != "Activity") {
            return null;
        }
        return helper::extract_iri_value($object->id, 'object');
    }

    /**
     * Try to convert an xAPI verb to a token record.
     *
     * @param stdClass $verb Statement verb (or full statement).
     * @return string|null The original verb ID used as a xAPI verb or null.
     */
    public function get_verb(stdClass $verb): ?string {
        if (isset($verb->verb)) {
            return $this->get_verb($verb->verb);
        }
        if (empty($verb->id)) {
            return null;
        }
        return helper::extract_iri_value($verb->id, 'verb');
    }

    /**
     * Returns a minified version of a given statement to store in the "other" field
     * of logstore. xAPI standard specifies a list of attributes that can be calculated
     * instead of stored literally. This function get rid of these attributes.
     *
     * Note: it also converts stdClass to assoc array to make it compatible
     * with "other" field in the logstore
     *
     * @param stdClass $statement
     * @return array the minimal statement needed to be stored a part from logstore data
     */
    protected function minify_statement(stdClass $statement): ?array {
        $result = clone($statement);
        $calculatedfields = ['actor', 'id', 'timestamp', 'stored', 'version'];
        foreach ($calculatedfields as $field) {
            if (isset($result->$field)) {
                unset($result->$field);
            }
        }
        return json_decode(json_encode($result), true);
    }
}
