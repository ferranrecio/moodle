# Create Phase Webservice Spec

## Purpose

This spec defines the Moodle external function to create a new phase in a `mod_peerassign` activity. It covers required and optional request parameters, validation and capability rules, sequencing behavior, persistence mapping to `peerassign_phases`, and the response contract.

## Background

`mod_peerassign` supports progressive phases (`sample`, `validation`, `submission`, `peer_review`, `teacher_eval`) stored in `peerassign_phases`. Teachers need an API-safe way to add new phases from the setup wizard and from external integrations. The webservice must follow Moodle external API patterns (`external_api`, `execute_parameters`, `execute`, `execute_returns`), validate context and capabilities, and accept a raw JSON payload for phase-specific settings.

The webservice name is `mod_peerassign_create_phase`.

## Behaviors

### Scenario: Webservice creates a phase with required fields and phase-specific JSON

**Why** Teachers need to programmatically add phases to activities via a structured API. The webservice must accept phase-specific configuration (via customdata) to support varied phase types (sample, submission, peer review, etc.) with different settings while ensuring data persists correctly.

**Given** an authenticated user with permission to manage a peerassign activity
**And** a valid `peerassignid` exists
**When** `mod_peerassign_create_phase` is called with required parameters
**Then** a new record is created in `peerassign_phases`

Acceptance criteria:

- [x] Required input fields are: `peerassignid`, `phasetype`, `sequencenumber`, `title`, `customdata`
- [x] `customdata` is accepted as `PARAM_RAW` in `execute_parameters`
- [x] `customdata` is JSON-encoded phase-specific settings and is validated before persistence
- [ ] `customdata` is persisted into `peerassign_phases.extras` as normalized JSON
- [x] `timecreated` and `timemodified` are set on insert
- [x] Response contains at minimum: `status`, `phaseid`, `peerassignid`, `message`
- [ ] `phasetype = sample` is valid for creating additional non-initial Sample & Description phases

---

### Scenario: Webservice accepts optional generic phase fields

**Why** Phases have common optional properties (description, unlock behavior, file settings) that apply across all phase types. The API must support partial requests where callers omit optional fields, with sensible defaults applied automatically.

**Given** a valid create request
**When** optional fields are provided
**Then** optional values are persisted to matching `peerassign_phases` fields

Acceptance criteria:

- [x] Optional fields accepted: `description`, `required`, `unlockmethod`, `unlockdate`, `allowfiles`, `filetypes`, `maxfilesize`
- [x] Missing optional fields default to schema defaults
- [x] `unlockmethod` only accepts supported values (`manual`, `date`)
- [x] `unlockdate` is required when `unlockmethod = date`
- [ ] `maxfilesize` is validated as non-negative integer

---

### Scenario: Webservice validates context and capability before create

**Why** Moodle webservices must enforce role-based access control. Only teachers with explicit permission to manage the activity should be able to create phases. This prevents unauthorized users from modifying activity workflows.

**Given** a request for `mod_peerassign_create_phase`
**When** the function executes
**Then** Moodle context and capability checks run before any write occurs

Acceptance criteria:

- [x] The activity context is loaded from `peerassignid`
- [x] `self::validate_context($context)` is called
- [x] Caller must have module management capability for the target activity context
- [x] If capability fails, no DB write occurs and an exception is returned

---

### Scenario: Webservice validates and normalizes phase sequence

**Why** Phase order drives the student workflow. Duplicate sequence numbers would create ambiguity in phase progression. The system must ensure each phase has a deterministic, unique position, either by rejecting conflicts or automatically reordering.

**Given** a valid request for phase creation
**When** `sequencenumber` conflicts with an existing phase
**Then** sequencing remains deterministic and consistent

Acceptance criteria:

- [ ] `sequencenumber` must be >= 1
- [ ] Duplicate sequence positions are resolved by shifting subsequent phases OR the request is rejected with a clear error (implementation must choose one behavior and document it)
- [ ] Final stored order is unique per `peerassignid`

---

### Scenario: Webservice enforces `customdata` rules by phase type

**Why** Different phase types require different settings (e.g., peer_review needs reviewer count, validation needs checklists). JSON Schema validation ensures only valid phase-specific configurations are persisted, preventing data corruption and runtime errors.

**Given** a create request with `phasetype` and `customdata`
**When** phase-type-specific rules are evaluated
**Then** invalid payloads are rejected and valid payloads are accepted

Acceptance criteria:

- [ ] `customdata` must be a valid JSON object (not array/scalar)
- [ ] `customdata` is validated against a JSON Schema provided by the selected phase type validator
- [ ] Unknown top-level keys are either dropped or rejected consistently (implementation must choose one behavior and document it)
- [ ] For `peer_review` phase, reviewer-related settings in `customdata` are validated (e.g., reviewer count > 0)
- [ ] For `validation` phase, condition/checklist-related settings in `customdata` are validated
- [ ] For `sample` phase, media/instruction settings in `customdata` are validated

---

### Scenario: Webservice distinguishes initial sample protection from teacher-created sample phases

**Why** Activities always include one protected initial Sample & Description phase, but teachers may add more sample phases later. The create API must support these additions without changing deletion protections for the initial phase.

**Given** an activity already has its auto-created initial Sample & Description phase
**When** `mod_peerassign_create_phase` is called with `phasetype = sample`
**Then** an additional non-initial sample phase is created successfully

Acceptance criteria:

- [ ] Create requests for additional sample phases are accepted when capability and validation pass
- [ ] Newly created sample phases are flagged as non-initial phases in persisted state
- [ ] The create API does not alter protections applied to the auto-created initial sample phase
- [ ] PHPunit tests verify that extra sample phases can be created in the activity and that the initial sample phase remains protected against deletion

---

### Scenario: Webservice rejects malformed input with Moodle-standard errors

**Why** Consistent error reporting helps API clients diagnose problems quickly. Moodle-standard exceptions ensure errors are properly logged and consumable by web service clients and mobile apps.

**Given** invalid request data
**When** validation fails
**Then** the function returns a clear error and no partial write

Acceptance criteria:

- [x] Missing required parameter raises invalid parameter exception
- [x] Non-JSON `customdata` raises invalid parameter exception
- [ ] JSON Schema validation failures for `customdata` return a specific documented webservice error code
- [ ] Invalid `phasetype` raises invalid parameter exception
- [x] Invalid `peerassignid` raises not-found style exception
- [x] Errors use Moodle exception flow and are consumable by WS clients

---

### Scenario: Webservice write is atomic

**Why** Multi-step database operations (normalization, validation, insert) must all succeed or all fail together. Partial writes would leave the database in an inconsistent state where a phase record exists without proper normalization.

**Given** a create request that requires normalization and insert
**When** any step fails during write
**Then** no partial phase data remains in the database

Acceptance criteria:

- [ ] DB writes execute inside a delegated transaction
- [ ] On exception, transaction is rolled back
- [ ] On success, transaction is committed once

---

### Scenario: Webservice definition is exposed in db/services.php

**Why** Moodle's webservice discovery system reads `db/services.php` to register available functions, assign access restrictions, and document parameters. Without this registration, the function is not discoverable to administrators or clients.

**Given** the plugin provides a callable external function
**When** webservice metadata is registered
**Then** the function is discoverable for service configuration

Acceptance criteria:

- [x] `mod_peerassign_create_phase` is declared in `db/services.php`
- [x] Class and method target the external function implementation
- [x] Function is marked with appropriate access restrictions for authenticated users
- [ ] Function description documents that `customdata` is a JSON-encoded payload accepted via `PARAM_RAW`

## Edge cases

- `sequencenumber` larger than current max + 1 is normalized to append, or rejected consistently.
- Empty JSON object `{}` is valid for phase types that do not require extra settings.
- `customdata` containing very large payloads must be bounded by a documented size limit.
- Concurrent create requests for the same `peerassignid` should not leave duplicate sequence numbers.
- `unlockdate` in the past is allowed only if explicitly permitted by activity rules.

## References

- `public/mod/peerassign/specs/introduction.md` — plugin behavior and phase model
- `public/mod/peerassign/specs/database/schema.spec.md` — table fields and constraints
- Moodle external functions API (`external_api`, parameter/return structures, context validation)
