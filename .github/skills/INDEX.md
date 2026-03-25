# Skills Index

This directory contains custom Copilot skills for this repository.

## Available skills

- **Moodle Dependency Injection**
  - Path: `.github/skills/moodle-dependency-injection/SKILL.md`
  - Purpose: Implement and refactor Moodle code to use `\core\di` with constructor injection, DI hooks, and test mocking patterns.

- **Moodle Course Format Plugin Implementation**
  - Path: `.github/skills/moodle-course-format-plugin-implementation/SKILL.md`
  - Purpose: Implement/scaffold Moodle `format_*` plugins with modern output architecture, editor/state actions, and core format patterns from `public/course/format`.

- **Moodle Mod Plugin Implementation**
  - Path: `.github/skills/moodle-mod-plugin-implementation/SKILL.md`
  - Purpose: Implement/scaffold Moodle activity modules (`mod/*`) using official mod, course overview, and visibility APIs, with examples from `mod_data`, `mod_assign`, and `mod_subsection`.

- **Moodle Mod Backup Restore**
  - Path: `.github/skills/moodle-mod-backup-restore/SKILL.md`
  - Purpose: Make `mod/*` plugins compatible with Moodle backup/restore by mapping `db/install.xml` tables into backup XML, restoring IDs/files/dates correctly, and following core module patterns.

- **Moodle Plugin Common Files**
  - Path: `.github/skills/moodle-plugin-common-files/SKILL.md`
  - Purpose: Create/audit the standard Moodle plugin file set using official Common files guidance (required files, optional files, and legacy caveats).

- **Moodle Plugin Webservice**
  - Path: `.github/skills/moodle-plugin-webservice/SKILL.md`
  - Purpose: Create and refactor Moodle plugin web services using `db/services.php`, `external_api`, and exporter-backed response schemas.

- **Moodle Persistent Classes**
  - Path: `.github/skills/moodle-persistent-classes/SKILL.md`
  - Purpose: Create and use Moodle `core\persistent` classes for DB-backed models, including property definitions, validation, lifecycle hooks, and usage patterns in services/APIs.

- **Moodle Routing Subsystem**
  - Path: `.github/skills/moodle-routing-subsystem/SKILL.md`
  - Purpose: Implement and refactor Moodle routes (controller/api/shim), including parameter schemas, response patterns, shortlinks, and route testing.

- **Moodle Output Rendering**
  - Path: `.github/skills/moodle-output-rendering/SKILL.md`
  - Purpose: Apply Moodle output architecture best practices across entry points, output classes, renderers, templates, and JS initialization.

- **Rubrics in Moodle Activity**
  - Path: `.github/skills/rubrics-in-activity/SKILL.md`
  - Purpose: Implement rubric-based advanced grading in activity modules using `gradingform_rubric` and `mod_assign` integration patterns.

- **Rubrics Testing in Moodle Activity**
  - Path: `.github/skills/rubrics-testing/SKILL.md`
  - Purpose: Build and maintain tests for rubric setup, submission payloads, grading outcomes, and external rubric endpoints.

## When to use which skill

- Use **Moodle Dependency Injection** when introducing `\core\di` into services/plugins, replacing globals, wiring DI hook definitions, or adding DI-based unit test mocks.

- Use **Moodle Course Format Plugin Implementation** when creating or refactoring a `course/format/*` plugin, especially for `format.php` orchestration, `format_<name>` base class methods, renderer/output overrides, and course editor mutations/state actions.

- Use **Moodle Mod Plugin Implementation** when building or refactoring an activity module in `mod/*`, especially for callbacks, `mod_form.php`, course overview integration, and module visibility/display behavior.

- Use **Moodle Mod Backup Restore** when creating or fixing `mod/*/backup/moodle2/*` classes, especially for deriving the backup tree from `db/install.xml`, restoring mapped IDs/files, and validating round-trip backup/restore behavior.

- Use **Moodle Plugin Common Files** when creating a new plugin structure or validating that a plugin includes the required and recommended standard files.

- Use **Moodle Plugin Webservice** when creating or reviewing plugin external functions in `classes/external`, registering them in `db/services.php`, or reusing exporters to define web service response structures.

- Use **Moodle Persistent Classes** when creating/refactoring DB-backed models in `classes/` that should extend `\core\persistent`, or when moving CRUD/validation patterns from raw `$DB` calls into persistent-based models.

- Use **Moodle Routing Subsystem** when creating or migrating routes with `#[route(...)]`, defining typed path/query/header parameters, adding legacy shim redirects, implementing shortlink handlers, or writing `\route_testcase` coverage.

- Use **Moodle Output Rendering** when implementing or reviewing Moodle UI code to keep data/logic/rendering responsibilities correctly separated.

- Use **Rubrics in Moodle Activity** when you are implementing or fixing runtime rubric integration in an activity (feature support, area mapping, grading form wiring, save flow).
- Use **Rubrics Testing in Moodle Activity** when you are creating/updating PHPUnit coverage for rubric definitions, submissions, grade outcomes, or rubric external endpoints.
