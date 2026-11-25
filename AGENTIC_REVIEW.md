# Peer review fixup

## Edited files and reasons

- `public/ai/classes/manager.php` — `process_action()` no-provider fallback: return a generic response for custom actions whose inferred core response class does not exist.
- `public/ai/classes/aiactions/responses/response_generic.php` — generic response constructor and response-data methods used by that fallback.
- `public/ai/tests/manager_test.php` — `test_process_action_custom_action_without_provider()` covers the fallback; `test_set_action_state_for_custom_action()` and `test_set_action_state_rejects_non_action_class()` cover the accepted valid and invalid class cases.
- `public/ai/classes/local/upgrader.php` — `migrate_action_names()` contains the extracted, directly testable data-migration logic.
- `public/lib/db/upgrade.php` — migration savepoint calls `core_ai\local\upgrader::migrate_action_names()`; course-module basename conversion behavior remains unchanged.
- `public/ai/tests/local/upgrader_test.php` — `test_migrate_action_names()` covers core register, placement and module migration paths, plus a custom action mapping.
- `public/ai/tests/aiactions/base_test.php` — `action_strings_provider()` now yields named generator cases as required.
- `public/ai/provider/anthropic/tests/process_explain_text_test.php` — action-name response assertion and `explain_text` import.
- `public/ai/provider/anthropic/tests/process_generate_text_test.php` — action-name response assertion and `generate_text` import.
- `public/ai/provider/anthropic/tests/process_summarise_text_test.php` — action-name response assertion and `summarise_text` import.
- `public/ai/provider/awsbedrock/tests/process_generate_image_test.php` — action-name response assertions and `generate_image` import.
- `public/ai/provider/awsbedrock/tests/process_generate_text_test.php` — action-name response assertions and `generate_text` import.
- `public/ai/provider/awsbedrock/tests/process_summarise_text_test.php` — action-name response assertions and `summarise_text` import.
- `public/ai/provider/azureai/tests/process_generate_image_test.php` — action-name response assertions and `generate_image` import.
- `public/ai/provider/azureai/tests/process_generate_text_test.php` — action-name response assertions and `generate_text` import.
- `public/ai/provider/azureai/tests/process_summarise_text_test.php` — action-name response assertions and `summarise_text` import.
- `public/ai/provider/deepseek/tests/process_explain_text_test.php` — action-name response assertions and `explain_text` import.
- `public/ai/provider/deepseek/tests/process_generate_text_test.php` — action-name response assertions and `generate_text` import.
- `public/ai/provider/deepseek/tests/process_summarise_text_test.php` — action-name response assertions and `summarise_text` import.
- `public/ai/provider/gemini/tests/process_explain_text_test.php` — action-name response assertions and `explain_text` import.
- `public/ai/provider/gemini/tests/process_generate_image_test.php` — action-name response assertions and `generate_image` import.
- `public/ai/provider/gemini/tests/process_generate_text_test.php` — action-name response assertions and `generate_text` import.
- `public/ai/provider/gemini/tests/process_summarise_text_test.php` — action-name response assertions and `summarise_text` import.
- `public/ai/provider/ollama/tests/process_explain_text_test.php` — action-name response assertions and `explain_text` import.
- `public/ai/provider/ollama/tests/process_generate_text_test.php` — action-name response assertions and `generate_text` import.
- `public/ai/provider/ollama/tests/process_summarise_text_test.php` — action-name response assertions and `summarise_text` import.
- `public/ai/provider/openai/tests/process_generate_image_test.php` — action-name response assertions and `generate_image` import.
- `public/ai/provider/openai/tests/process_generate_text_test.php` — action-name response assertions and `generate_text` import.
- `public/ai/provider/openai/tests/process_summarise_text_test.php` — action-name response assertions and `summarise_text` import.
