# Activity Icons Spec

## Purpose

This spec defines the visual design and implementation of the Peer Review Assignment activity icon (`monologo.svg`). The icon must be recognizable, consistent with Moodle's design system, and clearly communicate the peer review submission and feedback workflow.

## Background

Moodle activity modules display monologo icons in course overviews, activity choosers, and administrative interfaces. The Peer Review Assignment icon must visually represent two core concepts:

- **File submission**: Students submit work (similar to assignment submission)
- **Peer review**: The collaborative feedback mechanism with multiple participants

The icon follows Moodle's design conventions established across modules like `mod_assign`, `mod_workshop`, and others.

## Design Specifications

### Icon Format and Dimensions

Acceptance criteria:

- [x] Icon is a scalable vector graphic (SVG) format
- [x] Viewbox dimensions are `0 0 24 24` (24x24 coordinate space)
- [x] SVG width and height attributes are set to `24`
- [x] preserveAspectRatio is set to `xMinYMid meet` for consistent scaling
- [x] Fill color is `#212529` (Moodle's monochrome icon color)
- [x] Root SVG element has `fill="none"` with a single `<path>` element containing the icon geometry
- [x] Path uses `fill-rule="evenodd"` and `clip-rule="evenodd"` for proper rendering

### Visual Composition

Acceptance criteria:

- [x] Icon combines two visual elements: a document/file icon and a user/people icon
- [x] The file upload element is inspired by `mod_assign/pix/monologo.svg` (document with arrow indicating direction)
- [x] The peer/people element is inspired by `mod_workshop/pix/monologo.svg` (multiple users representing collaboration)
- [x] Both elements are visually balanced within the 24x24 canvas
- [x] Elements are positioned to avoid visual clutter while remaining clearly distinct

### Design Consistency

Acceptance criteria:

- [x] Icon style matches other Moodle activity module icons (forum, quiz, assign, workshop, etc.)
- [x] Icon uses clean, simple linework without unnecessary detail
- [x] Icon is legible at small scales (as little as 16x16 when rendered)
- [x] Icon follows Moodle's contemporary flat design aesthetic

## Implementation

### Scenario: Icon file is accessible and properly located

**Why** Moodle's icon system relies on file location and SVG validity to display activity icons in course overviews and pickers. Proper SVG structure ensures the icon renders consistently across all browsers and themes without errors.

**Given** the Peer Review Assignment plugin is installed
**When** Moodle loads activity icons
**Then** the monologo.svg file is found and rendered correctly

Acceptance criteria:

- [x] File is located at `public/mod/peerassign/pix/monologo.svg`
- [x] File is valid SVG markup with proper XML declaration (optional but recommended)
- [x] Icon renders without errors in course overviews, activity choosers, and admin interfaces
- [x] Icon is indexed by Moodle's icon system and available to theme renderers
- [x] The file/document element is immediately recognizable
- [x] Icon does not visually resemble a single-user activity (e.g., workshop-style collaboration is evident)
- [x] Icon does not visually resemble a pure assignment submission (peer feedback aspect is evident)

## References

- `public/mod/assign/pix/monologo.svg` — assignment submission icon reference
- `public/mod/workshop/pix/monologo.svg` — collaborative peer interaction icon reference
- `public/mod/peerassign/specs/introduction.md` — plugin overview and workflow model
