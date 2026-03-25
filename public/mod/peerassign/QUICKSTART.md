# Quick Start Guide for mod_peerassign

## What Was Created

A complete Moodle activity module skeleton (`mod_peerassign`) with:

- ✅ All required Moodle callbacks implemented
- ✅ Database schema from specs (6 tables)
- ✅ Core business logic (manager, permissions)
- ✅ Webservice endpoint for phase creation
- ✅ Activity form with group submissions support
- ✅ Language strings for all UI elements
- ✅ Test structure (placeholder)

**Status:** Ready for next phase of development

## Installation & Testing

### 1. Install the Plugin

```bash
cd /home/ferran/moodles/aitest/moodle
php admin/cli/install_plugins.php
# or run Moodle upgrade through web interface
```

### 2. Verify Installation

```bash
# Check plugin is detected
php -r "require 'config.php'; print_r(\core_component::get_plugin_list('mod'));" | grep peerassign
```

### 3. Create a Test Activity

- Log in to Moodle
- Create a course
- Add Activity > Peer Review Assignment
- Fill in name and description
- Save

## Development Workflow

### For Each Feature Implementation

1. **Read the spec**
   ```bash
   cat public/mod/peerassign/specs/[folder]/[file].md
   ```

2. **Implement the feature**
   - Create/edit the relevant PHP file
   - Follow the acceptance criteria
   - Run PHP syntax check: `php -l path/to/file.php`

3. **Write tests**
   ```bash
   # Add tests to public/mod/peerassign/tests/
   # Run tests:
   vendor/bin/phpunit public/mod/peerassign/tests/
   ```

4. **Mark criteria as done**
   ```bash
   # Edit the spec file, change [ ] to [x]
   vim public/mod/peerassign/specs/[folder]/[file].md
   ```

5. **Document in vibelog**
   ```bash
   # Add implementation summary
   cat >> public/mod/peerassign/vibelog/[date]_[feature].md
   ```

## Key Files to Understand

| File | Purpose | Next Steps |
|------|---------|-----------|
| `lib.php` | Core Moodle callbacks | Already implemented ✅ |
| `mod_form.php` | Activity settings form | Add phase tab, settings |
| `classes/manager.php` | Business logic | Add phase managers, submission handlers |
| `classes/external/create_phase.php` | Webservice | Reference for other WS endpoints |
| `db/install.xml` | Database schema | Already defined from specs ✅ |
| `specs/` | Ground truth | Refer here before coding |

## Current Gaps (Priority Order)

1. **Persistence Classes** (Required for operations)
   ```php
   // Create in classes/persistence/
   phase.php
   submission.php
   peer_review.php
   grade.php
   ```

2. **Phase Validators** (Required for webservice)
   ```php
   // Create in classes/validators/
   phase_type_validator.php
   ```

3. **View Templates** (Required for UI)
   ```
   Create templates/ directory
   Add phase list, submission list, etc.
   ```

4. **Backup & Restore** (Spec requirement)
   ```php
   // Create in classes/backup/
   backup_task.php
   backup_step.php
   restore_task.php
   restore_step.php
   ```

## Common Commands

```bash
# Check for PHP errors
php -l public/mod/peerassign/lib.php

# Run all tests
vendor/bin/phpunit public/mod/peerassign/tests/

# Run specific test
vendor/bin/phpunit --filter test_create_activity public/mod/peerassign/tests/

# Lint all PHP files
find public/mod/peerassign -name "*.php" -exec php -l {} \;

# Format code to Moodle standards
vendor/bin/phpcs --standard=phpcs.xml.dist public/mod/peerassign/

# Fix formatting issues
vendor/bin/phpcbf --standard=phpcs.xml.dist public/mod/peerassign/
```

## Key Specs to Read First

1. **Start here:** `specs/README.md` - Understanding the spec format
2. **Then:** `specs/introduction.md` - Feature overview
3. **For development:**
   - `specs/database/schema.spec.md` - Database structure
   - `specs/businessrules/create-activity.spec.md` - Activity lifecycle
   - `specs/webservices/create-phase.spec.md` - API contract

## Support

- Specs are the source of truth: `public/mod/peerassign/specs/`
- Plugin documentation: `public/mod/peerassign/README.md`
- Implementation log: `public/mod/peerassign/vibelog/`
- Moodle mod docs: https://moodledev.io/docs/5.2/apis/plugintypes/mod

## Next: What to Implement First

### Recommended Next Step: Persistence Layer

```bash
# Create persistence classes for all tables
mkdir -p public/mod/peerassign/classes/persistence

# Create models for:
# - phase.php
# - submission.php
# - peer_review.php
# - grade.php
# - phase_completion.php

# Use as references:
# - public/mod/assign/classes/
# - public/mod/data/classes/
```

This will enable the manager to properly save/retrieve all data and make the webservice fully functional.
