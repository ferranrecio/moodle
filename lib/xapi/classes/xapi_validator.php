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
 * This is the library for xAPI Statement validation.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi;
defined('MOODLE_INTERNAL') || die();

/**
 * This is the library for xAPI Statement validation.
 *
 * @copyright  2020 Ferran Recio
 * @since      Moodle 3.9
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xapi_validator {

    /** @var string last check error. */
    private $lastrerror;

    /** @var int index of the last statement checked. */
    private $lastcheck;

    /** @var \core_xapi\xapi_handler_base to check users and groups. */
    private $xapihandler;

    /**
     * Initialize a xapi_validator instance.
     *
     */
    public function __construct() {
        $this->lastrerror = '';
        $this->lastcheck = 0;
        $this->xapihandler = new \core_xapi\xapi_handler_base('core_xapi');
    }

    /**
     * Convert mulitple types of statement rquest into an array of statements.
     * @param string $requestjson json encoded statements structure
     * @return array(statements) | null
     */
    public function get_statements_form_json($requestjson): ?array {
        $request = json_decode($requestjson);
        if ($request === null) {
            $this->lastrerror = 'JSON parse, '.json_last_error_msg();
            return null;
        }
        $statements = $this->get_statements_form_request($request);
        if (empty($statements)) {
            return null;
        }
        return $statements;
    }

    /**
     * Convert mulitple types of statement rquest into an array of statements.
     * @param mixed $request json decoded statements structure
     * @return array(statements) | null
     */
    public function get_statements_form_request($request): ?array {
        $result = array();
        if (is_array($request)) {
            $this->lastcheck = 0;
            foreach ($request as $key => $value) {
                $statement = $this->get_statements_form_request ($value);
                if (empty($statement)) {
                    return null;
                }
                $result = array_merge($result, $statement);
                $this->lastcheck++;
            }
        } else {
            // Check if it's real statement or we need to go deeper in the structure.
            if (!$this->validate_statement ($request)) {
                return null;
            }
            $result[] = $request;
        }
        if (empty($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Return the last error occured during the last xAPI statement validation.
     *
     * @return string the last error message.
     */
    public function get_last_error_msg(): string {
        return $this->lastrerror;
    }

    /**
     * Return the index of the statement that generates the last error mesasage.
     *
     * @return int The index of the last statement checked.
     */
    public function get_last_check_index(): int {
        return $this->lastcheck;
    }

    /**
     * Basic xAPI statement structure validation. This will ensure that mandatory
     * fields are created so rest of the logic could avoid tons of calls to isset and empty.
     *
     * NOTE: For now this validator only check for supported statements. In the future this kind
     * of validation should be done with a more complex json schema validator
     * if more scenarios are supported.
     *
     * @param \stdClass $statement json decoded statement structure
     * @return bool
     */
    private function validate_statement(\stdClass $statement): bool {
        $requiredfields = ['actor', 'verb', 'object'];
        foreach ($requiredfields as $required) {
            if (empty($statement->$required)) {
                $this->lastrerror = "missing $required";
                return false;
            }
            $validatefunction = 'validate_'.$required;
            if (!$this->$validatefunction($statement->$required)) {
                return false;
            }
        }
        return true;
    }

    /**
     * check Agent minimal atributes (note: only Agent and Group supported).
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private function validate_actor(\stdClass $field): bool {
        if (empty($field->objectType)) {
            $field->objectType = 'Agent';
        }
        switch ($field->objectType) {
            case 'Agent':
                return $this->validate_agent($field);
            case 'Group':
                return $this->validate_group($field);
        }
        $this->lastrerror = "unsupported actor $field->objectType";
        return false;
    }

    /**
     * check Verb minimal atributes.
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private function validate_verb(\stdClass $field): bool {
        if (empty($field->id)) {
            $this->lastrerror = "missing verb id";
            return false;
        }
        if (!\core_xapi\xapi_helper::check_iri_value($field->id)) {
            $this->lastrerror = "verb id $field->id is not a valid IRI";
            return false;
        }
        return true;
    }

    /**
     * check Object minimal atributes (Note: for now only Activity supported).
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private function validate_object(\stdClass $field): bool {
        if (empty($field->objectType)) {
            $field->objectType = 'Activity';
        }
        switch ($field->objectType) {
            case 'Agent':
                return $this->validate_agent($field);
            case 'Group':
                return $this->validate_group($field);
            case 'Activity':
                if (empty($field->id)) {
                    $this->lastrerror = "missing Activity id";
                    return false;
                }
                if (!\core_xapi\xapi_helper::check_iri_value($field->id)) {
                    $this->lastrerror = "Activity id $field->id is not a valid IRI";
                    return false;
                }
                if (!empty($field->definition)) {
                    return $this->validate_definition($field->definition);
                }
                return true;
        }
        $this->lastrerror = "unsupported object type $field->objectType";
        return false;
    }

    /**
     * check Agent minimal atributes (note: mbox_sha1sum and openid not suported).
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private function validate_agent(\stdClass $field): bool {
        $unsupported = ['mbox_sha1sum', 'openid'];
        foreach ($unsupported as $attribute) {
            if (isset($field->$attribute)) {
                $this->lastrerror = "unsupported Actor $attribute";
                return false;
            }
        }
        $requiredfields = ['mbox' => [], 'account' => ['homePage', 'name']];
        $found = 0;
        foreach ($requiredfields as $required => $atributes) {
            if (!empty($field->$required)) {
                $found++;
                foreach ($atributes as $atribute) {
                    if (empty($field->$required->$atribute)) {
                        $this->lastrerror = "missing $required $atribute";
                        return false;
                    }
                }
            }
        }
        if ($found != 1) {
            $this->lastrerror = "more than one Agent identifier found";
            return false;
        }
        $user = $this->xapihandler->get_user($field);
        if (empty($user)) {
            $this->lastrerror = "Agent user not found";
            return false;
        }
        return true;
    }

    /**
     * Check Group minimal atributes, validating also group members as Agents.
     *
     * NOTE: anonymous group are not allowed (member attributes will be ignored)
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private function validate_group(\stdClass $field): bool {
        if (isset($field->member)) {
            $this->lastrerror = "anonynous groups are not supported";
            return false;
        }
        if (!isset($field->account)) {
            $this->lastrerror = "missing Group account";
            return false;
        }
        if (empty($field->account->homePage) || empty($field->name)) {
            $this->lastrerror = "invalid group account attribute";
            return false;
        }
        $group = $this->xapihandler->get_group($field);
        if (empty($group)) {
            $this->lastrerror = "Group not found";
            return false;
        }
        return true;
    }

    /**
     * check Object Defintion minimal atributes.
     *
     * Note: validate specific interactionType is delegated to plugins for now.
     *
     * @param \stdClass $field the specific statement atribute to check
     * @return bool
     */
    private function validate_definition(\stdClass $field): bool {
        if (empty($field->interactionType)) {
            return false;
        }
        $posiblevalues = [
            'choice' => true,
            'fill-in' => true,
            'long-fill-in' => true,
            'true-false' => true,
            'matching' => true,
            'performance' => true,
            'sequencing' => true,
            'likert' => true,
            'numeric' => true,
            'other' => true,
            'compound' => true,
        ];
        if (!isset($posiblevalues[$field->interactionType])) {
            $this->lastrerror = "definition unsupported $field->interactionType";
            return false;
        }
        return true;
    }

}
