# Peer Review Assignment Plugin (mod_peerassign)

## Overview

The Peer Review Assignment plugin (`mod_peerassign`) is a Moodle activity module that enables instructors to create progressive, multi-phase learning activities where students submit work, receive peer feedback, and benefit from instructor evaluation.

## Plugin Structure

```
mod_peerassign/
├── version.php                      # Plugin version information
├── lib.php                          # Core plugin callbacks (add_instance, update_instance, delete_instance, supports)
├── mod_form.php                     # Activity configuration form
├── view.php                         # Main activity view page
├── index.php                        # Course activity list (redirects to course view)
├── specs/                           # Specification documents
│   ├── README.md                    # How to use these specs
│   ├── introduction.md              # Plugin overview and features
│   ├── database/
│   │   └── schema.spec.md           # Database schema specification
│   ├── webservices/
│   │   └── create-phase.spec.md     # Webservice specification for phase creation
│   └── businessrules/
│       ├── create-activity.spec.md  # Business rules for creating activities
│       ├── update-activity.spec.md  # Business rules for updating activities
│       └── delete-activity.spec.md  # Business rules for deleting activities
├── db/
│   ├── access.php                   # Capability definitions
│   ├── install.xml                  # Database schema
│   ├── services.php                 # Webservice definitions
│   └── upgrade.php                  # Database upgrade script
├── lang/
│   └── en/
│       └── peerassign.php           # Language strings
├── classes/
│   ├── local/
│   │   ├── manager.php              # Core business logic manager
│   │   └── permissions.php          # Permission checks
│   └── external/
│       └── create_phase.php         # External function for creating phases
└── vibelog/                         # Implementation summaries and review notes
```

## Installation

1. Place the plugin in `public/mod/peerassign/`
2. Run the Moodle upgrade process to create the database tables
3. The plugin will be registered and available for use

## Database Schema

The plugin defines the following tables:

- `peerassign` - Main activity instances
- `peerassign_phases` - Progressive workflow stages
- `peerassign_submissions` - Student work submissions
- `peerassign_peer_reviews` - Peer feedback data
- `peerassign_grades` - Peer and teacher grades
- `peerassign_phase_completion` - Phase completion tracking

See `specs/database/schema.spec.md` for complete schema details.

## Key Features

- **Progressive Phases**: Activities consist of multiple phases (Sample & Description, Validation, Submission, Peer Review, Teacher Evaluation)
- **Peer Review**: Configurable peer reviewer assignments and anonymous grading options
- **Grading**: Support for both peer and instructor grades with flexible aggregation
- **File Sharing**: Upload files to submission, review, and phase phases
- **Group Submissions**: Optional group-based submission workflows

## Development

### Running Tests

```bash
# PHPUnit tests
vendor/bin/phpunit public/mod/peerassign/tests/

# Behat tests
php public/admin/tool/behat/cli/init.php
php public/admin/tool/behat/cli/run.php
```

### Webservices

The plugin provides the following external functions:

- `mod_peerassign_create_phase` - Create a new phase

See `specs/webservices/create-phase.spec.md` for the complete API specification.

## Spec Files

This plugin follows a spec-driven development approach. All behavior is documented in the specs folder:

- Read `specs/README.md` to understand how specs are structured
- Check `specs/introduction.md` for feature overview
- See `specs/database/schema.spec.md` for all database details
- Review `specs/businessrules/` for create/update/delete rules
- Check `specs/webservices/` for API contracts

Mark acceptance criteria with `[x]` as they are implemented and tested. All tests must verify the acceptance criteria from the specs.

## Implementation Status

This is an **alpha** version with basic plugin structure created. Key features are still under development and marked in the specs.

## Contributing

Follow the spec-driven development approach:

1. Before implementing, read the relevant spec file fully
2. Implement according to acceptance criteria
3. Write tests to verify the acceptance criteria
4. Mark criteria as `[x]` when implemented and tested
5. Add a summary to `vibelog/` documenting your changes
6. Check in code with reference to the spec

## References

- [Moodle Plugin Development](https://moodledev.io/docs/5.2/apis/plugintypes/mod)
- [Moodle Activity Module API](https://moodledev.io/docs/5.2/apis/plugintypes/mod/courseoverview)
