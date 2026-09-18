# Architecture and extension interfaces

`gitpress-forms.php` owns bootstrapping. `includes/` contains original PHP application code; `src/admin/` is the React/TypeScript builder and management app; `src/frontend/` hydrates server-rendered public HTML; `src/block/` integrates Gutenberg. `schema/fields.json` is the built-in field catalog. `build/` is generated. This standalone project owns all plugin dependencies and scripts and does not depend on the Barber Refinery website.

Definitions use `schemaVersion: 1` and contain `fields`, `settings` and `style`. Fields have stable IDs, unique names, nested children, options and optional rules/formulas. PHP rejects invalid/cyclic references. Arithmetic is parsed without eval in both runtimes. WordPress recalculates and validates every submitted value, removes hidden and unknown values, and strips passwords before persistence.

Tables use the site's table prefix plus `gitpress_forms_`: forms, revisions, entries, entry_revisions, files, feeds, jobs, payments, subscriptions, inventory, imports, tokens, events and logs. The last three financial tables do not imply a working payment engine. MySQL operations use transactions and nested savepoints; extension callbacks execute after commit. Form/entry writes reject stale versions.

Management REST endpoints live under `/wp-json/gitpress-forms/v1`. WordPress cookie authentication requires the REST nonce; application passwords use WordPress authentication. Public routes provide submission, upload, save and resume operations. Session tokens come from uncached `admin-ajax.php?action=gitpress_forms_session`. Rendering includes scoped CSS even after the page head, and the frontend renews stale sessions without relying on nonces stored in cached HTML.

The plugin adds `gitpress_form` to `dgs_allowed_inner_shortcodes`. GitPress caches its source fragment and expands the allowlisted shortcode. Each rendered form has a distinct instance ID and its own session state. There is no GitHub synchronization of form definitions or entries.

Register extensions on `gitpress_forms/register_extensions` using `GitPress\Forms\Extensions`. Implement the contracts in `includes/Contracts/FieldType.php`, `Integration.php` or `PaymentProvider.php`. Field settings use a validated schema and are available in the builder. Integration delivery receives a stable delivery key. PaymentProvider is a contract only; checkout orchestration is not implemented.

Developer actions currently include `gitpress_forms/form_saved` and `gitpress_forms/entry_accepted`. The accepted hook runs after the transaction commits. Durable external work should be a queue job. A crash after an external service accepts a request can lead to redelivery: provider idempotency is still required where supported.

## MCP

POST JSON-RPC requests to `/wp-json/gitpress-forms/v1/mcp` with WordPress application-password Basic authentication over HTTPS, or an authenticated WordPress cookie and REST nonce. Use a dedicated account with the narrowest form/entry capabilities. The tool list reflects its permissions; tool execution goes through the same management REST permissions and argument validation. Read `includes/Mcp.php` for current tools and input schemas.

The endpoint uses stateless JSON responses; GET/SSE and DELETE sessions return 405. Notifications return empty 202 responses. OAuth discovery and a complete client compatibility matrix are not implemented. Never place application passwords in query strings, repository files, or public form configuration.
