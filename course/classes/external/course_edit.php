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
 * Class for exporting a course summary from an stdClass.
 *
 * @package    core_course
 * @copyright  2021 Ferran Recio <moodle@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_course\external;

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use moodle_exception;

class course_edit extends external_api {

    /**
     * Webservice parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'action' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'action: cm_hide, cm_show, section_hide, section_show, cm_moveleft...',
                    VALUE_REQUIRED
                ),
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Target id'),
                    'Affected ids',
                    VALUE_DEFAULT,
                    []
                ),
                'targetsectionid' => new external_value(
                    PARAM_INT, 'Optional target section id', VALUE_DEFAULT, null
                ),
                'targetcmid' => new external_value(
                    PARAM_INT, 'Optional target cm id', VALUE_DEFAULT, null
                ),
            )
        );
    }

    /**
     * Web service execute method.
     *
     * This webservice will execute any action from the course editor. The default actions
     * are located in core_course\editactions but the format plugin can extend that class
     * in format_XXX\course\stateactions.
     *
     * The specific action metdhos will register in a core_course\stateupdate all the affected
     * sections, cms and course course attribute. This object (in JSON) will be send back to the
     * frontend editor to refresh the updated state elements.
     *
     * By default, core_course\stateupdate will register only create, delete and update events
     * on cms, sections and the general course data. However, if some plugin needs adhoc messages for
     * its own mutation module, it extend this class in format_XXX\course\stateupdate.
     *
     * @param string $action the action name to execute
     * @param int $courseid the course id
     * @param int[] $ids the affected ids (section or cm depending on the action)
     * @param int $targetsectionid optional target section id (for move action)
     * @param int $targetcmid optional target cm id (for move action)
     * @return string Course state in JSON
     */
    public static function execute(string $action, int $courseid, array $ids = [],
            ?int $targetsectionid = null, ?int $targetcmid = null): string {
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/course/lib.php');

        $params = external_api::validate_parameters(self::execute_parameters(), [
            'action' => $action,
            'courseid' => $courseid,
            'ids' => $ids,
        ]);
        $action = $params['action'];
        $courseid = $params['courseid'];
        $ids = $params['ids'];

        $format = course_get_format($courseid);
        $PAGE->set_course($format->get_course());

        // Create a course changes tracker object.
        $actionsclass = 'format_' . $format->get_format() . '\\course\\stateupdates';
        if (!class_exists($actionsclass)) {
            $actionsclass = 'core_course\\stateupdates';
        }
        $renderer = $PAGE->get_renderer('format_' . $format->get_format());
        $updates = new $actionsclass($renderer, $format);

        // Get the actions class from the course format.
        $actionsclass = 'format_'. $format->get_format().'\\course\\stateactions';
        if (!class_exists($actionsclass)) {
            $actionsclass = 'core_course\\stateactions';
        }
        $renderer = $PAGE->get_renderer('format_' . $format->get_format());
        $actions = new $actionsclass();

        if (!is_callable([$actions, $action])) {
            throw new moodle_exception("Invalid course state action $action ".get_class($actions));
        }

        // Execute the action.
        $actions->$action($updates, $format->get_course(), $ids, $targetsectionid, $targetcmid);

        return json_encode($updates);
    }

    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'Encoded course update JSON');
    }
}
