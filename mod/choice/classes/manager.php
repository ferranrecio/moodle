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

namespace mod_choice;

use cm_info;
use context_module;
use stdClass;

/**
 * Class manager for choice activity
 *
 * @package    mod_choice
 * @copyright  2025 Laurent David <laurent.david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** Module name. */
    const MODULE = 'choice';

    /** The plugin name. */
    const PLUGINNAME = 'mod_choice';

    /** @var stdClass course_module record. */
    private $instance;

    /** @var context_module the current context. */
    private $context;

    /** @var cm_info course_modules record. */
    private $cm;

    /** @var stdClass $course record. */
    private $course;

    /** @var \moodle_database the database instance. */
    private \moodle_database $db;

    /**
     * @var int $groupmode as defined in SEPARATEGROUPS, VISIBLEGROUPS, or NOGROUPS.
     */
    private int $groupmode;

    /**
     * Class constructor.
     *
     * @param cm_info $cm course module info object
     * @param stdClass $instance activity instance object.
     */
    public function __construct(cm_info $cm, stdClass $instance) {
        $this->cm = $cm;
        $this->instance = $instance;
        $this->context = context_module::instance($cm->id);
        $this->instance->cmidnumber = $cm->idnumber;
        $this->db = \core\di::get(\moodle_database::class);
        $this->course = $cm->get_course();
        $this->groupmode = groups_get_activity_groupmode($cm, $this->course);
    }

    /**
     * Create a manager instance from an instance record.
     *
     * @param stdClass $instance an activity record
     * @return manager
     */
    public static function create_from_instance(stdClass $instance): self {
        $cm = get_coursemodule_from_instance(self::MODULE, $instance->id);
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        return new self($cm, $instance);
    }

    /**
     * Create a manager instance from a course_modules record.
     *
     * @param stdClass|cm_info $cm an activity record
     * @return manager
     */
    public static function create_from_coursemodule($cm): self {
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        $db = \core\di::get(\moodle_database::class);
        $instance = $db->get_record(self::MODULE, ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($cm, $instance);
    }

    /**
     * Return the current context.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->context;
    }

    /**
     * Return the current instance.
     *
     * @return stdClass the instance record
     */
    public function get_instance(): stdClass {
        return $this->instance;
    }

    /**
     * Return the current cm_info.
     *
     * @return cm_info the course module
     */
    public function get_coursemodule(): cm_info {
        return $this->cm;
    }

    /**
     * Return the current course module id.
     *
     * @return int the course module id
     */
    public function get_coursemodule_id(): int {
        return $this->cm->id;
    }

    /**
     * Return the current answers for this choice module, that the provided user can see.
     *
     * @param int $userid the current user id (for grouping purposes)
     * @return array the answers
     */
    public function get_all_answers(int $userid): array {
        ['join' => $groupmemberjoin, 'params' => $params, 'where' => $where] =
            $this->get_group_member_join($userid, $this->instance->id);
        return $this->db->get_records_sql(
            'SELECT ca.* FROM {choice_answers} ca' . $groupmemberjoin . $where . ' ORDER BY ca.id',
            $params,
        );
    }

    /**
     * Get the SQL join for group members based on the provided user's group.
     *
     * @param int $userid the current user id
     * @param int $choiceid the choice id
     * @return array an array containing the SQL join string and parameters
     */
    private function get_group_member_join(int $userid, int $choiceid): array {
        $where = ' WHERE ca.choiceid = :choiceid';
        $params = ['choiceid' => $choiceid];
        if ($this->groupmode == SEPARATEGROUPS
            && !has_capability('moodle/site:accessallgroups', $this->context, $userid)) {
            $groups = groups_get_all_groups($this->course->id, $userid, 0, 'g.id');
            if (empty($groups)) {
                // No groups found for this user, return empty join but we show only records belonging to the user.
                $where .= ' AND ca.userid = :userid';
                $params['userid'] = $userid;
                return ['join' => '', 'params' => $params, 'where' => $where];
            }
            $groupids = array_column($groups, 'id');

            [$groupmembersql, $groupmemberparams] = groups_get_members_ids_sql($groupids, $this->context);
            $params = array_merge($params, $groupmemberparams);
            $groupmemberjoin = " JOIN ({$groupmembersql}) jg ON jg.id = ca.userid";
        } else {
            $groupmemberjoin = '';
        }
        return ['join' => $groupmemberjoin, 'params' => $params, 'where' => $where];
    }

    /**
     * Return the current answers for this choice module
     *
     * Note: this will return all answers, regardless of grouping.
     *
     * @param int $userid the user id
     * @return array the answers
     */
    public function get_user_answers(int $userid): array {
        $conditions = ['choiceid' => $this->instance->id, 'userid' => $userid];
        return $this->db->get_records('choice_answers', $conditions, 'id');
    }

    /**
     * Return the current answers count for this choice module, that the provided user.
     *
     * @param int $userid the current user id (for grouping purposes)
     * @return int the number of answers
     */
    public function get_all_answers_count(int $userid): int {
        ['join' => $groupmemberjoin, 'params' => $params, 'where' => $where] =
            $this->get_group_member_join($userid, $this->instance->id);
        return $this->db->count_records_sql(
            'SELECT COUNT(*) FROM {choice_answers} ca' . $groupmemberjoin . $where,
            $params
        );
    }

    /**
     * Return the current answers count for this choice module
     *
     * Note: this will count all answers, regardless of grouping.
     *
     * @param int $userid the user id
     * @return int the number of answers
     */
    public function get_user_answers_count(int $userid): int {
        $conditions = ['choiceid' => $this->instance->id, 'userid' => $userid];
        return $this->db->count_records('choice_answers', $conditions);
    }
}
