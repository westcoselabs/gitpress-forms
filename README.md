# GitPress Forms

Independent WordPress form builder for the GitPress ecosystem. Original PHP and React/TypeScript implementation; Fluent Forms is not a runtime dependency. There are no subscription checks, feature entitlements, site limits, hosted GitPress dependency, or plugin transaction fees.

**Version 0.1.0-dev is a development preview. It is not the complete Fluent Forms parity release described in the product plan.** Payments are not implemented. Native integration coverage and live account verification are incomplete. Review [current coverage](docs/STATUS.md) and the [release checklist](docs/ACCEPTANCE.md) before evaluation. This build has not been approved for client production use.

## Requirements

- WordPress 6.6 or later; PHP 8.2 or later.
- MySQL with InnoDB for production. WordPress Playground/SQLite is supported for local browser development.
- PHP mbstring, DOM, fileinfo, and Sodium or OpenSSL. GD is required for image/PDF operations.
- WordPress cron and a working WordPress mail transport for queued notifications.

## Install the preview

1. Build or obtain `dist/gitpress-forms-0.1.0-dev.zip`.
2. On a test site, open Plugins → Add New → Upload Plugin, upload the ZIP, and activate it.
3. Open GitPress Forms, create a form, choose Published, and save.
4. Embed `[gitpress_form id="123"]` or use the GitPress Form block.
5. For a popup, use `[gitpress_form id="123" popup="Contact us"]`.

GitPress can render the same shortcode inside its HTML fragments. GitPress remains optional and needs no source changes. Form definitions, entries and workflows belong to WordPress; they are not synchronized to GitHub.

## Reproducible build

Use Node.js 24, npm, Composer 2 and PHP 8.2+:

```sh
npm ci
composer install --no-dev --prefer-dist --optimize-autoloader
npm run typecheck
npm test
npm run test:php
npm run lint:php
npm run build
npm run package
```

Set `GPF_PHP` to your PHP executable if it is not on PATH. The Windows helper can also locate Local's PHP runtime. Packaging includes Composer dependencies, browser bundles, examples and documentation. An additional source ZIP includes the editable project and lockfiles. ZIP entries use fixed timestamps and sorted paths.

```sh
npm run release:check
```

This command intentionally fails while any required acceptance item lacks verified evidence. Stable-version packaging enforces it; development archives are explicitly named `-dev`.

## Development and verification

```sh
npm run dev:wordpress
```

Opens a disposable WordPress Playground site on port 9400. See [testing instructions and evidence](docs/VERIFICATION.md) for the isolated PHP/MySQL site, browser tests and version matrix. No production website was changed during implementation. GitPress Forms is a standalone project with its own dependencies and build configuration, independent of the Barber Refinery website and the GitPress plugin.

Additional documentation: [migration](docs/MIGRATION.md), [architecture and extension interfaces](docs/ARCHITECTURE.md), [security and data lifecycle](docs/SECURITY.md), [provider verification](docs/PROVIDERS.md), [third-party notices](docs/THIRD-PARTY-NOTICES.md).

Copyright 2026 WestCose Labs. Original plugin code is licensed under GPL-2.0-or-later; see [LICENSE](LICENSE). Third-party dependencies retain their licenses.
