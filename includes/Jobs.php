<?php
namespace GitPress\Forms;

final class Jobs
{
    public static function enqueue(int $entryId, string $kind, array $payload, string $key): void
    {
        global $wpdb;
        $table = Database::table('jobs');
        $inserted = $wpdb->query($wpdb->prepare("INSERT INTO $table (entry_id,kind,payload,dedupe_key,status,attempts,available_at,last_error,created_at) VALUES (%d,%s,%s,%s,'pending',0,%s,'',%s) ON DUPLICATE KEY UPDATE dedupe_key=dedupe_key", $entryId, $kind, Database::json($payload), $entryId . '-' . $key, Database::now(), Database::now()));
        if ($inserted === false) { throw new \RuntimeException('Could not queue the submission workflow. Please retry.'); }
        Database::afterCommit(static function () { if (!wp_next_scheduled('gitpress_forms_jobs_soon')) { wp_schedule_single_event(time() + 5, 'gitpress_forms_jobs_soon'); } });
    }
    public static function run(): void
    {
        global $wpdb;
        $table = Database::table('jobs');
        $wpdb->query($wpdb->prepare("UPDATE $table SET status='pending',locked_at=NULL WHERE status='processing' AND locked_at<%s", gmdate('Y-m-d H:i:s', time() - 900)));
        $jobs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE status='pending' AND available_at<=%s ORDER BY id LIMIT 15", Database::now()), ARRAY_A);
        foreach ($jobs as $job) {
            $claimed = $wpdb->update($table, ['status' => 'processing', 'locked_at' => Database::now(), 'attempts' => $job['attempts'] + 1], ['id' => $job['id'], 'status' => 'pending']);
            if ($claimed !== 1) { continue; }
            try {
                $payload = json_decode($job['payload'], true); $entry = Repository::entry((int) $job['entry_id']);
                if (!$entry || in_array($entry['status'], ['trash', 'spam', 'rejected'], true)) { $wpdb->update($table, ['status' => 'cancelled'], ['id' => $job['id']]); continue; }
                if ($job['kind'] === 'email') { self::email($payload); }
                elseif ($job['kind'] === 'notification') {
                    $n = $payload['notification'];
                    $attachment = '';
                    try {
                        if (!empty($n['attachPdf'])) { $attachment = wp_tempnam('gitpress-entry-' . $entry['id'] . '.pdf'); if (!$attachment || file_put_contents($attachment, Pdf::entry($entry), LOCK_EX) === false) { throw new \RuntimeException('Could not generate PDF attachment.'); } }
                        $message = ['to' => Submissions::smart($n['to'], $entry['response']), 'subject' => Submissions::smart($n['subject'], $entry['response']), 'message' => Submissions::smart(wp_strip_all_tags($n['message']), $entry['response'])];
                        foreach (['cc', 'bcc', 'replyTo'] as $key) { $message[$key] = Submissions::smart($n[$key] ?? '', $entry['response']); }
                        self::email($message, $attachment ? [$attachment] : []);
                    } finally { if ($attachment) { wp_delete_file($attachment); } }
                } elseif ($job['kind'] === 'integration') { Integrations::execute((int) $payload['feedId'], $entry); }
                else { throw new \RuntimeException('Unknown job type.'); }
                $wpdb->update($table, ['status' => 'completed', 'locked_at' => null, 'last_error' => ''], ['id' => $job['id']]);
                Database::log('job_completed', 'Completed ' . $job['kind'] . ' job ' . $job['id'], (int) $job['entry_id']);
            } catch (\Throwable $e) {
                $attempt = (int) $job['attempts'] + 1;
                // Provider response bodies can contain credentials or submitted data; never log them.
                $message = $job['kind'] === 'integration' ? 'Provider delivery failed. Check the connection and field mapping.' : sanitize_text_field($e->getMessage());
                $wpdb->update($table, ['status' => $attempt >= 5 ? 'failed' : 'pending', 'locked_at' => null, 'available_at' => gmdate('Y-m-d H:i:s', time() + min(3600, 60 * 2 ** $attempt)), 'last_error' => $message], ['id' => $job['id']]);
                Database::log('job_failed', $message, (int) $job['entry_id'], 'error');
            }
        }
        $next = $wpdb->get_var("SELECT MIN(available_at) FROM $table WHERE status='pending'");
        if ($next && !wp_next_scheduled('gitpress_forms_jobs_soon')) { wp_schedule_single_event(max(time() + 5, strtotime($next . ' UTC')), 'gitpress_forms_jobs_soon'); }
        self::cleanup();
    }
    private static function email(array $payload, array $attachments = []): void
    {
        $addresses = self::addresses($payload['to']); $headers = [];
        foreach (['cc' => 'Cc', 'bcc' => 'Bcc', 'replyTo' => 'Reply-To'] as $key => $header) { if (!empty($payload[$key])) { $headers[] = $header . ': ' . implode(', ', self::addresses($payload[$key])); } }
        if (!$addresses || !wp_mail($addresses, sanitize_text_field($payload['subject']), $payload['message'], $headers, $attachments)) { throw new \RuntimeException('WordPress could not send the notification. Check your mail transport.'); }
    }
    private static function addresses(string $input): array
    {
        $addresses = array_values(array_filter(array_map('trim', explode(',', $input))));
        foreach ($addresses as $email) { if (preg_match('/[\r\n]/', $email) || !is_email($email)) { throw new \RuntimeException('A notification has an invalid recipient mapping.'); } }
        return array_values(array_unique($addresses));
    }
    private static function cleanup(): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . Database::table('tokens') . ' WHERE expires_at<%s', Database::now()));
        $files = $wpdb->get_results($wpdb->prepare('SELECT id,path FROM ' . Database::table('files') . ' WHERE entry_id=0 AND created_at<%s LIMIT 100', gmdate('Y-m-d H:i:s', time() - 86400 * 31)), ARRAY_A);
        foreach ($files as $file) { wp_delete_file($file['path']); $wpdb->delete(Database::table('files'), ['id' => $file['id']]); }
        foreach (Repository::forms() as $record) {
            $form = Repository::form($record['id']); $days = $form['definition']['settings']['retentionDays'];
            if ($days > 0) { $ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . Database::table('entries') . ' WHERE form_id=%d AND created_at<%s LIMIT 100', $form['id'], gmdate('Y-m-d H:i:s', time() - $days * 86400))); foreach ($ids as $id) { Files::deleteEntry((int) $id); } }
        }
    }
}
