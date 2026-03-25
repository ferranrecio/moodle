# Output Classes Manager Instance Constructor Parameter

## Implementation summary

Refactored output classes to accept a `manager` instance as the first constructor parameter, following Moodle best practices for activity module UI composition.

**Changes:**

- Updated `activity_view` constructor to accept `manager` as first parameter
- Refactored `activity_view` to retrieve `peerassign` instance and coursemodule from manager
- Updated `view.php` to instantiate `manager` and pass it to `activity_view` constructor

This pattern allows output classes to access activity data consistently through the manager's API rather than duplicating data access logic.

## Deviations from spec

No deviations. The output class now follows the spec exactly.

## Spec changes during implementation

- Marked output class constructor parameter acceptance criterion as complete in `specs/architecture/classes.spec.md`.

## Skills created/updated

No new Copilot skills created or updated.

## Reviewer notes

### Prompt used

Implement the Scenario: output classes first construct param should be a manager instance specs

### Review notes

- All attributes are created the old way. I moved them to the construct params manually.
- The `view.php` is still using get_records instead of rely on the manager. I edit it manually.
- The manager is missing all getters. I added them using copilot.
- I needed to force copilot to update all hard-coded references to the plugfin name to use the manager constants. Prompt: *don't use 'peerassign' or 'mod_peerassign' , use the manager constants instead*
