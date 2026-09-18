<?php
namespace GitPress\Forms;

final class RestApi
{
    public static function register(): void
    {
        self::route('/forms', 'GET', static fn ($r) => array_values(array_filter(Repository::forms((string) ($r['search'] ?? '')), static fn ($form) => Permissions::can($form['id']) || Permissions::can($form['id'], 'entries'))), 'access_gitpress_forms');
        self::route('/forms', 'POST', static fn ($r) => Repository::save(self::body($r)));
        self::route('/forms/(?P<id>\d+)', 'GET', static fn ($r) => Repository::form((int) $r['id']) ?? new \WP_Error('not_found', 'Form not found.', ['status' => 404]));
        self::route('/forms/(?P<id>\d+)', 'PUT', static fn ($r) => Repository::save(self::body($r), (int) $r['id']));
        self::route('/forms/(?P<id>\d+)/duplicate', 'POST', static function ($r) { $form = Repository::form((int) $r['id']); if (!$form) { throw new \InvalidArgumentException('Form not found.'); } $form['title'] .= ' (Copy)'; $form['status'] = 'draft'; return Repository::save($form); });
        self::route('/forms/(?P<id>\d+)/revisions', 'GET', static function ($r) { global $wpdb; return $wpdb->get_results($wpdb->prepare('SELECT id,version,created_at,created_by FROM ' . Database::table('revisions') . ' WHERE form_id=%d ORDER BY version DESC LIMIT 100', (int) $r['id']), ARRAY_A); });
        self::route('/forms/(?P<id>\d+)/revisions/(?P<revision>\d+)', 'POST', static function ($r) { global $wpdb; $form = Repository::form((int) $r['id']); $raw = $wpdb->get_var($wpdb->prepare('SELECT definition FROM ' . Database::table('revisions') . ' WHERE form_id=%d AND id=%d', (int) $r['id'], (int) $r['revision'])); if (!$form || !$raw) { throw new \InvalidArgumentException('Revision not found.'); } $form['definition'] = json_decode($raw, true); return Repository::save($form, $form['id']); });
        self::route('/forms/(?P<id>\d+)/submit', 'POST', static fn ($r) => Submissions::submit((int) $r['id'], self::body($r)), 'public');
        self::route('/forms/(?P<id>\d+)/progress', 'POST', static fn ($r) => Submissions::saveProgress((int) $r['id'], self::body($r)), 'public');
        self::route('/forms/(?P<id>\d+)/resume', 'POST', static function ($r) { $body = self::body($r); Submissions::available((int) $r['id']); Security::rateLimit('resume', 20); return Progress::resume((int) $r['id'], (string) ($body['resumeToken'] ?? '')); }, 'public');
        self::route('/forms/(?P<id>\d+)/upload', 'POST', static function ($r) { $id = (int) $r['id']; Submissions::available($id); if (!Security::origin() || !Security::checkToken((string) $r['token'], $id)) { throw new \RuntimeException('Upload session expired.', 403); } Security::rateLimit('upload', 10); $files = $r->get_file_params(); return Files::upload($id, sanitize_text_field($r['field']), $files['file'] ?? []); }, 'public');
        self::route('/entries', 'GET', static fn ($r) => Repository::entries((int) ($r['form'] ?? 0), (string) ($r['search'] ?? ''), (string) ($r['status'] ?? ''), max(1, (int) ($r['page'] ?? 1)), true), 'view_gitpress_entries');
        self::route('/entries/(?P<id>\d+)', 'PUT', [self::class, 'updateEntry'], 'manage_gitpress_entries');
        self::route('/entries/(?P<id>\d+)/history', 'GET', static function ($r) { global $wpdb; $rows = $wpdb->get_results($wpdb->prepare('SELECT version,snapshot,created_by,created_at FROM ' . Database::table('entry_revisions') . ' WHERE entry_id=%d ORDER BY version DESC LIMIT 100', (int) $r['id']), ARRAY_A); foreach ($rows as &$row) { $row['snapshot'] = json_decode($row['snapshot'], true); } return $rows; }, 'view_gitpress_entries');
        self::route('/entries/(?P<id>\d+)/pdf', 'GET', static fn ($r) => ['url' => add_query_arg('_wpnonce', wp_create_nonce('gpf_pdf'), admin_url('admin-post.php?action=gpf_pdf&id=' . (int) $r['id']))], 'view_gitpress_entries');
        self::route('/entries/(?P<id>\d+)/files', 'GET', static function ($r) { global $wpdb; $files = $wpdb->get_results($wpdb->prepare('SELECT id,original_name,bytes FROM ' . Database::table('files') . ' WHERE entry_id=%d', (int) $r['id']), ARRAY_A); foreach ($files as &$file) { $file['url'] = add_query_arg('_wpnonce', wp_create_nonce('gpf_download'), admin_url('admin-post.php?action=gpf_download&id=' . $file['id'])); } return $files; }, 'view_gitpress_entries');
        self::route('/reports', 'GET', [self::class, 'reports'], 'view_gitpress_entries');
        self::route('/jobs', 'GET', static function () { global $wpdb; return $wpdb->get_results('SELECT j.id,j.entry_id,j.kind,j.status,j.attempts,j.last_error,j.available_at FROM ' . Database::table('jobs') . ' j JOIN ' . Database::table('entries') . ' e ON e.id=j.entry_id WHERE ' . Permissions::sql('e.form_id', 'edit') . ' ORDER BY j.id DESC LIMIT 100', ARRAY_A); }, 'manage_gitpress_integrations');
        self::route('/jobs/(?P<id>\d+)/retry', 'POST', static function ($r) { global $wpdb; $wpdb->query($wpdb->prepare('UPDATE ' . Database::table('jobs') . " SET status='pending',attempts=0,available_at=%s WHERE id=%d AND status='failed'", Database::now(), (int) $r['id'])); wp_schedule_single_event(time() + 1, 'gitpress_forms_jobs_soon'); return ['success' => true]; }, 'manage_gitpress_integrations');
        self::route('/logs', 'GET', static function () { global $wpdb; return $wpdb->get_results('SELECT l.* FROM ' . Database::table('logs') . ' l LEFT JOIN ' . Database::table('entries') . ' e ON e.id=l.entry_id WHERE ' . (current_user_can('manage_options') ? 'l.entry_id=0 OR ' : '') . Permissions::sql('e.form_id') . ' ORDER BY l.id DESC LIMIT 100', ARRAY_A); }, 'view_gitpress_entries');
        self::route('/integrations', 'GET', static fn () => Integrations::catalog(), 'manage_gitpress_integrations');
        self::route('/integrations/(?P<provider>[a-z0-9_-]+)', 'PUT', static fn ($r) => Integrations::saveConnection($r['provider'], self::body($r)), 'manage_gitpress_integrations');
        self::route('/forms/(?P<id>\d+)/feeds', 'GET', static function ($r) { global $wpdb; $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . Database::table('feeds') . ' WHERE form_id=%d', (int) $r['id']), ARRAY_A); foreach ($rows as &$row) { $row['configuration'] = json_decode($row['configuration'], true); } return $rows; }, 'manage_gitpress_integrations');
        self::route('/forms/(?P<id>\d+)/feeds', 'POST', static fn ($r) => Integrations::saveFeed((int) $r['id'], self::body($r)), 'manage_gitpress_integrations');
        self::route('/feeds/(?P<id>\d+)', 'DELETE', static function ($r) { global $wpdb; $wpdb->delete(Database::table('feeds'), ['id' => (int) $r['id']]); return ['success' => true]; }, 'manage_gitpress_integrations');
        self::route('/feeds/(?P<id>\d+)', 'PUT', static function ($r) { global $wpdb; $formId = (int) $wpdb->get_var($wpdb->prepare('SELECT form_id FROM ' . Database::table('feeds') . ' WHERE id=%d', (int) $r['id'])); if (!$formId) { throw new \RuntimeException('Feed not found.', 404); } return Integrations::saveFeed($formId, self::body($r), (int) $r['id']); }, 'manage_gitpress_integrations');
        self::route('/import/preview', 'POST', static fn ($r) => Importer::preview(self::body($r)));
        self::route('/import', 'POST', static fn ($r) => Importer::run(self::body($r)));
        self::route('/settings', 'GET', static fn () => ['deleteOnUninstall' => get_option('gitpress_forms_delete_on_uninstall') === 'yes', 'adminEmail' => get_option('admin_email')]);
        self::route('/settings', 'PUT', static function ($r) { update_option('gitpress_forms_delete_on_uninstall', !empty(self::body($r)['deleteOnUninstall']) ? 'yes' : 'no'); return ['success' => true]; }, 'manage_options');
        self::route('/permissions', 'GET', static fn () => Permissions::roles(), 'manage_options');
        self::route('/permissions', 'PUT', static fn ($r) => Permissions::saveRoles(self::body($r)), 'manage_options');
        self::route('/users', 'GET', static fn () => array_map(static fn ($u) => ['id' => $u->ID, 'name' => $u->display_name], get_users(['number' => 1000, 'fields' => ['ID', 'display_name']])), 'manage_options');
    }
    private static function route(string $path, string $method, callable $handler, string $cap = 'manage_gitpress_forms'): void
    {
        register_rest_route('gitpress-forms/v1', $path, ['methods' => $method, 'permission_callback' => static fn ($request) => Permissions::request($request, $cap), 'callback' => static function ($request) use ($handler) {
            try { $result = $handler($request); if (is_wp_error($result)) { return $result; } $response = rest_ensure_response($result); $response->header('Cache-Control', 'no-store, private'); return $response; }
            catch (\Throwable $e) { $status = in_array($e->getCode(), [401, 403, 404, 409, 429], true) ? $e->getCode() : 400; return new \WP_Error('gitpress_forms_error', $e->getMessage(), ['status' => $status]); }
        }]);
    }
    private static function body(\WP_REST_Request $r): array { $body = $r->get_json_params(); if (!is_array($body)) { throw new \InvalidArgumentException('Expected a JSON object.'); } return $body; }
    public static function updateEntry(\WP_REST_Request $request): array
    {
        global $wpdb;
        $id = (int) $request['id']; $entry = Repository::entry($id); $input = self::body($request);
        if (!$entry) { throw new \InvalidArgumentException('Entry not found.'); }
        if (isset($input['version']) && (int) $input['version'] !== $entry['version']) { throw new \RuntimeException('This entry changed elsewhere. Reload before editing.', 409); }
        $updates = ['updated_at' => Database::now(), 'version' => $entry['version'] + 1];
        if (isset($input['status'])) {
            $status = $input['status'];
            if (!in_array($status, ['unread', 'read', 'spam', 'trash', 'approved', 'rejected'], true)) { throw new \InvalidArgumentException('Invalid entry status.'); }
            if ($entry['status'] === 'partial' && !in_array($status, ['spam', 'trash'], true)) { throw new \InvalidArgumentException('A partial entry must be completed through its form.'); }
            if ($entry['status'] === 'pending_confirmation' && !in_array($status, ['spam', 'trash', 'rejected'], true)) { throw new \InvalidArgumentException('Email confirmation is still required.'); }
            if ($status === 'approved' && $entry['status'] !== 'pending_approval') { throw new \InvalidArgumentException('This entry is not waiting for approval.'); }
            if ($entry['status'] === 'pending_approval' && !in_array($status, ['approved', 'rejected', 'spam', 'trash'], true)) { throw new \InvalidArgumentException('Approve or reject this entry first.'); }
            $updates['status'] = $status;
        }
        if (isset($input['note']) && trim($input['note']) !== '') { $notes = $entry['notes']; $notes[] = ['body' => sanitize_textarea_field($input['note']), 'by' => get_current_user_id(), 'at' => Database::now()]; $updates['notes'] = Database::json($notes); }
        if (isset($input['response'])) { $form = Repository::formForEntry($entry); $validated = Validation::values($form['definition']['fields'], $input['response'], true); if ($validated['errors']) { throw new \InvalidArgumentException('Entry edits contain invalid field values.'); } $updates['response'] = Database::json(Validation::withoutPasswords($form['definition']['fields'], $validated['values'])); }
        Database::transaction(static function () use ($wpdb, $updates, $entry, $id) {
            $changed = $wpdb->update(Database::table('entries'), $updates, ['id' => $id, 'version' => $entry['version'], 'status' => $entry['status']]);
            if ($changed !== 1) { throw new \RuntimeException('This entry changed elsewhere. Reload before editing.', 409); }
            if (!$wpdb->insert(Database::table('entry_revisions'), ['entry_id' => $id, 'version' => $entry['version'], 'snapshot' => Database::json(['response' => $entry['response'], 'notes' => $entry['notes'], 'status' => $entry['status']]), 'created_by' => get_current_user_id(), 'created_at' => Database::now()])) { throw new \RuntimeException('Could not record entry history.'); }
            if (($updates['status'] ?? '') === 'approved') { Submissions::dispatch(Repository::entry($id), Repository::form($entry['form_id'])); }
        });
        Database::log('entry_updated', 'Entry updated by user ' . get_current_user_id(), $id);
        return Repository::entry($id);
    }
    public static function reports(): array
    {
        global $wpdb; $entries = Database::table('entries'); $events = Database::table('events');
        $where = Permissions::sql('form_id'); $formsWhere = Permissions::sql('id');
        return ['forms' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Database::table('forms') . " WHERE status!='trash' AND $formsWhere"), 'entries' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $entries WHERE status NOT IN ('trash','spam','partial') AND $where"), 'views' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $events WHERE kind='view' AND $where"), 'pending' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $entries WHERE status IN ('pending_approval','pending_confirmation') AND $where"), 'daily' => $wpdb->get_results($wpdb->prepare("SELECT DATE(created_at) AS date,COUNT(*) AS count FROM $entries WHERE status NOT IN ('trash','spam','partial') AND $where AND created_at>=%s GROUP BY DATE(created_at) ORDER BY date", gmdate('Y-m-d H:i:s', time() - 86400 * 30)), ARRAY_A), 'byForm' => $wpdb->get_results("SELECT f.title,COUNT(e.id) AS count FROM " . Database::table('forms') . " f LEFT JOIN $entries e ON e.form_id=f.id AND e.status NOT IN ('trash','spam','partial') WHERE " . Permissions::sql('f.id') . ' GROUP BY f.id,f.title ORDER BY count DESC LIMIT 10', ARRAY_A)];
    }
}
