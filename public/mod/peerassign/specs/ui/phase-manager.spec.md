# Phase Manager UI Spec

## Purpose

This spec defines the teacher-facing phase management UI for `mod_peerassign` activity view.

## Background

When a teacher accesses the activity, they must immediately see current activity phases. The UI uses Bootstrap 5 patterns available in Moodle and renders each phase as a card. Card content is produced by output classes and Mustache templates so each phase type can show its specific data while preserving a common layout.

## Behaviors

### Scenario: Teacher sees all phases when opening the activity

**Why** Phase visibility on entry is essential for planning and validating workflow progression.

**Given** a teacher with access to a Peer Review Assignment activity
**When** the teacher opens the activity page
**Then** the page includes a phase management section showing all current phases

Acceptance criteria:

- [ ] Phase management section is visible on activity teacher view by default
- [ ] Phases are listed in ascending `sequencenumber`
- [ ] Empty-state UI is shown if no phases exist (for recovery/diagnostics only)
- [ ] Student-facing views do not expose teacher management controls

---

### Scenario: Each phase is rendered as one Bootstrap 5 card

**Why** A card model makes each phase self-contained and easy to scan, edit, and compare.

**Given** one or more phases exist
**When** phase manager renders
**Then** each phase appears as a Bootstrap 5 card

Acceptance criteria:

- [ ] Each phase is rendered inside one `.card` container
- [ ] Card header includes phase sequence and phase display name
- [ ] Card body includes common summary fields and phase-specific details
- [ ] Card footer includes teacher actions (for example edit/delete/reorder) according to permissions and safety rules
- [ ] Card state styles distinguish unavailable, active, and completed teacher states where applicable

---

### Scenario: Card rendering uses output classes and templates

**Why** Separating export logic from templates keeps UI maintainable and testable.

**Given** cards are rendered for mixed phase types
**When** render pipeline executes
**Then** each card uses a phase manage output class implementing `named_templatable`

Acceptance criteria:

- [ ] One shared phase manage base output provides common export fields
- [ ] Each phase type output maps to one template under `templates/local/phase/`
- [ ] Template data includes all common fields expected by shared card partials
- [ ] Rendering remains stable when optional phase-specific fields are absent

---

### Scenario: UI remains usable on desktop and narrow layouts

**Why** Teachers may configure activities on laptops, tablets, or reduced-width windows.

**Given** phase manager is displayed at different viewport widths
**When** Bootstrap responsive layout rules apply
**Then** phase cards remain readable and controls remain operable

Acceptance criteria:

- [ ] Card content wraps without clipping on narrow widths
- [ ] Action controls remain keyboard accessible and visible without horizontal scroll
- [ ] Phase order is preserved in DOM and visual flow across breakpoints
- [ ] Focus styling is visible for keyboard users

## Edge cases

- Long phase titles or long translated strings wrap without breaking card layout.
- Unknown phase type card shows a fallback label while preserving safe management actions.
- If one card fails to render phase-specific details, the rest of the list still renders.

## References

- `public/mod/peerassign/specs/architecture/phases.spec.md` - class and template architecture
- `public/mod/peerassign/specs/businessrules/phase-management.spec.md` - create/delete sequencing and constraints
- Bootstrap 5 card and grid utilities used by Moodle core themes
