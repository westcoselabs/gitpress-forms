# Migration

Use Tools → Import Forms to upload a GitPress Forms or Fluent Forms JSON export, preview conversions and warnings, and then import. Destination forms are drafts. Publish only after reviewing fields, behavior and a test submission. Source data is read from the uploaded export and is never edited.

GitPress definitions include versioned fields, settings and design. Fluent conversion currently covers supported fields, basic options/layouts, selected conditions, direct notification recipients, basic confirmation messages, login requirements, total entry limits, schedules, label placement and button color. Unsupported fields/settings are reported. Unsupported export formats are rejected rather than silently treated as empty forms.

For deterministic repeated imports, wrap data as:

```json
{
  "source": "my-source-site-2026",
  "forms": [{ "id": 123, "title": "Contact", "form_fields": {}, "metas": [], "entries": [] }]
}
```

Use a stable, unique `source` per source website. Source form and entry IDs are mapped into plugin-owned import records. Re-importing a mapped form does not overwrite local edits; previously unseen historical entries can be added. Without a source identifier, the entire export content determines the namespace: the same file deduplicates, while a different file is a separate source. Do not use the same source name for unrelated websites.

Historical entries can be supplied in each form's `entries` array with `id`, `response`, optional `status`, `created_at` and `source_url`. Responses may be JSON objects or encoded JSON. Password fields are removed. Imported entries never enqueue emails or integration deliveries. Import up to 100 forms and 500 entries per form per request; larger migrations need batching. Attachments remain URL references; copying and securing the source attachments is not yet implemented.

Provider secrets and feeds are not migrated. Reconnect accounts and review mappings. Payment history is not imported and importing never initiates a charge or subscription. The other planned migration formats and full Fluent behavior conversion remain release blockers in ACCEPTANCE.md.
