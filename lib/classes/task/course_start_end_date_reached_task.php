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
 * A scheduled task.
 *
 * @package    core
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

/**
 * Simple task to trigger course start and end events
 * @copyright  2019 Ferran Recio calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class course_start_end_date_reached_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcoursedatereached', 'admin');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        $currentime = time();
        // Last run time cannot be trusted as query param because it is not
        // possible to predict how much time the event handlers will consume
        // and long handlers could cause event loose.
        $lastsync = (int) get_config('moodlecourse','last_course_start_end_sync');
        if (!$lastsync) {
            $lastsync = time();
        }
        set_config('last_course_start_end_sync', $currentime, 'moodlecourse');
        // Trigger start and end course events since last date checked
        $params = array(
                    'lastcron1' => $lastsync, 'time1' => $currentime,
                    'lastcron2' => $lastsync, 'time2' => $currentime,
                );
        $select = "(startdate >= :lastcron1 AND startdate < :time1)
                    OR (enddate >= :lastcron2 AND enddate < :time2)";
        $recordset = $DB->get_recordset_select ('course', 'id, startdate, enddate', $select, $params);
        mtrace('Triggering star and ended course events...');
        foreach($recordset as $course) {
            $context = context_course::instance($course->id);
            $params = array(
                'context' => $context,
                'objectid' => $course->id,
                'other' => ['startdate' => $course->startdate, 'enddate' => $course->enddate,],
            );
            if ($course->startdate >= $lastsync && $course->startdate < $currentime) {
                mtrace(' Course id '.$course->id.' started');
                $event = \core\event\course_started::create($params);
                $event->trigger();
            }
            if ($course->enddate >= $lastsync && $course->enddate < $currentime) {
                mtrace(' Course id '.$course->id.' ended');
                $event = \core\event\course_ended::create($params);
                $event->trigger();
            }
        }
        $recordset->close();
    }
}
