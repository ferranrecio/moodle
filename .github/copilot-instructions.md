# Moodle repository instructions

This repository uses a split layout:

- The repository root is the project/scaffold root.
- `public/` is the actual Moodle `dirroot` and webroot.
- Root-level `index.php` intentionally throws if accessed; real web entrypoints live under `public/`.
- `public/config.php` loads the root `config.php`, and `public/lib/setup.php` derives `$CFG->dirroot` as `public/` and `$CFG->root` as the repository root.

## Build, test, and lint commands

Prerequisites visible in the repo metadata:

- PHP `>=8.3` (`composer.json`)
- Node `>=22.11.0 <23` (`package.json`)

Install dependencies:

- `composer install`
- `npm install`

JavaScript/CSS build and lint:

- `grunt` from the repo root runs the `startup` task, which auto-detects the current working directory and runs the right pipeline for that component.
- `grunt js` builds/lints AMD, YUI, and React/ESM assets.
- `grunt css` runs SCSS/CSS checks and compilation.
- `grunt react` builds React components from `js/esm/src`.
- `grunt react:dev` does a non-minified React build.
- `grunt react:watch` uses esbuild watch mode for React components.
- `grunt watch -f` keeps rebuilding even when lint errors are present.
- To target specific files, use the Grunt `--files` option, for example:
  - `grunt amd --files=public/mod/forum/amd/src/some_file.js`
  - `grunt css --files=public/theme/boost/scss/moodle.scss`

PHP linting / coding standards:

- `vendor/bin/phpcs --standard=phpcs.xml.dist public/path/to/file.php`

PHPUnit:

- Configure `phpunit_*` settings in the root `config.php` as described in `config-dist.php`.
- Initialize or refresh the test site and generated config with `php public/admin/tool/phpunit/cli/init.php`.
- Run the full PHPUnit suite with `vendor/bin/phpunit`.
- Run a single test file with `vendor/bin/phpunit public/mod/forum/tests/some_test.php`.
- Run a single test method with `vendor/bin/phpunit --filter test_method_name public/mod/forum/tests/some_test.php`.

Behat:

- Configure `behat_*` settings in the root `config.php` as described in `config-dist.php`.
- Initialize Behat with `php public/admin/tool/behat/cli/init.php` or `php public/admin/tool/behat/cli/init.php --parallel=2`.
- Run Behat with `php public/admin/tool/behat/cli/run.php --tags='@javascript'`.
- Run a single feature with `php public/admin/tool/behat/cli/run.php --feature='/absolute/path/to/public/mod/forum/tests/behat/some.feature'`.

## High-level architecture

- Moodle still has many script-based entrypoints under `public/` (`index.php`, `admin/*`, `mod/*`, `course/*`, etc.). Those scripts typically require `config.php`, pull in subsystem libraries, then use globals such as `$CFG`, `$PAGE`, `$OUTPUT`, and capability/login helpers to render the response.
- `public/lib/setup.php` is the central bootstrap. It normalizes the environment, sets core paths, switches into PHPUnit/Behat environments when configured, and establishes the runtime assumptions the rest of the code relies on.
- `public/lib/classes/component.php` is the key component/plugin registry and autoloader. It maps Moodle subsystems and plugin types, supports Frankenstyle component names, and resolves autoloaded classes from `classes/` plus bundled third-party namespaces.
- Plugin and subsystem code is organized around Moodle component names (`mod_forum`, `tool_behat`, `core_user`, etc.). In practice that means code location and namespace/class naming are tightly coupled.
- The repo contains a newer routing layer in `public/lib/classes/router/*` built on Slim. `core\router\route_loader` discovers routes from `route\controller`, `route\api`, `route\shim`, and shortlink classes, then caches the discovered routes. This coexists with the older script-entrypoint model, so check both before adding a new endpoint.
- Front-end code is mixed by generation:
  - legacy YUI under `yui/src`
  - AMD modules under `amd/src`, built to `amd/build/*.min.js`
  - newer React/ESM code under `js/esm/src`, built with esbuild

## Key conventions

- Many upstream Moodle examples assume `dirroot` is the repository root. In this checkout it is `public/`, so paths from the repo root usually need a `public/` prefix.
- When adding or moving autoloaded PHP classes, follow Moodle’s Frankenstyle/component naming and place classes in the component’s `classes/` tree so `core_component` can resolve them correctly.
- For new routes, follow the routing namespaces already used in core:
  - `route\controller` for page/controller routes
  - `route\api` for API routes
  - `route\shim` for legacy-compatible shims
- If you add plugin tests or a new plugin under `public/*`, rerun `php public/admin/tool/phpunit/cli/init.php`; Moodle rebuilds `phpunit.xml` and plugin suites from the installed component set.
- Prefer running `grunt` from inside the component you are changing when possible. The Grunt startup logic uses the current directory to narrow work to that component and to choose between AMD, YUI, React/ESM, and theme CSS tasks.
- The GitHub repository is a mirror of the official Moodle repository. `CONTRIBUTING.md` says issue reporting and patch submission happen through Moodle Tracker rather than GitHub pull requests.
- Repository-specific Copilot skills live under `.github/skills/`. For Moodle-specific work, check `.github/skills/INDEX.md` first; there are focused skills for routing, DI, mod plugins, backup/restore, output rendering, and rubric work.
