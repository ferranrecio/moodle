# Database Schema Spec

## Purpose

This spec defines the database schema for the `mod_peerassign` Moodle plugin. It covers all tables required to support progressive multi-phase peer review assignments, including activity instances, phases, submissions, peer feedback, and grading. The main activity table is `peerassign`, and related tables are prefixed with `peerassign_` (e.g., `peerassign_phases`, `peerassign_submissions`).

## Background

The Peer Review Assignment activity stores instructor-defined phases, student submissions, peer feedback data, and both peer and instructor grades. Each assignment instance can have multiple phases (Sample & Description, Validation, Submission, Peer Review, Teacher Evaluation). Phases control the workflow progression, and students are automatically assigned peer reviewers based on configurable reviewer counts. Timestamps (`timecreated`, `timemodified`) are managed automatically by the Moodle persistence layer. Enums (phase type, review status) are stored as integers and may use PHP enums.

## Behaviors

### Scenario: Activity instance table is created with all required fields

**Given** the plugin is installed
**When** the database is provisioned
**Then** a `peerassign` table exists with all mandatory fields

Acceptance criteria:

- [x] `id` INT, Primary Key, auto-increment
- [x] `course` INT, Foreign Key, NOT NULL — references `course.id`
- [x] `name` VARCHAR, NOT NULL — activity display name
- [x] `intro` TEXT, default NULL — activity description
- [x] `introformat` INT, default 0 — intro text format
- [x] `blindreview` INT (BOOLEAN), default 0 — whether peer reviews are anonymous
- [x] `groupsubmissions` INT (BOOLEAN), default 0 — whether submissions are group-based
- [x] `groupingid` INT, default NULL — references `group_groupings.id` if the teacher wants to use a specific grouping for group submissions
- [x] `timecreated` DATETIME, NOT NULL — set automatically on record creation
- [x] `timemodified` DATETIME, NOT NULL — updated automatically on every modification

---

### Scenario: Phases table stores progressive activity workflow stages

**Given** the plugin is installed
**When** the database is provisioned
**Then** a `peerassign_phases` table exists with all required fields

Acceptance criteria:

- [x] `id` INT, Primary Key, auto-increment
- [x] `peerassignid` INT, Foreign Key, NOT NULL — references `peerassign.id`
- [x] `phasetype` INT, NOT NULL — phase type: 0=sample, 1=validation, 2=submission, 3=peer_review, 4=teacher_eval
- [x] `sequencenumber` INT, NOT NULL — phase order (1-based)
- [x] `title` VARCHAR, NOT NULL — phase display name
- [x] `description` TEXT, default NULL — detailed instructions for phase
- [x] `required` INT (BOOLEAN), default 1 — whether this phase must be completed
- [x] `unlockmethod` VARCHAR, default 'manual' — 'manual' or 'date'
- [x] `unlockdate` DATETIME, default NULL — phase unlock date if unlockmethod='date'
- [x] `allowfiles` INT (BOOLEAN), default 1 — whether file uploads are allowed
- [x] `filetypes` VARCHAR, default NULL — comma-separated allowed file extensions
- [x] `maxfilesize` INT, default 0 — max file size in bytes (0 = unlimited)
- [x] `extras` TEXT, default NULL — JSON serialized additional settings (e.g. number of reviewers for peer review phase)
- [x] `startdate` DATETIME, default NULL — when the phase becomes active
- [x] `enddate` DATETIME, default NULL — when the phase closes
- [x] `cutoffdate` DATETIME, default NULL — last date for submissions (can differ from enddate which may allow late grading)
- [x] `visible` INT (BOOLEAN), default 1 — whether the phase is visible to students
- [x] `timecreated` DATETIME, NOT NULL
- [x] `timemodified` DATETIME, NOT NULL

---

### Scenario: Submissions table records student work submissions

**Given** a student submits work
**When** the database is provisioned
**Then** a `peerassign_submissions` table exists with all required fields

Acceptance criteria:

- [x] `id` INT, Primary Key, auto-increment
- [x] `phaseid` INT, Foreign Key, NOT NULL — references `peerassign_phases.id`
- [x] `userid` INT, Foreign Key, NOT NULL — student who submitted
- [x] `groupid` INT, default NULL — if group submissions are enabled, references `groups.id`
- [x] `attemptnum` INT, default 0 — attempt number (0-based)
- [x] `timecreated` DATETIME, NOT NULL — submission timestamp
- [x] `timemodified` DATETIME, NOT NULL — last modification
- [x] `plugindata` TEXT, default NULL — JSON serialized submission content/metadata
- [x] `status` VARCHAR, default 'submitted' — e.g. 'draft', 'submitted', 'reopened'
- [x] One submission record per (phaseid, userid, attemptnum) combination

---

### Scenario: Submission files are stored in Moodle file storage

**Given** a student attaches files to a submission
**When** files are uploaded
**Then** files are stored in Moodle's file API with appropriate context

Acceptance criteria:

- [x] Files are stored in `mod_peerassign` component context
- [x] Filearea is `submission` for submission files
- [ ] Itemid is the `peerassign_submissions.id` of the associated submission record
- [x] Filearea is `reviewattachment` for peer review attachments
- [ ] Itemid is the `peerassign_peer_reviews.id` of the associated peer review record
- [x] Filearea is `sampledescription` for sample & description phase materials
- [ ] Itemid is the `peerassign_phases.id` of the associated phase record
- [ ] File records use standard Moodle `files` table

---

### Scenario: Peer reviews table records peer feedback

**Given** a peer reviewer submits feedback
**When** the database is provisioned
**Then** a `peerassign_peer_reviews` table exists with all required fields

Acceptance criteria:

- [x] `id` INT, Primary Key, auto-increment
- [x] `phaseid` INT, Foreign Key, NOT NULL — references `peerassign_phases.id`
- [x] `submissionid` INT, Foreign Key, NOT NULL — references `peerassign_submissions.id`
- [x] `revieweruserid` INT, Foreign Key, NOT NULL — the peer reviewer
- [x] `groupid` INT, default NULL — if group submissions are enabled, references `groups.id`
- [x] `feedback` TEXT, default NULL — review comments
- [x] `feedbackformat` INT, default 0 — Moodle text format
- [x] `grade` DECIMAL(5,2), default NULL — peer-assigned numeric grade
- [x] `timecreated` DATETIME, NOT NULL — when review was submitted
- [x] `timemodified` DATETIME, NOT NULL

---

### Scenario: Grades table records both peer and teacher grades

**Given** grades are assigned
**When** the database is provisioned
**Then** a `peerassign_grades` table exists with all required fields

Acceptance criteria:

- [x] `id` INT, Primary Key, auto-increment
- [x] `peerassignid` INT, Foreign Key, NOT NULL — references `peerassign.id`
- [x] `userid` INT, Foreign Key, NOT NULL — the student being graded
- [x] `attemptnum` INT, default 0 — which submission attempt this grade applies to
- [x] `peergrademethod` VARCHAR, default 'average' — how to aggregate peer grades ('average', 'median', 'highest')
- [x] `aggregatedpeergrade` DECIMAL(5,2), default NULL — computed peer grade
- [x] `teachergrade` DECIMAL(5,2), default NULL — instructor-assigned grade
- [x] `teacherfeedback` TEXT, default NULL — instructor comments
- [x] `teacherfeedbackformat` INT, default 0 — Moodle text format
- [x] `teacherid` INT, Foreign Key, default NULL — reference to grading teacher
- [x] `gradingvisible` INT (BOOLEAN), default 0 — teacher controls when grades are released to student
- [x] `timecreated` DATETIME, NOT NULL
- [x] `timemodified` DATETIME, NOT NULL
- [x] One record per (peerassignid, userid, attemptnum)

---

### Scenario: Phase completion tracking records student progress

**Given** a student completes a phase
**When** the database is provisioned
**Then** a `peerassign_phase_completion` table exists

Acceptance criteria:

- [x] `id` INT, Primary Key, auto-increment
- [x] `phaseid` INT, Foreign Key, NOT NULL — references `peerassign_phases.id`
- [x] `userid` INT, Foreign Key, NOT NULL — student
- [x] `completed` INT (BOOLEAN), default 0 — phase completion status
- [x] `timecompleted` DATETIME, default NULL — when phase was completed
- [x] `timecreated` DATETIME, NOT NULL
- [x] `timemodified` DATETIME, NOT NULL
- [x] One record per (phaseid, userid) combination

## Edge cases

- A student may not be assigned as a peer reviewer if they are absent or exempted from peer review. This is handled at the assignment generation layer, not the database schema.
- `reviewid` on peer_assignments may be NULL if the assigned reviewer has not yet submitted a review.
- Multiple submission attempts allow students to resubmit work. Each attempt gets its own grade record and peer review rounds.
- `aggregatedpeergrade` is computed from all peer reviews according to `peergrademethod` and is recalculated each time a new peer review is submitted.
- `blindreview` affects display only; the database stores reviewer identity on all records. Display logic controls anonymization.
- Phase completion is tracked separately from submission/grade records to handle phases with no grading (e.g., Sample & Description phase).
- A student cannot review their own submission; this constraint must be enforced at the application layer.
- Files are stored in Moodle's file API, not in the database, to support flexible storage backends.

## References

- `public/mod/peerassign/specs/introduction.md` — plugin behavior and workflow model
- Moodle Forum activity module (`mod_forum`) — reference for submission/discussion workflows
- Moodle Workshop activity module (`mod_workshop`) — reference for peer review patterns

### Scenario: persistence classes map to tables with correct field types and relations

**Given** the database schema is defined
**When** persistence classes are implemented
**Then** each class maps to the correct table with matching field types and relationships

Acceptance criteria:

- [x] `mod_peerassign\local\models\peerassign` class maps to `peerassign` table
- [x] `mod_peerassign\local\models\phase` class maps to `peerassign_phases` table
- [x] `mod_peerassign\local\models\submission` class maps to `peerassign_submissions` table
- [x] `mod_peerassign\local\models\peer_review` class maps to `peerassign_peer_reviews` table
- [x] `mod_peerassign\local\models\grade` class maps to `peerassign_grades` table
- [x] `mod_peerassign\local\models\phase_completion` class maps to `peerassign_phase_completion` table
- [x] All field types in persistence classes match the corresponding database schema field types
- [x] Foreign key relationships are represented in persistence classes (e.g., `phase` has a reference to `peerassignid`, `submission` has a reference to `phaseid`, etc.)
