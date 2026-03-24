---
name: Moodle Persistent Classes
description: Create and use Moodle `core\persistent` classes for database-backed models, including property definitions, validation, lifecycle hooks, and practical usage patterns in APIs/services.
---

# Moodle Persistent Classes

Use this skill when creating or refactoring Moodle models backed by a DB table using `\core\persistent`, and when wiring those models into business logic.

## Core references in this repo

- Base API:
  - `public/lib/classes/persistent.php`
- Minimal persistent examples:
  - `public/admin/tool/dataprivacy/classes/category.php`
  - `public/admin/tool/dataprivacy/classes/context_instance.php`
- Persistent with cache lifecycle:
  - `public/admin/tool/dataprivacy/classes/purpose.php`
  - `public/admin/tool/policy/classes/policy_version.php`
- Persistent with validation hooks/events:
  - `public/reportbuilder/classes/local/models/audience.php`
- Persistent with custom getters/setters and side effects:
  - `public/mod/bigbluebuttonbn/classes/recording.php`

## When to use `core\persistent`

- Use for one-table models with clear typed properties and validation rules.
- Use when you need a standard CRUD lifecycle (`create`, `update`, `delete`, `save`) plus hooks.
- Use when domain logic benefits from model-level validation (`validate_<property>()`) and controlled access via `get()/set()`.
- Avoid overloading persistents with orchestration logic; keep complex workflows in service/manager classes.

## Required model structure

1. Extend `\core\persistent`.
2. Set `const TABLE` to the exact DB table name.
3. Implement `protected static function define_properties()` and define each table-backed field.
4. Use `type` for every property (`PARAM_INT`, `PARAM_TEXT`, `PARAM_RAW`, etc.).
5. Add optional constraints where needed:
   - `default`
   - `null` (`NULL_ALLOWED` / `NULL_NOT_ALLOWED`)
   - `choices`
   - `message` (a `lang_string`)
6. Do **not** redefine core-managed fields unless intentional (`id`, `timecreated`, `timemodified`, `usermodified` are added by base persistent).

## Template to generate a persistent class

Use this as the default scaffold, then adapt names/types:

```php
namespace plugintype_pluginname;

defined('MOODLE_INTERNAL') || die();

class example extends \core\persistent {
    const TABLE = 'plugintype_example';

    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'description' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'status' => [
                'type' => PARAM_INT,
                'choices' => [0, 1],
                'default' => 0,
            ],
        ];
    }

    protected function validate_name($value) {
        return trim($value) === '' ? new \lang_string('invaliddata', 'error') : true;
    }
}
```

## Property and method conventions

- Access properties with `get('property')` and `set('property', $value)`.
- Custom accessors must be `protected` and named exactly:
  - `protected function get_propertyname()`
  - `protected function set_propertyname($value)`
- Use `raw_get/raw_set` only inside the model for low-level/internal behavior.
- Prefer `set_many([...])` when setting multiple attributes.

## Validation patterns

- Use built-in property validation first (`type`, `null`, `choices`).
- Add `protected function validate_<property>($value)` for cross-record/domain checks.
- Return `true` when valid, else return `new \lang_string(...)`.
- Keep FK validation focused: verify referenced record exists; enforce business transition rules in calling service/API layer.

## Lifecycle hooks (for side effects)

Use only when needed:

- `before_create()` / `after_create()`
- `before_update()` / `after_update($result)`
- `before_delete()` / `after_delete($result)`
- `before_validate()`

Typical side effects:

- Cache update/invalidations (see `purpose`, `policy_version`).
- Event triggers (see `core_reportbuilder\local\models\audience`).
- Remote synchronization before/after persistence (see `mod_bigbluebuttonbn\recording`).

## How to use persistents in code

### A) Create record

```php
$model = new my_persistent(0);
$model->set_many([
    'name' => $name,
    'status' => 1,
]);
$model->create();
$id = $model->get('id');
```

### B) Update existing record

```php
$model = new my_persistent($id);
$model->set('name', $newname);
$model->update();
```

### C) Create or update (upsert-like flow)

```php
$model = $id ? new my_persistent($id) : new my_persistent(0);
$model->set_many($values);
$model->save();
```

### D) Read/query

```php
$one = my_persistent::get_record(['name' => $name]);
$list = my_persistent::get_records(['status' => 1], 'id', 'DESC', 0, 50);
$exists = my_persistent::record_exists($id);
$count = my_persistent::count_records(['status' => 1]);
```

### E) Validate explicitly (when API needs detailed errors)

```php
if (!$model->is_valid()) {
    $errors = $model->get_errors();
    // Map lang_string errors to API/form response.
}
```

## Integration guidance (where to place logic)

- Persistent class:
  - Schema-level constraints, field sanitation, simple model invariants, lifecycle side effects.
- Service/manager class:
  - Authorization/capability checks, transaction orchestration, multi-model workflows.
- Entry points (external API, script, route controller):
  - Input parsing + calling service layer, not embedding low-level DB logic.

## Common pitfalls to avoid

- Declaring custom getter/setter/validator as `public` instead of `protected`.
- Writing DB queries directly when `get_record/get_records/get_records_select` is enough.
- Returning strings/booleans instead of `lang_string` from `validate_<property>()` failures.
- Mixing heavy orchestration into lifecycle hooks.
- Assuming `set()` writes to DB automatically (it only mutates in-memory state until `create/update/save`).

## Quick checklist before finishing a persistent implementation

1. `TABLE` matches DB schema.
2. Every model property has `type` and correct `null/default/choices` semantics.
3. Custom validators return `true|lang_string` and are `protected`.
4. Side effects are in hooks, with minimal scope.
5. Calling code uses `create/update/save` intentionally and handles validation errors.
6. Related tests (if present in component) cover validation + CRUD + side effects.

## Practical output style for this skill

When using this skill in chat:

- Propose minimal, idiomatic Moodle persistent classes first.
- Show exact class/property definitions aligned to the table fields.
- Keep domain orchestration in services, and explain what stays in the persistent vs caller.
- Include short usage snippets for create/update/query and error handling.
