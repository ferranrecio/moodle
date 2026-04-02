# Validation Phase Business Rules Spec

## Purpose

This spec defines business rules specific to the Validation phase type.

## Background

Validation phases are optional checkpoints where students acknowledge teacher-defined conditions before progressing.

## Behaviors

### Scenario: Validation phase stores checklist/condition definitions

**Why** Validation phases are only meaningful when explicit conditions are present and auditable.

**Given** a teacher configures a Validation phase
**When** validation settings are saved
**Then** phase extras include a supported condition/checklist structure

Acceptance criteria:

- [ ] Validation extras schema defines allowed condition/checklist fields
- [ ] Condition text fields are required and non-empty
- [ ] Optional metadata (for example ordering, mandatory flags) is validated

---

### Scenario: Student progression requires completion of required validation conditions

**Why** Validation checkpoints prevent students from skipping required acknowledgements.

**Given** a student reaches a required Validation phase
**When** the student has not completed all required conditions
**Then** progression to subsequent phases is blocked

Acceptance criteria:

- [ ] Completion logic verifies all required validation conditions
- [ ] Incomplete validation state is shown with actionable feedback
- [ ] Completion writes are tracked per student in phase completion records

---

### Scenario: Unlock configuration applies to Validation phase availability

**Why** Teachers may stage validation checkpoints by date or manual release.

**Given** Validation phase has unlock configuration
**When** availability is evaluated
**Then** phase follows common unlock rules (`manual` or `date`)

Acceptance criteria:

- [ ] `unlockmethod = date` requires valid unlock date
- [ ] Manual unlock state can be toggled by authorized teacher actions
- [ ] Availability checks are deterministic across repeated requests

## Edge cases

- A validation phase with zero required conditions is treated according to explicit policy (auto-complete or configuration error).
- Duplicate condition identifiers are rejected.
- Validation completion records remain consistent if conditions are edited after some students already completed the phase.

## References

- `public/mod/peerassign/specs/businessrules/phase-general.spec.md` - shared phase behavior
- `public/mod/peerassign/specs/introduction.md` - validation phase role in workflow
- `public/mod/peerassign/specs/database/schema.spec.md` - phase completion storage
