# Current implementation and release blockers

This is a working development preview, not the requested complete parity release. The acceptance inventory currently contains 433 targets from the supplied Fluent Forms 6.2.13 package, pinned documentation revision `26cc985c69f521ec9e7c11f21f5fc64352c99805`, and the published catalog captured on 2026-09-18. A checked infrastructure item does not establish full product parity.

## Implemented and exercised locally

- Standalone plugin boot, database migrations, independent Composer/npm dependencies, WordPress capabilities and per-form access rules.
- React builder with field palette, pointer/keyboard drag controls, nested layouts, duplication, undo/redo, saved revisions, previews and basic design controls. Arrangement was compared with the local reference builder; comprehensive visual parity is outstanding.
- Server-rendered forms, field sanitization, shared PHP/TypeScript arithmetic and conditions, server-authoritative calculations, multi-step/conversational mode, repeaters and basic validation.
- 38 field controls including Google reCAPTCHA v2 Checkbox, compound name/address, country list, file/image upload, signature, rich text, ratings, NPS, ranking, grids, dates, masking, consent and layouts. Field-specific options are not equivalent to the reference's full field inventory.
- Private encrypted uploads, authenticated downloads, optimistic entry edits and history, notes, approval, email confirmation, conditional notifications/confirmations, PDF generation, save/resume and partial entries at step boundaries.
- Standard shortcodes, Gutenberg block with appearance overrides, basic landing pages, native dialog popups, GitPress mode compatibility and fresh submission sessions on cached pages.
- REST management APIs and authenticated MCP tools using WordPress capabilities/application passwords. Extension contracts for fields, integrations and payment providers.
- Persistent jobs with duplicate enqueue protection, bounded retries, visible failures and manual retry; redacted provider logs. Privacy exporter/eraser and configurable retention.
- Basic analytics, CSV/JSON entry export, six starter templates, import preview, stable source mappings, selected Fluent settings conversion and historical entry import without delivery jobs.

## Significant unfinished work

- Payment execution is absent. The payments/subscriptions/inventory tables and provider interface are only infrastructure. Stripe, PayPal, Mollie, Razorpay, Square, Paddle, Paystack and Authorize.Net need checkout, pricing, webhook verification, reconciliation, recurring billing and gateway-specific management/refund/cancellation support. Products, coupons, donations and concurrent inventory controls are outstanding.
- Seven external connector handlers exist: generic webhooks, Slack, Discord, Telegram, Mailchimp, Brevo and HubSpot. They have not passed real controlled-account tests and do not provide full reference field/operation coverage. Native user and draft-post creation are initial implementations. The remaining reference connectors are not implemented.
- Dynamic/chained selections, address autocomplete, quiz scoring, advanced survey reporting, repeater aggregate calculations, multiple uploads per field, configurable compound subfields and the full advanced validation matrix are incomplete or absent.
- Google reCAPTCHA v2 Checkbox is implemented with encrypted global credentials and server verification. reCAPTCHA v3, hCaptcha, Cloudflare Turnstile, Akismet, full spam/compliance settings, localization catalogs, weekly summaries, WP-CLI, form-location discovery, folders, complete template inventory, reusable/responsive styles and page-builder-specific integrations remain incomplete or absent.
- User/profile updates, CPT/taxonomy/custom-field mapping, AI form generation, complete smart-code behavior and reference-level developer hook coverage are outstanding.
- Fluent advanced styling, comprehensive settings migration, protected attachment copying and historical payment migration are not implemented. WPForms, Contact Form 7, Gravity Forms, Ninja Forms and Caldera migration adapters are absent.
- A complete visual/accessibility audit, every-field/branch test matrix, payment concurrency tests, provider callbacks, dependency namespace isolation and multi-plugin production compatibility remain required.

## External verification blockers

No provider sandbox credentials or controlled accounts were supplied. Account connection tests, gateway callbacks, refunds/cancellations and renewals therefore cannot be marked verified. Tests that inject a controlled transport failure only validate the queue; they do not certify a provider integration.

Native modules are shown only when code exists; there are no premium upgrade buttons or simulated gateway controls. The release gate stays closed until implementation and evidence cover every acceptance item.
