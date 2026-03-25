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
 * Manager class for mod_peerassign.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerassign;

defined('MOODLE_INTERNAL') || die();

use cm_info;
use context_module;
use moodle_page;
use stdClass;
use mod_peerassign\output\renderer;

/**
 * Manager class for core plugin operations.
 *
 * @package   mod_peerassign
 * @copyright 2025 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** Module name. */
    const MODULE = 'peerassign';

    /** The plugin name. */
    const PLUGINNAME = 'mod_peerassign';

    /** @var string plugin path. */
    public $path;

    /** @var stdClass activity instance record. */
    private $instance;

    /** @var context_module the current context. */
    private $context;

    /** @var cm_info course_modules record. */
    private $cm;

    /**
     * Class constructor.
     *
     * @param cm_info $cm course module info object
     * @param stdClass $instance activity instance object
     */
    public function __construct(cm_info $cm, stdClass $instance) {
        global $CFG;

        $this->cm = $cm;
        $this->instance = $instance;
        $this->context = context_module::instance($cm->id);
        $this->instance->cmidnumber = $cm->idnumber;
        $this->path = $CFG->dirroot . '/mod/' . self::MODULE;
    }

    /**
     * Create a manager instance from an instance record.
     *
     * @param stdClass $instance an activity record
     * @return manager
     */
    public static function create_from_instance(stdClass $instance): self {
        $cm = get_coursemodule_from_instance(self::MODULE, $instance->id);
        $cm = cm_info::create($cm);
        return new self($cm, $instance);
    }

    /**
     * Create a manager instance from a course_modules record.
     *
     * @param stdClass|cm_info $cm an activity course module record
     * @return manager
     */
    public static function create_from_coursemodule($cm): self {
        global $DB;

        $cm = cm_info::create($cm);
        $instance = $DB->get_record(self::MODULE, ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($cm, $instance);
    }

    /**
     * Create a manager instance from a plugin data record.
     *
     * This helper accepts records with either:
     * - `peerassignid` (plugin child records)
     * - `id` (the peerassign instance record itself)
     *
     * @param stdClass $record the plugin data record
     * @return manager
     */
    public static function create_from_data_record(stdClass $record): self {
        global $DB;

        if (!empty($record->peerassignid)) {
            $instanceid = (int)$record->peerassignid;
        } else if (!empty($record->id)) {
            $instanceid = (int)$record->id;
        } else {
            throw new \coding_exception('Missing peerassign instance identifier in data record.');
        }

        $instance = $DB->get_record(self::MODULE, ['id' => $instanceid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(self::MODULE, $instance->id);
        $cm = cm_info::create($cm);
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
     * Return the current module renderer.
     *
     * @param moodle_page|null $page the current page
     * @return renderer the module renderer
     */
    public function get_renderer(?moodle_page $page = null): renderer {
        global $PAGE;
        $page = $page ?? $PAGE;
        return $page->get_renderer(self::PLUGINNAME);
    }

    /**
     * Trigger module viewed event and set the module viewed for completion.
     *
     * @param stdClass $course course object
     */
    public function set_module_viewed(stdClass $course) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        // Trigger module viewed event.
        $event = \mod_peerassign\event\course_module_viewed::create([
            'objectid' => $this->instance->id,
            'context' => $this->context,
        ]);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot(self::MODULE, $this->instance);
        $event->trigger();

        // Completion.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($this->cm);
    }

    /**
     * Create the initial Sample & Description phase when an activity is created.
     *
     * @param int $peerassignid The ID of the peerassign activity
     * @return int The ID of the created phase
     * @throws \Exception
     */
    public static function create_initial_sample_phase($peerassignid) {
        global $DB;

        $phase = new \stdClass();
        $phase->peerassignid = $peerassignid;
        $phase->phasetype = 0; // 0 = sample
        $phase->sequencenumber = 1;
        $phase->title = get_string('sampledescriptionphase', self::PLUGINNAME);
        $phase->description = null;
        $phase->required = 1;
        $phase->unlockmethod = 'manual';
        $phase->unlockdate = null;
        $phase->allowfiles = 1;
        $phase->filetypes = null;
        $phase->maxfilesize = 0;
        $phase->extras = null;
        $phase->startdate = null;
        $phase->enddate = null;
        $phase->cutoffdate = null;
        $phase->visible = 1;
        $phase->timecreated = time();
        $phase->timemodified = $phase->timecreated;

        $phaseid = $DB->insert_record('peerassign_phases', $phase);

        return $phaseid;
    }

    /**
     * Delete an activity and all associated data.
     *
     * @param int $peerassignid The ID of the peerassign activity to delete
     * @throws \Exception
     */
    public static function delete_activity_cascade($peerassignid) {
        global $DB;

        $context = \context_module::instance_by_id($peerassignid);
        if (!$context) {
            // If context can't be found, still try to delete the data.
            $context = null;
        }

        // Delete all related data in reverse dependency order.
        $DB->delete_records('peerassign_phase_completion', ['phaseid' => $DB->sql_in(
            'SELECT id FROM {peerassign_phases} WHERE peerassignid = ?',
            [$peerassignid]
        )]);

        $DB->delete_records('peerassign_grades', ['peerassignid' => $peerassignid]);

        $DB->delete_records('peerassign_peer_reviews', ['phaseid' => $DB->sql_in(
            'SELECT id FROM {peerassign_phases} WHERE peerassignid = ?',
            [$peerassignid]
        )]);

        $DB->delete_records('peerassign_submissions', ['phaseid' => $DB->sql_in(
            'SELECT id FROM {peerassign_phases} WHERE peerassignid = ?',
            [$peerassignid]
        )]);

        $DB->delete_records('peerassign_phases', ['peerassignid' => $peerassignid]);

        // Clean up file areas if context exists.
        if ($context) {
            $fs = \get_file_storage();
            $fs->delete_area_files($context->id, self::PLUGINNAME, 'submission');
            $fs->delete_area_files($context->id, self::PLUGINNAME, 'reviewattachment');
            $fs->delete_area_files($context->id, self::PLUGINNAME, 'sampledescription');
        }
    }
}
