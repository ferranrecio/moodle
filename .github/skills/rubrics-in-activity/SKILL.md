---
name: Rubrics in Moodle Activity
description: Implement or refactor rubric-based advanced grading in a Moodle activity plugin using gradingform_rubric APIs and the mod_assign integration pattern.
---

# Rubrics in Moodle Activity

Use this skill when adding or fixing rubric grading in a Moodle activity (especially `mod_*` plugins).

## Canonical references in this repo

- Rubric engine and controller: `public/grade/grading/form/rubric/lib.php`
- Rubric test generator helpers: `public/grade/grading/form/rubric/tests/generator/lib.php`
- Assignment feature support: `public/mod/assign/lib.php`
- Assignment advanced grading mapping: `public/mod/assign/classes/grades/gradeitems.php`
- Assignment grade form + rubric submission flow: `public/mod/assign/locallib.php`
- Assignment UI behaviour (quick grading disabled when advanced grading active): `public/mod/assign/classes/output/grading_actionmenu.php`

## What to implement in an activity

1. **Enable advanced grading feature**
   - In your activity `lib.php`, return `FEATURE_ADVANCED_GRADING => true` from `*_supports()`.

2. **Declare advanced grading item names**
   - Implement `core_grades\local\gradeitem\advancedgrading_mapping` (see `mod_assign\grades\gradeitems`).
   - Return the area names in `get_advancedgrading_itemnames()` (assign uses `submissions`).
   - Keep this area string consistent everywhere (`get_grading_manager(..., component, area)`).

3. **Obtain the grading controller/instance in grading UI**
   - Use `get_grading_manager($context, 'mod_yourplugin', 'yourarea')`.
   - If active method exists, get controller via `$gradingmanager->get_controller($gradingmethod)`.
   - If form is available:
     - frozen/read-only: `$controller->get_current_instance($raterid, $itemid)`
     - editable: `$controller->get_or_create_instance($instanceid, $raterid, $itemid)`
   - Set grade range on controller with your activity grade menu (assign pattern):
     - `$gradinginstance->get_controller()->set_grade_range($grademenu, $allowgradedecimals)`

4. **Render form element**
   - Add a MoodleQuickForm `grading` element:
     - `$mform->addElement('grading', 'advancedgrading', get_string('gradenoun').':', ['gradinginstance' => $gradinginstance]);`
   - Freeze it when grading is disabled.
   - Persist `advancedgradinginstanceid` hidden param for editable flows.

5. **Submit and store grade**
   - On save, if advanced grading is active, submit rubric payload through instance:
     - `$grade->grade = $gradinginstance->submit_and_get_grade($formdata->advancedgrading, $grade->id);`
   - Fallback to direct numeric/scale grade only when no advanced grading instance is active.

6. **UI compatibility rules**
   - If an advanced grading controller is active, disable quick grading-style shortcuts (assign does this in action menu).
   - Handle locked/overridden grades in the same branching where you fetch/create grading instances.

## Rubric payload shape (what rubric expects)

`gradingform_rubric_instance::update()` and validation expect:

```php
[
    'criteria' => [
        <criterionid> => [
            'levelid' => <levelid>,
            'remark' => 'Optional text',
        ],
    ],
]
```

When using the `grading` form element, this structure is produced for `advancedgrading` automatically.

## Rubric definition lifecycle APIs

- Create/update definition: `gradingform_rubric_controller::update_definition()`
- Compare without commit: `gradingform_rubric_controller::update_or_check_rubric(..., $doupdate = false)`
- Regrade marking when definition changed: `mark_for_regrade()`
- Controller defaults/options: `get_default_options()` / `get_options()`
- Compute score span for mapping: `get_min_max_score()`

## Common pitfalls

- Inconsistent area names between grade item mapping and grading manager lookup.
- Forgetting to keep/store `advancedgradinginstanceid` in editable forms.
- Attempting quick grading while advanced grading is active.
- Treating rubric as numeric grade input instead of routing via grading instance.

## Response style for this skill

When applying this skill in chat:

- Prefer minimal, surgical changes in the activity plugin.
- Reuse existing Moodle APIs and assign-like flow instead of inventing wrappers.
- Cite exact files/functions to modify.
- Include test updates when the plugin already has tests.
