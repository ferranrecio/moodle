---
name: Moodle Dependency Injection
description: Implement and refactor Moodle code to use the core DI container via \core\di, constructor injection, DI configuration hooks, and test-time mocking patterns.
---

# Moodle Dependency Injection

Use this skill when introducing or improving dependency injection in Moodle code, especially when replacing globals/service locators in business logic with constructor-injected dependencies.

## Canonical documentation

- https://moodledev.io/docs/5.2/apis/core/di

## Core references in this repo

- DI wrapper and container bootstrap:
  - `public/lib/classes/di.php`
- Core DI configuration hook registration:
  - `public/lib/db/hooks.php`
- Example DI configuration callback:
  - `public/lib/classes/router/hook_callbacks.php`
- Test-time DI mocking helpers:
  - `public/lib/phpunit/classes/advanced_testcase.php`

## Primary DI rules for Moodle

1. Prefer constructor injection in classes
   - Inject typed dependencies into `__construct(...)`.
   - Prefer promoted `readonly` properties where possible.

2. Use `\core\di::get()` at legacy boundaries
   - Good for scripts/callbacks/factories where constructor injection is not practical.
   - Keep usage localized; avoid deep service-location inside domain code.

3. Do not inject the container itself
   - Avoid `\Psr\Container\ContainerInterface` injection.
   - Request concrete dependencies directly.

4. Use string IDs as class/interface names
   - Keep IDs aligned with class/interface FQCN.
   - Avoid arbitrary custom IDs unless absolutely required.

## Typical implementation patterns

### A) Constructor injection for service classes

- Create classes that receive dependencies explicitly:
  - `public function __construct(protected readonly \moodle_database $db) {}`
- Fetch class from container at composition/entry points:
  - `$service = \core\di::get(my_service::class);`

### B) Legacy script/controller usage

- In entry scripts, acquire top-level services with `\core\di::get(...)`.
- Example pattern in repo:
  - `public/r.php` uses `\core\di::get(\core\router::class)`.

### C) Extending container definitions

- Register hook in component `db/hooks.php` for `\core\hook\di_configuration`.
- Add definitions in hook listener via `$hook->add_definition(...)`.
- See:
  - `public/lib/db/hooks.php`
  - `public/lib/classes/router/hook_callbacks.php`

### D) Unit tests with mocked dependencies

- Override dependencies using `\core\di::set(...)` in tests.
- Prefer existing PHPUnit helpers where available (e.g. mock clock helpers).
- See:
  - `public/lib/phpunit/classes/advanced_testcase.php`

## Where to use DI in Moodle plugin work

- Managers and domain services (`classes/manager.php`, service classes)
- Output/helper classes that currently depend on globals
- External APIs and task classes where dependencies are stable and testable
- Hook listeners and integration points that orchestrate other services

## Migration checklist (legacy -> DI)

1. Identify global dependencies (`$DB`, time providers, hook manager, string manager, HTTP clients).
2. Move data/logic into injectable classes where feasible.
3. Add constructor dependencies with explicit types.
4. Replace internal global access with injected property usage.
5. Keep entry points thin and resolve top-level service via `\core\di::get(...)`.
6. Add/update tests using `\core\di::set(...)` mocks as needed.

## Anti-patterns to avoid

- Calling `\core\di::get(...)` repeatedly throughout deep domain methods.
- Injecting or passing around the container.
- Mixing DB queries and service resolution ad-hoc in output/render layers.
- Using `\core\di::reset_container()` in normal runtime code.

## Practical output style for this skill

When using this skill in chat:

- Propose minimal, incremental DI refactors.
- Keep public APIs stable unless the task requires interface changes.
- Show exact files and constructors to update.
- Include test update guidance for mocked dependencies.
