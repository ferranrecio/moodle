# Classes Architecture Spec

## Purpose

This spec defines a minimal baseline class architecture for `mod_peerassign`. It documents current class responsibilities, boundaries, and interaction points so future changes can stay consistent and maintainable.

## Background

`mod_peerassign` currently uses a small set of classes split by concern:

- Main plugin namespace classes for business and permission helpers (`manager`, `permissions`)
- `external` classes for web service endpoints
- `output` classes for renderable/template UI composition
- `local` classes for domain-specific internals as needed.

This architecture should remain simple while the plugin grows.

## Behaviors

### Scenario: Manager class owns activity lifecycle helpers

**Given** plugin-level needs to instantiate a manager class
**When** activity lifecycle events occur (create, delete, view, etc.)
**Then** `mod_peerassign\\manager` has all the necessary methods.

Acceptance criteria:

- [x] `manager` class has all the static creators like other modules `create_from_instance`, `create_from_coursemodule`, and `create_from_data_record`. For example: `public/mod/data/classes/manager.php`, `public/mod/subsection/classes/manager.php`, or `public/mod/h5pactivity/classes/local/manager.php`
- [x] Entry points/hooks call `manager` methods instead of duplicating lifecycle SQL
- [X] The `manager` class has several static methods to create an instance of the manager class, similar to `public/mod/data/classes/manager.php`, `public/mod/subsection/classes/manager.php`.
- [X] The `manager` class has a $course attribute that is set in the constructor and a `get_course()` method to return it, to avoid having to call `get_course()` separately in the view and other places.
- [x] There's a PHPUnit test that covers the manager static creators to ensure they work as expected, and any other public methods on the manager class.

---

### Scenario: Permissions class centralizes capability checks

**Given** multiple entry points need role/capability decisions
**When** permission checks are required
**Then** `mod_peerassign\permissions` provides reusable capability helper methods

Acceptance criteria:

- [x] Capability helper methods exist for view/manage/submit/review/grade use-cases
- [x] `peerassign_pluginfile()` calls `permissions::can_view_files()` before serving files
- [ ] File-area-specific access logic is expanded beyond the current broad `mod/peerassign:view` check

---

### Scenario: persistent classes must exists per each table

**Given** the need to interact with the database tables
**When** performing CRUD operations on the plugin's data
**Then** there are persistent classes that represent each table and handle database interactions

Acceptance criteria:

- [x] All persistent classes should be located in `mod_peerassign\local\models` namespace to follow Moodle's standard structure for models
- [x] There is a persistent class for each of the plugin's tables (e.g., `phase`, `submission`, `peer_review`, `grade`, etc.)
- [x] These classes use Moodle's persistent patterns and provide methods extending `public/lib/classes/persistent.php`

### Scenario: output classes first construct param should be a manager instance

**Given** the need to render UI components that depend on activity state
**When** constructing output classes for rendering
**Then** the first parameter should be a `manager` instance to provide necessary context and data

Acceptance criteria:

- [x] All output classes in `mod_peerassign\output` namespace have constructors that accept a `mod_peerassign\manager` instance as the first parameter

## References

- `public/mod/peerassign/specs/README.md` — spec file structure and guidelines
- `public/mod/peerassign/specs/architecture/testing.spec.md` — testing architecture and generator patterns
