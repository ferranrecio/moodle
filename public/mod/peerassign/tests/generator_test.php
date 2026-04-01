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

namespace mod_peerassign;

use mod_peerassign\manager;

/**
 * Unit tests for the mod_peerassign data generator.
 *
 * @package   mod_peerassign
 * @category  test
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_peerassign_generator::class)]
final class generator_test extends \advanced_testcase {
    /**
     * Test that create_instance returns a valid record with expected defaults.
     */
    public function test_create_instance_defaults(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);

        $this->assertNotEmpty($instance->id);
        $this->assertEquals($course->id, $instance->course);

        // Verify the DB record has the expected defaults.
        global $DB;
        $record = $DB->get_record(manager::MODULE, ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals(0, (int) $record->blindreview);
        $this->assertEquals(0, (int) $record->groupsubmissions);
        $this->assertEquals(0, (int) $record->groupingid);
    }

    /**
     * Test that create_instance accepts custom field values.
     */
    public function test_create_instance_custom_values(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
            'name' => 'Custom Peer Assign',
            'blindreview' => 1,
            'groupsubmissions' => 1,
        ]);

        global $DB;
        $record = $DB->get_record(manager::MODULE, ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals('Custom Peer Assign', $record->name);
        $this->assertEquals(1, (int) $record->blindreview);
        $this->assertEquals(1, (int) $record->groupsubmissions);
    }

    /**
     * Test that create_instance creates a corresponding course module.
     */
    public function test_create_instance_creates_course_module(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);

        $cm = get_coursemodule_from_instance(manager::MODULE, $instance->id, $course->id, false, MUST_EXIST);
        $this->assertNotEmpty($cm->id);
        $this->assertEquals($instance->id, $cm->instance);
    }

    /**
     * Test create_phase creates a phase linked to the correct peerassign instance.
     */
    public function test_create_phase(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $phase = $generator->create_phase([
            'peerassignid' => $instance->id,
            'title' => 'Submission phase',
            'phasetype' => 2,
        ]);

        $this->assertNotEmpty($phase->id);
        $this->assertEquals($instance->id, $phase->peerassignid);
        $this->assertEquals('Submission phase', $phase->title);
        $this->assertEquals(2, (int) $phase->phasetype);
        $this->assertEquals(1, (int) $phase->required);
        $this->assertEquals('manual', $phase->unlockmethod);
        $this->assertEquals(1, (int) $phase->visible);
        $this->assertNotEmpty($phase->timecreated);
        $this->assertNotEmpty($phase->timemodified);
    }

    /**
     * Test create_phase throws on missing peerassignid.
     */
    public function test_create_phase_requires_peerassignid(): void {
        $this->resetAfterTest();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $this->expectException(\coding_exception::class);
        $generator->create_phase(['title' => 'Orphan phase']);
    }

    /**
     * Test create_submission creates a submission linked to the correct phase and user.
     */
    public function test_create_submission(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $phase = $generator->create_phase(['peerassignid' => $instance->id]);
        $submission = $generator->create_submission([
            'phaseid' => $phase->id,
            'userid' => $user->id,
        ]);

        $this->assertNotEmpty($submission->id);
        $this->assertEquals($phase->id, $submission->phaseid);
        $this->assertEquals($user->id, $submission->userid);
        $this->assertEquals('submitted', $submission->status);
        $this->assertEquals(0, (int) $submission->attemptnum);
        $this->assertNotEmpty($submission->timecreated);
        $this->assertNotEmpty($submission->timemodified);
    }

    /**
     * Test create_submission throws on missing phaseid.
     */
    public function test_create_submission_requires_phaseid(): void {
        $this->resetAfterTest();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $this->expectException(\coding_exception::class);
        $generator->create_submission(['userid' => 1]);
    }

    /**
     * Test create_submission throws on missing userid.
     */
    public function test_create_submission_requires_userid(): void {
        $this->resetAfterTest();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $this->expectException(\coding_exception::class);
        $generator->create_submission(['phaseid' => 1]);
    }

    /**
     * Test create_peer_review creates a review linked to the correct submission and reviewer.
     */
    public function test_create_peer_review(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $reviewer = $this->getDataGenerator()->create_user();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $phase = $generator->create_phase(['peerassignid' => $instance->id, 'phasetype' => 3]);
        $submission = $generator->create_submission(['phaseid' => $phase->id, 'userid' => $student->id]);

        $review = $generator->create_peer_review([
            'phaseid' => $phase->id,
            'submissionid' => $submission->id,
            'revieweruserid' => $reviewer->id,
        ]);

        $this->assertNotEmpty($review->id);
        $this->assertEquals($phase->id, $review->phaseid);
        $this->assertEquals($submission->id, $review->submissionid);
        $this->assertEquals($reviewer->id, $review->revieweruserid);
        $this->assertNotEmpty($review->timecreated);
        $this->assertNotEmpty($review->timemodified);
    }

    /**
     * Test create_peer_review throws on missing required fields.
     */
    public function test_create_peer_review_requires_foreign_keys(): void {
        $this->resetAfterTest();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $this->expectException(\coding_exception::class);
        $generator->create_peer_review(['submissionid' => 1, 'revieweruserid' => 1]);
    }

    /**
     * Test create_grade creates a grade linked to the correct peerassign instance and user.
     */
    public function test_create_grade(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $grade = $generator->create_grade([
            'peerassignid' => $instance->id,
            'userid' => $user->id,
        ]);

        $this->assertNotEmpty($grade->id);
        $this->assertEquals($instance->id, $grade->peerassignid);
        $this->assertEquals($user->id, $grade->userid);
        $this->assertNotEmpty($grade->timecreated);
        $this->assertNotEmpty($grade->timemodified);
    }

    /**
     * Test create_grade throws on missing peerassignid.
     */
    public function test_create_grade_requires_peerassignid(): void {
        $this->resetAfterTest();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $this->expectException(\coding_exception::class);
        $generator->create_grade(['userid' => 1]);
    }

    /**
     * Test create_grade throws on missing userid.
     */
    public function test_create_grade_requires_userid(): void {
        $this->resetAfterTest();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        $this->expectException(\coding_exception::class);
        $generator->create_grade(['peerassignid' => 1]);
    }

    /**
     * Test that reset clears internal counters.
     */
    public function test_reset_clears_counters(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
        ]);

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        // Create some records to increment counters.
        $phase1 = $generator->create_phase(['peerassignid' => $instance->id]);
        $phase2 = $generator->create_phase(['peerassignid' => $instance->id]);

        // Phase titles should use counter.
        $this->assertEquals('Phase 1', $phase1->title);
        $this->assertEquals('Phase 2', $phase2->title);

        // Reset.
        $generator->reset();

        // Counter should restart.
        $phase3 = $generator->create_phase(['peerassignid' => $instance->id]);
        $this->assertEquals('Phase 1', $phase3->title);
    }
}
