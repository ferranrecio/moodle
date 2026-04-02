# Peer Review Assignment Plugin

## Overview

The **Peer Review Assignment** plugin transforms how students learn through structured feedback. It enables instructors to create progressive, multi-phase learning activities where students submit work, receive peer feedback, and benefit from instructor evaluation. All in a transparent, organized workflow.

## Core Features

### Progressive Phases

Activities begin with a **wizard** that guides setup:

1. **Sample & Description** (Initial Phase auto-created on activity creation)
    - Upload instructional materials (file, video, audio)
    - Add notes and clarifications
    - Sets expectations before submission

2. **Validation Page** (Optional, one or More)
    - Written instructions with acceptance criteria
    - Checkbox conditions students must acknowledge
    - Configurable unlock via button or date

3. **Submission Phase** (One or More)
    - Students upload or author work
    - Configurable deadlines or manual closure
    - Supports multiple submission rounds

4. **Peer Review Phase** (Optional, one or more)
    - Configurable peer reviewer count per submission
    - Blind or anonymous grading options
    - Auto-generates peer feedback grades

5. **Teacher Evaluation Phase** (Optional)
    - Instructor adds summative grades
    - Independent scoring from peer grades
    - Weighted contribution to final mark

### Transparency & Feedback Access

- Students view peer feedback from other reviewers
- Teacher controls evaluation visibility and publication
- Clear separation between phases prevents premature disclosure

## Use Cases

- Formative assessment with peer learning
- Multi-round iterative improvement
- Mixed peer and instructor feedback
- Scaffolded skill development

## Phase completion

- Each phase has clear completion criteria (e.g., submission made, reviews completed)
- If the phase has a start or end date, the student cannot access the next phase until the start date has passed or the end date has been reached
- If a phase does not have any dates, the student can access the next phase as soon as they complete the current one
- The review phase can only be completed if there are enough submissions to review. The teacher can specify the number of reviews required per submission, and also the minimal number of submissions required for the review phase to be doable

## Plugin glossary

- **Activity**: A single Peer Review Assignment instance in a course, including its phases, rules, and grading settings.
- **Initial Phase**: All activities start with a Sample & Description phase already created that cannot be deleted, but can be modified.
- **Manual closure**: A teacher action that ends a phase without waiting for an end date.
- **Minimum submissions threshold**: The minimum number of available submissions required to run peer review allocation.
- **Peer Review phase**: A phase where learners review assigned peer submissions under configured anonymity and quota settings.
- **Phase completion**: The completion criteria that must be met before the next phase becomes available.
- **Phase**: A defined step in the activity lifecycle. Progression to the next phase depends on completion and availability rules.
- **Review quota**: The number of peer reviews each learner is required to complete.
- **Round**: One iteration of submission and/or review in an activity configured for multiple cycles.
- **Sample & Description phase**: A phase where instructional examples, media, and expectations are provided.
- **Submission phase**: A phase where learners create, upload, or update their submission within configured availability limits.
- **Teacher Evaluation phase**: A teacher-only phase for final or moderation grading.
- **Validation Page phase**: An optional checkpoint where learners acknowledge required conditions before continuing.
- **Visibility controls**: Rules that define when peer feedback and teacher evaluation are visible to learners.
- **Wizard**: The teacher setup flow that creates and configures the activity structure.
