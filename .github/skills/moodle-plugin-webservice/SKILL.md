---
name: moodle-plugin-webservice
description: Create and refactor Moodle plugin web services using the External functions API, `db/services.php`, and exporter-backed response structures that reuse existing output schemas.
---

# Moodle Plugin Webservice

Use this skill when creating, extending, or reviewing a Moodle plugin web service, especially when the response should reuse an existing exporter instead of duplicating `external_*_structure` definitions.

## Canonical documentation

- https://moodledev.io/docs/5.2/apis/subsystems/external/writing-a-service
- https://moodledev.io/docs/5.2/apis/subsystems/external/functions
- https://moodledev.io/docs/5.2/apis/subsystems/external/description
- https://moodledev.io/docs/5.2/apis/subsystems/external/security

## In-repo reference implementations

- Exporter-backed external functions:
  - `public/course/format/classes/external/get_overview_information.php`
  - `public/course/format/classes/external/get_section_content_items.php`
- Exporters used to define reusable response structures:
  - `public/course/format/classes/external/activityname_exporter.php`
  - `public/course/format/classes/external/overviewitem_exporter.php`
  - `public/course/format/classes/external/overviewdialog_exporter.php`
  - `public/course/format/classes/external/overviewtable_exporter.php`
- Service declaration examples:
  - `public/mod/quiz/db/services.php`
- Test patterns:
  - `public/course/format/tests/external/get_overview_information_test.php`
  - `public/course/format/tests/external/get_section_content_items_test.php`

## Standard file layout in a plugin

For a plugin component `<component>` such as `local_myplugin`, `mod_myactivity`, or `tool_example`, the typical layout is:

1. `<plugin>/db/services.php`
2. `<plugin>/classes/external/<verb>_<noun>.php`
3. Optional exporter classes under `<plugin>/classes/external/*_exporter.php`
4. PHPUnit coverage under `<plugin>/tests/external/`
5. `version.php` bump when adding or changing an external function

Keep the external entry point in `classes/external`, not in ad-hoc scripts.

## Naming and registration rules

The service function name in `db/services.php` must follow Moodle naming rules:

- `[frankenstyle_component]_[verb]_[noun]`
- Typical verbs: `get`, `create`, `update`, `delete`

Example:

- `local_groupmanager_create_groups`
- `mod_myplugin_get_items`

Register the function in `<plugin>/db/services.php` with:

- `classname`
- `description`
- `type` (`read` or `write`)
- `capabilities` when applicable
- `ajax` if it is intended for AJAX use
- `services` if it must be exposed to a named service such as `MOODLE_OFFICIAL_MOBILE_SERVICE`

Modern class-based declarations can point directly at the namespaced class, as in newer entries in `public/mod/quiz/db/services.php`.

## External function class contract

Each web service class should extend `core_external\external_api` and usually implement:

1. `execute_parameters()`
2. `execute(...)`
3. `execute_returns()`

Optionally implement deprecation helpers when needed:

1. `execute_is_deprecated()`

Keep the method parameter order exactly aligned with `execute_parameters()`.

## Implementation flow inside `execute()`

Follow this order consistently:

1. Validate input with `self::validate_parameters(...)`.
2. Resolve the relevant context.
3. Call `self::validate_context($context)`.
4. Check capabilities with `require_capability(...)`.
5. Perform the plugin business logic.
6. Return data matching `execute_returns()` exactly.

Do not call:

- `require_login()`
- `$PAGE->set_context()`

The external API context validation already handles the required setup.

## Exporter-first response design

When the plugin already has output data objects or `externable` objects, prefer reusing exporters instead of hand-writing the same response schema twice.

This repo shows three useful patterns.

### Pattern 1: Return one exporter structure directly

If the web service returns one exported object, define `execute_returns()` using the exporter structure directly.

Reference:

- `public/course/format/classes/external/get_overview_information.php`

Pattern:

```php
public static function execute_returns(): external_single_structure {
    return overviewtable::get_read_structure();
}
```

This works when the domain/output object exposes an exporter and the exporter inherits from `core\external\exporter`.

### Pattern 2: Wrap an exporter inside another structure

If the result has a top-level wrapper key like `content_items`, embed the exporter structure inside `external_multiple_structure` or `external_single_structure`.

Reference:

- `public/course/format/classes/external/get_section_content_items.php`

Pattern:

```php
public static function execute_returns(): external_single_structure {
    return new external_single_structure([
        'content_items' => new external_multiple_structure(
            \core_course\local\exporters\course_content_item_exporter::get_read_structure()
        ),
    ]);
}
```

Use this when the API contract needs envelope metadata or named keys around a list.

### Pattern 3: Reuse nested exporter property definitions

Inside an exporter, reuse other exporter property definitions instead of manually copying nested arrays.

Reference:

- `public/course/format/classes/external/overviewtable_exporter.php`

Pattern:

```php
'items' => [
    'type' => overviewitem::read_properties_definition(),
    'multiple' => true,
    'description' => 'The items associated with the activity.',
],
```

Use `read_properties_definition()` when you need the nested property definition only, not the full top-level read structure.

## How to execute and export the response

The cleanest pattern is:

1. Build or fetch a domain/output object.
2. Obtain its exporter.
3. Export it with a renderer.
4. Return the exported object or array.

Reference:

- `public/course/format/classes/external/get_overview_information.php`

Pattern:

```php
$page = \core\di::get(\core\output\renderer_helper::class)->get_page();
$renderer = $format->get_renderer($page);

$exporter = $overviewtable->get_exporter($context);
return $exporter->export($renderer);
```

Important details:

- Pass the right related data to the exporter, especially `context`.
- Use the same exporter for both runtime export and `execute_returns()` so the response shape stays in sync.
- If an object implements `core\output\externable`, let that object provide its exporter rather than constructing ad-hoc arrays.

`public/course/format/classes/external/overviewitem_exporter.php` shows delegation to a nested exporter when the content object is itself externable.

## Exporter design rules

When creating a new exporter for a web service response:

1. Extend `core\external\exporter`.
2. Keep `define_properties()` for constructor data only.
3. Use `define_related()` for required related objects like `context`.
4. Use `define_other_properties()` for computed response fields.
5. Implement `get_other_values(renderer_base $output)` to normalize final values.

References:

- `public/course/format/classes/external/activityname_exporter.php`
- `public/course/format/classes/external/overviewitem_exporter.php`
- `public/course/format/classes/external/overviewdialog_exporter.php`
- `public/course/format/classes/external/overviewtable_exporter.php`

Practical guidance:

- Normalize URLs to strings with `->out(false)`.
- Return plain scalars, arrays, or `stdClass` values matching the declared property types.
- Use `multiple => true` for lists.
- Use nullable/default settings deliberately; do not rely on accidental null handling.

## When to create an exporter

Create an exporter when any of these are true:

- The same response shape is used by both rendering code and web services.
- The response contains nested structures that would be duplicated across multiple services.
- The returned values are derived from an output object rather than a raw database record.
- You want `execute_returns()` to stay coupled to one canonical schema source.

Do not create an exporter just to wrap a couple of trivial scalar fields if no reuse exists.

## Service declaration checklist

In `db/services.php`, define each function with the right access model:

1. Choose `read` or `write` correctly.
2. Add `capabilities` if access should be constrained.
3. Add `ajax => true` only when this is intended for browser AJAX.
4. Add `services` only when the function should be exposed through a specific service.
5. Keep descriptions human-readable because they appear in generated docs.

Use `public/mod/quiz/db/services.php` as the syntax reference.

## Testing pattern

Add PHPUnit coverage for every new external function.

Preferred test flow:

1. Extend `core_external\tests\externallib_testcase`.
2. Create the required course, plugin data, and enrolled users.
3. Switch users to verify permissions.
4. Call `execute(...)` directly.
5. Clean the result with `external_api::clean_returnvalue(...)`.
6. Assert the cleaned result matches the declared structure and expected values.

References:

- `public/course/format/tests/external/get_overview_information_test.php`
- `public/course/format/tests/external/get_section_content_items_test.php`

For exporter-backed services, compare the cleaned external result against the exporter output when practical. The `get_overview_information` test is the canonical example in this repo.

## Common pitfalls

- Duplicating response definitions in both `execute_returns()` and custom array-building code.
- Returning keys or types not declared in `execute_returns()`.
- Forgetting `validate_context()` before touching context-owned data.
- Checking login with `require_login()` in an external function.
- Making top-level parameters `VALUE_OPTIONAL`; use `VALUE_DEFAULT` at the top level instead.
- Returning rendered HTML when a structured exporter response would be more reusable.
- Skipping tests for capability failures and empty-result cases.

## Practical implementation recipe

When asked to add a plugin web service, work in this order:

1. Define the external contract in `<plugin>/db/services.php`.
2. Create `classes/external/<verb>_<noun>.php` extending `external_api`.
3. Decide whether the response should reuse an exporter.
4. If yes, create or reuse `*_exporter.php` and make `execute_returns()` depend on it.
5. Keep `execute()` focused on validation, context, capability checks, orchestration, and exporter invocation.
6. Add PHPUnit tests that call `execute()` and clean the return value.
7. Bump the plugin version.

## Practical output style for this skill

When applying this skill in chat:

- Propose changes by file: `db/services.php`, `classes/external/...`, exporter classes, tests.
- Prefer exporter reuse over manually duplicated external structures.
- Reference `public/course/format/classes/external/` for response-shape patterns.
- Call out context and capability checks explicitly.
- Mention the version bump whenever a new service is added.
