<?php
namespace GitPress\Forms;

final class Database
{
    private static int $transactionDepth = 0;
    private static array $committedCallbacks = [];
    public static function table(string $name): string { global $wpdb; return $wpdb->prefix . 'gitpress_forms_' . $name; }
    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collation = $wpdb->get_charset_collate();
        $schemas = [
            'forms' => "id bigint unsigned NOT NULL AUTO_INCREMENT, title varchar(255) NOT NULL, slug varchar(190) NOT NULL, status varchar(30) NOT NULL DEFAULT 'draft', definition longtext NOT NULL, version bigint NOT NULL DEFAULT 1, created_by bigint NOT NULL DEFAULT 0, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY slug (slug), KEY status (status)",
            'revisions' => "id bigint unsigned NOT NULL AUTO_INCREMENT, form_id bigint unsigned NOT NULL, version bigint NOT NULL, definition longtext NOT NULL, created_by bigint NOT NULL DEFAULT 0, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY revision (form_id,version)",
            'entry_revisions' => "id bigint unsigned NOT NULL AUTO_INCREMENT, entry_id bigint unsigned NOT NULL, version bigint unsigned NOT NULL, snapshot longtext NOT NULL, created_by bigint NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY revision (entry_id,version)",
            'entries' => "id bigint unsigned NOT NULL AUTO_INCREMENT, version bigint unsigned NOT NULL DEFAULT 1, form_id bigint unsigned NOT NULL, form_version bigint NOT NULL, status varchar(30) NOT NULL, response longtext NOT NULL, notes longtext NOT NULL, user_id bigint NOT NULL DEFAULT 0, source_url text NOT NULL, idempotency_key varchar(64) NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY submission_key (form_id,idempotency_key), KEY form_status (form_id,status), KEY created_at (created_at)",
            'files' => "id bigint unsigned NOT NULL AUTO_INCREMENT, form_id bigint unsigned NOT NULL, entry_id bigint unsigned NOT NULL DEFAULT 0, token_hash varchar(64) NOT NULL, field_name varchar(190) NOT NULL, original_name varchar(255) NOT NULL, path text NOT NULL, mime varchar(190) NOT NULL, bytes bigint NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY token_hash (token_hash), KEY entry_id (entry_id)",
            'feeds' => "id bigint unsigned NOT NULL AUTO_INCREMENT, form_id bigint unsigned NOT NULL, provider varchar(100) NOT NULL, title varchar(255) NOT NULL, enabled tinyint NOT NULL DEFAULT 1, configuration longtext NOT NULL, PRIMARY KEY  (id), KEY form_id (form_id)",
            'jobs' => "id bigint unsigned NOT NULL AUTO_INCREMENT, entry_id bigint unsigned NOT NULL, kind varchar(100) NOT NULL, payload longtext NOT NULL, dedupe_key varchar(190) NOT NULL, status varchar(30) NOT NULL DEFAULT 'pending', attempts int NOT NULL DEFAULT 0, available_at datetime NOT NULL, locked_at datetime DEFAULT NULL, last_error text NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY dedupe_key (dedupe_key), KEY runnable (status,available_at)",
            'logs' => "id bigint unsigned NOT NULL AUTO_INCREMENT, entry_id bigint unsigned NOT NULL DEFAULT 0, level varchar(20) NOT NULL, event varchar(100) NOT NULL, message text NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY entry_id (entry_id)",
            'payments' => "id bigint unsigned NOT NULL AUTO_INCREMENT, entry_id bigint unsigned NOT NULL, provider varchar(100) NOT NULL, external_id varchar(190) NOT NULL, status varchar(30) NOT NULL, amount bigint NOT NULL, currency varchar(10) NOT NULL, metadata longtext NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY external_payment (provider,external_id), KEY entry_id (entry_id)",
            'subscriptions' => "id bigint unsigned NOT NULL AUTO_INCREMENT, entry_id bigint unsigned NOT NULL, provider varchar(100) NOT NULL, external_id varchar(190) NOT NULL, status varchar(30) NOT NULL, metadata longtext NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY external_subscription (provider,external_id)",
            'inventory' => "id bigint unsigned NOT NULL AUTO_INCREMENT, sku varchar(190) NOT NULL, quantity bigint NOT NULL DEFAULT 0, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY sku (sku)",
            'imports' => "id bigint unsigned NOT NULL AUTO_INCREMENT, source varchar(80) NOT NULL, source_id varchar(190) NOT NULL, target_id bigint unsigned NOT NULL, kind varchar(30) NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY source_item (source,source_id,kind)",
            'tokens' => "id bigint unsigned NOT NULL AUTO_INCREMENT, token_hash varchar(64) NOT NULL, purpose varchar(40) NOT NULL, form_id bigint unsigned NOT NULL, payload longtext NOT NULL, expires_at datetime NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY token_hash (token_hash), KEY expiry (expires_at)",
            'events' => "id bigint unsigned NOT NULL AUTO_INCREMENT, form_id bigint unsigned NOT NULL, kind varchar(30) NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY form_event (form_id,kind,created_at)",
        ];
        foreach ($schemas as $name => $schema) {
            // dbDelta expects one field per line, but commas inside indexes are preserved.
            $schema = preg_replace('/, (?=[a-zA-Z_]+ (?:bigint|varchar|longtext|text|datetime|tinyint|int)|PRIMARY|UNIQUE|KEY)/', ",\n", $schema);
            dbDelta('CREATE TABLE ' . self::table($name) . " (\n$schema\n) ENGINE=InnoDB $collation;");
        }
        foreach (['manage_gitpress_forms', 'view_gitpress_entries', 'manage_gitpress_entries', 'manage_gitpress_integrations'] as $cap) { get_role('administrator')?->add_cap($cap); }
        update_option('gitpress_forms_schema', 3, false);
        if (!wp_next_scheduled('gitpress_forms_jobs')) { wp_schedule_event(time() + 60, 'hourly', 'gitpress_forms_jobs'); }
    }
    public static function json(mixed $value): string { return (string) wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }
    public static function now(): string { return gmdate('Y-m-d H:i:s'); }
    public static function transaction(callable $callback): mixed
    {
        global $wpdb;
        $depth = self::$transactionDepth++;
        $callbackCount = count(self::$committedCallbacks);
        $savepoint = 'gpf_transaction_' . $depth;
        try {
            if ($wpdb->query($depth ? "SAVEPOINT $savepoint" : 'START TRANSACTION') === false) { throw new \RuntimeException('Could not begin database transaction.'); }
            $value = $callback();
            if ($wpdb->query($depth ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT') === false) { throw new \RuntimeException('Could not commit database transaction.'); }
        } catch (\Throwable $e) { self::$committedCallbacks = array_slice(self::$committedCallbacks, 0, $callbackCount); $wpdb->query($depth ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK'); throw $e; }
        finally { self::$transactionDepth--; }
        if (!$depth) {
            $callbacks = self::$committedCallbacks; self::$committedCallbacks = [];
            foreach ($callbacks as $callback) {
                try { $callback(); }
                catch (\Throwable $e) { self::log('extension_callback_failed', 'An after-commit callback failed. Inspect the extension independently.', 0, 'error'); }
            }
        }
        return $value;
    }
    public static function afterCommit(callable $callback): void
    {
        if (self::$transactionDepth) { self::$committedCallbacks[] = $callback; }
        else { $callback(); }
    }
    public static function log(string $event, string $message, int $entryId = 0, string $level = 'info'): void
    {
        global $wpdb;
        $wpdb->insert(self::table('logs'), ['entry_id' => $entryId, 'event' => sanitize_key($event), 'message' => substr(sanitize_text_field($message), 0, 2000), 'level' => $level, 'created_at' => self::now()]);
    }
}
