# Capabilities Architecture Spec

## Purpose

This spec defines a minimal baseline capability model for `mod_peerassign`. It documents current capability keys and permission helper expectations so future changes remain consistent across `db/access.php` and runtime checks.

## Background

`mod_peerassign` currently defines these activity capabilities in `db/access.php`.

Capability checks are centralized in `mod_peerassign\permissions` using helper methods that are called by plugin entry points and file serving

## Behaviors

### Scenario: all activity capabilities are defined and enforced through permissions helpers

**Given** the activity capability definitions in `db/access.php`
**When** runtime access control is needed in hooks, external functions, and UI/file flows
**Then** capabilities are checked through `mod_peerassign\permissions` helper methods and capability requirements remain aligned with the defined keys.

**Acceptance criteria**

- [x] `db/access.php` defines all module capabilities: `addinstance`, `view`, `manage`, `submit`, `review`, `grade`, `addphase` under the `mod/peerassign:*` namespace
- [x] `permissions::can_view_files()` checks `mod/peerassign:view` for file visibility
- [x] `permissions::can_manage()` checks `mod/peerassign:manage`
- [x] `permissions::can_submit()` checks `mod/peerassign:submit`
- [x] `permissions::can_review()` checks `mod/peerassign:review`
- [x] `permissions::can_grade()` checks `mod/peerassign:grade`
- [x] `permissions::can_add_phase()` checks `mod/peerassign:addphase`
- [x] `permissions::require_view_activity()` requires login and `mod/peerassign:view`
- [x] `permissions` adds a helper for `mod/peerassign:addinstance` checks where add-instance authorization needs to be reused
- [x] Lang files define capability strings for all keys with clear descriptions of what each capability
