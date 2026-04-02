# Peer Review Phase Business Rules Spec

## Purpose

This spec defines business rules specific to the Peer Review phase type.

## Background

Peer Review phases assign learners to review peer submissions. Configuration includes review quota, optional anonymity rules, and minimum data conditions needed for fair allocation.

## Behaviors

### Scenario: Peer review phase validates reviewer-allocation settings

**Why** Invalid allocation settings can make review assignment impossible or unfair.

**Given** a teacher configures a Peer Review phase
**When** phase extras are validated
**Then** reviewer-allocation settings must be valid and feasible

Acceptance criteria:

- [ ] Reviewer quota is a positive integer
- [ ] Minimum submissions threshold (if configured) is a non-negative integer
- [ ] Anonymity/blindness options are validated against activity-level constraints

---

### Scenario: Allocation runs only when preconditions are met

**Why** Review assignment should not run if there is insufficient submission data.

**Given** a Peer Review phase is active
**When** assignment generation is requested
**Then** allocation runs only when configured preconditions are satisfied

Acceptance criteria:

- [ ] Allocation is blocked if available submissions are below configured threshold
- [ ] Learners are never assigned to review their own submission
- [ ] Allocation output is deterministic for the same input state unless randomization policy explicitly applies

---

### Scenario: Peer review completion requires configured review work

**Why** Completion must represent actual peer feedback contribution, not just phase access.

**Given** a learner is assigned peer reviews
**When** learner submits required review artifacts
**Then** phase completion reflects configured completion rules

Acceptance criteria:

- [ ] Completion requires the configured number of submitted reviews
- [ ] Partial review completion does not unlock next phase when phase is required
- [ ] Completion state updates after review edits without duplicate completion rows

## Edge cases

- If no eligible submissions exist, phase behavior follows explicit fallback policy (blocked, deferred, or teacher override).
- Withdrawn or deleted submissions are removed from allocation candidates safely.
- Reallocation behavior after late submissions is deterministic and documented.

## References

- `public/mod/peerassign/specs/introduction.md` - peer review glossary and completion constraints
- `public/mod/peerassign/specs/database/schema.spec.md` - peer review table and relationships
- `public/mod/peerassign/specs/businessrules/phase-general.spec.md` - shared phase behavior
