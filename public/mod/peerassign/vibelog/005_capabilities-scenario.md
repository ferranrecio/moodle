# Capabilities scenario implementation summary

## Approach

Implemented the `architecture/capabilities.spec.md` scenario by aligning capability definitions, language strings, permission helpers, and runtime checks.

## What was implemented

- Added `mod/peerassign:addphase` capability in `db/access.php` with module context and teacher/manager archetypes.
- Added `peerassign:addphase` language string in `lang/en/peerassign.php`.
- Added permission helper methods in `classes/permissions.php`:
  - `can_add_phase($context): bool`
  - `can_add_instance($context): bool`
- Updated `classes/external/create_phase.php` to enforce phase creation access via `permissions::can_add_phase()` and throw `required_capability_exception` when denied.
- Updated `db/services.php` service-level `requiredcapability` to `mod/peerassign:addphase`.
- Marked all acceptance criteria as complete in `specs/architecture/capabilities.spec.md`.

## Deviations from spec

No functional deviations. The scenario required helper methods and capability alignment; implementation follows this directly.

## Spec changes made

Updated checklist items in `specs/architecture/capabilities.spec.md` from unchecked to checked after implementation.

## Skills created or updated

No new Copilot skill files were created or modified.

## Review notes

### Prompt used

Using how it is described in README.md, review that the Scenario: all activity capabilities are defined and enforced through permissions helpers is fully implemented. Add any necessary capability, lang string, and permissions method necessary.

### Review findings

- It works as expected.
