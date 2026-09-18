<?php
namespace GitPress\Forms;

final class Progress
{
    public static function save(array $form, array $payload, array $values): array
    {
        global $wpdb;
        $previous = !empty($payload['resumeToken']) ? Security::lookup((string) $payload['resumeToken'], 'resume', $form['id']) : null;
        $entryId = (int) ($previous['payload']['entryId'] ?? 0);
        if (!empty($payload['resumeToken']) && !$previous) { throw new \RuntimeException('Your saved link expired. Save a new copy of the form.', 409); }
        $key = (string) ($payload['idempotencyKey'] ?? bin2hex(random_bytes(24)));
        if (!preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $key)) { throw new \InvalidArgumentException('Invalid progress identifier.'); }
        $partialKey = 'partial_' . substr(hash('sha256', $key), 0, 48);
        if (!empty($form['definition']['settings']['trackPartial'])) {
            $entryId = Database::transaction(static function () use ($wpdb, $entryId, $form, $values, $payload, $partialKey) {
                $table = Database::table('entries'); $now = Database::now();
                $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Database::table('forms') . ' WHERE id=%d FOR UPDATE', $form['id']));
                if (!$entryId) { $entryId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE form_id=%d AND idempotency_key=%s", $form['id'], $partialKey)); }
                if ($entryId) {
                    $entry = Repository::entry($entryId);
                    if (!$entry || $entry['status'] !== 'partial') { throw new \RuntimeException('This saved form has already been submitted.', 409); }
                    $changed = $wpdb->update($table, ['response' => Database::json($values), 'updated_at' => $now, 'version' => $entry['version'] + 1], ['id' => $entryId, 'status' => 'partial', 'version' => $entry['version']]);
                    if ($changed !== 1) { throw new \RuntimeException('Progress changed in another session. Reload your saved link.', 409); }
                    return $entryId;
                }
                $wpdb->insert($table, ['form_id' => $form['id'], 'form_version' => $form['version'], 'status' => 'partial', 'response' => Database::json($values), 'notes' => '[]', 'user_id' => get_current_user_id(), 'source_url' => esc_url_raw($payload['source'] ?? ''), 'idempotency_key' => $partialKey, 'created_at' => $now, 'updated_at' => $now]);
                if (!$wpdb->insert_id) { throw new \RuntimeException('Could not save partial entry.'); }
                return (int) $wpdb->insert_id;
            });
        }
        $token = Security::issue('resume', $form['id'], ['values' => $entryId ? [] : $values, 'version' => $form['version'], 'entryId' => $entryId], 86400 * 30);
        return ['token' => $token, 'expiresAt' => gmdate('c', time() + 86400 * 30)];
    }

    public static function resume(int $formId, string $token): array
    {
        $row = Security::lookup($token, 'resume', $formId);
        if (!$row) { throw new \RuntimeException('This saved form link is invalid or expired.', 404); }
        if (!empty($row['payload']['entryId'])) {
            $entry = Repository::entry((int) $row['payload']['entryId']);
            if (!$entry || $entry['status'] !== 'partial') { throw new \RuntimeException('This saved form has already been submitted.', 409); }
            $row['payload']['values'] = $entry['response'];
        }
        return $row['payload'];
    }

    public static function consume(int $formId, array $payload, array $data): int
    {
        global $wpdb;
        if (empty($payload['resumeToken'])) { return 0; }
        $resume = self::resume($formId, (string) $payload['resumeToken']);
        $entryId = (int) ($resume['entryId'] ?? 0);
        if (!$entryId) { $wpdb->delete(Database::table('tokens'), ['token_hash' => hash('sha256', (string) $payload['resumeToken']), 'purpose' => 'resume']); return 0; }
        $data['updated_at'] = Database::now(); unset($data['created_at']);
        $entry = Repository::entry($entryId); $data['version'] = $entry['version'] + 1;
        $changed = $wpdb->update(Database::table('entries'), $data, ['id' => $entryId, 'form_id' => $formId, 'status' => 'partial', 'version' => $entry['version']]);
        if ($changed !== 1) { throw new \RuntimeException('This saved entry has already been submitted.', 409); }
        return $entryId;
    }
}
