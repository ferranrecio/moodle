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

use cm_info;
use context_module;
use mod_peerassign\local\models\peerassign as peerassign_model;
use mod_peerassign\local\models\phase as phase_model;
use mod_peerassign\local\models\submission as submission_model;
use mod_peerassign\local\models\peer_review as peer_review_model;
use mod_peerassign\local\models\grade as grade_model;
use mod_peerassign\local\models\phase_completion as phase_completion_model;

/**
 * Unit tests for the mod_peerassign manager class.
 *
 * @package   mod_peerassign
 * @category  test
 * @copyright 2026 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(manager::class)]
class manager_test extends \advanced_testcase {

    /**
     * Helper to create a course and peerassign activity.
     *
     * @return array{0: \stdClass, 1: \stdClass} [$course, $module]
     */
    private function create_activity(): array {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(manager::MODULE, [
            'course' => $course->id,
            'name' => 'Test Peer Assignment',
        ]);
        return [$course, $module];
    }

    /**
     * Test create_from_instance returns a manager with correct data.
     */
    public function test_create_from_instance(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();

        $manager = manager::create_from_instance($instance);

        $this->assertInstanceOf(manager::class, $manager);
        $this->assertEquals($module->id, $manager->get_instance()->id);
        $this->assertEquals('Test Peer Assignment', $manager->get_instance()->name);
        $this->assertInstanceOf(cm_info::class, $manager->get_coursemodule());
        $this->assertEquals($module->id, $manager->get_coursemodule()->instance);
    }

    /**
     * Test create_from_coursemodule returns a manager with correct data.
     */
    public function test_create_from_coursemodule(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $cm = get_coursemodule_from_instance(manager::MODULE, $module->id, $course->id, false, MUST_EXIST);

        $manager = manager::create_from_coursemodule($cm);

        $this->assertInstanceOf(manager::class, $manager);
        $this->assertEquals($module->id, $manager->get_instance()->id);
        $this->assertEquals('Test Peer Assignment', $manager->get_instance()->name);
        $this->assertInstanceOf(cm_info::class, $manager->get_coursemodule());
    }

    /**
     * Test create_from_data_record with a peerassignid field.
     */
    public function test_create_from_data_record_with_peerassignid(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $record = (object) ['peerassignid' => $module->id];

        $manager = manager::create_from_data_record($record);

        $this->assertInstanceOf(manager::class, $manager);
        $this->assertEquals($module->id, $manager->get_instance()->id);
    }

    /**
     * Test create_from_data_record with an id field (instance record).
     */
    public function test_create_from_data_record_with_id(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $record = (object) ['id' => $module->id];

        $manager = manager::create_from_data_record($record);

        $this->assertInstanceOf(manager::class, $manager);
        $this->assertEquals($module->id, $manager->get_instance()->id);
    }

    /**
     * Test create_from_data_record throws on missing identifier.
     */
    public function test_create_from_data_record_throws_on_missing_id(): void {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Missing peerassign instance identifier');

        manager::create_from_data_record((object) []);
    }

    /**
     * Test that all static creators produce equivalent managers for the same activity.
     */
    public function test_static_creators_produce_equivalent_managers(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $cm = get_coursemodule_from_instance(manager::MODULE, $module->id, $course->id, false, MUST_EXIST);

        $fromInstance = manager::create_from_instance($instance);
        $fromCm = manager::create_from_coursemodule($cm);
        $fromRecord = manager::create_from_data_record((object) ['peerassignid' => $module->id]);

        // All three should reference the same activity instance.
        $this->assertEquals($fromInstance->get_instance()->id, $fromCm->get_instance()->id);
        $this->assertEquals($fromCm->get_instance()->id, $fromRecord->get_instance()->id);

        // All three should reference the same course module.
        $this->assertEquals($fromInstance->get_coursemodule()->id, $fromCm->get_coursemodule()->id);
        $this->assertEquals($fromCm->get_coursemodule()->id, $fromRecord->get_coursemodule()->id);

        // All three should reference the same context.
        $this->assertEquals($fromInstance->get_context()->id, $fromCm->get_context()->id);
        $this->assertEquals($fromCm->get_context()->id, $fromRecord->get_context()->id);
    }

    /**
     * Test get_context returns correct context_module.
     */
    public function test_get_context(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $manager = manager::create_from_instance($instance);

        $context = $manager->get_context();
        $this->assertInstanceOf(context_module::class, $context);
        $this->assertEquals(CONTEXT_MODULE, $context->contextlevel);
        $this->assertEquals($manager->get_coursemodule()->id, $context->instanceid);
    }

    /**
     * Test get_course returns the course record.
     */
    public function test_get_course(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $manager = manager::create_from_instance($instance);

        $managerCourse = $manager->get_course();
        $this->assertInstanceOf(\stdClass::class, $managerCourse);
        $this->assertEquals($course->id, $managerCourse->id);
    }

    /**
     * Test get_renderer returns the plugin renderer.
     */
    public function test_get_renderer(): void {
        global $PAGE;
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $manager = manager::create_from_instance($instance);

        $PAGE->set_url('/mod/' . manager::MODULE . '/view.php', ['id' => $manager->get_coursemodule()->id]);
        $renderer = $manager->get_renderer();
        $this->assertInstanceOf(\mod_peerassign\output\renderer::class, $renderer);
    }

    /**
     * Test create_initial_sample_phase creates a phase with correct defaults.
     */
    public function test_create_initial_sample_phase(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();

        $phaseId = manager::create_initial_sample_phase($module->id);

        $this->assertGreaterThan(0, $phaseId);

        $phase = phase_model::get_record(['id' => $phaseId]);
        $this->assertNotFalse($phase);
        $this->assertEquals($module->id, $phase->get('peerassignid'));
        $this->assertEquals(0, $phase->get('phasetype'));
        $this->assertEquals(1, $phase->get('sequencenumber'));
        $this->assertEquals(1, $phase->get('required'));
        $this->assertEquals('manual', $phase->get('unlockmethod'));
        $this->assertEquals(1, $phase->get('allowfiles'));
        $this->assertEquals(1, $phase->get('visible'));
    }

    /**
     * Test delete_activity_cascade removes all associated data.
     */
    public function test_delete_activity_cascade(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();

        /** @var \mod_peerassign_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);

        // Create phases.
        $phase1 = $generator->create_phase(['peerassignid' => $module->id, 'phasetype' => 1]);
        $phase2 = $generator->create_phase(['peerassignid' => $module->id, 'phasetype' => 2]);

        // Create users.
        $student = $this->getDataGenerator()->create_user();
        $reviewer = $this->getDataGenerator()->create_user();

        // Create submissions.
        $submission = $generator->create_submission(['phaseid' => $phase1->id, 'userid' => $student->id]);

        // Create peer review.
        $review = $generator->create_peer_review([
            'phaseid' => $phase2->id,
            'submissionid' => $submission->id,
            'revieweruserid' => $reviewer->id,
        ]);

        // Create grade.
        $grade = $generator->create_grade(['peerassignid' => $module->id, 'userid' => $student->id]);

        // Create phase completion.
        $completion = new phase_completion_model(0, (object) [
            'phaseid' => $phase1->id,
            'userid' => $student->id,
            'status' => 'complete',
        ]);
        $completion->create();

        // Verify records exist before deletion.
        $this->assertNotEmpty(phase_model::get_records(['peerassignid' => $module->id]));
        $this->assertNotEmpty(submission_model::get_records(['phaseid' => $phase1->id]));
        $this->assertNotEmpty(peer_review_model::get_records(['phaseid' => $phase2->id]));
        $this->assertNotEmpty(grade_model::get_records(['peerassignid' => $module->id]));
        $this->assertNotEmpty(phase_completion_model::get_records(['phaseid' => $phase1->id]));

        // Delete everything.
        manager::delete_activity_cascade($module->id);

        // Verify all records are gone.
        $this->assertEmpty(phase_model::get_records(['peerassignid' => $module->id]));
        $this->assertEmpty(submission_model::get_records(['phaseid' => $phase1->id]));
        $this->assertEmpty(peer_review_model::get_records(['phaseid' => $phase2->id]));
        $this->assertEmpty(grade_model::get_records(['peerassignid' => $module->id]));
        $this->assertEmpty(phase_completion_model::get_records(['phaseid' => $phase1->id]));
    }

    /**
     * Test that the manager path attribute is set correctly.
     */
    public function test_path_attribute(): void {
        global $CFG;
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $manager = manager::create_from_instance($instance);

        $this->assertEquals($CFG->dirroot . '/mod/' . manager::MODULE, $manager->path);
    }

    /**
     * Test that the instance has cmidnumber set from cm.
     */
    public function test_instance_cmidnumber(): void {
        $this->resetAfterTest();

        [$course, $module] = $this->create_activity();
        $instance = peerassign_model::get_record(['id' => $module->id], MUST_EXIST)->to_record();
        $manager = manager::create_from_instance($instance);

        // The cmidnumber is set from the cm record during construction.
        $this->assertObjectHasProperty('cmidnumber', $manager->get_instance());
    }
}
