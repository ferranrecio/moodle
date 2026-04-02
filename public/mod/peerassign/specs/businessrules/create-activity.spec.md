# Activity CREATE Business Rules Spec

## Purpose

This spec defines the business rules for creating a `mod_peerassign` activity instance. It covers required and optional settings, defaults, validation rules, group-submission behavior, and failure handling for the create operation.

## Background

Teachers create a Peer Review Assignment from the Moodle activity chooser and configure core settings before they can start defining phases. CREATE logic must preserve data integrity and predictable behavior across the `peerassign` instance and Moodle file/course-module integrations.

## Behaviors

### Scenario: Teacher creates an activity with minimum required fields

**Why** Teachers must be able to quickly create a new Peer Review Assignment with just a name and course context. Sensible defaults allow immediate setup without overwhelming configuration options, and auto-creation of the initial Sample & Description phase provides a predictable starting state.

**Given** a teacher with permission to add activities in a course
**When** they create a Peer Review Assignment with only required fields
**Then** a valid `peerassign` activity instance is created

Acceptance criteria:

- [x] Required create inputs include at minimum: `course`, `name`
- [x] A new record is inserted in `peerassign`
- [x] `course` references an existing course
- [x] `name` is persisted as the activity display name
- [x] `timecreated` and `timemodified` are set on insert
- [x] The created instance is linked to a course module using Moodle standard module creation flow
- [x] The plugin `lib.php` functions (`add_instance`, `update_instance`, `delete_instance`, `supports`) are implemented according to Moodle module development guidelines and integrate with the core activity lifecycle
- [x] An initial Sample & Description phase is automatically created and linked to the activity instance

---

### Scenario: Optional activity description fields are accepted

**Why** Teachers need to provide context and instructions for the activity. Rich-text descriptions help students understand goals and expectations before engaging with the assignment workflow.

**Given** a valid activity creation request
**When** the teacher provides description content
**Then** description fields are persisted correctly

Acceptance criteria:

- [x] `intro` is optional and may be NULL
- [x] `introformat` is persisted when provided
- [x] If omitted, `introformat` defaults to `0`
- [x] Rich text in `intro` follows Moodle text format handling rules

---

### Scenario: Blind review setting is configured at creation time

**Why** Anonymity in peer review can reduce bias and improve feedback quality. Teachers must be able to decide upfront whether reviewer identities remain hidden throughout the activity lifecycle.

**Given** a teacher creates a Peer Review Assignment
**When** they set the anonymous/blind review option
**Then** the blind review preference is stored and used by later display logic

Acceptance criteria:

- [x] `blindreview` is accepted as a boolean-like value
- [x] If omitted, `blindreview` defaults to `0`
- [x] If provided, `blindreview` is normalized to integer `0` or `1`

---

### Scenario: Activity is created for individual submissions by default

**Why** Individual submissions are the most common case and should be the default. Group submissions require additional complexity around group membership and ownership, so they should be opt-in rather than forcing everyone to configure grouping constraints.

**Given** a teacher creates a Peer Review Assignment without enabling group submissions
**When** the activity is saved
**Then** it is configured for individual submissions

Acceptance criteria:

- [x] `groupsubmissions` defaults to `0`
- [ ] `groupingid` defaults to `NULL`
- [ ] Submission and review workflows treat users as individual submitters unless changed later

---

### Scenario: Group submissions can be enabled during creation

**Why** Some courses use collaborative group work. Enabling group submissions allows teams to submit collectively and receive group-level feedback, reducing administrative overhead compared to managing individual submissions.

**Given** a teacher wants students to submit as groups
**When** they enable group submissions while creating the activity
**Then** the activity is persisted with group-based submission mode

Acceptance criteria:

- [x] `groupsubmissions` accepts a boolean-like value and is normalized to `0`/`1`
- [x] When enabled, `groupsubmissions` is stored as `1`
- [ ] Group-mode downstream workflows use group membership for submission ownership and peer assignment decisions

---

### Scenario: Grouping is optional when group submissions are enabled

**Why** Teachers may enable group submissions using the course groups. Alternatively, they may lock submissions to a specific grouping (e.g., project teams) when users are in more than one group.

**Given** group submissions are enabled and the course has groupings defined
**When** the teacher optionally selects a grouping
**Then** grouping constraints are persisted and validated

Acceptance criteria:

- [x] `groupingid` is optional and may be NULL even when `groupsubmissions = 1`
- [x] If provided, `groupingid` must reference an existing `groupings.id`
- [x] If provided, the grouping must belong to the same course as the activity
- [x] If validation fails, activity creation is rejected with a clear validation error

---

### Scenario: Grouping value is ignored or rejected when group submissions are disabled

**Why** If individual submissions are enabled, grouping constraints make no sense and could confuse downstream logic. The system must enforce consistency by either rejecting the conflicting inputs or automatically clearing the grouping value.

**Given** group submissions are disabled and the course has groupings defined
**When** a non-null `groupingid` is provided
**Then** behavior is deterministic and documented

Acceptance criteria:

- [x] Implementation chooses one behavior and documents it: either reject non-null `groupingid`, or persist as NULL
- [x] No inconsistent state is stored where individual submissions depend on grouping constraints
- [ ] The chosen behavior is covered by automated tests

---

### Scenario: Grouping option is not present if the course has no groupings

**Why** If no groupings exist in the course, offering a grouping selector creates confusion and false expectations. The UI should adapt dynamically to show only relevant options based on course configuration.

**Given** a course has no groupings defined
**When** a teacher creates or updates an activity and enables group submissions
**Then** the grouping selection option is not shown in the UI

Acceptance criteria:

- [x] During create/update, the UI checks for existing groupings in the course
- [x] If no groupings exist, the grouping selection field is hidden or disabled
- [x] The absence of groupings does not prevent enabling group submissions; `groupingid` remains NULL

---

### Scenario: Activity creation initializes a predictable initial state workflow

**Why** Every activity must start in a properly initialized state. Auto-creating the Sample & Description phase provides a predictable starting point and ensures teachers can immediately begin configuring the workflow without manual boilerplate setup.

**Given** a new activity instance is created
**When** initial plugin setup completes
**Then** the activity has a deterministic starting state for phase authoring

Acceptance criteria:

- [x] The initial Sample & Description phase is automatically created when the activity is created
- [x] No additional phases beyond the initial Sample & Description phase are auto-created
- [ ] The setup UI can add further phases immediately after activity creation
- [x] The auto-created Sample & Description phase is deterministic and documented
- [ ] Initial state does not expose student-facing phase actions before teacher configuration is complete

---

### Scenario: Capability and course-module context are validated before create write

**Why** Only authorized users should create activities. Checking capabilities before writing prevents unauthorized activity creation and ensures audit trail consistency.

**Given** a user attempts to create a Peer Review Assignment
**When** create validation runs
**Then** permission checks occur before any plugin record is written

Acceptance criteria:

- [ ] User must have capability to add module instances in the target course/module context
- [ ] If capability validation fails, no `peerassign` record is created
- [ ] Errors follow Moodle exception and form-validation patterns

---

### Scenario: Create operation is atomic

**Why** Creating an activity involves multiple steps: inserting the plugin record and creating the course module. Partial completion (e.g., activity record created but no course module) would leave the system in an inconsistent state where the activity is unusable.

**Given** activity creation involves multiple persistence steps
**When** one step fails
**Then** no partial activity state remains

Acceptance criteria:

- [x] On any exception, plugin-specific inserted data is rolled back
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
