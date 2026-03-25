# Activity CREATE Business Rules Spec

## Purpose

This spec defines the business rules for creating a `mod_peerassign` activity instance. It covers required and optional settings, defaults, validation rules, group-submission behavior, and failure handling for the create operation.

## Background

Teachers create a Peer Review Assignment from the Moodle activity chooser and configure core settings before they can start defining phases. CREATE logic must preserve data integrity and predictable behavior across the `peerassign` instance and Moodle file/course-module integrations.

## Behaviors

### Scenario: Teacher creates an activity with minimum required fields

**Given** a teacher with permission to add activities in a course
**When** they create a Peer Review Assignment with only required fields
**Then** a valid `peerassign` activity instance is created

**Acceptance criteria**

- [ ] Required create inputs include at minimum: `course`, `name`
- [ ] A new record is inserted in `peerassign`
- [ ] `course` references an existing course
- [ ] `name` is persisted as the activity display name
- [ ] `timecreated` and `timemodified` are set on insert
- [ ] The created instance is linked to a course module using Moodle standard module creation flow
- [ ] The plugin `lib.php` functions (`add_instance`, `update_instance`, `delete_instance`, `supports`) are implemented according to Moodle module development guidelines and integrate with the core activity lifecycle
- [ ] An initial Sample & Description phase is automatically created and linked to the activity instance

---

### Scenario: Optional activity description fields are accepted

**Given** a valid activity creation request
**When** the teacher provides description content
**Then** description fields are persisted correctly

**Acceptance criteria**

- [ ] `intro` is optional and may be NULL
- [ ] `introformat` is persisted when provided
- [ ] If omitted, `introformat` defaults to `0`
- [ ] Rich text in `intro` follows Moodle text format handling rules

---

### Scenario: Blind review setting is configured at creation time

**Given** a teacher creates a Peer Review Assignment
**When** they set the anonymous/blind review option
**Then** the blind review preference is stored and used by later display logic

**Acceptance criteria**

- [ ] `blindreview` is accepted as a boolean-like value
- [ ] If omitted, `blindreview` defaults to `0`
- [ ] If provided, `blindreview` is normalized to integer `0` or `1`
- [ ] The setting affects reviewer identity visibility in UI behavior, not persistence of reviewer IDs

---

### Scenario: Activity is created for individual submissions by default

**Given** a teacher creates a Peer Review Assignment without enabling group submissions
**When** the activity is saved
**Then** it is configured for individual submissions

**Acceptance criteria**

- [ ] `groupsubmissions` defaults to `0`
- [ ] `groupingid` defaults to `NULL`
- [ ] Submission and review workflows treat users as individual submitters unless changed later

---

### Scenario: Group submissions can be enabled during creation

**Given** a teacher wants students to submit as groups
**When** they enable group submissions while creating the activity
**Then** the activity is persisted with group-based submission mode

**Acceptance criteria**

- [ ] `groupsubmissions` accepts a boolean-like value and is normalized to `0`/`1`
- [ ] When enabled, `groupsubmissions` is stored as `1`
- [ ] Group-mode downstream workflows use group membership for submission ownership and peer assignment decisions

---

### Scenario: Grouping is optional when group submissions are enabled

**Given** group submissions are enabled and the course has groupings defined
**When** the teacher optionally selects a grouping
**Then** grouping constraints are persisted and validated

**Acceptance criteria**

- [ ] `groupingid` is optional and may be NULL even when `groupsubmissions = 1`
- [ ] If provided, `groupingid` must reference an existing `groupings.id`
- [ ] If provided, the grouping must belong to the same course as the activity
- [ ] If validation fails, activity creation is rejected with a clear validation error

---

### Scenario: Grouping value is ignored or rejected when group submissions are disabled

**Given** group submissions are disabled and the course has groupings defined
**When** a non-null `groupingid` is provided
**Then** behavior is deterministic and documented

**Acceptance criteria**

- [ ] Implementation chooses one behavior and documents it: either reject non-null `groupingid`, or persist as NULL
- [ ] No inconsistent state is stored where individual submissions depend on grouping constraints
- [ ] The chosen behavior is covered by automated tests

---

### Scenario: Grouping option is not present if the course has no groupings

**Given** a course has no groupings defined
**When** a teacher creates or updates an activity and enables group submissions
**Then** the grouping selection option is not shown in the UI

**Acceptance criteria**

- [ ] During create/update, the UI checks for existing groupings in the course
- [ ] If no groupings exist, the grouping selection field is hidden or disabled
- [ ] The absence of groupings does not prevent enabling group submissions; `groupingid` remains NULL

---

### Scenario: Activity creation initializes a predictable initial state workflow

**Given** a new activity instance is created
**When** initial plugin setup completes
**Then** the activity has a deterministic starting state for phase authoring

**Acceptance criteria**

- [ ] The initial Sample & Description phase is automatically created when the activity is created
- [ ] No additional phases beyond the initial Sample & Description phase are auto-created
- [ ] The setup UI can add further phases immediately after activity creation
- [ ] The auto-created Sample & Description phase is deterministic and documented
- [ ] Initial state does not expose student-facing phase actions before teacher configuration is complete

---

### Scenario: Capability and course-module context are validated before create write

**Given** a user attempts to create a Peer Review Assignment
**When** create validation runs
**Then** permission checks occur before any plugin record is written

**Acceptance criteria**

- [ ] User must have capability to add module instances in the target course/module context
- [ ] If capability validation fails, no `peerassign` record is created
- [ ] Errors follow Moodle exception and form-validation patterns

---

### Scenario: Create operation is atomic

**Given** activity creation involves multiple persistence steps
**When** one step fails
**Then** no partial activity state remains

**Acceptance criteria**

- [ ] Plugin-specific writes execute atomically (single successful commit)
- [ ] On any exception, plugin-specific inserted data is rolled back
- [ ] No orphan plugin record remains if course module creation fails

---

## Edge cases

- Activity name collisions inside the same course are allowed only if Moodle core permits duplicate module names; plugin must not add stricter undocumented constraints.
- Group submissions enabled with no groups/grouping configured in the course remains valid at create/update time; assignment-generation behavior later handles insufficient group data.
- Blind review enabled does not remove reviewer identity from persistence; anonymization remains a presentation/access-control concern.

## References

- `public/mod/peerassign/specs/introduction.md` — plugin overview and workflow model
- `public/mod/peerassign/specs/database/schema.spec.md` — `peerassign` and related tables
- Moodle module lifecycle flow (`mod_form`, `add_instance`, `update_instance`, `delete_instance`, course module capability checks)
