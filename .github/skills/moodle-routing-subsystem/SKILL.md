---
name: moodle-routing-subsystem
description: Implement and refactor Moodle 5.2 routes (controller/api/shim), including parameters, responses, shortlinks, and route testing patterns.
---

# Moodle Routing Subsystem

Use this skill when creating, migrating, or reviewing Moodle routes using the new routing system.

## Canonical documentation

- https://moodledev.io/docs/5.2/apis/subsystems/routing
- https://moodledev.io/docs/5.2/apis/subsystems/routing/parameters
- https://moodledev.io/docs/5.2/apis/subsystems/routing/responses
- https://moodledev.io/docs/5.2/apis/subsystems/routing/shortlinks
- https://moodledev.io/docs/5.2/apis/subsystems/routing/testing

## In-repo reference implementations

- Shim routes (legacy URL compatibility):
  - `public/course/classes/route/shim/course_routes.php`
- Controller routes:
  - `public/course/classes/route/controller/course_management.php`
  - `public/course/classes/route/controller/course_navigation.php`
  - `public/course/classes/route/controller/restricted_module.php`
  - `public/course/classes/route/controller/restricted_section.php`
  - `public/course/classes/route/controller/tags_controller.php`

## External training references (moodle-local_routing)

- Repository: https://github.com/laurentdavid/moodle-local_routing
- Markdown references:
  - `README.md`
  - `doc/00-Introduction.md`
  - `doc/01-Routing-Framework.md`
  - `doc/02-Road signs and rules.md`
  - `doc/03-Creating-Your-Own-Routes.md`
  - `doc/04-Bridges and shortcuts.md`
  - `doc/05-Do not get lost.md`
  - `doc/frameworks/06-Moodle GPS.md`

## Route architecture rules

1. Place routes in the component `route` namespace
   - Use L2 namespace `route`.
   - Use L3 namespace as route group (for example `api`).
   - Unknown route groups are ignored.

2. Use `#[\core\router\route(...)]` on methods
   - Define `path`.
   - Add `method` where needed (`['GET']`, `['GET', 'POST']`, etc).
   - Add `pathtypes`, `queryparams`, and `headerparams` for validation.

3. Use `route_controller` for controller classes
   - Add `use \core\router\route_controller;` in routed classes.
   - Keep handlers focused; push heavy business logic into dedicated services.

4. Secure routes explicitly
   - Use `requirelogin: new \core\router\require_login(...)` where applicable.
   - Keep capability checks in the handler when route-level login is not sufficient.

## Parameter patterns

1. Path parameters
   - Define placeholders in the path (for example `/{course}` or `/cms/{cm}/next`).
   - Describe each with `pathtypes`.
   - Prefer Moodle reusable path classes where available:
     - `\core\router\parameters\path_course`
     - `\core\router\parameters\path_coursemodule`
     - `\core\router\parameters\path_section`

2. Optional path segments
   - Use square-bracket optional syntax (for example `/users[/{username}]`).
   - Ensure method parameter nullability/defaults are consistent.

3. Query and header parameters
   - Use `query_parameter` / `header_object` metadata for validation and docs.
   - Mark required fields explicitly via `required: true`.
   - Provide defaults and examples where useful.

4. Reusable and mapped parameters
   - Create reusable parameter classes for common semantics.
   - Implement `\core\router\schema\referenced_object` for OpenAPI reuse.
   - Use mapped parameters for ID-to-object/context mapping when needed.

## Response patterns

Routes should return one of:

- `\Psr\Http\Message\ResponseInterface` (typical controller flow), or
- `\core\router\schema\response\payload_response` (typical API flow).

Guidance:

- Prefer `payload_response` for API data responses.
- Pass through the incoming request/response objects when creating payload responses.
- If headers are required, mutate response headers before creating `payload_response`.
- For controller pages, write output to `$response->getBody()` and return the response.

## Controller, API, and shim usage

1. Controller routes
   - Build full Moodle pages (`$PAGE`, `$OUTPUT`) and return `ResponseInterface`.
   - In this repo, see:
     - `course_management::administer_course`
     - `tags_controller::administer_tags`
     - `restricted_module::restricted_module_page`

2. API routes
   - Return structured payloads and define response metadata for schema clarity.

3. Shim routes
   - Preserve legacy paths and redirect to new handlers.
   - Use `redirect_to_callable(...)` for legacy to modern route forwarding.
   - In this repo, see:
     - `course_routes::administer_course`
     - `course_routes::administer_tags`

## URL generation and redirect patterns

- Generate internal route URLs with `\core\router\util::get_path_for_callable(...)`.
- Redirect with:
  - `\core\router\util::redirect(...)`, or
  - `\core\router\route_controller::redirect(...)` helper methods.
- For shim migration, map legacy query args to new path params and exclude deprecated params.

## Shortlinks integration

When implementing shortlinks:

1. Use `\core\di::get(\core\shortlink::class)`.
2. Create:
   - Public links (`/p/...`) with `create_shortlink(...)`, or
   - Private links (`/s/...`) with `create_shortlink_for_users(...)`.
3. Implement a component `shortlink_handler` with:
   - `get_valid_linktypes()`
   - `process_shortlink(string $type, string $identifier): ?\core\url`

## Testing guidance

Use the routing test utilities:

1. Extend `\route_testcase`.
2. Add target routes before router creation:
   - `add_route_to_route_loader(...)`, or
   - `add_class_routes_to_route_loader(...)`.
3. Process requests with:
   - `create_request(...)` + router app handle, or
   - `process_request(...)` / `process_api_request(...)` shortcuts.
4. Assert:
   - status code,
   - response body,
   - and parameter validation behavior (especially mapped parameters).

Important:

- Add mocked routes before calling `get_router()`.
- Set correct route group when not implied by namespace.

## Migration checklist (legacy script -> routed controller)

1. Create a routed controller method with `#[route(...)]`.
2. Define strict path/query/header parameter metadata.
3. Add `require_login` and capability checks.
4. Replace hardcoded URLs with `get_path_for_callable(...)`.
5. Add shim route(s) to preserve old entry points.
6. Update old scripts to delegate to router entry where required.
7. Add route tests using `\route_testcase`.

## Anti-patterns to avoid

- Missing parameter metadata for placeholders in the path.
- Returning raw JSON strings instead of `payload_response` for APIs.
- Defining heavy business logic directly in route handlers.
- Calling router resolution utilities inconsistently with route definitions.
- Forgetting shim coverage for existing public legacy URLs.

## Practical output style for this skill

When using this skill in chat:

- Propose changes by route type: **controller**, **api**, **shim**, **tests**.
- Reference exact Moodle files to model after in this repo.
- Include complete `#[route(...)]` snippets with typed parameters.
- Call out login/capability requirements and redirect behavior explicitly.
