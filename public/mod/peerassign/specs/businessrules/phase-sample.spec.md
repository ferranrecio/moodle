# Sample Phase Business Rules Spec

## Purpose

This spec defines business rules specific to the Sample & Description phase type.

## Background

Every activity starts with one auto-created initial sample phase. Teachers may add additional non-initial sample phases. Sample phases provide orientation material before or between workflow stages.

## Behaviors

### Scenario: Initial sample phase exists by invariant

**Why** Every activity requires a stable instructional baseline.

**Given** a new Peer Review Assignment activity is created
**When** initial setup completes
**Then** one initial Sample & Description phase exists

Acceptance criteria:

- [ ] Initial sample phase is created automatically with sequence 1
- [ ] Initial sample phase is explicitly flagged as initial/protected
- [ ] Initial sample phase title and defaults follow plugin defaults

---

### Scenario: Additional sample phases can be created by teachers

**Why** Teachers may need extra instructional checkpoints between other phases.

**Given** an activity already has its initial sample phase
**When** teacher creates a new phase with `phasetype = sample`
**Then** a non-initial sample phase is created

Acceptance criteria:

- [ ] Teacher-created sample phases are normal phases with `phasetype = sample`. Only the initial sample phase has the protected flag.
- [ ] Teacher-created sample phases follow standard sequencing rules
- [ ] Create validation for sample-specific extras is applied

---

### Scenario: Sample phase supports instructional media configuration

**Why** Teachers use sample phases to provide files, videos, audio, and explanatory notes.

**Given** a sample phase is configured
**When** sample-specific description fields are provided
**Then** the phase can have description and attached files that follow sample phase policies

Acceptance criteria:

- [ ] When adding a sample phase, the teacher can define the description and attach files according to the sample phase configuration schema
- [ ] File usage aligns with sample file area policies

## Edge cases

- Deleting the initial sample phase is never allowed.
- Deleting a non-initial sample phase follows generic safe-delete rules.
- Empty sample phases are not valid, each sample phase must have at least a non-empty description or an attached file to be considered valid.

## References

- `public/mod/peerassign/specs/businessrules/phase-management.spec.md` - phase lifecycle and delete protections
- `public/mod/peerassign/specs/businessrules/phase-general.spec.md` - shared phase behavior
- `public/mod/peerassign/specs/introduction.md` - sample phase role in workflow
