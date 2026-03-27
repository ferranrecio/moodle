# Anti-patterns Spec

## Purpose

This spec enumerates coding patterns that must **not** appear in `mod_peerassign`. Every acceptance criterion is a prohibition — CI, peer review, and the SDV agent must treat violations as implementation errors.

## Background

During the initial implementation rounds, several recurring mistakes were caught in peer review: wrong namespaces, hardcoded strings, hallucinated constants, unnecessary files, and bypassed abstractions. Capturing these as an explicit checklist prevents the same mistakes from reappearing in future iterations.

## Behaviors

### Scenario: No hardcoded plugin or module name strings

**Given** the manager class exposes `manager::MODULE` (`'peerassign'`) and `manager::PLUGINNAME` (`'mod_peerassign'`)
**When** any PHP file in the plugin references the plugin name
**Then** it must use the constants, not literal strings

Rules to follow:

- [ ] No PHP file contains the literal `'peerassign'` outside of the `manager::MODULE` definition and `lang/en/peerassign.php`
- [ ] No PHP file contains the literal `'mod_peerassign'` outside of the `manager::PLUGINNAME` definition and `lang/en/peerassign.php`

Verification command:

```bash
grep -rn "'peerassign'" public/mod/peerassign/ --include='*.php' | grep -v 'manager::MODULE' | grep -v 'lang/en/peerassign.php'
grep -rn "'mod_peerassign'" public/mod/peerassign/ --include='*.php' | grep -v 'manager::PLUGINNAME' | grep -v 'lang/en/peerassign.php'
```

---

### Scenario: No html_writer in entry points or output classes

**Given** the plugin uses `output` classes implementing `renderable` and `templatable`
**When** building UI for any entry point
**Then** all markup is produced via Mustache templates, never `html_writer`

Rules to follow:

- [ ] No entry point (`view.php`, `index.php`, etc.) calls `html_writer::*`
- [ ] No class in `classes/output/` calls `html_writer::*`
- [ ] Every output class implements both `\renderable` and `\templatable` or `\named_templatable`
- [ ] Every output class has a corresponding Mustache template in `templates/`

---

### Scenario: No custom render methods for templatable output classes

**Given** output classes implement `\templatable` or `\named_templatable` and are rendered via `$OUTPUT->render()`
**When** the renderer is defined
**Then** it must not contain `render_<classname>` methods that duplicate the templatable contract

Rules to follow:

- [ ] The plugin renderer does not define `render_*` methods for any class that already implements `\templatable` or `\named_templatable`

---

### Scenario: Output classes use constructor promotion, not old-style properties

**Given** PHP 8.3+ is the minimum version
**When** defining output class attributes
**Then** use constructor parameter promotion instead of separate property declarations with manual assignment

Rules to follow:

- [ ] Output classes in `classes/output/` use constructor promoted parameters (e.g. `public function __construct(protected manager $manager, ...)`)
- [ ] No output class has a block of `$this->x = $x` assignments that could be replaced by promotion

---

### Scenario: Required language strings are always present

**Given** Moodle requires specific lang strings for every activity module
**When** the plugin is installed
**Then** all mandatory language strings exist

Rules to follow:

- [ ] `$string['pluginname']` exists in `lang/en/peerassign.php`
- [ ] `$string['pluginadministration']` exists in `lang/en/peerassign.php`
- [ ] `$string['modulename']` exists in `lang/en/peerassign.php`
- [ ] Every capability defined in `db/access.php` has a matching `$string['peerassign:<action>']`
