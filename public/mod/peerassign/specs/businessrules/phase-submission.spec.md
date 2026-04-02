# Submission Phase Business Rules Spec

## Purpose

This spec defines business rules specific to the Submission phase type.

## Background

Submission phases collect learner work for later peer review and teacher evaluation. Activities may include one or more submission phases (for multiple rounds).

## Behaviors

### Scenario: Submission phase accepts learner submissions under configured constraints

**Why** Submission settings must be enforceable so submitted artifacts are valid for grading and peer allocation.

**Given** a learner accesses an active Submission phase
**When** learner submits work
**Then** submission is accepted only if phase constraints are met

Acceptance criteria:

- [ ] Submission respects allowfiles/filetypes/maxfilesize constraints
- [ ] Submission attempts are linked to phase and learner (or group in group mode)
- [ ] Submission status lifecycle is deterministic (`draft`, `submitted`, and any supported transitions)

---

### Scenario: Submission timing rules gate create and update actions

**Why** Start/end/cutoff windows define fairness and predictability.

**Given** Submission phase has date restrictions
**When** learner attempts to submit or edit
**Then** action is allowed or blocked according to timing policy

Acceptance criteria:

- [ ] Start date blocks early submission attempts
- [ ] End date and cutoff date behavior is explicit and testable
- [ ] Late submission policy is deterministic and communicated in errors/UI
- [ ] PHPUnit tests verify timing rules for submission creation and updates

---

### Scenario: Submission phase completion reflects required submission work

**Why** Workflow progression depends on accurate completion signals.

**Given** a required Submission phase
**When** learner meets submission completion condition
**Then** phase completion is recorded for that learner

Acceptance criteria:

- [ ] Completion condition for submission phase is explicitly defined
- [ ] Completion is not marked when constraints fail (for example invalid file type)
- [ ] Completion updates are idempotent and do not create duplicate completion rows
- [ ] PHPUnit tests verify completion logic for submission phases

## Edge cases

- Multiple submission phases in one activity remain independent by phase id.
- Group-submission mode enforces group ownership constraints consistently.
- Reopened submissions preserve attempt/audit integrity.

## References

- `public/mod/peerassign/specs/database/schema.spec.md` - submissions schema and file storage
- `public/mod/peerassign/specs/businessrules/phase-general.spec.md` - shared phase behavior
- `public/mod/peerassign/specs/introduction.md` - submission phase role in workflow
