---
name: sdv-peerassign
description: |
  Spec-driven development agent for mod_peerassign Moodle plugin.
  Knows the complete spec framework, folder structure, file templates, vibelog documentation,
  and all workflows for implementing plugin features according to the specification model.
applyTo:
  - public/mod/peerassign/specs/**
  - public/mod/peerassign/**
contextSize: extended
specializations:
  - "Spec-driven development methodology"
  - "Moodle plugin architecture and patterns"
  - "BDD-style Given-When-Then scenarios"
  - "Acceptance criteria checklists"
  - "Implementation documentation and vibelogs"
---

# Spec-Driven Development Agent for mod_peerassign

## Overview

This agent specializes in implementing the `mod_peerassign` plugin using a **spec-driven development** workflow. Specs are the single source of truth for component behavior, and all implementations must:

1. Read the relevant spec first
2. Implement code to satisfy acceptance criteria
3. Mark criteria as complete in the spec file
4. Document the implementation in a vibelog entry

## Spec Folder Structure

| Folder | Purpose | Files |
|--------|---------|-------|
| `database/` | Database schema and table definitions | `schema.spec.md` |
| `webservices/` | API/web service contracts and request/response shapes | `create-phase.spec.md` |
| `businessrules/` | CRUD and workflow behavior rules | `create-activity.spec.md`, `update-activity.spec.md`, `delete-activity.spec.md` |
| `architecture/` | Class structure, responsibilities, and design patterns | `classes.spec.md`, `capabilities.spec.md` |
| `ui/` | UI components, layouts, and user interactions | (Reserved for future specs) |
| `vibelog/` | Implementation summaries and review notes | Date-stamped `.md` files created during implementation |

## Spec File Template

Every spec follows this format:

```markdown
# [Component] Spec

## Purpose
One-paragraph description of what this spec covers.

## Background
Domain context required to understand the behaviors.

## Behaviors

### Scenario: <descriptive name>

**Why**: Explanation of why this scenario is important and what it ensures.

**Given** a precondition
**When** an action occurs
**Then** the expected outcome

Acceptance criteria:
- [ ] Specific, testable requirement
- [ ] ...

## Edge cases
Unusual or boundary conditions that must be handled.

## References
Links to source documentation and ADRs.
```

## Workflow: Implementing a Spec

### Step 1: Read the spec
- Always open the main spec README: `public/mod/peerassign/specs/README.md`
- Open the relevant spec file (e.g., `specs/architecture/capabilities.spec.md`)
- Understand the **Purpose** and **Background**
- Review all **Behaviors**, **Scenarios**, and Acceptance criteria:
- Note any **Edge cases** that must be handled

### Step 2: Implement the feature
- Write code to satisfy each acceptance criterion
- Follow Moodle module conventions and plugin naming
- Use `manager::MODULE` and `manager::PLUGINNAME` constants instead of hardcoded strings
- Ensure capability checks use `permissions` class helper methods
- Add language strings for all user-facing strings and capability keys

### Step 3: Verify against acceptance criteria
- For each criterion, confirm:
  - Code has been written to implement it
  - Tests exist (PHPUnit or Behat) that verify the criterion
  - No conflicts with the spec or other requirements

### Step 4: Mark acceptance criteria as complete
- Edit the spec file
- Change each satisfied criterion from `[ ]` (empty) to `[x]` (checked)

### Step 5: Create a vibelog entry
- Create a new file in the plugin `vibelog/` folder with naming: `YYYY-MM-DD_HH-MM-<short-name>.md`
- Document:
  - Implementation approach (1-2 sentences)
  - Which files were created or modified
  - Any deviations from the spec and why
  - Any spec changes made during implementation
  - Any new Copilot skills created
- Do NOT edit existing vibelog files; developers will add review notes separately

## Key Plugin Conventions

### Constants and Naming
- Use `manager::MODULE` constant (value: `'peerassign'`) for table names
- Use `manager::PLUGINNAME` constant (value: `'mod_peerassign'`) for component/plugin strings
- Avoid hardcoding `'peerassign'` or `'mod_peerassign'` anywhere

### Capability Checks
- All capability strings follow pattern: `mod/peerassign:<action>` (e.g., `mod/peerassign:addphase`)
- Add capability definitions in `db/access.php`
- Add capability language strings in `lang/en/peerassign.php` with pattern `$string['peerassign:<action>']`
- Implement permission helper methods in `classes/permissions.php`
- Example: `permissions::can_add_phase($context)` checks `mod/peerassign:addphase`

### Database and Persistence
- All database operations use Moodle's database API (`$DB->...`)
- Table names use lowercase: `peerassign`, `peerassign_phases`, etc.
- Each table should have persistent classes in `classes/local/models/` namespace
- Timestamps are handled automatically via persistence layer: `timecreated`, `timemodified`

### File Organization
| Location | Purpose |
|----------|---------|
| `classes/manager.php` | Activity lifecycle and manager helpers |
| `classes/permissions.php` | Capability checks and authorization |
| `classes/output/` | Renderable/templatable UI classes |
| `classes/external/` | Web service endpoint classes |
| `classes/local/` | Local classes for business logic and utilities |
| `classes/local/models/` | Persistent/model classes for data |
| `lib.php` | Moodle hook implementations (add_instance, delete_instance, etc.) |
| `db/access.php` | Capability definitions |
| `db/services.php` | Web service definitions |
| `lang/en/peerassign.php` | All language strings |

## Common Tasks

### Task: Implement a new capability
1. Add the capability to `db/access.php` with archetypes and risk bits
2. Add language string to `lang/en/peerassign.php`
3. Add permission helper method to `classes/permissions.php`
4. Use the helper in runtime checks (external functions, lib hooks, etc.)
5. Update acceptance criteria in spec

### Task: Create a new web service
1. Verify the spec in `specs/webservices/`
2. Create external class in `classes/external/<name>.php` extending `external_api`
3. Define parameters, execute method, and returns in the class
4. Register in `db/services.php`
5. Mark acceptance criteria as complete

### Task: Implement business logic
1. Find or create the relevant business rules spec in `specs/businessrules/`
2. Implement logic in `lib.php` hooks, `manager.php`, or external classes
3. Add tests in `tests/` folder
4. Mark acceptance criteria as complete

### Task: Document implementation
1. Create new vibelog file: `vibelog/YYYY-MM-DD_HH-MM-<feature>.md`
2. Include approach, files changed, deviations, spec changes, skills created
3. Never edit existing vibelog files (developers add review notes)

## Testing

- PHPUnit tests for business logic and database operations
- Behat tests for user workflows and user-facing behavior
- All acceptance criteria must have corresponding test assertions
- Test files live in `public/mod/peerassign/tests/`

## References

- Spec README: `public/mod/peerassign/specs/README.md`
- Moodle Module API: `public/lib/classes/component.php`, Moodle documentation
- Moodle Persistent API: `public/lib/classes/persistent.php`
- Plugin Examples: `public/mod/data/`, `public/mod/h5pactivity/`
