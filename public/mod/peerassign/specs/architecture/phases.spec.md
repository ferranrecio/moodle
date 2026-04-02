# Phase Architecture Spec

## Purpose

This spec defines the class architecture for phase logic and phase management rendering in `mod_peerassign`.

## Background

The phase management page shows all configured phases when a teacher opens the activity. The implementation needs two aligned hierarchies:

- Domain logic classes in `mod_peerassign\local\phase`
- Rendering classes in `mod_peerassign\output\local\phase`

Each phase type (sample, validation, submission, peer review, teacher evaluation) has its own logic and output classes while sharing common behavior through base classes.

## Behaviors

### Scenario: Teacher activity view includes phase management list

**Why** Teachers need immediate visibility of the configured workflow when entering the activity. Rendering phases in one place reduces setup errors and makes reordering/configuration tasks discoverable.

**Given** a teacher opens a Peer Review Assignment activity
**When** the activity page is rendered
**Then** the current phases are shown through the phase management UI contract

Acceptance criteria:

- [ ] Activity teacher view uses a dedicated phase management render path
- [ ] Render path receives all current phases in deterministic sequence order
- [ ] The render path delegates per-phase card rendering to phase manage output classes

---

### Scenario: Base phase logic class defines common phase API

**Why** Shared behavior (loading extras JSON, exposing common fields, generic validation hooks) should not be duplicated in each phase type implementation.

**Given** multiple phase types with type-specific behavior
**When** phase logic classes are implemented
**Then** they all extend a common base class `mod_peerassign\local\phase`

Acceptance criteria:

- [ ] `mod_peerassign\local\phase` is the abstract/common parent for all phase types
- [ ] Each phase type has a concrete class under `mod_peerassign\local\phase\*`
- [ ] Base class provides common getters for persisted phase fields
- [ ] Base class provides methods to decode and expose per-phase `extras` JSON safely
- [ ] Base class defines extension hooks for per-phase validation or normalization

---

### Scenario: Phase manage outputs use a common export base class

**Why** The phase management page needs consistent export payload shape while still allowing each phase type to add type-specific fields.

**Given** each phase type renders as a management card
**When** output classes export template data
**Then** each output extends `mod_peerassign\output\local\phase_manage`

Acceptance criteria:

- [ ] `mod_peerassign\output\local\phase_manage` provides shared export logic for all phase cards
- [ ] Each phase type has one output class in `mod_peerassign\output\local\phase`
- [ ] Per-type output class naming follows `<phase>-manage` convention at spec level and maps to valid PHP class names in implementation
- [ ] Shared export payload includes id, sequence, title, phase type, and card action metadata
- [ ] Per-type output classes can append additional template fields without changing shared keys

---

### Scenario: Phase manage outputs are named templatable with explicit template mapping

**Why** Named templates allow each phase card to map to a specific Mustache template while preserving a common render contract.

**Given** phase cards are rendered with Mustache templates
**When** output classes are built
**Then** all phase manage output classes implement `named_templatable`

Acceptance criteria:

- [ ] `mod_peerassign\output\local\phase_manage` implements `named_templatable`
- [ ] Child outputs inherit or override template names as needed
- [ ] Template files are located under `templates/local/phase/` and names align with phase type manage outputs
- [ ] Template naming and class naming are documented with a deterministic mapping rule
- [ ] Missing template mappings fail early with a developer-facing error

---

### Scenario: Phase type logic and rendering remain aligned

**Why** If phase logic classes and output classes drift apart, UI cards may display wrong or incomplete configuration values.

**Given** each phase has a logic class and an output class
**When** phase data is exported for management UI
**Then** output data is sourced from the corresponding phase logic object

Acceptance criteria:

- [ ] Each phase output receives a concrete phase logic instance of the same type
- [ ] Export methods avoid direct DB access and rely on phase logic getters
- [ ] Changes in phase-specific `extras` fields are reflected through phase logic methods before render

### Scenario: A specific phase instance can be created from the phase models class instances

**Why** A class provides a factory method to create new phases of a given type with default values. This ensures new phases are initialized correctly according to their type. The factory will use mod_peerassign\local\models\phase as a data container and return a fully instantiated phase logic object.

**Given** a request to create a new phase of a specific type
**When** the factory method is called with a phase model instance containing the desired type
**Then** a new phase logic object of the correct type is returned with defaults applied

Acceptance criteria:

- [ ] The factory method accepts a phase model instance with at least `peerassignid` and `phasetype` set
- [ ] The factory method returns an instance of the correct phase logic class based on `phasetype`
- [ ] The returned phase logic object has default values applied according to its type (for example, a sample phase has its `extras` initialized with default instructional fields)
- [ ] Invalid or unsupported `phasetype` values result in a clear exception without side effects
- [ ] PHPUnit tests cover factory behavior for all supported phase types and invalid input cases
- [ ] The factory class namespace will be mod_peerassign\local\phase_factory and the method signature will be something like `public static function create_from_model(phase_model $model): phase` where `phase` is the base logic class and concrete classes extend it.
- [ ] The factory method does not perform any DB writes; it only returns instantiated objects. Persistence is handled separately by the phase management logic.

## Edge cases

- Unknown phase type values are rejected before phase logic or output instantiation.
- A missing phase type class or missing phase manage output class produces a deterministic developer error.
- Invalid or malformed `extras` JSON does not break page rendering; base phase logic returns safe defaults and records a validation issue.

## References

- `public/mod/peerassign/specs/README.md` - spec structure and conventions
- `public/mod/peerassign/specs/businessrules/phase-general.spec.md` - shared behavior across phase types
- Moodle output contracts (`renderable`, `templatable`, `named_templatable`) and Mustache template conventions
