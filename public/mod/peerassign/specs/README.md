# Specs — Premium Hub (local_premiumhub)

This folder contains plain-markdown behavior specs for the **Peer Review Assignments**, covering the `mod_peerassign` Moodle plugin. The specs follow a **spec-driven development** approach: each spec is the single source of truth for a component's expected behavior during implementation.

## What these specs cover

| Folder | Component |
| --- | --- |
| `database/` | MVP database schema |
| `webservices/` | The Moodle web service methods |
| `businessrules/` | The business rules governing the plugin's behavior |
| `ui/` | The user interface components and interactions |
| `architecture/` | The overall architecture and design decisions |

## Spec file structure

Every spec file follows this template:

```markdown
# [Component] Spec

## Purpose
One-paragraph description of what this spec covers.

## Background
Domain context required to understand the behaviors.

## Behaviors

### Scenario: <name>

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

## How to use these specs

1. **Before implementing** a component, read its spec file in full.
2. Use the acceptance criteria checklists as the definition of done for each behavior.
3. Edge cases are equally binding — they must be implemented and tested.
4. If a behavior conflicts with the source docs, the source docs take precedence; update the spec and note the discrepancy.
5. All implemented behaviours must provide PHPUnit tests or Behat tests that verify the acceptance criteria.
6. Edit the original spec files to mark acceptance criteria as `[x]` when the behavior is implemented and verified.
7. If an implementation adds something that will benefit from a copilot skill, create the new skill immediately.
8. Every time you implement something, include a summary in the `vibelog` folder. Do not edit any existing file in this folder.

## Spec file index

| Spec file | Description | Key ADRs |
| --- | --- | --- |
| `introduction.md` | The plugin introduction | — |
| `database/schema.spec.md` | All tables with fields, types, constraints, and relations | — |
| `webservices/create-phase.spec.md` | Webservice contract to create a new phase with `param_raw` JSON settings | — |
| `businessrules/create-activity.spec.md` | Business rules for creating `peerassign` activity instances | — |
| `businessrules/update-activity.spec.md` | Business rules specific to updating existing activity instances | — |
| `businessrules/delete-activity.spec.md` | Business rules specific to deleting activity instances | — |
| `architecture/classes.spec.md` | The baseline class architecture and responsibilities | — |
| `architecture/capabilities.spec.md` | The baseline capability model and permission helper expectations | — |
| `architecture/testing.spec.md` | The testing architecture, generator patterns, and coverage requirements | — |
| `architecture/antipatterns.spec.md` | Prohibited coding patterns collected from peer-review feedback | — |
| `ui/activity-icons.spec.md` | Design specifications for the activity icon (monologo.svg) | — |

## The `vibelog` folder

The `vibelog` folder contains implementation summaries for each behavior, written by the AI which implemented them. Each summary includes:

- A brief description of the implementation approach.
- Any deviations from the spec and why.
- Any changes made to the specs during implementation.
- Any skills created or updated in the process, with links to the relevant copilot skill files.

Later, the developer will add the review notes to the same file, so it can be used to improve the AI's future implementations:

- The peer review feedback on the implementation.
