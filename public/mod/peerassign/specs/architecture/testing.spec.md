# Testing Architecture Spec

## Purpose

This spec defines the general testing conventions for `mod_peerassign`. It documents the expected test infrastructure, generator patterns, and baseline coverage requirements so tests remain consistent and maintainable as the plugin evolves.

## Background

Moodle activity modules provide a `testing_module_generator` subclass at `tests/generator/lib.php` so that PHPUnit (and Behat) tests can create realistic activity instances and related data without duplicating setup boilerplate. `mod_peerassign` should follow the same pattern used by core modules such as `mod_data`, `mod_assign`, and `mod_forum`.

## Behaviors

### Scenario: Generator class exists and follows core conventions

**Why** Test generators reduce boilerplate and ensure consistent test setup across PHPUnit and Behat. A standard generator interface makes tests maintainable and allows developers to quickly set up realistic scenarios without duplicating database logic.

**Given** a developer needs to write tests for any peerassign feature
**When** they look for a data generator
**Then** `mod_peerassign_generator` is available at `tests/generator/lib.php` and follows the standard Moodle generator contract

Acceptance criteria:

- [x] File lives at `public/mod/peerassign/tests/generator/lib.php`
- [x] Class is named `mod_peerassign_generator` and extends `testing_module_generator`
- [x] `create_instance($record, $options)` accepts an object or array, sets sensible defaults for all peerassign-specific fields (`blindreview`, `groupsubmissions`, `groupingid`), and delegates to `parent::create_instance()`
- [x] After calling `create_instance()`, a valid `peerassign` record exists in the database and a corresponding course module is created

---

### Scenario: Generator provides helpers for core domain objects

**Why** Domain-specific helper methods (create_phase, create_submission) make test intent clear and reduce test code complexity. Each helper encapsulates default values and relationships, so tests focus on behavior rather than setup.

**Given** tests need to set up phases, submissions, peer reviews, and grades
**When** using the generator
**Then** helper methods exist to create each major domain record with sensible defaults

Acceptance criteria:

- [x] `create_phase($record)` creates a `peerassign_phases` record linked to a peerassign instance, defaulting `phasetype`, `sequencenumber`, `title`, `required`, `unlockmethod`, and visibility fields
- [x] `create_submission($record)` creates a `peerassign_submissions` record linked to a phase and user, defaulting `status` to `'submitted'` and `attemptnum` to `0`
- [x] `create_peer_review($record)` creates a `peerassign_peer_reviews` record linked to a phase, submission, and reviewer user
- [x] `create_grade($record)` creates a `peerassign_grades` record linked to a peerassign instance and user
- [x] Each helper requires its foreign-key field (e.g. `peerassignid`, `phaseid`, `submissionid`) and throws if missing
- [x] Each helper fills `timecreated` and `timemodified` automatically when not provided

---

### Scenario: Generator has its own unit test coverage

**Why** The generator is foundational infrastructure used by other tests. Testing the generator itself ensures it creates correct, consistent data structures, preventing cascading test failures caused by broken generator logic.

**Given** the generator is a foundational test utility
**When** verifying it works correctly
**Then** a dedicated test validates all generator methods

Acceptance criteria:

- [x] A test file (e.g. `tests/generator_test.php`) verifies `create_instance()` returns a valid record with expected defaults
- [x] The test verifies `create_phase()` creates a phase linked to the correct peerassign instance
- [x] The test verifies `create_submission()` creates a submission linked to the correct phase and user
- [x] The test verifies `create_peer_review()` creates a review linked to the correct submission and reviewer
- [x] The test verifies `create_grade()` creates a grade linked to the correct peerassign instance and user

---

### Scenario: PHPUnit 12 compatibility

**Why** Using modern PHPUnit features (PHP attributes, ::class syntax) keeps the codebase future-proof and allows CI to enforce contemporary testing practices. Avoiding deprecated features prevents test runtime warnings and preparation for future PHPUnit major versions.

**Given** the generator is used in PHPUnit tests
**When** running tests with PHPUnit 12
**Then** the generator and all tests using it run without deprecation warnings or errors

Acceptance criteria:

- [x] The generator does not use any deprecated PHPUnit 11 features (e.g. `setUpBeforeClass()`, `assertInternalType()`, etc.)
- [x] All tests uses PHP attribute-based annotations (e.g. `#[\PHPUnit\Framework\Attributes\CoversClass(...)]`, `#[\PHPUnit\Framework\Attributes\DataProvider(...)]`, etc.) instead of docblock annotations
- [x] To reference class names, it will use `::class` syntax instead of strings (e.g. `mod_peerassign_generator::class` instead of `'mod_peerassign_generator'`)
- [x] Generator will use \Generator and yield for any data providers instead of arrays
- [x] All array params returned by generators will have string indexes with the same name as the test method params (e.g. `return ['visible' => 1, 'expected' => 0];` instead of `return [1, 0];`)
