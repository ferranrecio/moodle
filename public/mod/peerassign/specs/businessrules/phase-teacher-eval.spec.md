# Teacher Evaluation Phase Business Rules Spec

## Purpose

This spec defines business rules specific to the Teacher Evaluation phase type.

## Background

Teacher Evaluation phases are teacher-only grading stages that can provide summative or moderation grades independent from peer-assigned grades.

## Behaviors

### Scenario: Only authorized teachers can submit teacher evaluation grades

**Why** Teacher evaluation affects official grading outcomes and must remain restricted.

**Given** a Teacher Evaluation phase is active
**When** a user attempts to create or update teacher evaluation data
**Then** action is allowed only for users with grading capability

Acceptance criteria:

- [ ] Teacher evaluation writes require grading capability in activity context
- [ ] Learners cannot create or modify teacher evaluation grades
- [ ] Unauthorized attempts have no side effects

---

### Scenario: Teacher evaluation stores grade and feedback with auditability

**Why** Grade decisions need traceability and clear feedback for learners.

**Given** a teacher submits an evaluation
**When** evaluation data is saved
**Then** grade, feedback, grader identity, and timestamps are persisted consistently

Acceptance criteria:

- [ ] Teacher grade validates against configured grade scale or numeric range
- [ ] Feedback text/format is stored using Moodle text format conventions
- [ ] Grader identity and modification timestamps are preserved for audit

---

### Scenario: Grade visibility follows teacher-controlled publication rules

**Why** Teachers may need to moderate grades before releasing them to learners.

**Given** teacher evaluation records exist
**When** learner views grade-related information
**Then** visibility respects configured publication state

Acceptance criteria:

- [ ] Hidden teacher evaluation data is not shown to learners before publication
- [ ] Published teacher evaluation data is visible through defined learner views
- [ ] Visibility changes are logged and consistently enforced

## Edge cases

- Teacher evaluation can coexist with missing peer grades when policy allows teacher-only grading.
- Regrading updates prior teacher evaluation entries without losing audit history.
- Deleted teacher accounts are handled according to Moodle grading ownership conventions.

## References

- `public/mod/peerassign/specs/introduction.md` - teacher evaluation role in workflow
- `public/mod/peerassign/specs/database/schema.spec.md` - grades table fields
- `public/mod/peerassign/specs/businessrules/phase-general.spec.md` - shared phase behavior
