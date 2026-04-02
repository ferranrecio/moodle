# Phase Management Business Rules Spec

## Purpose

This spec defines the business rules for teacher-managed phase lifecycle in `mod_peerassign`, including creating and deleting phases after activity creation.

## Background

Each Peer Review Assignment starts with an auto-created Sample & Description phase. Teachers then configure workflow by adding and removing additional phases, including extra Sample & Description phases when needed, plus Validation, Submission, Peer Review, and Teacher Evaluation phases. Phase management must preserve deterministic ordering, enforce permissions, and avoid destructive changes that corrupt existing student data.

## Behaviors

### Scenario: Teacher creates a new phase in an existing activity

**Why** Teachers need to tailor workflow structure to course goals after activity creation. Adding phases must be predictable so students and teachers see a coherent progression.

**Given** an existing Peer Review Assignment activity
**And** the teacher has permission to manage the activity
**When** the teacher adds a phase
**Then** the new phase is persisted and shown in phase order

Acceptance criteria:

- [ ] Teacher can add phase types supported by the plugin: `sample`, `validation`, `submission`, `peer_review`, `teacher_eval`
- [ ] New phase is linked to the target activity via `peerassignid`
- [ ] New phase gets a unique `sequencenumber` within the activity
- [ ] Default phase fields are initialized according to schema defaults when optional values are omitted
- [ ] `timecreated` and `timemodified` are set on successful creation
- [ ] Additional `sample` phases created by teachers are marked as non-initial phases

---

### Scenario: Phase create validates sequencing and keeps order deterministic

**Why** Students advance by phase order. Ambiguous or duplicated ordering can break progression and availability logic.

**Given** a teacher adds a phase to an activity with existing phases
**When** sequence validation runs
**Then** final ordering is unique, contiguous, and deterministic

Acceptance criteria:

- [ ] Final phase order uses unique sequence values per activity
- [ ] Sequence values are contiguous starting from 1 after create
- [ ] If insertion position is omitted, the new phase is appended at the end
- [ ] If insertion position is provided, existing phases are shifted deterministically to keep unique order

---

### Scenario: Teacher deletes a non-initial phase with no dependent learner data

**Why** Teachers must be able to simplify or correct workflows when a planned phase is no longer needed, as long as removal is safe.

**Given** a non-initial phase exists in the activity
**And** the phase has no dependent submissions, peer reviews, grades, completion, or attached files
**And** the teacher has permission to manage the activity
**When** the teacher deletes that phase
**Then** the phase is removed and remaining phases are re-sequenced

Acceptance criteria:

- [ ] Deletion is allowed for non-initial phases that have no dependent data
- [ ] Deleted phase record is removed from `peerassign_phases`
- [ ] Remaining phases are re-sequenced to contiguous order starting at 1
- [ ] Deletion updates `timemodified` for affected remaining phase records

---

### Scenario: Teacher cannot delete the initial Sample & Description phase

**Why** The initial phase is a workflow invariant and baseline instructional context for every activity. Allowing deletion would break assumptions in setup and progression.

**Given** an activity with the auto-created initial Sample & Description phase
**When** a teacher attempts to delete that initial phase
**Then** deletion is rejected and the initial phase remains unchanged

Acceptance criteria:

- [ ] The initial Sample & Description phase is never deletable by teachers
- [ ] This non-deletable rule applies only to the auto-created initial Sample & Description phase
- [ ] Delete attempts against the initial phase return a clear validation error
- [ ] No side effects occur when initial-phase deletion is rejected

---

### Scenario: Deleting a phase with dependent learner data is blocked

**Why** Removing a phase that already has learner artifacts can orphan related records and compromise grading/audit history.

**Given** a non-initial phase has dependent submissions, peer reviews, grades, completion, or attached files
**When** a teacher attempts to delete that phase
**Then** deletion is blocked

Acceptance criteria:

- [ ] Delete is rejected when dependent learner data exists for the target phase
- [ ] Error explains why deletion is blocked and indicates required teacher action
- [ ] Existing data remains unchanged after rejection

---

### Scenario: Teacher can delete newly created Sample & Description phases

**Why** Teachers may create Sample & Description phases while designing the activity flow. These extra non-initial sample phases should be removable if they are unused.

**Given** a teacher-created non-initial Sample & Description phase exists
**And** the phase has no dependent submissions, peer reviews, grades, completion, or attached files
**When** a teacher deletes that phase
**Then** deletion succeeds and phase order remains consistent

Acceptance criteria:

- [ ] Non-initial Sample & Description phases are deletable under the same safe-delete rules as other non-initial phases
- [ ] Deleting a non-initial Sample & Description phase does not affect the auto-created initial phase
- [ ] Re-sequencing after delete keeps a unique contiguous order starting at 1

---

### Scenario: Capability and context are validated before phase create/delete writes

**Why** Phase management changes activity behavior and must be restricted to authorized users in the correct Moodle context.

**Given** a request to create or delete a phase
**When** validation runs
**Then** capability and context checks complete before any write

Acceptance criteria:

- [ ] User must have module-management capability in the target activity context
- [ ] Invalid activity or phase identifiers are rejected before write
- [ ] Unauthorized attempts produce Moodle-standard permission errors with no side effects

---

### Scenario: Phase create/delete operations are atomic

**Why** Phase lifecycle operations may update multiple rows (target phase and sequence reordering). Atomic writes prevent partial state.

**Given** a phase create or delete operation modifies multiple records
**When** any write step fails
**Then** no partial phase state remains

Acceptance criteria:

- [ ] Multi-write phase management operations run in delegated transactions
- [ ] On failure, writes are rolled back
- [ ] On success, transaction commits once and resulting state is internally consistent

## Edge cases

- Multiple Sample & Description phases are allowed, but exactly one initial auto-created Sample & Description phase remains protected from deletion.
- Concurrent phase create/delete actions for the same activity must not leave duplicate or missing sequence numbers.
- Deleting the last non-initial phase leaves the activity with only the initial phase and remains valid.

## References

- `public/mod/peerassign/specs/introduction.md` — plugin overview and initial phase invariant
- `public/mod/peerassign/specs/database/schema.spec.md` — `peerassign_phases` fields and related data tables
- `public/mod/peerassign/specs/webservices/create-phase.spec.md` — create-phase API behavior and validation
- Moodle capability and context validation patterns for module-scoped writes
