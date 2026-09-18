# Verification evidence

Evaluation date: 2026-09-18. These results validate the implemented subset, not complete Fluent Forms parity. No provider sandbox has been tested.

## Automated checks

| Check | Result |
| --- | --- |
| TypeScript | Pass: `npm run typecheck` |
| Browser bundles | Pass: `npm run build` |
| Shared conditions/arithmetic and tree operations | 25 tests passed: `npm test` |
| PHP validation and calculation authority | 31 checks passed: `npm run test:php` |
| PHP syntax | Pass: `npm run lint:php` |
| WordPress/MySQL capabilities, encryption, savepoints and callback isolation | 18 checks passed: `node scripts/wordpress.mjs tests/wp/security.php` |
| Import idempotency, privacy and persistent queue failures | 13 checks passed: `node scripts/wordpress.mjs tests/wp/data.php` |
| WordPress 6.9.1 / PHP 8.2.27 / MySQL 8.0.35 | 19 browser tests passed together, plus the added expired-upload-session test passed separately |
| WordPress 6.6 / PHP 8.2.27 / MySQL 8.0.35 | Nine core browser scenarios passed; one WordPress login-focus race was fixed in the test helper and the affected import test reran successfully |
| WordPress 7.1.1 / PHP 8.5 / Playground SQLite | Nine core browser scenarios passed, including real PDF generation and encrypted uploads |
| Parent Next.js website | TypeScript, ESLint and production build passed |
| npm audit | Zero advisories after pinning the development `qs` override |
| Composer audit | No advisories reported |

The 20 browser scenarios cover builder creation/publication, public storage, server recalculation and hidden-field pruning, malicious choices, idempotency, private resume tokens, stale revision rejection, approval gating, nested repeaters, protected downloads, entry history and PDFs, native popup behavior, Gutenberg appearance overrides, keyboard multi-step completion and automated WCAG AA checks on a mobile public form. GitPress tests cover Theme Wrapped, Full Canvas, managed layouts, stale fragments and independent multiple instances with expired-session renewal. MCP tests use a restricted WordPress account and real application-password authentication.

The minimum-version site has no Fluent Forms or GitPress installed. The current Playground site also runs this plugin independently. The main isolated site uses the supplied GitPress plugin and temporarily installed Fluent Forms only for reference UI capture. No source edits were made to either supplied plugin. Fluent Forms was then deactivated in the main isolated site and the builder/submission flow passed again.

## Visual checks

The local reference package's form list, builder, settings, entries and free preview/styler screens were captured for comparison. The builder was adjusted toward the reference's white canvas, right-side palette, blue accents and field/history tabs. Original GitPress components and branding were retained. Full screenshot-diff coverage, premium reference behavior checks and comprehensive keyboard/accessibility coverage are not complete.

A two-page PDF containing accented names and a long wrapped response was rendered with Dompdf, converted with `pdftoppm`, and both pages inspected. Content, pagination and footers were readable without overlap. Browser tests verify the protected PDF endpoint returns actual PDF bytes.

## Reproduce locally

1. Run the build and unit commands from README.md.
2. For Playground: `npm run dev:wordpress`. For the current matrix: `npx wp-playground-cli server --auto-mount --port=9425 --php=8.5 --wp=7.1.1 --blueprint=tests/matrix-blueprint.json`.
3. For native MySQL on Windows with Local runtimes, run `scripts/native-test-setup.ps1 -WordPressSource '<clean local WordPress core path>'`. It creates `.runtime/wordpress`, a separate database on port 9406, a test-only configuration, and a junction to this plugin. Start it with `npm run dev:native`.
4. Copy `tests/wp/test-transport.php` into the isolated site's `wp-content/mu-plugins/`. It suppresses test email and supplies the controlled GitPress outage fixture. It is not production code.
5. To test GitPress, create a junction from the isolated `wp-content/plugins/gitpress` to your reference GitPress folder and run `node scripts/wordpress.mjs tests/wp/setup.php`. Run `node scripts/wordpress.mjs tests/wp/mcp-setup.php` for the restricted MCP test account.
6. Run `npm run test:e2e`. Use `GPF_TEST_URL` to select another local site. Core matrix tests are `npx playwright test tests/e2e/forms.spec.ts tests/e2e/advanced.spec.ts`.

The local tests use `gpf_admin` / `gitpress-test-only` in disposable fixtures. Never use that configuration or password on a public site. The integration scripts require an isolated-sandbox marker and do not target a client installation. Playwright reports/traces, local database data and test application passwords live in ignored directories and are excluded from archives.

The release gate intentionally fails. Remaining implementation, provider access and parity verification blockers are recorded individually in ACCEPTANCE.md and summarized in STATUS.md.

## Package and lifecycle checks

The development ZIP was extracted into a separate plugin directory on the isolated WordPress 6.6/MySQL site. Builder/public submission and private entry/PDF scenarios passed using those packaged PHP files, Composer dependencies and browser assets. Deactivation preserved forms. The default uninstall preserved forms. A separate explicit-deletion uninstall removed plugin tables and role capabilities. Run the two lifecycle cases in separate PHP processes because WordPress includes uninstall.php only once per process.

The canonical `gitpress-forms.zip` installer was also uploaded through WordPress's Plugins → Add New → Upload Plugin screen, installed, and activated successfully on PHP 8.2. The GitHub source download and the `-source.zip` development archive are not WordPress installers.

Archive paths are checked for private runtime directories, environment files, test credential files and node_modules. Two consecutive package runs with unchanged inputs must produce identical SHA-256 hashes. Stable version packaging rejects an incomplete acceptance checklist.
