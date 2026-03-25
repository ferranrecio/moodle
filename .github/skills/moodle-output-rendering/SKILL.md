---
name: moodle-output-rendering
description: Apply Moodle output rendering best practices across entry points, output classes, renderers, and Mustache templates to keep UI code maintainable, themeable, and architecturally correct.
---

# Good practices using Moodle Output Rendering

This guide defines good practices for Moodle UI development. In Moodle, bad examples outnumber good ones; follow these rules to ensure code is maintainable, themeable, and architecturally sound, while addressing common misconceptions and "bad practices" found in Moodle core.

---

## 🏛️ The Three Pillars of Output

## 🚀 The Entry Point (The Caller PHP File)
*The script that initiates the process (e.g., view.php, index.php). It acts as the orchestrator.*

- **✅ DO:**
  - Initialize the page context: `$PAGE->set_url()`, `$PAGE->set_context()`.
  - Fetch the "Domain Context" (data from the DB or a Manager).
  - Get the renderer instance: `$renderer = $PAGE->get_renderer('component_name');`.
  - Instantiate the Output class passing all depenendacies. Usally it requires some kind of manager class, a `cm_info`, or some helper.
  - Render the output: `echo $renderer->render($renderable);`. Because almost all new outputs implements `templatable` or `named_templatable`, the standard `render` method is enough.
  - In the best scenarios, the output class should have all dependencies injected in the constructor, and should be created using the \core\di library.
- **❌ DON'T:**
  - Avoid calling `echo $OUTPUT->render_from_template()` directly from the entry point. Always go through a `renderable` object and a `$renderer->render` method. Otherwise, themes will not be able to modify the logic.
  - Avoid logic that decides *how* things look; just pass the data to the Output class.
  - In the rare cases where the access point needs to decide something based on the output opinion, never use the `export_for_template` data. Instead, create public methods in the output.

### 1. 🧠 The Output Class (UX Expert)
*The "Intelligence" of the UI. It interprets the business logic into a displayable state. The Output class understands the domain context and prepares data for display.*

- **✅ DO:**
  - Implement `templatable` or `named_templatable`.
  - Use a Manager or Helper class to fetch data (Dependency Injection style).
  - Use `export_for_template(renderer_base $output)` to format data for Mustache.
  - If necessary, inform the renderer or the entry point of UI preferences via custom public methods.
  - In most cases, all dependencies should be injected through the constructor. The  exception is when the output needs extra optional settings after creation, in that case, those settings can be set through public methods.
  - All setters should return `$this` to allow chaining.
- **❌ DON'T:**
  - **No `$DB`:** Output classes must never query the database directly.
  - **No HTML/JS:** Never return raw HTML strings or use `html_writer`.
  - **No Globals:** Using `$OUTPUT` or `$PAGE` inside the class is forbidden. Use the renderer instance passed to the export method instead. The `$USER` can be used because there is not yet a suitable altenrative.
- **⚠️ The "Capability Anomaly":** While logic should ideally stay in a `manager` or `permissions` class, checking user capabilities via `has_capability` inside an output class is a tolerated practice while the validations are simple conditions.

### 2. 🎨 The Renderer (Architect Expert)
The Bridge between logic and the template. It was far overused in the past, but for new code it should be mostly empty classes, with some minor helpers at most.

- **✅ DO:**
  - Use the standard `$OUTPUT->render($instance)` method 99% of the time unless there is no other way.
  - Help output classes build complex components (like tabs or navigation).
  - Provide a way for themes to override specific UI elements.
  - It is a good practice that every plugin creates it's own rendering extending `core\output\plugin_renderer_base`. All entry points should use an instance of that rendered instead of the global `$OUTPUT`.
- **❌ DON'T:**
  - **No Data Mutation:** Never modify the object returned by `export_for_template` before rendering.
  - **No Logic Decisions:** Don't "peek" into the data to decide how to render; the Output class should tell the renderer what state it is in. If the renderer need to know some internals of the output, the output should provide public methods to get it.

### 3. 🖥️ The Template (Frontend Expert)

The responsible for HTML structure and component-level JS.

- **✅ DO:**
  - Keep logic minimal (simple truthy/falsy Mustache tags).
  - Initialize component-specific JS modules (AMD) within the template.
  - Use data attributes for JS selectors instead of IDs.
- **❌ DON'T:**
  - **No Inline JS:** Never write raw `<script>` logic inside the `.mustache` file. Use `{{#js}}` instead and keep it minimal, just the requires and call some `init` method, any more thant this is considered wrong.
  - **No Hardcoded IDs:** Avoid `id="my-button"`. If an ID is required for ARIA, generate a unique one.

---

## 🔍 Peer Review Checklist (Vibe Coding/Patches)

1. **Does it use `$DB` in the Output class?** 🚩 (Move to a manager/helper)
2. **Does it use `$OUTPUT` in the Output class?** 🚩 (Use the passed renderer)
3. **Is there a new Renderer method for a simple component?** 🚩 (Use `templatable` + `$renderer->render()`)
4. **Is JavaScript being initialized in the PHP instead of the Template?** 🚩 (Move to Mustache)
5. **Is the Output class returning `html_writer` tags?** 🚩 (Move to Template)
6. **Is there a new Renderer that only takes an output instance and renders it?** 🚩 (Use `templatable` + `$renderer->render()`)
7. **Does the entry point cals `render_for_template`?** 🚩 (Move to output class)
8. **Does the entry point generate template data?** 🚩 (Move to output class)
9. **Does an output class construct gets complex custom `stdClass` or `array` structures?** 🚩 (use helpers, managers or any other class instances)
