# Activity UPDATE Business Rules Spec

## Purpose

This spec defines the business rules for updating an existing `mod_peerassign` activity instance. It covers editable fields, validation rules, group-submission transitions, and failure handling for the update operation.

## Background

Over time, teachers can update activity settings (for example, group submissions, blind review, and basic metadata) to adjust workflows and behavior. UPDATE logic must validate changes consistently with create rules, handle state transitions safely, and preserve data integrity across affected submissions, peer reviews, and grades.

## Behaviors

### Scenario: Teacher updates editable activity fields

**Given** an existing Peer Review Assignment activity
**When** a teacher edits the activity settings and saves
**Then** editable fields are updated in `peerassign`

Acceptance criteria:

- [x] Update supports editable fields at minimum: `name`, `intro`, `introformat`, `blindreview`, `groupsubmissions`, `groupingid`
- [x] `id` and `course` identify the target activity and are not reassigned to a different course by update flow
- [x] `timemodified` is updated on successful save
- [ ] Unchanged fields preserve their previous values

---

### Scenario: Update applies the same validation rules as create for shared fields

**Given** an update request for an existing activity
**When** shared settings are modified
**Then** validation and normalization are consistent with create logic

Acceptance criteria:

- [x] Boolean-like fields (`blindreview`, `groupsubmissions`) are normalized to `0` or `1`
- [x] `groupingid` validation (exists, same course) is enforced when provided
- [x] Invalid values are rejected with clear validation errors
- [x] On validation failure, no partial update is persisted

---

### Scenario: Enabling group submissions on update is allowed only with consistent constraints

**Given** an activity currently configured for individual submissions
**When** a teacher enables `groupsubmissions`
**Then** the updated configuration remains internally consistent

Acceptance criteria:

- [x] `groupsubmissions` can transition from `0` to `1`
- [x] If `groupingid` is provided, it must pass grouping validation rules
- [x] If no `groupingid` is provided, configuration remains valid with `groupingid = NULL`
- [ ] Downstream ownership/assignment logic can resolve to group-based behavior after update

---

### Scenario: Disabling group submissions on update handles existing group-linked data safely

**Given** an activity with existing group-mode submissions or reviews
**When** a teacher attempts to set `groupsubmissions` from `1` to `0`
**Then** data integrity is preserved through deterministic behavior

Acceptance criteria:

- [ ] Implementation chooses and documents one behavior: block change with a clear error, or allow change with explicit migration rules
- [ ] Existing submissions, peer reviews, and grades are not left in ambiguous ownership state
- [ ] If change is blocked, the error message explains required remediation
- [ ] If change is allowed, affected records are migrated or marked consistently

---

### Scenario: Update rejects invalid or missing target activity identifiers

**Given** an update request is made
**When** the target activity does not exist or the identifier is malformed
**Then** the operation fails safely with clear errors

Acceptance criteria:

- [ ] Non-existent activity `id` returns a not-found style error
- [ ] Malformed identifier types are rejected during parameter validation
- [ ] No write side effect occurs when target resolution fails

---

## Edge cases

- Switching `groupsubmissions` after data exists may be blocked or require migration, but behavior must be deterministic and documented.
- Blind review enabled does not remove reviewer identity from persistence; anonymization remains a presentation/access-control concern.

## References

- `public/mod/peerassign/specs/introduction.md` — plugin overview and workflow model
- `public/mod/peerassign/specs/database/schema.spec.md` — `peerassign` and related tables
- Moodle module lifecycle flow (`mod_form`, `add_instance`, `update_instance`, `delete_instance`, course module capability checks)
