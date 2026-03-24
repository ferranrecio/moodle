---
name: moodle-mod-backup-restore
description: Make a Moodle activity module compatible with backup and restore by implementing the Moodle 2 backup task and step classes, mapping install.xml tables into backup XML, restoring IDs/files/dates correctly, and validating the round trip with core mod examples.
---

# Moodle Mod Backup Restore

Use this skill when adding backup and restore support to a `mod/*` plugin, or when reviewing/fixing an existing module's backup/restore implementation.

## Canonical documentation

- https://docs.moodle.org/dev/Backup_API
- https://docs.moodle.org/dev/Restore_API

## In-repo reference implementations

- Small single-table activity:
  - `public/mod/url/backup/moodle2`
  - `public/mod/subsection/backup/moodle2`
- Parent/child activity with files on child records:
  - `public/mod/book/backup/moodle2`
- Activity with userinfo-gated tables and post-pass remapping:
  - `public/mod/feedback/backup/moodle2`
- Activity with user/group/grouping mappings, subplugins, overrides, and fileareas:
  - `public/mod/assign/backup/moodle2`
- Link encoding/decoding patterns:
  - `public/mod/url/backup/moodle2/backup_url_activity_task.class.php`
  - `public/mod/url/backup/moodle2/restore_url_activity_task.class.php`
  - `public/mod/forum/backup/moodle2/backup_forum_activity_task.class.php`
  - `public/mod/forum/tests/backup_forum_activity_task_test.php`
- Date restore behavior test patterns:
  - `public/mod/bigbluebuttonbn/tests/backup_restore_test.php`

## Files a mod plugin usually needs

Create these files under `mod/<modname>/backup/moodle2/`:

1. `backup_<modname>_activity_task.class.php`
2. `backup_<modname>_stepslib.php`
3. `restore_<modname>_activity_task.class.php`
4. `restore_<modname>_stepslib.php`

For most modules, this is the minimum backup/restore file set.

## Start from install.xml, not from guesswork

Derive the backup XML structure from `mod/<modname>/db/install.xml`.

Use this mapping process:

1. Identify the main activity table.
   - Usually this is the plugin name itself, for example `url`, `book`, `assign`.
   - The root backup element normally uses the same name as the table and module.

2. Identify tables directly owned by the activity.
   - Look for foreign keys such as `<modtable>id`, `assignment`, `feedback`, `bookid`, or similar references back to the main record.
   - These usually become nested child elements below the activity root.

3. Identify userinfo data separately.
   - Tables storing submissions, grades, attempts, responses, logs, subscriptions, or per-user state are usually included only when `userinfo` is enabled.
   - Compare `assign_submission`, `assign_grades`, `feedback_completed`, and `feedback_value`.

4. Identify cross-record references that require mapping.
   - If one restored record points to another restored record, call `set_mapping()` when inserting the referenced record and `get_mappingid()` when restoring dependent records.
   - Compare `book_chapter`, `feedback_item`, `forum_post`, and `assign_submission`.

5. Exclude tables that are not activity-instance backup data.
   - Temporary tables, caches, derived tables, site-wide templates, and unrelated integration tables usually do not belong in activity backup.
   - `feedback_template`, `feedback_completedtmp`, and `feedback_valuetmp` are good examples of tables that are not part of the activity instance restore flow.

## Table-to-XML patterns from core examples

### Simplest case: one activity table

`mod_url` and `mod_subsection` show the minimal pattern:

- one root `backup_nested_element`
- `set_source_table('<modtable>', ['id' => backup::VAR_ACTIVITYID])`
- no child elements unless the schema actually has activity-owned child tables
- restore path only for `/activity/<modname>`
- restore inserts the main row, sets `course`, and calls `apply_activity_instance()`

Use this pattern when `db/install.xml` contains only the main activity table or when extra tables are not activity-owned.

### Parent/child case: child rows become nested elements

`mod_book` maps:

- `book` -> root `<book>` element
- `book_chapters` -> nested `<chapters>/<chapter>` elements

Pattern:

- child source uses `backup::VAR_PARENTID`
- restore child uses `get_new_parentid('<parentname>')`
- if files attach to child records, call `set_mapping(..., true)` on restore and `add_related_files()` in `after_execute()`

### Userinfo case: conditionally include user data

`mod_feedback` and `mod_assign` show the standard userinfo split:

- call `$userinfo = $this->get_setting_value('userinfo')`
- define user-owned sources only inside the `if ($userinfo)` branch
- mirror the same condition in restore `define_structure()`

If backup omits userinfo, restore must not expect those paths.

### Complex case: post-pass repair and subplugins

Use `mod_feedback` and `mod_assign` when your activity has:

- child records referencing sibling child records
- subplugin data hanging off the activity or child nodes
- mappings that need old context ids for file restoration
- user/group/grouping/scale references
- rules that differ when `userinfo` or `groups` is disabled

Notable patterns:

- `feedback_item.dependitem` is repaired after restore once all items exist.
- `assign` adds subplugin structures at both activity and child levels.
- `assign` preserves mappings for submission and grade records because subplugin fileareas depend on them.

## backup_<modname>_activity_task.class.php

Implement these responsibilities:

1. `define_my_settings()`
   - Leave empty unless the activity truly has backup-specific settings.

2. `define_my_steps()`
   - Add one structure step writing `<modname>.xml`.

3. `encode_content_links()`
   - Encode links to your module's `index.php`, `view.php`, `discuss.php`, or equivalent entry points.
   - Follow `mod_url` for simple `index/view` cases.
   - Follow `mod_forum` when the activity has multiple URL forms or anchors/query variations.

Test link encoding if you add non-trivial patterns.

## backup_<modname>_stepslib.php

Define the backup tree in the same ownership order as the schema.

Core responsibilities:

1. Define nested elements.
   - Root element matches the main activity table.
   - Child container names should be stable and readable, for example `chapters`, `items`, `submissions`.

2. Build the tree explicitly.
   - Add containers and children in the order they appear in XML.

3. Define sources.
   - Main record uses `backup::VAR_ACTIVITYID`.
   - Child records usually use `backup::VAR_PARENTID`.
   - Use `set_source_sql()` when the source needs joins, filters, or context-based selection.

4. Annotate referenced IDs.
   - Use `annotate_ids()` for values that refer to `user`, `group`, `grouping`, `scale`, `question`, and similar core entities.
   - Do not annotate the activity's own parent foreign keys like `course` or `<modtable>id`; those are rebuilt by the restore flow.

5. Annotate files.
   - Use `annotate_files('<component>', '<filearea>', null)` when the filearea has no itemid.
   - Use `annotate_files(..., 'id')` when the filearea itemid is the restored record id.
   - Compare `mod_url` for `intro`, `mod_book` for child-item files, and `mod_feedback` for mixed activity and child fileareas.

6. Gate user data.
   - Wrap per-user sources in `userinfo` checks.
   - If your plugin has group-sensitive data, follow the `assign` pattern for `groups` as well.

7. Add subplugin structures only when the plugin architecture needs them.
   - Follow `assignsubmission` and `assignfeedback` in `mod_assign`.

## restore_<modname>_activity_task.class.php

Implement these responsibilities:

1. `define_my_settings()`
   - Usually empty.

2. `define_my_steps()`
   - Add one restore structure step reading `<modname>.xml`.

3. `define_decode_contents()`
   - List text fields that may contain encoded links, embedded pluginfile URLs, or activity references.
   - `mod_url` decodes both `intro` and `externalurl`.

4. `define_decode_rules()`
   - Reverse the placeholders produced by `encode_content_links()`.

5. `define_restore_log_rules()` and `define_restore_log_rules_for_course()`
   - Add rules when legacy log restore still matters for the activity.
   - Use `mod_url` as the minimal pattern.

## restore_<modname>_stepslib.php

Restore code is where most correctness bugs happen.

Core responsibilities:

1. Define restore paths.
   - Include exactly the XML nodes produced by backup.
   - Gate user paths behind the same `userinfo` checks used during backup.

2. Restore the main activity first.
   - Cast `$data` to object.
   - Set `$data->course = $this->get_courseid()`.
   - Translate date fields with `apply_date_offset()` only when they are schedule-relative fields.
   - Insert the main table record.
   - Call `apply_activity_instance($newitemid)` immediately after insertion.

3. Restore child records with parent remapping.
   - Use `get_new_parentid('<parentname>')` for the activity-owned foreign key.
   - Use `get_mappingid()` for referenced users, groups, scales, child records, and similar IDs.

4. Create mappings when later restore logic or files depend on them.
   - Use `set_mapping('<mapname>', $oldid, $newitemid)` after inserting records that are referenced later.
   - If the restored record has files keyed by itemid, pass `true` in the file-related argument.
   - Follow `book`, `feedback`, `forum`, and `assign` for the common variants.

5. Repair relationships in a second pass when needed.
   - If record A points to record B but B may not exist yet during the first insert, store both and update later in `after_execute()` or a dedicated post-processing method.
   - `feedback_item.dependitem` is the canonical example.

6. Restore related files in `after_execute()`.
   - Use `add_related_files('<component>', '<filearea>', <mappingname-or-null>)`.
   - Match the fileareas annotated during backup.

## Date handling rules

Use `apply_date_offset()` only for fields whose value is expected to move with the course timeline.

Typical offset fields:

- `timeopen`
- `timeclose`
- `duedate`
- `allowsubmissionsfromdate`
- `cutoffdate`
- `gradingduedate`
- `available`
- `deadline`
- assessment window fields

Typical non-offset fields:

- `timecreated`
- `timemodified`
- log creation times that should preserve historical timestamps

`mod_bigbluebuttonbn/tests/backup_restore_test.php` shows the kind of test that confirms creation/modification timestamps are preserved.

## ID mapping rules

Use these heuristics:

- `userid`, `grader`, `usermodified` -> `get_mappingid('user', ...)`
- `groupid` -> `get_mappingid('group', ...)`
- `groupingid` -> `get_mappingid('grouping', ...)`
- negative grade/scale references -> map the scale and restore the sign, as in `assign`, `forum`, and `glossary`
- child record references -> `set_mapping()` when creating the target, then `get_mappingid()` when restoring the dependent row

When the old context id is needed for file restoration on child entities, follow the `assign` pattern using `$this->task->get_old_contextid()`.

## What not to restore blindly

Be conservative with these categories:

- temporary rows
- caches and denormalized summaries
- rows that belong to site-wide templates rather than the activity instance
- rows that can only be valid on same-site restores unless special handling exists
- fields that depend on users/groups that were intentionally excluded from backup

If user or group mappings are missing, prefer the defensive patterns from `assign` and `feedback` over inserting broken rows.

## Testing expectations

At minimum, validate:

1. Backup/restore round trip recreates the activity and its owned child data.
2. Userinfo-disabled backups still restore cleanly.
3. Fileareas annotated in backup are restored to the correct contexts/itemids.
4. Date-offset fields move correctly, while historical timestamps stay unchanged.
5. Encoded internal links are re-written correctly.

Useful patterns:

- `advanced_testcase` for `encode_content_links()` assertions:
  - `public/mod/forum/tests/backup_forum_activity_task_test.php`
- restore date round-trip coverage:
  - `public/mod/bigbluebuttonbn/tests/backup_restore_test.php`

## Practical checklist for implementing backup/restore in a new mod

1. Read `mod/<modname>/db/install.xml` and mark:
   - main table
   - activity-owned child tables
   - userinfo-only tables
   - file-bearing tables
   - foreign keys that need ID remapping
2. Scaffold the four `backup/moodle2` classes.
3. Implement the backup structure from simplest parent-to-child ownership.
4. Implement the restore structure with explicit mapping and date handling.
5. Add file annotations and matching `add_related_files()` calls.
6. Add link encoding and decoding rules if the activity exposes internal URLs.
7. Add or update tests for round-trip restore behavior and link encoding.

## Practical output format for this skill

When applying this skill in chat:

- Start with a table-by-table mapping from `db/install.xml` to backup XML nodes.
- Then list the exact files to create or edit under `mod/<modname>/backup/moodle2/`.
- Call out which tables are gated by `userinfo` or `groups`.
- Explicitly list fileareas, date fields, and foreign keys that require special restore handling.
- Prefer the smallest core example that matches the plugin's schema complexity before combining patterns from larger modules.
