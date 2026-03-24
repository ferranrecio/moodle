---
name: Moodle Plugin Common Files
description: Create or review Moodle plugin file structure using the official Common files API documentation (Moodle 5.2), including required files, optional files, and legacy caveats.
---

# Moodle Plugin Common Files

Use this skill when creating a new Moodle plugin, auditing plugin structure, or fixing missing/incorrect standard files.

Primary reference:

- https://moodledev.io/docs/5.2/apis/commonfiles

## Required baseline files

Always ensure these exist and are valid:

1. `version.php`
   - Required metadata for install/upgrade.
   - Include version, dependencies, minimum Moodle version, and maturity.

2. `lang/en/<langfile>.php`
   - Must include at least `pluginname`.
   - Naming rule:
     - Most plugin types: frankenstyle file (e.g. `auth_ldap.php`).
     - Activity modules (`mod/*`) are different: use plugin shortname file (e.g. `mod/forum/lang/en/forum.php`).

3. `db/install.xml`
   - Defines initial DB schema.
   - Use XMLDB editor for schema creation/changes.

## Plugin-type quick matrix

Use this to quickly validate plugin path, frankenstyle name, and language filename:

| Plugin type | Plugin location example | Frankenstyle component | Language file example |
|---|---|---|---|
| Activity module (`mod`) | `mod/forum` | `mod_forum` | `mod/forum/lang/en/forum.php` |
| Authentication (`auth`) | `auth/ldap` | `auth_ldap` | `auth/ldap/lang/en/auth_ldap.php` |
| Admin tool (`tool`) | `admin/tool/example` | `tool_example` | `admin/tool/example/lang/en/tool_example.php` |
| Local plugin (`local`) | `local/myplugin` | `local_myplugin` | `local/myplugin/lang/en/local_myplugin.php` |
| Block (`block`) | `blocks/myoverview` | `block_myoverview` | `blocks/myoverview/lang/en/block_myoverview.php` |
| Question type (`qtype`) | `question/type/stack` | `qtype_stack` | `question/type/stack/lang/en/qtype_stack.php` |

Rule of thumb:

- Most plugin types use frankenstyle file names in `lang/en`.
- `mod/*` is the main exception: use plugin shortname (e.g. `forum.php`, not `mod_forum.php`).

## Common optional files (add when needed)

- `db/upgrade.php` for upgrade steps.
- `db/access.php` for capabilities.
- `db/install.php` post-install hook (install only).
- `db/uninstall.php` pre-uninstall hook.
- `db/events.php` event observers.
- `db/messages.php` message providers.
- `db/services.php` web service declarations.
- `db/tasks.php` scheduled task defaults.
- `settings.php` admin settings.
- `classes/` autoloaded classes.
- `cli/` CLI scripts.
- `amd/src/*.js` JavaScript modules (write new JS as ESM; transpiled to AMD).
- `backup/` backup/restore support where plugin data requires it.
- `styles.css` plugin styles (prefer Bootstrap classes where possible).
- `pix/icon.svg` plugin icon.
- `environment.xml` plugin-specific environment checks.
- `README.md|README.txt` administrator-facing plugin info.
- `CHANGES.*` changelog for plugins directory uploads.
- `upgrade.txt` significant API/runtime changes by release section.
- `thirdpartylibs.xml` for bundled third-party libraries.
- `*/readme_moodle.txt` for third-party import/build instructions.

## Legacy and compatibility guidance

- `lib.php`
  - Legacy bridge file; keep minimal.
  - Only callbacks and unavoidable global function entries.
  - Move business logic to autoloaded classes.

- `locallib.php`
  - Legacy and no longer recommended for new usage in core.
  - Prefer namespaced classes under `classes/`.

- `db/legacyclasses.php` and `db/renamedclasses.php`
  - Use for backwards compatibility when public API class names/locations changed.
  - Usually unnecessary for purely internal/private class moves.

- `yui/`
  - Legacy; new YUI code is not accepted (except Atto plugin exceptions).

## High-value checks when reviewing a plugin

1. Required files are present and syntactically valid.
2. Language file naming matches plugin type rules.
3. `db/install.xml` and `db/upgrade.php` are consistent with each other.
4. `version.php` increment matches upgrade steps.
5. New code uses `classes/` + ESM (`amd/src`) instead of legacy patterns.
6. Third-party code is declared in `thirdpartylibs.xml` with GPL-compatible licensing.

## Practical output format for this skill

When using this skill in chat, provide:

- A concise file checklist: **missing**, **recommended**, **legacy to avoid**.
- Concrete file paths to create/update for the plugin type.
- Minimal starter skeleton notes per missing file.
- Upgrade/install caveats (XMLDB editor usage, version bump sequencing).
