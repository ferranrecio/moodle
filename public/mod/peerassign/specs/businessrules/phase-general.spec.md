# General Phase Business Rules Spec

## Purpose

This spec defines business rules that apply to all phase types in `mod_peerassign`.

## Background

All phases are stored in `peerassign_phases`, share common fields, and include phase-specific configuration inside `extras` JSON. Shared behavior is implemented in the base logic class `mod_peerassign\local\phase`, while phase-specific behavior is implemented in subclasses.

## Behaviors

### Scenario: Common phase fields are consistently available for all phase types

**Why** All downstream logic (UI, webservices, progression checks) relies on a predictable common phase payload.

**Given** a phase record exists in `peerassign_phases`
**When** phase data is accessed through domain logic
**Then** common fields are exposed consistently regardless of phase type

Acceptance criteria:

- [ ] Base phase logic exposes at minimum id, peerassignid, phasetype, sequence, title, description, required, unlock fields, visibility, and timestamps
- [ ] Common getters return normalized scalar types suitable for templates and validation
- [ ] Missing optional fields return deterministic defaults

---

### Scenario: `extras` JSON is decoded and accessed through base phase methods

**Why** Each phase type has different JSON shape, but consumers should use a uniform API instead of decoding raw JSON repeatedly.

**Given** phase-specific data is stored in `peerassign_phases.extras`
**When** business logic or output needs phase-specific values
**Then** values are obtained through `mod_peerassign\local\phase` methods

Acceptance criteria:

- [ ] Base phase class decodes `extras` JSON safely and caches parsed representation per instance
- [ ] Base class exposes methods to retrieve raw extras object and typed values with defaults
- [ ] Malformed JSON is handled deterministically (validation error path plus safe runtime fallback)
- [ ] Callers outside phase logic do not decode `extras` directly

---

### Scenario: Phase type class resolution is deterministic

**Why** Correct type-to-class mapping is required to instantiate the right business logic and rendering behavior.

**Given** a phase record has a `phasetype`
**When** the system resolves domain class and output class
**Then** the same deterministic mapping rule is used everywhere

Acceptance criteria:

- [ ] Supported types map to concrete classes: sample, validation, submission, peer_review, teacher_eval
- [ ] Unknown types are rejected before any write or render side effects
- [ ] Mapping rules are documented in one canonical place and reused by all call sites

---

### Scenario: All phase operations enforce activity context and manage capability

**Why** Phase definitions affect student workflow and grading, so unauthorized changes are a security risk.

**Given** a request to create, update, delete, reorder, or manage a phase
**When** authorization is checked
**Then** activity context and module-management capability are validated first

Acceptance criteria:

- [ ] Context is resolved from the target activity before phase writes
- [ ] Unauthorized actions fail with Moodle-standard permission exceptions
- [ ] No phase side effects occur when permission checks fail
- [ ] PHPUnit tests cover unauthorized attempts for each phase operation type

## Edge cases

- `extras` can be empty (`{}`) for phase types with no additional settings.
- A legacy record with unknown keys in `extras` is tolerated only according to explicit normalization policy.
- Large `extras` payloads are bounded by documented validation limits.

## References

- `public/mod/peerassign/specs/architecture/phases.spec.md` - class hierarchy and output contracts
- `public/mod/peerassign/specs/webservices/create-phase.spec.md` - API-level phase creation rules
- `public/mod/peerassign/specs/database/schema.spec.md` - `peerassign_phases` schema
