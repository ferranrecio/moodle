---
name: Moodle Course Format Plugin Implementation
description: Implement or scaffold Moodle course format plugins (`format_*`) using Moodle 5.2 format API docs and deep core examples from `public/course/format`.
---

# Moodle Course Format Plugin Implementation

Use this skill when creating a new course format plugin, migrating an old format to modern output architecture, or implementing format-specific editor/state behavior.

## Canonical documentation

- https://moodledev.io/docs/5.2/apis/plugintypes/format

## Deep in-repo references

- Core format subsystem:
  - `public/course/format/lib.php`
  - `public/course/format/update.php`
  - `public/course/format/classes/base.php`
  - `public/course/format/classes/stateactions.php`
  - `public/course/format/classes/output/section_renderer.php`

- Production format plugins:
  - `public/course/format/topics`
  - `public/course/format/weeks`
  - `public/course/format/singleactivity`
  - `public/course/format/social`

- Practical examples to copy:
  - Base class + options + navigation: `public/course/format/topics/lib.php`, `public/course/format/weeks/lib.php`
  - Alternative format behavior (single activity): `public/course/format/singleactivity/lib.php`
  - Special section visibility and social behavior: `public/course/format/social/lib.php`
  - Render entrypoint pattern: `public/course/format/topics/format.php`, `public/course/format/weeks/format.php`
  - Output override classes: `public/course/format/topics/classes/output/courseformat/content.php`
  - Section control menu override: `public/course/format/topics/classes/output/courseformat/content/section/controlmenu.php`
  - Format-specific state actions: `public/course/format/topics/classes/courseformat/stateactions.php`
  - Frontend mutations/actions: `public/course/format/topics/amd/src/mutations.js`, `public/course/format/topics/amd/src/section.js`

## Minimum plugin skeleton (`course/format/<name>/`)

Required:

1. `version.php`
2. `lib.php` (must define class `format_<name>` extending `core_courseformat\base`)
3. `format.php` (main render entrypoint included by `course/view.php`)
4. `lang/en/format_<name>.php` (must include `pluginname`; also define `sectionname`)
5. `classes/output/renderer.php` (format renderer extending `core_courseformat\output\section_renderer`)

Common optional files:

- `settings.php`
- `db/upgrade.php`
- `classes/courseformat/stateactions.php`
- `amd/src/*.js` for format-specific editor mutations
- `classes/output/courseformat/**` output override classes
- `templates/local/**` and related template structure when overriding mustache blocks

## `format.php` rendering contract

Preferred pattern (topics/weeks):

1. Build format instance with `course_get_format($course)`
2. Prepare course/section state (`set_sectionnum`, ensure section 0 if needed)
3. Use format renderer (`$PAGE->get_renderer('format_<name>')`)
4. Resolve output class via `$format->get_output_classname('content')`
5. Render widget through renderer

Keep `format.php` thin: orchestration only, rendering logic in output classes/renderer.

## Base class design in `lib.php`

Implement class `format_<name> extends core_courseformat\base` and override only what the UX requires.

High-value methods frequently used in core formats:

- Layout/navigation:
  - `uses_sections()`
  - `uses_course_index()`
  - `get_view_url()`
  - `extend_course_navigation()`
  - `page_title()`

- Editor/reactive support:
  - `supports_components()` (enable component-based editor)
  - `supports_ajax()`
  - `ajax_section_move()`

- Section naming/visibility:
  - `get_section_name()`
  - `get_default_section_name()`
  - `get_section_highlighted_name()`
  - `is_section_visible()`

- Format options and forms:
  - `course_format_options($foreditform = false)`
  - `section_format_options($foreditform = false)` (if needed)
  - `create_edit_form_elements(...)` (advanced form behavior)

- Defaults/behavior:
  - `get_default_blocks()`
  - `allow_stealth_module_visibility(...)` (if format-specific handling required)

## Output architecture (Moodle 4+ and 5.x)

Primary rendering model is output classes + templates.

1. Required renderer class
   - `classes/output/renderer.php`
   - extend `core_courseformat\output\section_renderer`

2. Override output classes (recommended)
   - Place classes under `classes/output/courseformat/...`
   - Example: `format_topics\output\courseformat\content`
   - Use `get_template_name()` and/or `export_for_template()` as needed

3. Template strategy
   - If overriding templates, keep the expected `local/content` structure coherent.
   - Choose strategy based on scope:
     - small UI tweaks: override a few blocks
     - major UI changes: keep more intermediate structure for long-term stability

Note: core formats in this repo often rely heavily on output-class overrides and may not need local templates for every change.

## Course editor and state actions

For format-specific edit actions (beyond core actions):

1. Backend state actions
   - Create `classes/courseformat/stateactions.php`
   - Extend `core_courseformat\stateactions`
   - Implement action methods with standard signature (`stateupdates`, `course`, `ids`, targets)

2. Frontend mutations
   - Add `amd/src/mutations.js`
   - Register with `courseEditor.addMutations(...)`
   - Map content actions to mutation names

3. UI hooks for actions
   - Add `data-action` attributes in output/controlmenu classes
   - See topics control menu and section JS integration

4. Non-AJAX execution path
   - Actions can be executed through `course/format/update.php` with action params and sesskey
   - Support confirmations through courseupdate output class conventions when required

## Practical patterns from core formats

- `format_topics`
  - Strong reference for modern component-based format + custom section highlight action
  - Includes output class overrides and JS mutation integration

- `format_weeks`
  - Good reference for time-based section naming and current section behavior

- `format_singleactivity`
  - Reference for special-format behavior using `main_activity_interface`
  - Capability-aware activity type management and course-form integration

- `format_social`
  - Reference for constrained section model, social block defaults, and specific visibility behavior

## Common pitfalls to avoid

- Putting heavy logic in `format.php` instead of output classes/base class.
- Enabling reactive editor UI without preserving expected data attributes and fragment rendering hooks.
- Overriding too many renderer methods when output-class overrides are sufficient.
- Defining format option names that collide with core `course`/`course_sections` fields.
- Forgetting `sectionname` language string (can be requested unconditionally).

## Practical output style for this skill

When applying this skill in chat:

- Propose a phased implementation plan: **skeleton**, **base class behavior**, **rendering**, **editor actions**, **settings/upgrade**.
- Reference exact paths under `course/format/<name>/`.
- Prefer copying patterns from `topics`, `weeks`, `singleactivity`, or `social` before introducing new architecture.
- Explicitly call out performance-sensitive points (reactive callbacks, section rendering, mutation-driven updates).
