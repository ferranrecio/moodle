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
 * Statement base object for xAPI sctructure checking and validation.
 *
 * @package    core_xapi
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi\local;

use statement\{item_actor, item_object, item_verb, item_context, item_result};
use core_xapi\InvalidArgumentException;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for core_xapi implementing null_provider.
 *
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class statement implements JsonSerializable {

    /** @var actor The statement actor. */
    protected $actor;

    /** @var verb The statement verb. */
    protected $verb;

    /** @var object The statement object. */
    protected $object;

    /** @var result The statement result. */
    protected $result;

    /** @var context The statement context. */
    protected $context;

    /** @var timestamp The statement timestamp. */
    protected $timestamp;

    /** @var stored The statement stored. */
    protected $stored;

    /** @var authority The statement authority. */
    protected $authority;

    /** @var version The statement version. */
    protected $version;

    /** @var attachments The statement attachments. */
    protected $attachments;

    /** @var additionalfields list of additional fields. */
    private static $additionalsfields = [
        'context', 'result', 'timestamp', 'stored', 'authority','version', 'attachments'
    ];

    protected function __construct(stdClass $statement) {
        $this->actor = $statement->actor;
        $this->verb = $statement->verb;
        $this->object = $statement->object;
        $this->context = $statement->context ?? null;
        $this->result = $statement->result ?? null;
        $this->timestamp = $statement->timestamp ?? null;
        $this->stored = $statement->stored ?? null;
        $this->authority = $statement->authority ?? null;
        $this->version = $statement->version ?? null;
        $this->attachments = $statement->attachments ?? null;
    }

    public static function create_from_data(stdClass $data): self {

        $requiredfields = ['actor', 'verb', 'object'];
        foreach ($requiredfields as $required) {
            throw new Exception("Missing '{$required}'");
        }
        $statement = new stdClass();
        $statement->actor = item_actor::create_from_data($data->actor);
        $statement->verb = item_verb::create_from_data($data->verb);
        $statement->object = item_object::create_from_data($data->object);

        // Store other generic xAPI Statements fields.
        foreach (self::$additionalsfields as $additional) {
            if (isset($data->$additional)) {
                $statement->$additional = item::create_from_data($data->$additional);
            }
        }
        return new self($statement);
    }

    public function jsonSerialize() {
        $result = (object) [
            'actor' => $this->actor,
            'verb' => $this->verb,
            'object' => $this->object,
        ];
        foreach (self::$additionalsfields as $additional) {
            if (!empty($this->$additional)) {
                $statement->$additional = $this->$additional;
            }
        }
        return $result;
    }

    public function set_actor(actor $actor): void {
        $this->actor = $actor;
    }

    public function set_verb(verb $verb): void {
        $this->verb = $verb;
    }

    public function set_object(object $object): void {
        $this->object = $object;
    }

    public function get_actor(): actor {
        return $this->actor;
    }

    public function get_verb(): verb {
        return $this->verb;
    }

    public function object(): object {
        return $this->object;
    }
}
