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

use mod_peerassign\manager;

/**
 * Data generator for mod_peerassign.
 *
 * @package   mod_peerassign
 * @category  test
 * @copyright 2026 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerassign_generator extends testing_module_generator {

    /** @var int Counter for generated phases. */
    protected int $phasecount = 0;

    /** @var int Counter for generated submissions. */
    protected int $submissioncount = 0;

    /** @var int Counter for generated peer reviews. */
    protected int $peerreviewcount = 0;

    /** @var int Counter for generated grades. */
    protected int $gradecount = 0;

    /**
     * Reset generator counters.
     *
     * @return void
     */
    public function reset(): void {
        $this->phasecount = 0;
        $this->submissioncount = 0;
        $this->peerreviewcount = 0;
        $this->gradecount = 0;

        parent::reset();
    }

    /**
     * Create a peerassign activity instance.
     *
     * @param array|stdClass|null $record Data for the instance.
     * @param array|null $options Additional options.
     * @return stdClass The created instance record.
     */
    public function create_instance($record = null, ?array $options = null): stdClass {
        $record = (object) (array) $record;

        if (!isset($record->blindreview)) {
            $record->blindreview = 0;
        }
        if (!isset($record->groupsubmissions)) {
            $record->groupsubmissions = 0;
        }
        if (!isset($record->groupingid)) {
            $record->groupingid = 0;
        }

        return parent::create_instance((array) $record, $options);
    }

    /**
     * Create a phase record for a peerassign instance.
     *
     * @param array|stdClass $record Phase data. Must include 'peerassignid'.
     * @return stdClass The created phase record.
     * @throws coding_exception If peerassignid is missing.
     */
    public function create_phase($record): stdClass {
        global $DB;

        $record = (array) $record;

        if (!isset($record['peerassignid'])) {
            throw new coding_exception('peerassignid must be specified when calling create_phase().');
        }

        $this->phasecount++;

        if (!isset($record['phasetype'])) {
            $record['phasetype'] = 2; // Submission type by default.
        }
        if (!isset($record['sequencenumber'])) {
            $record['sequencenumber'] = $this->phasecount;
        }
        if (!isset($record['title'])) {
            $record['title'] = 'Phase ' . $this->phasecount;
        }
        if (!isset($record['required'])) {
            $record['required'] = 1;
        }
        if (!isset($record['unlockmethod'])) {
            $record['unlockmethod'] = 'manual';
        }
        if (!isset($record['visible'])) {
            $record['visible'] = 1;
        }
        if (!isset($record['timecreated'])) {
            $record['timecreated'] = time();
        }
        if (!isset($record['timemodified'])) {
            $record['timemodified'] = $record['timecreated'];
        }

        $id = $DB->insert_record(manager::MODULE . '_phases', (object) $record);
        return $DB->get_record(manager::MODULE . '_phases', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Create a submission record for a phase.
     *
     * @param array|stdClass $record Submission data. Must include 'phaseid' and 'userid'.
     * @return stdClass The created submission record.
     * @throws coding_exception If phaseid or userid is missing.
     */
    public function create_submission($record): stdClass {
        global $DB;

        $record = (array) $record;

        if (!isset($record['phaseid'])) {
            throw new coding_exception('phaseid must be specified when calling create_submission().');
        }
        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be specified when calling create_submission().');
        }

        $this->submissioncount++;

        if (!isset($record['status'])) {
            $record['status'] = 'submitted';
        }
        if (!isset($record['attemptnum'])) {
            $record['attemptnum'] = 0;
        }
        if (!isset($record['timecreated'])) {
            $record['timecreated'] = time();
        }
        if (!isset($record['timemodified'])) {
            $record['timemodified'] = $record['timecreated'];
        }

        $id = $DB->insert_record(manager::MODULE . '_submissions', (object) $record);
        return $DB->get_record(manager::MODULE . '_submissions', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Create a peer review record.
     *
     * @param array|stdClass $record Review data. Must include 'phaseid', 'submissionid', and 'revieweruserid'.
     * @return stdClass The created peer review record.
     * @throws coding_exception If required foreign keys are missing.
     */
    public function create_peer_review($record): stdClass {
        global $DB;

        $record = (array) $record;

        if (!isset($record['phaseid'])) {
            throw new coding_exception('phaseid must be specified when calling create_peer_review().');
        }
        if (!isset($record['submissionid'])) {
            throw new coding_exception('submissionid must be specified when calling create_peer_review().');
        }
        if (!isset($record['revieweruserid'])) {
            throw new coding_exception('revieweruserid must be specified when calling create_peer_review().');
        }

        $this->peerreviewcount++;

        if (!isset($record['timecreated'])) {
            $record['timecreated'] = time();
        }
        if (!isset($record['timemodified'])) {
            $record['timemodified'] = $record['timecreated'];
        }

        $id = $DB->insert_record(manager::MODULE . '_peer_reviews', (object) $record);
        return $DB->get_record(manager::MODULE . '_peer_reviews', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Create a grade record.
     *
     * @param array|stdClass $record Grade data. Must include 'peerassignid' and 'userid'.
     * @return stdClass The created grade record.
     * @throws coding_exception If required foreign keys are missing.
     */
    public function create_grade($record): stdClass {
        global $DB;

        $record = (array) $record;

        if (!isset($record['peerassignid'])) {
            throw new coding_exception('peerassignid must be specified when calling create_grade().');
        }
        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be specified when calling create_grade().');
        }

        $this->gradecount++;

        if (!isset($record['timecreated'])) {
            $record['timecreated'] = time();
        }
        if (!isset($record['timemodified'])) {
            $record['timemodified'] = $record['timecreated'];
        }

        $id = $DB->insert_record(manager::MODULE . '_grades', (object) $record);
        return $DB->get_record(manager::MODULE . '_grades', ['id' => $id], '*', MUST_EXIST);
    }
}
