---
name: Rubrics Testing in Moodle Activity
description: Create and maintain PHPUnit coverage for rubric-based advanced grading in Moodle activities using gradingform_rubric generator patterns and assign examples.
---

# Rubrics Testing in Moodle Activity

Use this skill when writing or updating tests for rubric grading flows in activity plugins.

## Canonical references in this repo

- Rubric generator helpers: `public/grade/grading/form/rubric/tests/generator/lib.php`
- Rubric generator tests: `public/grade/grading/form/rubric/tests/generator_test.php`
- Rubric grading panel external tests:
  - `public/grade/grading/form/rubric/tests/grades/grader/gradingpanel/external/fetch_test.php`
  - `public/grade/grading/form/rubric/tests/grades/grader/gradingpanel/external/store_test.php`
- Assignment advanced grading examples: `public/mod/assign/tests/externallib_test.php`
- Assignment runtime integration points: `public/mod/assign/locallib.php`

## Test patterns to follow

1. **Create rubric definition through generator APIs**
   - Use plugin generator: `$generator->get_plugin_generator('gradingform_rubric')`.
   - Create rubric against your component/area with
     `create_instance($context, 'mod_yourplugin', 'yourarea', $name, $description, $criteria)`.

2. **Build submitted form payload safely**
   - Prefer `get_submitted_form_data()` or `get_test_form_data()` from rubric generator.
   - Avoid hand-crafting criterion/level IDs in tests when helper methods can derive them.

3. **Exercise the same save path as production**
   - Route data through activity grading save flow (or equivalent) so rubric instance methods are used.
   - Validate grade result originates from
     `submit_and_get_grade($formdata->advancedgrading, $itemid)` behavior.

4. **Assert both structure and outcomes**
   - Verify rubric method is active for target area.
   - Verify criteria/level selections are persisted.
   - Verify computed numeric grade is written as expected.

5. **Include negative/guard cases**
   - Method not configured for rubric.
   - Invalid component/area/item names for external endpoints.
   - Incomplete criteria payload rejected by validation.

## Suggested assertions

- Rubric definition contains expected criterion and levels.
- Submitted payload includes expected `criteria[criterionid][levelid]` mapping.
- Gradebook-facing grade changes after valid rubric submission.
- Quick grading style paths are blocked/ignored when advanced grading is active.

## Common pitfalls

- Using inconsistent area names between test setup and runtime lookup.
- Inserting raw DB records for criteria/levels when generator can provide stable setup.
- Asserting only grade, without checking rubric filling integrity.
- Coupling tests to UI strings instead of controller/instance behavior.

## Response style for this skill

When applying this skill in chat:

- Keep tests focused on rubric behavior and activity integration contracts.
- Reuse existing Moodle test generators and fixtures before introducing new helpers.
- Prefer minimal assertions that prove end-to-end rubric correctness.
