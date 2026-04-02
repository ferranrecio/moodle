# Activity DELETE Business Rules Spec

## Purpose

This spec defines the business rules for deleting a `mod_peerassign` activity instance. It covers cascade cleanup, capability checks, and failure handling for the delete operation.

## Background

At end-of-life, teachers can delete the activity from the course. DELETE logic must remove all plugin-owned data consistently, clean up file areas, validate permissions, and ensure no orphan records remain.

## Behaviors

### Scenario: Teacher deletes an activity from the course

**Why** When an activity is no longer needed, teachers must be able to remove it cleanly from the course. All related data (phases, submissions, reviews, files) must be removed to prevent orphaned records and storage waste.

**Given** an existing Peer Review Assignment activity
**When** a teacher deletes the activity using Moodle module deletion flow
**Then** plugin data is removed consistently

Acceptance criteria:

- [ ] Deletion uses Moodle standard module deletion integration for the plugin
- [ ] The `peerassign` record is deleted
- [ ] Related plugin records are deleted according to plugin cleanup rules (phases, submissions, peer reviews, grades, phase completion)
- [ ] Associated plugin file areas (`submission`, `reviewattachment`, `sampledescription`) are cleaned up via Moodle file API

---

### Scenario: Delete enforces capability and context checks before removal

**Why** Deletion is destructive and irreversible. Checking permissions before proceeding prevents accidental or malicious data loss and ensures only authorized users can remove activities.

**Given** a user attempts to delete a Peer Review Assignment
**When** delete validation runs
**Then** permission checks are enforced before destructive actions

Acceptance criteria:

- [ ] User must have capability to delete/manage module instances in target context
- [ ] If capability validation fails, no data is deleted
- [ ] Error flow is Moodle-standard and consumable by UI/automation layers

---

### Scenario: Delete operation is atomic and leaves no orphan plugin data

**Why** Deletion may involve removing records from multiple tables and file storage. Partial deletion leaves orphaned data that wastes storage and confuses future operations. Atomic deletion ensures all-or-nothing semantics.

**Given** activity deletion may involve multiple table and file operations
**When** any delete step fails
**Then** failure handling prevents silent partial cleanup

Acceptance criteria:

- [ ] Delete path uses transactional/rollback-safe behavior where supported
- [ ] Failures are logged and surfaced according to Moodle error-handling conventions
- [ ] Implementation documents expected behavior for partial-failure risks in external subsystems (for example, file storage cleanup retries)
- [ ] Successful delete leaves no orphan rows in plugin-owned tables

---

### Scenario: Delete rejects invalid or missing target activity identifiers

**Why** Attempting to delete a non-existent activity is a sign of client error (e.g., race condition, malformed request). Failing fast with a clear error prevents silent failures and helps surface bugs in deletion workflows.

**Given** a delete request is made
**When** the target activity does not exist or the identifier is malformed
**Then** the operation fails safely with clear errors

Acceptance criteria:

- [ ] Non-existent activity `id` returns a not-found style error
- [ ] Malformed identifier types are rejected during parameter validation
- [ ] No delete side effect occurs when target resolution fails

---

## Edge cases

- Deleting activities with large related datasets must remain bounded and should avoid request timeouts when possible (for example, batched cleanup when required by implementation constraints).

## References

- `public/mod/peerassign/specs/introduction.md` — plugin overview and workflow model
- `public/mod/peerassign/specs/database/schema.spec.md` — `peerassign` and related tables
- Moodle module lifecycle flow (`mod_form`, `add_instance`, `update_instance`, `delete_instance`, course module capability checks)
