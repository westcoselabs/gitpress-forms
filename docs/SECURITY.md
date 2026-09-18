# Security and data lifecycle

This document describes implemented controls, not a completed security certification. The complete release remains blocked on the full data/security and provider test matrix.

- Management APIs enforce WordPress capabilities and form-specific grants. Only administrators can change global role assignments or form access policy. Entry viewing and editing are separate capabilities.
- Submissions require a short-lived form-scoped HMAC token obtained from an uncached session endpoint. Tokens authorize submission, not reading entries. Origin checks, rate slots and a honeypot are implemented. CAPTCHA/native anti-spam provider coverage remains outstanding.
- The server validates choices, bounds and field types, evaluates conditions and recalculates formulas. Submitted client totals are not suitable for payment pricing; a payment engine does not yet exist.
- Password fields are stripped recursively before entries, resume data and queue payloads are stored. Registration currently generates a WordPress password rather than persisting a submitted password.
- Uploads use an extension allowlist, MIME/content checks, size limits and encrypted random `.enc` files. Downloads require a WordPress nonce and entry/form permissions. Store backups of WordPress salts alongside database/files backups: changing salts makes credentials and uploads unreadable until restored or reconnected.
- Credentials use Sodium secretbox, with AES-GCM as fallback. API responses expose configuration state, not stored secrets. Provider response bodies are not copied into logs.
- Resume/confirmation/private-view links contain opaque random tokens whose hashes are stored. Treat a private link as a bearer credential. Login-restricted entry views are available. Resume data expires after 30 days; confirmations after three days; entry-view tokens after one year. Privacy erasure removes matching resume data and entries.
- Imported entries do not send notifications, recreate subscriptions or initiate payments. Imported source user IDs and roles are not trusted or assigned to destination users.
- Notifications and feeds enter a persistent queue inside the entry transaction. Jobs have unique dedupe keys, retry delays and a visible terminal failure after five attempts. Run WordPress cron reliably; the hourly sweep recovers missed scheduled attempts. External services may require their own idempotency keys to avoid redelivery after process interruption.
- Custom CSS/JS requires WordPress `unfiltered_html`; only trusted site administrators should receive that capability. Submitted content is never evaluated as JavaScript or PHP.

Deactivation preserves forms, entries, uploads and settings. Uninstall also preserves data by default. Enabling Global Settings → delete data on uninstall and then deleting the plugin permanently removes its tables, connection settings and recorded private files. Back up first. Multisite network-wide uninstall and complete dependency coexistence testing remain outstanding.

Retention runs during background cleanup. A positive per-form retention period deletes matching old entries and their recorded files/jobs/history. Keep the value zero to preserve them. WordPress Tools → Export/Erase Personal Data includes matching submissions and unfinished resume data. Historical financial retention policies are not implemented because payment processing is not implemented.
