<?php
namespace GitPress\Forms;

final class Privacy
{
    public static function register(): void
    {
        add_filter('wp_privacy_personal_data_exporters', static function ($items) { $items['gitpress-forms'] = ['exporter_friendly_name' => __('GitPress Forms submissions', 'gitpress-forms'), 'callback' => [self::class, 'export']]; return $items; });
        add_filter('wp_privacy_personal_data_erasers', static function ($items) { $items['gitpress-forms'] = ['eraser_friendly_name' => __('GitPress Forms submissions', 'gitpress-forms'), 'callback' => [self::class, 'erase']]; return $items; });
    }

    private static function rows(string $email, int $page): array
    {
        global $wpdb;
        $user = get_user_by('email', $email); $id = $user ? $user->ID : -1;
        $pattern = '%' . $wpdb->esc_like(Database::json($email)) . '%';
        $rows = $wpdb->get_results($wpdb->prepare('SELECT DISTINCT e.id,e.response,e.user_id FROM ' . Database::table('entries') . ' e LEFT JOIN ' . Database::table('entry_revisions') . ' r ON r.entry_id=e.id WHERE e.user_id=%d OR e.response LIKE %s OR r.snapshot LIKE %s ORDER BY e.id LIMIT 100 OFFSET %d', $id, $pattern, $pattern, max(0, $page - 1) * 100), ARRAY_A);
        $items = [];
        foreach ($rows as $row) {
            $matches = self::contains(json_decode($row['response'], true), $email);
            if (!$matches) { foreach ($wpdb->get_col($wpdb->prepare('SELECT snapshot FROM ' . Database::table('entry_revisions') . ' WHERE entry_id=%d', $row['id'])) as $snapshot) { if (self::contains(json_decode($snapshot, true), $email)) { $matches = true; break; } } }
            if ($matches || (int) $row['user_id'] === $id) { $items[] = Repository::entry((int) $row['id']); }
        }
        return ['items' => $items, 'done' => count($rows) < 100];
    }

    private static function contains(array $values, string $email): bool
    {
        $matches = false;
        array_walk_recursive($values, static function ($value) use ($email, &$matches) { if (is_string($value) && strcasecmp($value, $email) === 0) { $matches = true; } });
        return $matches;
    }

    private static function progress(string $email, int $page): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare('SELECT id,payload,created_at FROM ' . Database::table('tokens') . " WHERE purpose='resume' AND payload LIKE %s ORDER BY id LIMIT 100 OFFSET %d", '%' . $wpdb->esc_like(Database::json($email)) . '%', max(0, $page - 1) * 100), ARRAY_A);
        return ['items' => array_values(array_filter($rows, static fn ($row) => self::contains(json_decode($row['payload'], true), $email))), 'done' => count($rows) < 100];
    }

    public static function export(string $email, int $page = 1): array
    {
        $rows = self::rows($email, $page); $data = [];
        foreach ($rows['items'] as $entry) {
            $values = [['name' => 'Submitted', 'value' => $entry['created_at']], ['name' => 'Status', 'value' => $entry['status']]];
            foreach ($entry['response'] as $key => $value) { $values[] = ['name' => $key, 'value' => is_array($value) ? Database::json($value) : (string) $value]; }
            $values[] = ['name' => 'Source page', 'value' => $entry['source_url']];
            $values[] = ['name' => 'Entry notes', 'value' => Database::json($entry['notes'])];
            $data[] = ['group_id' => 'gitpress-forms', 'group_label' => 'GitPress Forms', 'item_id' => 'gitpress-entry-' . $entry['id'], 'data' => $values];
        }
        $progress = self::progress($email, $page);
        foreach ($progress['items'] as $item) { $data[] = ['group_id' => 'gitpress-forms-progress', 'group_label' => 'GitPress Forms saved progress', 'item_id' => 'gitpress-progress-' . $item['id'], 'data' => [['name' => 'Saved', 'value' => $item['created_at']], ['name' => 'Answers', 'value' => Database::json(json_decode($item['payload'], true)['values'] ?? [])]]]; }
        return ['data' => $data, 'done' => $rows['done'] && $progress['done']];
    }

    public static function erase(string $email, int $page = 1): array
    {
        // Always consume page one: deleting records must not shift later pages past the cursor.
        $rows = self::rows($email, 1);
        foreach ($rows['items'] as $entry) { Files::deleteEntry($entry['id']); }
        global $wpdb; $progress = self::progress($email, 1);
        foreach ($progress['items'] as $item) { $wpdb->delete(Database::table('tokens'), ['id' => $item['id']]); }
        return ['items_removed' => (bool) ($rows['items'] || $progress['items']), 'items_retained' => false, 'messages' => [], 'done' => $rows['done'] && $progress['done']];
    }
}
