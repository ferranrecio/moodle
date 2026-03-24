---
name: Moodle Mod Plugin Implementation
description: Implement or scaffold a Moodle activity module (mod plugin) using Moodle 5.2 plugintype documentation, including course overview and visibility/display APIs, with patterns from mod_data, mod_assign, and mod_subsection.
---

# Moodle Mod Plugin Implementation

Use this skill when creating a new activity module in `mod/` or when refactoring an existing mod plugin to current Moodle patterns.

## Canonical documentation

- https://moodledev.io/docs/5.2/apis/plugintypes/mod
- https://moodledev.io/docs/5.2/apis/plugintypes/mod/courseoverview
- https://moodledev.io/docs/5.2/apis/plugintypes/mod/visibility

## In-repo reference implementations

- General mod structure and callbacks:
  - `public/mod/data`
  - `public/mod/assign`
  - `public/mod/subsection`
- Course overview integration:
  - `public/mod/data/classes/courseformat/overview.php`
  - `public/mod/assign/classes/courseformat/overview.php`
- Visibility/display integration:
  - `public/mod/assign/lib.php` (`mod_assign_cm_info_dynamic`, `assign_get_coursemodule_info`)
  - `public/mod/subsection/lib.php` (`subsection_cm_info_dynamic`, `subsection_cm_info_view`, `subsection_get_coursemodule_info`)

## Minimum mod plugin skeleton checklist

1. `version.php`
   - Define component (`mod_<name>`), version, requires, maturity, release.

2. `db/install.xml`
   - Include plugin main table named exactly as module name.
   - Include standard fields: `id`, `course`, `name`, `timemodified`, plus `intro`/`introformat` if using `FEATURE_MOD_INTRO`.

3. `lang/en/<modname>.php`
   - Must include `pluginname` and key UI strings.

4. `lib.php`
   - Implement required callbacks:
     - `<modname>_add_instance($data, $mform = null): int`
     - `<modname>_update_instance($data, $mform = null): bool`
     - `<modname>_delete_instance($id): bool`
   - Implement `<modname>_supports($feature)` and declare relevant features including `FEATURE_MOD_PURPOSE`.

5. `mod_form.php`
   - Define class `mod_<modname>_mod_form` for add/edit instance form.

6. `view.php`
   - Main activity page using CM id (`id`) lookup flow.

7. `index.php`
   - Prefer redirect to course overview:
     - `\core_courseformat\activityoverviewbase::redirect_to_overview_page($courseid, '<modname>');`
   - See `public/mod/data/index.php` and `public/mod/assign/index.php`.

8. `db/access.php`
   - Include at minimum:
     - `mod/<modname>:addinstance`
     - `mod/<modname>:view`

9. `db/upgrade.php`
   - Keep schema changes aligned with `db/install.xml`; bump `version.php` accordingly.

## Course overview integration (Moodle 5.x)

Implement class:

- File: `mod/<modname>/classes/courseformat/overview.php`
- Namespace: `mod_<modname>\courseformat`
- Class: `overview extends \core_courseformat\activityoverviewbase`

Key methods:

- `get_due_date_overview(): ?overviewitem`
- `get_actions_overview(): ?overviewitem`
- `get_extra_overview_items(): array`
- Optional for multi-grade plugins: `get_grade_item_names(array $items): array`

Patterns to copy:

- Actions + badges: `public/mod/assign/classes/courseformat/overview.php`
- Multi-column extra metrics: `public/mod/data/classes/courseformat/overview.php`

Notes:

- Return `overviewitem` values suitable for filtering (`value`) and rendering (`content`).
- Prefer renderable/exportable content for webservice/app compatibility.
- Use DI constructor arguments where needed; call `parent::__construct($cm)`.

## Visibility and display API integration

Use callbacks in `lib.php`:

1. `<modname>_get_coursemodule_info($coursemodule)`
   - Return `cached_cm_info` for cacheable metadata (`name`, `content`, `customdata`, etc).

2. `mod_<modname>_cm_info_dynamic(cm_info $cm)`
   - Fast per-user/per-request adjustments.
   - Keep it lightweight (avoid DB queries).
   - Can change visibility/link behaviour.

3. `<modname>_cm_info_view(cm_info $cm)`
   - Course-page-only dynamic output.
   - DB queries are acceptable but keep efficient across many instances.

Examples:

- Override date/customdata per user: `public/mod/assign/lib.php`
- No view link + delegated section content rendering: `public/mod/subsection/lib.php`

If activity should not expose a standard view link, return `FEATURE_NO_VIEW_LINK` in `<modname>_supports` and/or call `$cm->set_no_view_link()` in dynamic callback (as appropriate).

## Design guidance from examples

- Keep `lib.php` as callback bridge; move logic to classes.
- Use match-based `*_supports()` to keep feature declarations explicit.
- Store reusable course-page metadata in `customdata` from `*_get_coursemodule_info()`.
- Keep backward compatibility where old index/list behaviour is still intentionally retained (see `mod_subsection/index.php`).
- Use namespaces and classes for logic; keep `lib.php` as a callback bridge.
- Use a `manager` class for general plugin operations (database access, business logic) and inject it into callbacks as needed. Reference implementations: `public/mod/h5pactivity/classes/local/manager.php`, `public/mod/data/classes/manager.php`.
- Use a `permissions` class for capability checks; all methods should be static with a `can_` prefix.
- Keep mandatory access points (`view.php`, `index.php`) lightweight; delegate business logic to manager classes and rendering to renderable/templatable classes.
- Create a plugin `classes\output\renderer` extending `plugin_renderer_base` as an empty class. Implement all rendering logic in separate classes that implement `templatable` or `named_templatable` interfaces.
- For additional pages, prefer routing over separate PHP scripts. Reference: `public/course/classes/route/controller/restricted_section.php` for routing and controller patterns.

## Practical output format for this skill

When applying this skill in chat:

- Provide a phased checklist: **skeleton**, **callbacks**, **overview**, **visibility**, **upgrade/data**.
- Reference exact file paths to add/edit under `mod/<modname>/`.
- Use `mod_assign`, `mod_data`, and `mod_subsection` patterns before inventing new architecture.
- Call out performance boundaries explicitly for `cm_info_dynamic` vs `cm_info_view`.
