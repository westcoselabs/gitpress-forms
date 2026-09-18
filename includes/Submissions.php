<?php
namespace GitPress\Forms;

final class Submissions
{
    public static function available(int $id): array
    {
        $form = Repository::form($id);
        if (!$form || $form['status'] !== 'published') { throw new \RuntimeException('This form is not available.', 404); }
        $s = $form['definition']['settings'];
        if ($s['requireLogin'] && !is_user_logged_in()) { throw new \RuntimeException('Please sign in to complete this form.', 401); }
        if ($s['opensAt'] && strtotime($s['opensAt']) > time()) { throw new \RuntimeException('This form is not open yet.', 403); }
        if ($s['closesAt'] && strtotime($s['closesAt']) < time()) { throw new \RuntimeException('This form has closed.', 403); }
        return $form;
    }
    public static function submit(int $id, array $payload): array
    {
        global $wpdb;
        $form = self::available($id); $s = $form['definition']['settings'];
        if (!Security::origin() || !Security::checkToken((string) ($payload['token'] ?? ''), $id)) { throw new \RuntimeException('Your form session expired. Please submit again.', 403); }
        Security::rateLimit('submit-' . $id, 12);
        if (!empty($payload['website'])) { throw new \InvalidArgumentException('Unable to accept this submission.'); }
        $key = (string) ($payload['idempotencyKey'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $key)) { throw new \InvalidArgumentException('Missing submission identifier.'); }
        $existing = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Database::table('entries') . ' WHERE form_id=%d AND idempotency_key=%s', $id, $key));
        if ($existing) { return self::confirmation(Repository::entry((int) $existing), $form); }
        $validation = Validation::values($form['definition']['fields'], is_array($payload['values'] ?? null) ? $payload['values'] : []);
        if ($validation['errors']) { return ['errors' => $validation['errors']]; }
        $recaptchaErrors = Recaptcha::verify($form['definition']['fields'], $validation['values'], $id);
        if ($recaptchaErrors) { return ['errors' => $recaptchaErrors]; }
        // Passwords and one-time anti-abuse tokens are never stored in entries or job payloads.
        $validation['values'] = Validation::withoutPasswords($form['definition']['fields'], $validation['values']);
        if (!$validation['values'] || !array_filter($validation['values'], static fn ($v) => $v !== '' && $v !== [])) { throw new \InvalidArgumentException('Please complete the form before submitting.'); }
        $entry = Database::transaction(static function () use ($wpdb, $id, $form, $s, $validation, $payload, $key) {
            // Serializes the entry-limit and uniqueness checks for this form on MySQL.
            $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Database::table('forms') . ' WHERE id=%d FOR UPDATE', $id));
            $table = Database::table('entries');
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE form_id=%d AND idempotency_key=%s", $id, $key));
            if ($existing) { return Repository::entry((int) $existing); }
            $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE form_id=%d AND status NOT IN ('trash','spam','partial')", $id));
            if ($s['maxEntries'] && $count >= $s['maxEntries']) { throw new \RuntimeException('This form has reached its entry limit.', 409); }
            foreach (Definition::flatten($form['definition']['fields']) as $field) {
                if (!empty($field['unique']) && isset($validation['values'][$field['name']])) {
                    $needle = Database::json($field['name']) . ':' . Database::json($validation['values'][$field['name']]);
                    $matches = $wpdb->get_col($wpdb->prepare("SELECT response FROM $table WHERE form_id=%d AND status NOT IN ('trash','spam','partial') AND response LIKE %s", $id, '%' . $wpdb->esc_like($needle) . '%'));
                    foreach ($matches as $raw) { if ((json_decode($raw, true)[$field['name']] ?? null) === $validation['values'][$field['name']]) { throw new \InvalidArgumentException($field['label'] . ' must be unique.'); } }
                }
            }
            $status = $s['doubleOptin'] ? 'pending_confirmation' : ($s['approval'] ? 'pending_approval' : 'unread');
            $now = Database::now();
            $data = ['form_id' => $id, 'form_version' => $form['version'], 'status' => $status, 'response' => Database::json($validation['values']), 'notes' => '[]', 'user_id' => get_current_user_id(), 'source_url' => esc_url_raw($payload['source'] ?? ''), 'idempotency_key' => $key, 'created_at' => $now, 'updated_at' => $now];
            $entryId = Progress::consume($id, $payload, $data);
            $ok = $entryId || $wpdb->insert($table, $data);
            if (!$ok) { throw new \RuntimeException('Could not save your submission. Please retry.'); }
            $entryId = $entryId ?: (int) $wpdb->insert_id;
            Files::claim($id, $entryId, $form['definition']['fields'], $validation['values']);
            $entry = Repository::entry($entryId);
            if ($status === 'pending_confirmation') {
                $email = self::firstEmail($form, $validation['values']);
                if (!$email) { throw new \InvalidArgumentException('An email address is required for confirmation.'); }
                $token = Security::issue('confirm', $id, ['entryId' => $entryId], 86400 * 3);
                Jobs::enqueue($entryId, 'email', ['to' => $email, 'subject' => 'Confirm your submission', 'message' => 'Confirm your submission: ' . esc_url(add_query_arg(['gpf_confirm' => $token, 'form' => $id], home_url('/')))], 'confirm');
            } elseif ($status === 'unread') { self::dispatch($entry, $form); }
            return $entry;
        });
        return self::confirmation($entry, $form);
    }
    public static function dispatch(array $entry, array $form): void
    {
        foreach ($form['definition']['settings']['notifications'] as $notification) {
            if (Logic::matches($notification['condition'] ?? null, $entry['response'])) { Jobs::enqueue($entry['id'], 'notification', ['notification' => $notification], 'notification-' . $notification['id']); }
        }
        global $wpdb;
        $feeds = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . Database::table('feeds') . ' WHERE form_id=%d AND enabled=1', $form['id']), ARRAY_A);
        foreach ($feeds as $feed) { $config = json_decode($feed['configuration'], true); if (Logic::matches($config['condition'] ?? null, $entry['response'])) { Jobs::enqueue($entry['id'], 'integration', ['feedId' => (int) $feed['id']], 'feed-' . $feed['id']); } }
        Database::afterCommit(static fn () => do_action('gitpress_forms/entry_accepted', $entry['id'], $form['id']));
    }
    public static function confirmation(array $entry, array $form): array
    {
        $settings = $form['definition']['settings']; $message = $settings['confirmation']; $redirect = $settings['redirect'];
        foreach ($settings['confirmations'] as $confirmation) { if (Logic::matches($confirmation['condition'], $entry['response'])) { $message = $confirmation['message']; $redirect = $confirmation['redirect']; break; } }
        if ($entry['status'] === 'pending_confirmation') { $message = 'Please check your email to confirm this submission.'; $redirect = ''; }
        return ['success' => true, 'message' => self::smart($message, $entry['response']), 'redirect' => $redirect, 'entryView' => EntryView::link($entry, $form)];
    }
    public static function smart(string $text, array $values): string
    {
        return preg_replace_callback('/\{([a-zA-Z][\w]*)\}/', static function ($m) use ($values) { if ($m[1] === 'all_fields') { $lines = []; foreach ($values as $key => $value) { $lines[] = $key . ': ' . (is_array($value) ? Database::json($value) : $value); } return implode("\n", $lines); } $value = $values[$m[1]] ?? ''; return is_array($value) ? Database::json($value) : (string) $value; }, $text);
    }
    public static function firstEmail(array $form, array $values): string
    {
        foreach (Definition::flatten($form['definition']['fields']) as $field) { if ($field['type'] === 'email' && !empty($values[$field['name']]) && is_email($values[$field['name']])) { return $values[$field['name']]; } }
        return '';
    }
    public static function saveProgress(int $id, array $payload): array
    {
        global $wpdb;
        $form = self::available($id);
        if ((!$form['definition']['settings']['saveResume'] && empty($form['definition']['settings']['trackPartial'])) || !Security::origin() || !Security::checkToken((string) ($payload['token'] ?? ''), $id)) { throw new \RuntimeException('Saving progress is not available.', 403); }
        Security::rateLimit('progress-' . $id, 10);
        $result = Validation::values($form['definition']['fields'], $payload['values'] ?? [], true);
        $result['values'] = Validation::withoutPasswords($form['definition']['fields'], $result['values']);
        return Progress::save($form, $payload, $result['values']);
    }
    public static function confirm(int $id, string $token): void
    {
        global $wpdb;
        $row = Security::lookup($token, 'confirm', $id);
        if (!$row) { throw new \InvalidArgumentException('This confirmation link is invalid or expired.'); }
        Database::transaction(static function () use ($wpdb, $row, $id) {
            $entry = Repository::entry((int) $row['payload']['entryId']); $form = Repository::form($id);
            if (!$entry || !$form || $entry['status'] !== 'pending_confirmation') { throw new \InvalidArgumentException('This entry has already been processed.'); }
            $status = $form['definition']['settings']['approval'] ? 'pending_approval' : 'unread';
            $changed = $wpdb->update(Database::table('entries'), ['status' => $status, 'version' => $entry['version'] + 1, 'updated_at' => Database::now()], ['id' => $entry['id'], 'status' => 'pending_confirmation', 'version' => $entry['version']]);
            if ($changed !== 1) { throw new \RuntimeException('This entry was already confirmed.', 409); }
            $wpdb->delete(Database::table('tokens'), ['id' => $row['id']]);
            if ($status === 'unread') { self::dispatch(Repository::entry($entry['id']), $form); }
        });
    }
}
